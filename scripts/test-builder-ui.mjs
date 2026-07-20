import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, useHostFilesystem } from '@php-wasm/node';

const root = process.cwd().replaceAll('\\', '/');
const fixture = {
  resume: {
    id: 1,
    user_id: 1,
    name: 'Product CV',
    template_key: 'modern',
    language: 'en',
    accent_color: '#5b4df7',
    font_family: 'Inter',
    job_description: '',
    completion: 80,
    ats_score: 72,
    version: 1,
    updated_at: '2026-07-20 00:00:00',
    content: {
      personal: {
        full_name: 'Alex Morgan',
        headline: 'Product Manager',
        email: 'alex@example.com',
        phone: '+233 24 000 0000',
        location: 'Accra, Ghana',
        website: '',
        linkedin: '',
      },
      summary: 'Product manager delivering thoughtful digital experiences and measurable growth for cross-functional teams.',
      experience: [{
        id: 'experience-1',
        role: 'Product Manager',
        company: 'Forward Labs',
        location: 'Accra',
        start_date: '2023',
        end_date: '',
        current: true,
        bullets: ['Improved customer activation by 22%.'],
      }],
      education: [{
        id: 'education-1',
        degree: 'Bachelor of Arts',
        school: 'University of Ghana',
        location: 'Legon',
        start_date: '2018',
        end_date: '2022',
        details: '',
      }],
      skills: ['Strategy', 'Analytics', 'Research', 'Leadership', 'Communication'].map((name) => ({ id: name, name, level: 'Proficient' })),
      projects: [],
      certifications: [],
      languages: [],
      references: [],
      interests: [],
      settings: { density: 'comfortable' },
    },
  },
  templates: [
    { template_key: 'modern', name: 'Modern Focus', color: '#5b4df7' },
    { template_key: 'executive', name: 'Executive Edge', color: '#16324f' },
  ],
};

const encoded = Buffer.from(JSON.stringify(fixture)).toString('base64');
const phpCode = '<?php ' +
  '$sessionPath = sys_get_temp_dir() . "/lunettistar-ui-sessions";' +
  'if (!is_dir($sessionPath)) { mkdir($sessionPath, 0777, true); }' +
  'ini_set("session.save_path", $sessionPath);' +
  'putenv("APP_URL=http://localhost");' +
  '$_SERVER["SCRIPT_NAME"] = "/index.php";' +
  '$_SERVER["REQUEST_URI"] = "/resume/builder/1";' +
  '$_SERVER["HTTP_HOST"] = "localhost";' +
  'require ' + JSON.stringify(root + '/config/app.php') + ';' +
  '$fixture = json_decode(base64_decode(' + JSON.stringify(encoded) + '), true);' +
  '$resume = $fixture["resume"]; $templates = $fixture["templates"];' +
  '$title = "Edit Product CV"; $csrfToken = Csrf::token();' +
  'require ' + JSON.stringify(root + '/app/Views/resume/builder.php') + ';';

const php = new PHP(await loadNodeRuntime('8.1', {
  emscriptenOptions: { processId: 2 },
}));
useHostFilesystem(php);
const rendered = await php.runStream({ code: phpCode });
const html = await rendered.stdoutText;
const phpErrors = await rendered.stderrText;
const phpExitCode = await rendered.exitCode;
assert.equal(phpExitCode, 0, phpErrors || 'Builder view failed to render.');
assert.equal(phpErrors, '', phpErrors);
assert.match(html, /id="builderData"/);

const dom = new JSDOM(html, {
  url: 'http://localhost/resume/builder/1',
  runScripts: 'outside-only',
  pretendToBeVisual: true,
});
const { window } = dom;
window.console = console;
window.HTMLElement.prototype.scrollTo = function () {};
window.open = () => null;

const apiCalls = [];
window.Lunetti = {
  async api(endpoint, options = {}) {
    apiCalls.push({ endpoint, options });
    if (endpoint.endsWith('/ats')) {
      return {
        success: true,
        data: {
          report: {
            score: 84,
            keyword_score: 60,
            grade: 'Strong',
            categories: {
              'Contact details': 15,
              'Professional summary': 15,
              'Experience impact': 20,
              'Relevant skills': 14,
              'Education': 10,
              'Readability': 8,
              'Job keywords': 2,
            },
            recommendations: ['Add two target-role keywords naturally.'],
            matched_keywords: ['strategy'],
            missing_keywords: ['roadmap'],
          },
        },
      };
    }
    if (endpoint.endsWith('/assistant')) {
      return { success: true, data: { result: { text: 'A focused suggestion.' } } };
    }
    if (options.method === 'PUT') {
      return {
        success: true,
        data: {
          resume: { version: 2, updated_at: '2026-07-20 00:01:00', completion: 90 },
        },
      };
    }
    return { success: true, data: {} };
  },
  toast() {},
  openModal(id) {
    const modal = window.document.getElementById(id);
    if (modal) modal.classList.add('open');
  },
  closeModal(id) {
    const modal = window.document.getElementById(id);
    if (modal) modal.classList.remove('open');
  },
};

const previewScroll = window.document.getElementById('previewScroll');
Object.defineProperty(previewScroll, 'clientWidth', { value: 900 });
Object.defineProperty(previewScroll, 'clientHeight', { value: 1000 });

window.eval(fs.readFileSync(path.join(root, 'public/assets/resume/renderer.js'), 'utf8'));
window.eval(fs.readFileSync(path.join(root, 'public/assets/resume/builder.js'), 'utf8'));

assert.match(window.document.getElementById('resumePreview').innerHTML, /Alex Morgan/);
assert.match(window.document.getElementById('sectionEditor').textContent, /Personal details/);

const editorControls = [...window.document.querySelectorAll('#sectionEditor input:not([type="hidden"]), #sectionEditor textarea, #sectionEditor select')];
editorControls.forEach((control) => {
  assert.ok(control.labels?.length, `Builder field ${control.id || control.dataset.field || control.dataset.key} should have an associated label.`);
});
const builderIds = [...window.document.querySelectorAll('[id]')].map((element) => element.id);
assert.equal(new Set(builderIds).size, builderIds.length, 'Builder should not render duplicate element IDs.');

const nameInput = window.document.querySelector('[data-field="personal.full_name"]');
nameInput.value = 'Ama Mensah';
nameInput.dispatchEvent(new window.Event('input', { bubbles: true }));
assert.match(window.document.getElementById('resumePreview').innerHTML, /Ama Mensah/);

window.document.querySelector('[data-template-key="executive"]').click();
assert.ok(window.document.querySelector('#resumePreview .cv-template-executive'));

const title = window.document.getElementById('resumeTitle');
title.value = 'Tailored Product CV';
title.dispatchEvent(new window.Event('input', { bubbles: true }));
await new Promise((resolve) => setTimeout(resolve, 1400));
const saveCall = apiCalls.find((call) => call.options.method === 'PUT');
assert.ok(saveCall, 'Autosave should issue a PUT request.');
assert.equal(JSON.parse(saveCall.options.body).version, 1, 'Autosave should send its optimistic-lock version.');
assert.match(window.document.getElementById('saveStatusText').textContent, /saved/i);

window.document.getElementById('runAtsButton').click();
await new Promise((resolve) => setTimeout(resolve, 50));
assert.equal(window.document.getElementById('atsScore').textContent, '84');
assert.match(window.document.getElementById('atsRecommendations').textContent, /target-role keywords/);

window.document.querySelector('[data-smart-action="summary"]').click();
assert.ok(window.document.getElementById('summaryAssistantModal').classList.contains('open'));

window.close();
console.log('Builder UI smoke test passed.');
process.exit(0);
