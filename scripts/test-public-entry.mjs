import process from 'node:process';
import assert from 'node:assert/strict';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, useHostFilesystem } from '@php-wasm/node';

const root = process.cwd().replaceAll('\\', '/');
const phpCode = '<?php ' +
  '$sessionPath = sys_get_temp_dir() . "/brightcv-entry-sessions";' +
  'if (!is_dir($sessionPath)) { mkdir($sessionPath, 0777, true); }' +
  'ini_set("session.save_path", $sessionPath);' +
  'putenv("APP_URL=http://localhost/NewArchitecture_BrightCv/public");' +
  '$_SERVER["REQUEST_METHOD"] = "GET";' +
  '$_SERVER["SCRIPT_NAME"] = "/NewArchitecture_BrightCv/public/index.php";' +
  '$_SERVER["REQUEST_URI"] = "/NewArchitecture_BrightCv/public/";' +
  '$_SERVER["HTTP_HOST"] = "localhost";' +
  'require ' + JSON.stringify(root + '/public/index.php') + ';';

const php = new PHP(await loadNodeRuntime('8.1', {
  emscriptenOptions: { processId: 3 },
}));
useHostFilesystem(php);

const rendered = await php.runStream({ code: phpCode });
const html = await rendered.stdoutText;
const errors = await rendered.stderrText;

assert.equal(await rendered.exitCode, 0, errors || 'The public entry point failed to boot.');
assert.equal(errors, '', errors);
assert.match(html, /<!doctype html>/i);
assert.match(html, /BrightCV/);
assert.match(html, /\/NewArchitecture_BrightCv\/public\/assets\/common\/app\.css/);

console.log('Public WAMP entry-point smoke test passed.');
process.exit(0);
