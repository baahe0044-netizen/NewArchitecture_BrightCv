import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, useHostFilesystem } from '@php-wasm/node';

const root = process.cwd().replaceAll('\\', '/');
const fixture = {
  user: {
    id: 1,
    name: 'Ama Mensah',
    email: 'ama@example.com',
    job_title: 'Product Designer',
    locale: 'en',
    auth_version: 1,
  },
  resume: {
    id: 7,
    name: 'Product Design CV',
    template_key: 'modern',
    accent_color: '#4052b5',
    status: 'draft',
    completion: 72,
    ats_score: 68,
    language: 'en',
    updated_at: '2026-07-20 10:30:00',
    content: {},
  },
  templates: [
    { template_key: 'modern', name: 'Modern Focus', category: 'Modern', description: 'Clean single-column hierarchy.', color: '#4052b5', is_premium: 0 },
    { template_key: 'executive', name: 'Executive Edge', category: 'Professional', description: 'Structured layout for experienced professionals.', color: '#16324f', is_premium: 0 },
  ],
};

const encoded = Buffer.from(JSON.stringify(fixture)).toString('base64');
const phpCode = '<?php ' +
  '$sessionPath = sys_get_temp_dir() . "/lunettistar-view-sessions";' +
  'if (!is_dir($sessionPath)) { mkdir($sessionPath, 0777, true); }' +
  'ini_set("session.save_path", $sessionPath);' +
  'putenv("APP_URL=http://localhost");' +
  '$_SERVER["SCRIPT_NAME"] = "/index.php";' +
  '$_SERVER["REQUEST_URI"] = "/";' +
  '$_SERVER["HTTP_HOST"] = "localhost";' +
  'require ' + JSON.stringify(root + '/config/app.php') + ';' +
  '$fixture = json_decode(base64_decode(' + JSON.stringify(encoded) + '), true);' +
  'function captureView(string $view, array $vars, string $path): string {' +
  '  $_SERVER["REQUEST_URI"] = $path; ob_start(); View::render($view, $vars); return (string) ob_get_clean();' +
  '}' +
  '$user = $fixture["user"]; $resume = $fixture["resume"]; $templates = $fixture["templates"];' +
  '$dashboard = ["stats" => ["total_resumes" => 1, "completed_resumes" => 0, "average_ats" => 68, "total_downloads" => 2, "average_completion" => 72], "resumes" => [$resume], "activity" => [["action" => "resume_saved", "description" => "Updated Product Design CV", "created_at" => "2026-07-20 10:30:00"]]];' +
  '$pages = [];' +
  '$pages["landing"] = captureView("landing/index", ["title" => "Create a professional CV", "user" => null], "/");' +
  '$pages["login"] = captureView("auth/login", ["oldEmail" => "", "message" => null, "error" => null], "/login");' +
  '$pages["register"] = captureView("auth/register", ["old" => [], "errors" => []], "/register");' +
  '$pages["forgot"] = captureView("auth/forgot_password", ["message" => null], "/forgot-password");' +
  '$pages["reset"] = captureView("auth/reset_password", ["token" => "token", "email" => "ama@example.com", "error" => null], "/reset-password");' +
  '$pages["dashboard"] = captureView("dashboard/index", ["user" => $user, "dashboard" => $dashboard, "message" => null], "/dashboard");' +
  '$pages["templates"] = captureView("templates/list", ["user" => $user, "templates" => $templates], "/templates");' +
  '$pages["profile"] = captureView("account/profile", ["user" => $user, "message" => null, "error" => null, "errors" => []], "/account/profile");' +
  '$pages["security"] = captureView("account/security", ["user" => $user, "message" => null, "error" => null], "/account/security");' +
  '$pages["print"] = captureView("resume/print", ["resume" => $resume, "csrfToken" => Csrf::token()], "/resume/7/print");' +
  'foreach ([403, 404, 419, 500] as $code) { $pages["error_" . $code] = captureView("errors/" . $code, [], "/missing"); }' +
  'echo base64_encode(json_encode($pages, JSON_THROW_ON_ERROR));';

const php = new PHP(await loadNodeRuntime('8.1', { emscriptenOptions: { processId: 3 } }));
useHostFilesystem(php);
const rendered = await php.runStream({ code: phpCode });
const stdout = await rendered.stdoutText;
const stderr = await rendered.stderrText;
assert.equal(await rendered.exitCode, 0, stderr || 'Views failed to render.');
assert.equal(stderr, '', stderr);

const pages = JSON.parse(Buffer.from(stdout, 'base64').toString('utf8'));
assert.ok(Object.keys(pages).length >= 14, 'Expected all primary views and error states.');

for (const [name, html] of Object.entries(pages)) {
  const dom = new JSDOM(html, { url: 'http://localhost/' });
  const { document } = dom.window;
  assert.ok(document.querySelector('main'), `${name}: missing main landmark.`);
  assert.ok(document.title.trim(), `${name}: missing document title.`);

  const ids = [...document.querySelectorAll('[id]')].map((element) => element.id);
  const duplicateIds = ids.filter((id, index) => ids.indexOf(id) !== index);
  assert.deepEqual(duplicateIds, [], `${name}: duplicate element IDs.`);

  const controls = [...document.querySelectorAll('input:not([type="hidden"]), textarea, select')];
  controls.forEach((control) => {
    assert.ok(control.labels?.length, `${name}: #${control.id || control.name || control.tagName} has no associated label.`);
  });

  [...document.querySelectorAll('button')].forEach((button) => {
    const nameText = (button.getAttribute('aria-label') || button.textContent || '').trim();
    assert.ok(nameText, `${name}: found a button without an accessible name.`);
  });

  dom.window.close();
}

const required = {
  login: ['form[action="http://localhost/login"]', '#email', '#password'],
  register: ['form[action="http://localhost/register"]', '#name', '#password_confirmation'],
  dashboard: ['[data-create-resume]', '#createResumeModal', '#deleteResumeModal', '[data-duplicate]', '[data-delete]'],
  templates: ['#templateSearch', '[data-preview-template]', '[data-use-template]', '#templatePreviewModal', '#nameTemplateResumeModal'],
  profile: ['form[action="http://localhost/account/profile"]', '[name="name"]', '[name="job_title"]', '[name="locale"]'],
  security: ['form[action="http://localhost/account/password"]', '[name="current_password"]', '[name="password_confirmation"]'],
  print: ['#printNowButton', '#printResume', '#printData'],
};

for (const [page, selectors] of Object.entries(required)) {
  const dom = new JSDOM(pages[page], { url: 'http://localhost/' });
  selectors.forEach((selector) => assert.ok(dom.window.document.querySelector(selector), `${page}: missing ${selector}.`));
  dom.window.close();
}

assert.ok(fs.existsSync(path.join(root, 'public/assets/common/app.css')));
console.log('Primary views preserve accessible labels, unique IDs, forms, and JavaScript hooks.');
process.exit(0);
