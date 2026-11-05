const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const SCOPE = 'https://example.test/brightcv/public/';
const SOURCE = fs.readFileSync(path.join(__dirname, '../public/sw.js'), 'utf8');

/** An in-memory stand-in for the Cache Storage API. */
function createCacheStorage() {
  const stores = new Map();

  const cacheFor = (name) => {
    if (!stores.has(name)) stores.set(name, new Map());
    const store = stores.get(name);
    return {
      async put(request, response) {
        store.set(typeof request === 'string' ? request : request.url, response);
      },
      async add(request) {
        const url = typeof request === 'string' ? request : request.url;
        store.set(url, new Response('offline page', { headers: { 'Content-Type': 'text/html' } }));
      },
      async match(request) {
        return store.get(typeof request === 'string' ? request : request.url) || undefined;
      },
    };
  };

  return {
    stores,
    async open(name) {
      return cacheFor(name);
    },
    async keys() {
      return [...stores.keys()];
    },
    async delete(name) {
      return stores.delete(name);
    },
    async match(request) {
      const url = typeof request === 'string' ? request : request.url;
      for (const store of stores.values()) {
        if (store.has(url)) return store.get(url);
      }
      return undefined;
    },
  };
}

/**
 * Load sw.js into a sandbox with just enough of the service worker globals to
 * drive its event handlers.
 */
function loadWorker({ fetchImpl } = {}) {
  const listeners = {};
  const caches = createCacheStorage();
  const calls = { skipWaiting: 0, claim: 0 };
  const waits = [];

  const self = {
    registration: { scope: SCOPE },
    location: { origin: 'https://example.test' },
    addEventListener: (type, handler) => {
      listeners[type] = handler;
    },
    skipWaiting: () => { calls.skipWaiting++; return Promise.resolve(); },
    clients: { claim: () => { calls.claim++; return Promise.resolve(); } },
  };

  const sandbox = {
    self,
    caches,
    URL,
    Request,
    Response,
    Promise,
    console,
    fetch: fetchImpl || (() => Promise.reject(new Error('offline'))),
  };
  sandbox.globalThis = sandbox;

  vm.createContext(sandbox);
  vm.runInContext(SOURCE, sandbox);

  const dispatch = async (type, event = {}) => {
    let responded;
    const built = {
      ...event,
      waitUntil: (p) => { waits.push(p); },
      respondWith: (p) => { responded = p; },
    };
    listeners[type](built);
    await Promise.all(waits.splice(0));
    return responded ? await responded : undefined;
  };

  return { listeners, caches, calls, dispatch };
}

const navigation = (url) => ({ url, method: 'GET', mode: 'navigate' });

test('install precaches the offline page and activates immediately', async () => {
  const w = loadWorker({
    fetchImpl: async () => new Response('offline page', { headers: { 'Content-Type': 'text/html' } }),
  });
  await w.dispatch('install');

  const shell = [...w.caches.stores.keys()].find((key) => key.endsWith('-shell'));
  assert.ok(shell, 'a shell cache should exist');
  assert.ok(w.caches.stores.get(shell).has(SCOPE + 'offline.html'), 'offline page should be precached');
  assert.equal(w.calls.skipWaiting, 1);
});

test('activate removes caches from previous versions only', async () => {
  const w = loadWorker();
  w.caches.stores.set('brightcv-v1-assets', new Map());
  w.caches.stores.set('brightcv-v2-assets', new Map());
  w.caches.stores.set('unrelated-app-cache', new Map());

  await w.dispatch('activate');

  const remaining = [...w.caches.stores.keys()];
  assert.ok(!remaining.includes('brightcv-v1-assets'), 'an older BrightCV cache should be dropped');
  assert.ok(remaining.includes('brightcv-v2-assets'), 'the current cache should survive');
  assert.ok(remaining.includes('unrelated-app-cache'), 'another app on the origin should be left alone');
  assert.equal(w.calls.claim, 1);
});

test('static assets are cached and then served from the cache', async () => {
  let networkHits = 0;
  const w = loadWorker({
    fetchImpl: async () => {
      networkHits++;
      return new Response('body{}', { headers: { 'Content-Type': 'text/css' } });
    },
  });

  const request = new Request(SCOPE + 'assets/common/app.css?v=123');
  await w.dispatch('fetch', { request });
  await new Promise((resolve) => setImmediate(resolve));
  assert.equal(networkHits, 1, 'the first request goes to the network');

  const second = await w.dispatch('fetch', { request });
  assert.equal(networkHits, 1, 'the second request is served from the cache');
  assert.equal(await second.text(), 'body{}');
});

test('API responses are never cached or intercepted', async () => {
  const w = loadWorker({ fetchImpl: async () => new Response('{"secret":true}') });
  const responded = await w.dispatch('fetch', { request: new Request(SCOPE + 'api/resumes/5') });

  assert.equal(responded, undefined, 'the worker should not answer API requests at all');
  for (const store of w.caches.stores.values()) {
    assert.equal(store.size, 0, 'no API response should reach a cache');
  }
});

test('a page is fetched from the network and never stored', async () => {
  const w = loadWorker({
    fetchImpl: async () => new Response('<h1>My CV</h1>', { headers: { 'Content-Type': 'text/html' } }),
  });
  const response = await w.dispatch('fetch', { request: navigation(SCOPE + 'dashboard') });

  assert.equal(await response.text(), '<h1>My CV</h1>');
  for (const store of w.caches.stores.values()) {
    assert.ok(
      ![...store.keys()].some((key) => key.endsWith('/dashboard')),
      'a signed-in page must not be left in the cache'
    );
  }
});

test('an offline page load falls back to the precached offline page', async () => {
  let online = true;
  const w = loadWorker({
    fetchImpl: async () => {
      if (!online) throw new Error('offline');
      return new Response('offline page', { headers: { 'Content-Type': 'text/html' } });
    },
  });
  await w.dispatch('install');

  online = false;
  const response = await w.dispatch('fetch', { request: navigation(SCOPE + 'dashboard') });
  assert.equal(await response.text(), 'offline page');
});

test('requests outside the app scope are left to the browser', async () => {
  const w = loadWorker({ fetchImpl: async () => new Response('other') });

  assert.equal(
    await w.dispatch('fetch', { request: new Request('https://other.test/assets/app.css') }),
    undefined,
    'a cross-origin request is not handled'
  );
  assert.equal(
    await w.dispatch('fetch', { request: new Request('https://example.test/elsewhere/assets/app.css') }),
    undefined,
    'a same-origin request outside the scope is not handled'
  );
  assert.equal(
    await w.dispatch('fetch', { request: new Request(SCOPE + 'api/resumes', { method: 'POST' }) }),
    undefined,
    'a non-GET request is not handled'
  );
});

test('signing out clears every cache', async () => {
  const w = loadWorker({ fetchImpl: async () => new Response('body{}') });
  await w.dispatch('fetch', { request: new Request(SCOPE + 'assets/common/app.css') });
  await new Promise((resolve) => setImmediate(resolve));
  assert.ok(w.caches.stores.size > 0, 'there should be something to clear');

  await w.dispatch('message', { data: { type: 'clear-caches' } });
  assert.equal(w.caches.stores.size, 0, 'nothing should be left for the next person on the device');
});
