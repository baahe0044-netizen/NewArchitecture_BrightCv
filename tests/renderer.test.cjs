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

function sectionOrder(html) {
  return [...html.matchAll(/cv-(summary|experience|education|skills|projects)-section/g)].map((match) => match[1]);
}

test('renders every template as one vertical column by default', () => {
  renderer.TEMPLATE_KEYS.forEach((key) => {
    const data = resume();
    data.template_key = key;
    const html = renderer.renderResume(data, { placeholders: false });
    const stacked = renderer.TEMPLATES[key].layout === 'stacked';
    assert.equal(html.includes('cv-body'), stacked, key + ' should render its designed layout');
    assert.equal(html.includes('cv-columns'), !stacked, key + ' should not mix both layouts');
    assert.match(html, new RegExp('cv-layout-' + renderer.TEMPLATES[key].layout));
  });
});

test('the CV layout setting overrides the template default', () => {
  const data = resume();
  data.template_key = 'executive';
  data.content.settings = { layout: 'stacked' };
  const html = renderer.renderResume(data, { placeholders: false });
  assert.match(html, /cv-layout-stacked/);
  assert.doesNotMatch(html, /cv-columns/);
});

test('section order follows the template, then the CV setting', () => {
  const standard = resume();
  standard.template_key = 'modern';
  assert.deepEqual(
    sectionOrder(renderer.renderResume(standard, { placeholders: false })).slice(0, 4),
    ['summary', 'experience', 'education', 'skills']
  );

  const skillsFirst = resume();
  skillsFirst.template_key = 'modern';
  skillsFirst.content.settings = { section_order: 'skills_first' };
  assert.deepEqual(
    sectionOrder(renderer.renderResume(skillsFirst, { placeholders: false })).slice(0, 3),
    ['summary', 'skills', 'experience']
  );
});

test('density is declared on the document so print matches the preview', () => {
  const data = resume();
  data.content.settings = { density: 'compact' };
  assert.match(renderer.renderResume(data, { placeholders: false }), /cv-density-compact/);
  assert.match(renderer.renderResume(resume(), { placeholders: false }), /cv-density-comfortable/);
});

test('an unknown template falls back to modern rather than failing', () => {
  const data = resume();
  data.template_key = 'not-a-template';
  const html = renderer.renderResume(data, { placeholders: false });
  assert.match(html, /cv-template-modern/);
  assert.match(html, /cv-layout-stacked/);
});

test('a stated proficiency is read into a number a template can draw', () => {
  assert.equal(renderer.skillStrength('Expert'), 100);
  assert.equal(renderer.skillStrength('Native speaker'), 100);
  assert.equal(renderer.skillStrength('Advanced'), 78);
  assert.equal(renderer.skillStrength('Intermediate'), 55);
  assert.equal(renderer.skillStrength('Basic'), 30);

  // Written as figures instead of words.
  assert.equal(renderer.skillStrength('85%'), 85);
  assert.equal(renderer.skillStrength('4/5'), 80);
  assert.equal(renderer.skillStrength('3 of 5'), 60);

  // Nothing is invented from a level that says nothing about strength, and a
  // word that merely contains one is not one.
  assert.equal(renderer.skillStrength(''), 0);
  assert.equal(renderer.skillStrength('Certified'), 0);
  assert.equal(renderer.skillStrength('Goodwill ambassador'), 0);
});

test('skills carry their level as numbers for templates that draw it', () => {
  const data = resume();
  data.content.skills = [
    { name: 'PHP', level: 'Expert' },
    { name: 'Go', level: '' },
  ];
  const html = renderer.renderResume(data, { placeholders: false });

  assert.match(html, /<span class="cv-skill" style="--cv-skill:100;--cv-skill-dots:5"><b>PHP<\/b>/);
  assert.match(html, /<i class="cv-level-meter" aria-hidden="true"><\/i>/);

  // A skill with no level stated has nothing to draw, so no meter is drawn and
  // no strength is guessed for it.
  assert.match(html, /<span class="cv-skill"><b>Go<\/b><\/span>/);
  assert.equal((html.match(/cv-level-meter/g) || []).length, 1);
});

test('languages are set the same way as skills so a template styles both', () => {
  const data = resume();
  data.content.languages = [{ name: 'Twi', level: 'Native' }];
  const html = renderer.renderResume(data, { placeholders: false });

  assert.match(html, /<span class="cv-language" style="--cv-skill:100;--cv-skill-dots:5"><b>Twi<\/b>/);
});

test('a level written by the applicant is escaped like any other content', () => {
  const data = resume();
  data.content.skills = [{ name: 'PHP', level: '"><script>alert(1)</script>' }];
  const html = renderer.renderResume(data, { placeholders: false });

  assert.ok(!html.includes('<script>alert(1)</script>'));
  assert.match(html, /&lt;script&gt;/);
  // An unreadable level must not reach the style attribute either.
  assert.ok(!html.includes('style="--cv-skill:0'));
});

test('every template can be rendered and names itself on the document', () => {
  for (const key of renderer.TEMPLATE_KEYS) {
    const data = resume();
    data.template_key = key;
    assert.match(renderer.renderResume(data, { placeholders: false }), new RegExp('cv-template-' + key + '\\b'));
  }
});
