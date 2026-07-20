const test = require('node:test');
const assert = require('node:assert/strict');
const renderer = require('../public/assets/resume/renderer.js');

function resume(overrides = {}) {
  return {
    template_key: 'modern',
    language: 'en',
    accent_color: '#5b4df7',
    font_family: 'Inter',
    content: {
      personal: {
        full_name: 'Emmanuel Baah',
        headline: 'Software Engineer',
        email: 'emmanuel@example.com',
        phone: '+233 24 000 0000',
        location: 'Accra, Ghana',
        website: '',
        linkedin: '',
      },
      summary: 'Software engineer focused on reliable applications, thoughtful user experiences, and measurable improvements for growing teams.',
      experience: [{
        role: 'Developer',
        company: 'Example Ltd',
        start_date: '2024',
        current: true,
        bullets: ['Improved checkout completion by 18%.'],
      }],
      education: [{ degree: 'Diploma in Software Engineering', school: 'IPMC', end_date: '2026' }],
      skills: ['PHP', 'MySQL', 'JavaScript', 'Python', 'React'].map((name) => ({ name, level: 'Proficient' })),
      projects: [],
      certifications: [],
      languages: [],
      references: [],
      interests: [],
      ...overrides,
    },
  };
}

test('renders a selected template and core CV sections', () => {
  const html = renderer.renderResume(resume(), { placeholders: false });
  assert.match(html, /cv-template-modern/);
  assert.match(html, /EMMANUEL BAAH|Emmanuel Baah/);
  assert.match(html, /Professional Summary/);
  assert.match(html, /Improved checkout completion by 18%/);
});

test('escapes untrusted CV content', () => {
  const data = resume();
  data.content.personal.full_name = '<script>alert(1)</script>';
  const html = renderer.renderResume(data, { placeholders: false });
  assert.doesNotMatch(html, /<script>/);
  assert.match(html, /&lt;script&gt;/);
});

test('translates section headings without changing user content', () => {
  const data = resume();
  data.language = 'fr';
  const html = renderer.renderResume(data, { placeholders: false });
  assert.match(html, /Expérience/);
  assert.match(html, /Software engineer focused/);
});

test('calculates progress from meaningful sections', () => {
  assert.equal(renderer.calculateProgress(resume().content), 90);
  assert.equal(renderer.calculateProgress({ personal: {}, experience: [], education: [], skills: [] }), 0);
});
