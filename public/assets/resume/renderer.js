(function (root, factory) {
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  root.LunettiResume = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';

  const translations = {
    en: {
      summary: 'Professional Summary', experience: 'Experience', education: 'Education',
      skills: 'Skills', projects: 'Projects', certifications: 'Certifications',
      languages: 'Languages', references: 'References', interests: 'Interests',
      present: 'Present', referenceAvailable: 'References available upon request',
    },
    fr: {
      summary: 'Profil Professionnel', experience: 'Expérience', education: 'Formation',
      skills: 'Compétences', projects: 'Projets', certifications: 'Certifications',
      languages: 'Langues', references: 'Références', interests: 'Centres d’intérêt',
      present: 'Aujourd’hui', referenceAvailable: 'Références disponibles sur demande',
    },
    es: {
      summary: 'Perfil Profesional', experience: 'Experiencia', education: 'Educación',
      skills: 'Habilidades', projects: 'Proyectos', certifications: 'Certificaciones',
      languages: 'Idiomas', references: 'Referencias', interests: 'Intereses',
      present: 'Actualidad', referenceAvailable: 'Referencias disponibles a petición',
    },
  };

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function safeUrl(value) {
    const url = String(value || '').trim();
    if (!url) return '';
    return /^(https?:\/\/|mailto:|tel:)/i.test(url) ? url : 'https://' + url.replace(/^\/+/, '');
  }

  function text(value, fallback, placeholders) {
    const clean = String(value || '').trim();
    return escapeHtml(clean || (placeholders ? fallback : ''));
  }

  function hasText(value) {
    return String(value || '').trim() !== '';
  }

  function meaningful(entries, keys) {
    return (Array.isArray(entries) ? entries : []).filter((entry) =>
      keys.some((key) => hasText(entry?.[key]))
    );
  }

  function formatPeriod(entry, locale) {
    const t = translations[locale] || translations.en;
    const start = String(entry.start_date || '').trim();
    const end = entry.current ? t.present : String(entry.end_date || '').trim();
    if (!start && !end) return '';
    if (!start) return escapeHtml(end);
    if (!end) return escapeHtml(start);
    return escapeHtml(start + ' — ' + end);
  }

  function section(title, body, className = '') {
    if (!body) return '';
    return '<section class="cv-section ' + className + '"><h2>' + escapeHtml(title) + '</h2>' + body + '</section>';
  }

  function renderResume(resume, options = {}) {
    const content = resume?.content || {};
    const personal = content.personal || {};
    const placeholders = Boolean(options.placeholders);
    const locale = ['en', 'fr', 'es'].includes(resume?.language) ? resume.language : 'en';
    const t = translations[locale];
    const template = ['modern', 'executive', 'minimal', 'creative', 'graduate', 'tech'].includes(resume?.template_key)
      ? resume.template_key
      : 'modern';
    const accent = /^#[0-9a-f]{6}$/i.test(resume?.accent_color || '') ? resume.accent_color : '#5b4df7';
    const font = ['Inter', 'Arial', 'Georgia', 'Poppins', 'Source Sans 3'].includes(resume?.font_family)
      ? resume.font_family
      : 'Inter';

    const name = text(personal.full_name, 'YOUR NAME', placeholders);
    const headline = text(personal.headline, 'TARGET ROLE', placeholders);
    const summaryText = text(
      content.summary,
      'Add a focused professional summary that explains your experience, strongest skills, and the value you bring.',
      placeholders
    );

    const contactItems = [
      personal.email && '<span>' + escapeHtml(personal.email) + '</span>',
      personal.phone && '<span>' + escapeHtml(personal.phone) + '</span>',
      personal.location && '<span>' + escapeHtml(personal.location) + '</span>',
      personal.website && '<span>' + escapeHtml(personal.website) + '</span>',
      personal.linkedin && '<span>' + escapeHtml(personal.linkedin) + '</span>',
    ].filter(Boolean);
    if (placeholders && contactItems.length === 0) {
      contactItems.push('<span>you@example.com</span>', '<span>+233 00 000 0000</span>', '<span>Accra, Ghana</span>');
    }

    const experiences = meaningful(content.experience, ['role', 'company', 'bullets']);
    const experienceBody = (experiences.length ? experiences : (placeholders ? [{
      role: 'Your most recent role', company: 'Company name', start_date: '2023',
      current: true, bullets: ['Describe an achievement and its result', 'Add another relevant contribution'],
    }] : [])).map((entry) => {
      const bullets = (Array.isArray(entry.bullets) ? entry.bullets : []).filter(hasText);
      return '<article class="cv-entry">' +
        '<div class="cv-entry-head"><div><h3>' + text(entry.role, 'Role title', placeholders) + '</h3>' +
        '<p class="cv-organization">' + text(entry.company, 'Company', placeholders) +
        (entry.location ? ' · ' + escapeHtml(entry.location) : '') + '</p></div>' +
        '<time>' + (formatPeriod(entry, locale) || (placeholders ? '2023 — ' + t.present : '')) + '</time></div>' +
        (bullets.length ? '<ul>' + bullets.map((bullet) => '<li>' + escapeHtml(bullet) + '</li>').join('') + '</ul>' : '') +
        '</article>';
    }).join('');

    const education = meaningful(content.education, ['degree', 'school', 'details']);
    const educationBody = (education.length ? education : (placeholders ? [{
      degree: 'Qualification or degree', school: 'Institution name', start_date: '2019', end_date: '2023',
    }] : [])).map((entry) =>
      '<article class="cv-entry cv-education-entry"><div class="cv-entry-head"><div><h3>' +
      text(entry.degree, 'Qualification', placeholders) + '</h3><p class="cv-organization">' +
      text(entry.school, 'Institution', placeholders) + (entry.location ? ' · ' + escapeHtml(entry.location) : '') +
      '</p></div><time>' + formatPeriod(entry, locale) + '</time></div>' +
      (entry.details ? '<p class="cv-detail">' + escapeHtml(entry.details) + '</p>' : '') + '</article>'
    ).join('');

    const skills = meaningful(content.skills, ['name']);
    const skillBody = (skills.length ? skills : (placeholders ? [
      { name: 'Add a key skill', level: 'Advanced' },
      { name: 'Add another skill', level: 'Intermediate' },
    ] : [])).map((skill) =>
      '<span class="cv-skill"><b>' + text(skill.name, 'Skill', placeholders) + '</b>' +
      (skill.level ? '<small>' + escapeHtml(skill.level) + '</small>' : '') + '</span>'
    ).join('');

    const projects = meaningful(content.projects, ['name', 'description']);
    const projectBody = projects.map((project) =>
      '<article class="cv-entry"><div class="cv-entry-head"><div><h3>' + escapeHtml(project.name || '') + '</h3>' +
      (project.role ? '<p class="cv-organization">' + escapeHtml(project.role) + '</p>' : '') +
      '</div><time>' + formatPeriod(project, locale) + '</time></div>' +
      (project.description ? '<p class="cv-detail">' + escapeHtml(project.description) + '</p>' : '') +
      (project.url ? '<p class="cv-link">' + escapeHtml(project.url) + '</p>' : '') + '</article>'
    ).join('');

    const certifications = meaningful(content.certifications, ['name']);
    const certificationBody = certifications.map((item) =>
      '<article class="cv-compact-entry"><div><b>' + escapeHtml(item.name || '') + '</b>' +
      (item.issuer ? '<span>' + escapeHtml(item.issuer) + '</span>' : '') + '</div>' +
      (item.date ? '<time>' + escapeHtml(item.date) + '</time>' : '') + '</article>'
    ).join('');

    const languages = meaningful(content.languages, ['name']);
    const languageBody = languages.map((item) =>
      '<span class="cv-language"><b>' + escapeHtml(item.name || '') + '</b>' +
      (item.level ? '<small>' + escapeHtml(item.level) + '</small>' : '') + '</span>'
    ).join('');

    const references = meaningful(content.references, ['name']);
    const referenceBody = references.map((item) =>
      '<article class="cv-reference"><b>' + escapeHtml(item.name || '') + '</b>' +
      (item.position || item.company ? '<span>' + escapeHtml([item.position, item.company].filter(Boolean).join(' · ')) + '</span>' : '') +
      (item.email ? '<small>' + escapeHtml(item.email) + '</small>' : '') +
      (item.phone ? '<small>' + escapeHtml(item.phone) + '</small>' : '') + '</article>'
    ).join('');

    const interests = (Array.isArray(content.interests) ? content.interests : []).filter(hasText);
    const interestBody = interests.map((item) => '<span>' + escapeHtml(item) + '</span>').join('');

    const mainSections = [
      section(t.summary, summaryText ? '<p class="cv-summary">' + summaryText.replace(/\n/g, '<br>') + '</p>' : '', 'cv-summary-section'),
      section(t.experience, experienceBody, 'cv-experience-section'),
      section(t.projects, projectBody, 'cv-projects-section'),
    ].join('');

    const secondarySections = [
      section(t.education, educationBody, 'cv-education-section'),
      section(t.skills, skillBody ? '<div class="cv-skills">' + skillBody + '</div>' : '', 'cv-skills-section'),
      section(t.certifications, certificationBody, 'cv-certifications-section'),
      section(t.languages, languageBody ? '<div class="cv-languages">' + languageBody + '</div>' : '', 'cv-languages-section'),
      section(t.interests, interestBody ? '<div class="cv-interests">' + interestBody + '</div>' : '', 'cv-interests-section'),
      section(t.references, referenceBody, 'cv-references-section'),
    ].join('');

    return '<article class="cv-document cv-template-' + template + '" style="--cv-accent:' + accent + ';--cv-font:' +
      escapeHtml(font) + '">' +
      '<header class="cv-header"><div class="cv-monogram">' + escapeHtml((personal.full_name || 'CV').split(/\s+/).map((part) => part[0] || '').slice(0, 2).join('').toUpperCase()) +
      '</div><div class="cv-identity"><h1>' + name + '</h1><p>' + headline + '</p></div></header>' +
      '<div class="cv-contact">' + contactItems.join('<i aria-hidden="true"></i>') + '</div>' +
      '<div class="cv-columns"><main class="cv-main">' + mainSections + '</main><aside class="cv-side">' + secondarySections + '</aside></div>' +
      '</article>';
  }

  function calculateProgress(content) {
    content = content || {};
    const personal = content.personal || {};
    let score = 0;
    ['full_name', 'headline', 'email', 'phone', 'location'].forEach((field) => {
      if (hasText(personal[field])) score += 4;
    });
    if (String(content.summary || '').trim().length >= 80) score += 15;
    if (meaningful(content.experience, ['role', 'company']).some((item) => hasText(item.role) && hasText(item.company))) score += 25;
    if (meaningful(content.education, ['degree', 'school']).some((item) => hasText(item.degree) && hasText(item.school))) score += 15;
    const skills = meaningful(content.skills, ['name']).length;
    score += skills >= 5 ? 15 : skills >= 2 ? 8 : 0;
    if (
      meaningful(content.projects, ['name']).length ||
      meaningful(content.certifications, ['name']).length ||
      meaningful(content.languages, ['name']).length
    ) score += 10;
    return Math.min(100, score);
  }

  return { renderResume, calculateProgress, escapeHtml, safeUrl, translations };
});
