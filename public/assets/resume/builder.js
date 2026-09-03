(() => {
  'use strict';

  const payloadElement = document.getElementById('builderData');
  if (!payloadElement || !window.LunettiResume || !window.Lunetti) return;

  const config = JSON.parse(payloadElement.textContent);
  const clone = (value) => JSON.parse(JSON.stringify(value));
  const h = window.LunettiResume.escapeHtml;
  const { TEMPLATES, TEMPLATE_KEYS, LAYOUTS, ORDERS } = window.LunettiResume;
  const serverResume = clone(config.resume);
  let state = clone(serverResume);
  let currentSection = 'personal';
  let currentExtra = 'certifications';
  let zoom = 0.82;
  let dirty = false;
  let saving = false;
  let saveAgain = false;
  let saveTimer = null;
  let localTimer = null;
  let history = [];
  let historyIndex = -1;
  let pendingSuggestion = null;
  let lastFocusedInput = null;
  let speechRecognition = null;
  let previewTimer = null;
  const localKey = 'lunettistar.resume.' + state.id;

  // Declared before the bootstrap calls below: these are const, so anything
  // that runs during init would hit the temporal dead zone otherwise.

  // Ordered flow used by the step chips and the Back/Next footer.
  const SECTIONS = [
    { key: 'personal', label: 'Personal details' },
    { key: 'summary', label: 'Summary' },
    { key: 'experience', label: 'Experience' },
    { key: 'education', label: 'Education' },
    { key: 'skills', label: 'Skills' },
    { key: 'projects', label: 'Projects' },
    { key: 'extras', label: 'More sections' },
  ];

  // Entries collapse so a CV with several roles stays scannable instead of
  // rendering as one long wall of inputs. Keyed by entry id, which survives
  // reordering (index does not).
  const collapsedEntries = new Set();
  const entryKey = (array, entry, index) => array + ':' + (entry?.id || index);

  const DATE_HINT = 'Use one consistent format across your CV, for example Jan 2023.';

  normalizeState();
  restoreLocalDraft();
  pushHistory(true);
  renderEditor();
  updateStepNav();
  renderPreview();
  updateProgress();
  updateDesignControls();
  updateJobCount();
  setTimeout(fitPage, 60);

  function normalizeState() {
    state.content = state.content && typeof state.content === 'object' ? state.content : {};
    const defaults = {
      personal: {
        full_name: '', headline: '', email: '', phone: '', location: '', website: '', linkedin: '',
      },
      summary: '',
      experience: [],
      education: [],
      skills: [],
      projects: [],
      certifications: [],
      languages: [],
      references: [],
      interests: [],
      settings: { density: 'comfortable', layout: '', section_order: '' },
    };
    Object.keys(defaults).forEach((key) => {
      if (state.content[key] === undefined || state.content[key] === null) state.content[key] = clone(defaults[key]);
    });
    state.content.personal = Object.assign({}, defaults.personal, state.content.personal || {});
    Object.keys(defaults.personal).forEach((key) => {
      state.content.personal[key] = scalarText(state.content.personal[key], 255);
    });
    state.content.summary = scalarText(state.content.summary, 3000);
    state.content.settings = Object.assign({}, defaults.settings, state.content.settings || {});
    const densities = ['compact', 'comfortable', 'spacious'];
    if (!densities.includes(state.content.settings.density)) state.content.settings.density = 'comfortable';

    ['experience', 'education', 'skills', 'projects', 'certifications', 'languages', 'references'].forEach((key) => {
      const entries = Array.isArray(state.content[key]) ? state.content[key] : [];
      state.content[key] = entries
        .filter((entry) => entry && typeof entry === 'object' && !Array.isArray(entry))
        .slice(0, 25)
        .map((entry) => {
          const normalized = Object.assign(defaultEntry(key), entry);
          Object.keys(defaultEntry(key)).forEach((field) => {
            if (field === 'current') {
              normalized[field] = Boolean(normalized[field]);
            } else if (field === 'bullets') {
              normalized[field] = (Array.isArray(normalized[field]) ? normalized[field] : [])
                .map((bullet) => scalarText(bullet, 600))
                .filter(Boolean)
                .slice(0, 12);
            } else {
              normalized[field] = scalarText(normalized[field], field === 'id' ? 60 : 1600);
            }
          });
          normalized.id = normalized.id || uuid();
          return normalized;
        });
    });
    state.content.interests = (Array.isArray(state.content.interests) ? state.content.interests : [])
      .map((interest) => scalarText(interest, 80))
      .filter(Boolean)
      .slice(0, 20);

    state.name = scalarText(state.name, 150) || 'Untitled CV';
    if (!TEMPLATE_KEYS.includes(state.template_key)) state.template_key = 'modern';
    // Layout and order fall back to the template's own design, which is what a
    // CV saved before these controls existed has stored.
    const design = TEMPLATES[state.template_key];
    if (!LAYOUTS.includes(state.content.settings.layout)) state.content.settings.layout = design.layout;
    if (!ORDERS.includes(state.content.settings.section_order)) state.content.settings.section_order = design.order;
    if (!['en', 'fr', 'es'].includes(state.language)) state.language = 'en';
    if (!/^#[0-9a-f]{6}$/i.test(state.accent_color || '')) state.accent_color = '#5b4df7';
    if (!['Inter', 'Arial', 'Georgia', 'Poppins', 'Source Sans 3'].includes(state.font_family)) state.font_family = 'Inter';
    state.job_description = scalarText(state.job_description, 15000);
  }

  function scalarText(value, limit) {
    return typeof value === 'string' || typeof value === 'number'
      ? String(value).slice(0, limit)
      : '';
  }

  function restoreLocalDraft() {
    try {
      const local = JSON.parse(localStorage.getItem(localKey) || 'null');
      const serverTime = Date.parse(state.updated_at || 0);
      if (local && local.resume && local.savedAt > serverTime) {
        state = Object.assign(state, local.resume, { id: serverResume.id });
        normalizeState();
        dirty = true;
        setTimeout(() => window.Lunetti.toast('Recovered newer unsaved changes from this device.'), 250);
        scheduleSave();
      }
    } catch {
      localStorage.removeItem(localKey);
    }
  }

  function uuid() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();
    return Date.now().toString(36) + Math.random().toString(36).slice(2);
  }

  function setByPath(object, path, value) {
    const parts = path.split('.');
    let target = object;
    parts.slice(0, -1).forEach((part) => {
      if (!target[part] || typeof target[part] !== 'object') target[part] = {};
      target = target[part];
    });
    target[parts[parts.length - 1]] = value;
  }

  function field(label, path, value, options = {}) {
    const type = options.type || 'text';
    const placeholder = options.placeholder || '';
    const hint = options.hint ? '<span class="field-hint">' + h(options.hint) + '</span>' : '';
    const attrs = (options.maxlength ? ' maxlength="' + Number(options.maxlength) + '"' : '') +
      (options.autocomplete ? ' autocomplete="' + h(options.autocomplete) + '"' : '');
    const labelHtml = '<label for="' + h(path) + '">' + h(label) + '</label>';
    let control;
    if (type === 'textarea') {
      control = '<textarea id="' + h(path) + '" data-field="' + h(path) + '" placeholder="' + h(placeholder) + '"' + attrs + '>' + h(value || '') + '</textarea>';
    } else {
      control = '<input id="' + h(path) + '" type="' + h(type) + '" data-field="' + h(path) + '" value="' + h(value || '') + '" placeholder="' + h(placeholder) + '"' + attrs + '>';
    }
    return '<div class="field">' + labelHtml + control + hint + '</div>';
  }

  function arrayField(label, array, index, key, value, options = {}) {
    const type = options.type || 'text';
    const placeholder = options.placeholder || '';
    const id = 'field-' + array + '-' + Number(index) + '-' + key;
    const attrs = ' id="' + h(id) + '" data-array="' + h(array) + '" data-index="' + Number(index) + '" data-key="' + h(key) + '"' +
      (options.maxlength ? ' maxlength="' + Number(options.maxlength) + '"' : '');
    let control;
    if (type === 'textarea') {
      control = '<textarea' + attrs + ' placeholder="' + h(placeholder) + '">' + h(value || '') + '</textarea>';
    } else {
      control = '<input type="' + h(type) + '"' + attrs + ' value="' + h(value || '') + '" placeholder="' + h(placeholder) + '">';
    }
    return '<div class="field"><label for="' + h(id) + '">' + h(label) + '</label>' + control +
      (options.hint ? '<span class="field-hint">' + h(options.hint) + '</span>' : '') + '</div>';
  }

  function selectField(label, array, index, key, value, options) {
    const id = 'field-' + array + '-' + Number(index) + '-' + key;
    return '<div class="field"><label for="' + h(id) + '">' + h(label) + '</label><select id="' + h(id) + '" data-array="' + h(array) +
      '" data-index="' + Number(index) + '" data-key="' + h(key) + '">' +
      options.map((option) => '<option value="' + h(option) + '"' + (value === option ? ' selected' : '') + '>' + h(option) + '</option>').join('') +
      '</select></div>';
  }

  function editorHeading(title, description, actionHtml = '') {
    return '<div class="section-editor-heading"><div><h2>' + h(title) + '</h2><p>' + h(description) + '</p></div>' + actionHtml + '</div>';
  }

  function entryHeader(array, index, title, entry, summary) {
    const key = entryKey(array, entry, index);
    const collapsed = collapsedEntries.has(key);
    return '<div class="entry-card-header">' +
      '<button class="entry-toggle" type="button" data-toggle-entry="' + h(key) + '" aria-expanded="' + (!collapsed) + '">' +
      '<span class="entry-caret" aria-hidden="true">' + (collapsed ? '▸' : '▾') + '</span>' +
      '<span class="entry-titles"><b>' + h(title || ('Entry ' + (index + 1))) + '</b>' +
      (summary ? '<small>' + h(summary) + '</small>' : '') + '</span></button>' +
      '<div class="entry-card-actions">' +
      '<button type="button" data-move-entry="' + h(array) + '" data-entry-index="' + index + '" data-direction="-1" title="Move up" aria-label="Move up"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg></button>' +
      '<button type="button" data-move-entry="' + h(array) + '" data-entry-index="' + index + '" data-direction="1" title="Move down" aria-label="Move down"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>' +
      '<button type="button" data-remove-entry="' + h(array) + '" data-entry-index="' + index + '" title="Remove" aria-label="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>' +
      '</div></div>';
  }

  // Wraps an entry so collapsing hides the body but keeps the header usable.
  function entryCard(array, index, entry, title, summary, body) {
    const collapsed = collapsedEntries.has(entryKey(array, entry, index));
    return '<article class="entry-card' + (collapsed ? ' collapsed' : '') + '">' +
      entryHeader(array, index, title, entry, summary) +
      '<div class="entry-card-body"' + (collapsed ? ' hidden' : '') + '>' + body + '</div></article>';
  }

  function emptyState(message, actionLabel, array) {
    return '<div class="section-empty"><p>' + h(message) + '</p>' +
      '<button class="btn btn-secondary btn-small" type="button" data-add-entry="' + h(array) + '">' + h(actionLabel) + '</button></div>';
  }

  // Individual rows rather than one newline-separated textarea: the old field
  // relied on an invisible "one per line" rule and gave no way to add, remove
  // or reorder a single achievement.
  function bulletRows(array, entryIndex, bullets) {
    const rows = bullets.length ? bullets : [''];
    return '<div class="bullet-rows">' + rows.map((bullet, bulletIndex) =>
      '<div class="bullet-row">' +
      '<span class="bullet-dot" aria-hidden="true">•</span>' +
      '<textarea rows="2" maxlength="400" data-bullet-array="' + h(array) + '" data-bullet-entry="' + entryIndex +
      '" data-bullet-index="' + bulletIndex + '" placeholder="Delivered X, which improved Y by Z…" aria-label="Achievement ' + (bulletIndex + 1) + '">' +
      h(bullet) + '</textarea>' +
      (rows.length > 1
        ? '<button type="button" class="bullet-remove" data-remove-bullet="' + bulletIndex + '" data-bullet-entry="' + entryIndex +
          '" data-bullet-array="' + h(array) + '" title="Remove achievement" aria-label="Remove achievement ' + (bulletIndex + 1) + '">×</button>'
        : '') +
      '</div>'
    ).join('') + '</div>' +
    (rows.length < 12
      ? '<button class="add-bullet-button" type="button" data-add-bullet="' + entryIndex + '" data-bullet-array="' + h(array) + '">+ Add achievement</button>'
      : '<span class="field-hint">Maximum of 12 achievements per role.</span>');
  }

  function renderEditor() {
    const mount = document.getElementById('sectionEditor');
    if (!mount) return;
    const c = state.content;

    if (currentSection === 'personal') {
      mount.innerHTML = editorHeading('Personal details', 'Make it easy for recruiters to contact you.') +
        '<div class="editor-fields">' +
        field('Full name', 'personal.full_name', c.personal.full_name, { placeholder: 'e.g. Emmanuel Baah', maxlength: 120, autocomplete: 'name' }) +
        field('Professional headline', 'personal.headline', c.personal.headline, { placeholder: 'e.g. Software Engineer', maxlength: 160 }) +
        '<div class="field-grid">' +
        field('Email', 'personal.email', c.personal.email, { type: 'email', placeholder: 'you@example.com', maxlength: 190, autocomplete: 'email' }) +
        field('Phone', 'personal.phone', c.personal.phone, { type: 'tel', placeholder: '+233 24 000 0000', maxlength: 80, autocomplete: 'tel' }) +
        '</div>' +
        field('Location', 'personal.location', c.personal.location, { placeholder: 'Accra, Ghana', maxlength: 160 }) +
        '<div class="field-grid">' +
        field('Website', 'personal.website', c.personal.website, { placeholder: 'yourportfolio.com', maxlength: 255 }) +
        field('LinkedIn', 'personal.linkedin', c.personal.linkedin, { placeholder: 'linkedin.com/in/you', maxlength: 255 }) +
        '</div></div>';
      return;
    }

    if (currentSection === 'summary') {
      const count = String(c.summary || '').length;
      mount.innerHTML = editorHeading(
        'Professional summary',
        'Lead with the role you want, your relevant experience, and the value you deliver.',
        '<button class="btn btn-secondary" type="button" data-open-summary-assistant>Open summary writer</button>'
      ) +
        '<div class="editor-fields"><div class="field"><div class="field-label-row"><label for="summary">Summary</label><span class="character-count" id="summaryCount">' +
        count + ' / 3,000</span></div><textarea id="summary" data-field="summary" maxlength="3000" placeholder="Write 3–5 focused lines about your professional value…">' +
        h(c.summary || '') + '</textarea><span class="field-hint">A strong summary is usually 80–600 characters and avoids generic claims.</span></div>' +
        '<div class="bullet-help"><span>Tip</span><div>Replace phrases such as “hardworking team player” with evidence, specialist skills, or a clear result.</div></div></div>';
      return;
    }

    if (currentSection === 'experience') {
      mount.innerHTML = editorHeading('Experience', 'Show what changed because of your work.') +
        (c.experience.length
          ? '<div class="entry-list">' + c.experience.map((entry, index) => renderExperience(entry, index)).join('') + '</div>' +
            '<button class="add-entry-button" type="button" data-add-entry="experience">+ Add experience</button>'
          : emptyState('No roles added yet. Start with your most recent position.', '+ Add your first role', 'experience'));
      return;
    }

    if (currentSection === 'education') {
      mount.innerHTML = editorHeading('Education', 'Include the qualifications most relevant to your next role.') +
        (c.education.length
          ? '<div class="entry-list">' + c.education.map((entry, index) => renderEducation(entry, index)).join('') + '</div>' +
            '<button class="add-entry-button" type="button" data-add-entry="education">+ Add education</button>'
          : emptyState('No qualifications added yet.', '+ Add your first qualification', 'education'));
      return;
    }

    if (currentSection === 'skills') {
      mount.innerHTML = editorHeading('Skills', 'Prioritize role-specific abilities recruiters actually search for.') +
        (c.skills.length
          ? '<div class="entry-list">' + c.skills.map((entry, index) => renderSkill(entry, index)).join('') + '</div>' +
            '<button class="add-entry-button" type="button" data-add-entry="skills">+ Add skill</button>'
          : emptyState('No skills added yet.', '+ Add your first skill', 'skills')) +
        '<div class="bullet-help spaced"><span>ATS</span><div>Use the same truthful terminology as the target job description. Five to twelve focused skills usually work well.</div></div>';
      return;
    }

    if (currentSection === 'projects') {
      mount.innerHTML = editorHeading('Projects', 'Use projects to prove practical skills and initiative.') +
        (c.projects.length
          ? '<div class="entry-list">' + c.projects.map((entry, index) => renderProject(entry, index)).join('') + '</div>' +
            '<button class="add-entry-button" type="button" data-add-entry="projects">+ Add project</button>'
          : emptyState('No projects added yet. Projects are a strong substitute for limited work history.', '+ Add your first project', 'projects'));
      return;
    }

    renderExtras();
  }

  function updateStepNav() {
    const index = SECTIONS.findIndex((section) => section.key === currentSection);
    const previous = document.querySelector('[data-step-prev]');
    const next = document.querySelector('[data-step-next]');
    const count = document.getElementById('stepCount');
    if (!previous || !next || !count) return;

    previous.disabled = index <= 0;
    next.disabled = index >= SECTIONS.length - 1;
    count.textContent = 'Step ' + (index + 1) + ' of ' + SECTIONS.length;
  }

  function goToSection(key, { focusFirst = true } = {}) {
    if (!SECTIONS.some((section) => section.key === key)) return;
    currentSection = key;
    document.querySelectorAll('[data-section]').forEach((item) => {
      const active = item.dataset.section === key;
      item.classList.toggle('active', active);
      if (active) item.setAttribute('aria-current', 'step');
      else item.removeAttribute('aria-current');
    });
    // Optional call: scrollIntoView is absent in the jsdom-based UI test.
    document.querySelector('[data-section="' + key + '"]')?.scrollIntoView?.({ block: 'nearest', inline: 'center' });
    renderEditor();
    updateStepNav();

    const scroller = document.getElementById('editorContentMode');
    if (scroller) scroller.scrollTop = 0;
    if (focusFirst) {
      document.querySelector('#sectionEditor input:not([disabled]), #sectionEditor textarea, #sectionEditor select')?.focus();
    }
  }

  // Re-rendering the panel replaces its markup, which would otherwise throw the
  // reader back to the top of the form after adding or removing an entry.
  function rerenderEditor(focusSelector) {
    const scroller = document.getElementById('editorContentMode');
    const top = scroller ? scroller.scrollTop : 0;
    renderEditor();
    if (scroller) scroller.scrollTop = top;
    if (focusSelector) {
      const target = document.querySelector(focusSelector);
      target?.focus();
      target?.scrollIntoView?.({ block: 'nearest' });
    }
  }

  function renderExperience(entry, index) {
    const bullets = Array.isArray(entry.bullets) ? entry.bullets : [];
    const period = [entry.start_date, entry.current ? 'Present' : entry.end_date].filter(Boolean).join(' – ');
    const summary = [entry.company, period].filter(Boolean).join(' · ');

    const body =
      arrayField('Role title', 'experience', index, 'role', entry.role, { placeholder: 'e.g. Marketing Manager', maxlength: 160 }) +
      '<div class="field-grid">' +
      arrayField('Company', 'experience', index, 'company', entry.company, { placeholder: 'Company name', maxlength: 160 }) +
      arrayField('Location', 'experience', index, 'location', entry.location, { placeholder: 'City, Country', maxlength: 160 }) +
      '</div><div class="field-grid">' +
      arrayField('Start date', 'experience', index, 'start_date', entry.start_date, { placeholder: 'Jan 2023', maxlength: 30 }) +
      (entry.current
        ? '<div class="field"><label>End date</label><input value="Present" disabled aria-label="End date"></div>'
        : arrayField('End date', 'experience', index, 'end_date', entry.end_date, { placeholder: 'Jun 2026', maxlength: 30 })) +
      '</div><span class="field-hint">' + h(DATE_HINT) + '</span>' +
      '<label class="checkbox-field"><input type="checkbox" data-array="experience" data-index="' + index + '" data-key="current"' +
      (entry.current ? ' checked' : '') + '><span>I currently work here</span></label>' +
      // A group label rather than <label for>, since the achievements are now
      // several inputs; each row carries its own accessible name.
      '<div class="field" role="group" aria-labelledby="achievements-label-' + index + '">' +
      '<div class="field-label-row"><span class="field-label" id="achievements-label-' + index + '">Achievements</span>' +
      '<button type="button" data-improve-entry="' + index + '">Improve a bullet</button></div>' +
      bulletRows('experience', index, bullets) +
      '<span class="field-hint">Start with an action verb. Add scale, speed, money, or quality where you genuinely have the result.</span></div>';

    return entryCard('experience', index, entry, entry.role || 'New experience', summary, body);
  }

  function renderEducation(entry, index) {
    const period = [entry.start_date, entry.end_date].filter(Boolean).join(' – ');
    const summary = [entry.school, period].filter(Boolean).join(' · ');

    const body =
      arrayField('Degree or qualification', 'education', index, 'degree', entry.degree, { placeholder: 'e.g. Diploma in Software Engineering', maxlength: 180 }) +
      arrayField('Institution', 'education', index, 'school', entry.school, { placeholder: 'Institution name', maxlength: 180 }) +
      '<div class="field-grid">' +
      arrayField('Location', 'education', index, 'location', entry.location, { placeholder: 'Accra, Ghana', maxlength: 160 }) +
      arrayField('Start date', 'education', index, 'start_date', entry.start_date, { placeholder: 'Sep 2024', maxlength: 30 }) +
      '</div>' +
      arrayField('End date', 'education', index, 'end_date', entry.end_date, { placeholder: 'Jun 2026', maxlength: 30, hint: DATE_HINT }) +
      arrayField('Details', 'education', index, 'details', entry.details, { type: 'textarea', placeholder: 'Honours, relevant coursework, or achievement', maxlength: 1000 }) ;

    return entryCard('education', index, entry, entry.degree || 'New qualification', summary, body);
  }

  function renderSkill(entry, index) {
    const body = '<div class="field-grid">' +
      arrayField('Skill', 'skills', index, 'name', entry.name, { placeholder: 'e.g. MySQL', maxlength: 100 }) +
      selectField('Level', 'skills', index, 'level', entry.level || 'Proficient', ['Beginner', 'Intermediate', 'Proficient', 'Advanced', 'Expert']) +
      '</div>';

    return entryCard('skills', index, entry, entry.name || 'New skill', entry.name ? entry.level : '', body);
  }

  function renderProject(entry, index) {
    const period = [entry.start_date, entry.end_date].filter(Boolean).join(' – ');
    const summary = [entry.role, period].filter(Boolean).join(' · ');

    const body =
      arrayField('Project name', 'projects', index, 'name', entry.name, { placeholder: 'e.g. Pharmacy Management System', maxlength: 180 }) +
      '<div class="field-grid">' +
      arrayField('Your role', 'projects', index, 'role', entry.role, { placeholder: 'e.g. Full-stack developer', maxlength: 160 }) +
      arrayField('Project link', 'projects', index, 'url', entry.url, { placeholder: 'github.com/you/project', maxlength: 255 }) +
      '</div><div class="field-grid">' +
      arrayField('Start date', 'projects', index, 'start_date', entry.start_date, { placeholder: 'Mar 2026', maxlength: 30 }) +
      arrayField('End date', 'projects', index, 'end_date', entry.end_date, { placeholder: 'Jul 2026', maxlength: 30 }) +
      '</div>' +
      arrayField('What you built and why it matters', 'projects', index, 'description', entry.description, { type: 'textarea', placeholder: 'Technology, problem solved, and result…', maxlength: 1600 }) ;

    return entryCard('projects', index, entry, entry.name || 'New project', summary, body);
  }

  function renderExtras() {
    const c = state.content;
    const nav = '<div class="extras-nav">' +
      ['certifications', 'languages', 'references', 'interests'].map((name) =>
        '<button class="' + (currentExtra === name ? 'active' : '') + '" type="button" data-extra-section="' + name + '">' +
        h(name.charAt(0).toUpperCase() + name.slice(1)) + '</button>'
      ).join('') + '</div>';
    let body = '';

    if (currentExtra === 'certifications') {
      body = (c.certifications.length
        ? '<div class="entry-list">' + c.certifications.map((entry, index) => entryCard(
            'certifications', index, entry, entry.name || 'New certification',
            [entry.issuer, entry.date].filter(Boolean).join(' · '),
            arrayField('Certification', 'certifications', index, 'name', entry.name, { placeholder: 'e.g. AWS Cloud Practitioner', maxlength: 180 }) +
            '<div class="field-grid">' +
            arrayField('Issuer', 'certifications', index, 'issuer', entry.issuer, { placeholder: 'Issuing organization', maxlength: 180 }) +
            arrayField('Date', 'certifications', index, 'date', entry.date, { placeholder: 'Mar 2026', maxlength: 30 }) +
            '</div>' +
            arrayField('Credential link', 'certifications', index, 'url', entry.url, { placeholder: 'https://…', maxlength: 255 })
          )).join('') + '</div><button class="add-entry-button" type="button" data-add-entry="certifications">+ Add certification</button>'
        : emptyState('No certifications added yet.', '+ Add a certification', 'certifications'));
    }

    if (currentExtra === 'languages') {
      body = (c.languages.length
        ? '<div class="entry-list">' + c.languages.map((entry, index) => entryCard(
            'languages', index, entry, entry.name || 'New language', entry.name ? entry.level : '',
            '<div class="field-grid">' +
            arrayField('Language', 'languages', index, 'name', entry.name, { placeholder: 'e.g. English', maxlength: 100 }) +
            selectField('Proficiency', 'languages', index, 'level', entry.level || 'Professional', ['Basic', 'Conversational', 'Professional', 'Fluent', 'Native']) +
            '</div>'
          )).join('') + '</div><button class="add-entry-button" type="button" data-add-entry="languages">+ Add language</button>'
        : emptyState('No languages added yet.', '+ Add a language', 'languages'));
    }

    if (currentExtra === 'references') {
      body = (c.references.length
        ? '<div class="entry-list">' + c.references.map((entry, index) => entryCard(
            'references', index, entry, entry.name || 'New reference',
            [entry.position, entry.company].filter(Boolean).join(' · '),
            arrayField('Full name', 'references', index, 'name', entry.name, { placeholder: 'Reference name', maxlength: 160 }) +
            '<div class="field-grid">' +
            arrayField('Position', 'references', index, 'position', entry.position, { placeholder: 'Job title', maxlength: 160 }) +
            arrayField('Company', 'references', index, 'company', entry.company, { placeholder: 'Organization', maxlength: 160 }) +
            '</div><div class="field-grid">' +
            arrayField('Email', 'references', index, 'email', entry.email, { type: 'email', placeholder: 'email@example.com', maxlength: 190 }) +
            arrayField('Phone', 'references', index, 'phone', entry.phone, { placeholder: '+233…', maxlength: 80 }) +
            '</div>'
          )).join('') + '</div><button class="add-entry-button" type="button" data-add-entry="references">+ Add reference</button>'
        : emptyState('No references added yet. "Available on request" is also acceptable.', '+ Add a reference', 'references'));
    }

    if (currentExtra === 'interests') {
      body = '<div class="field"><label for="interestInput">Add an interest</label><div class="tag-input-row"><input id="interestInput" maxlength="80" placeholder="e.g. Community volunteering"><button type="button" data-add-interest aria-label="Add interest">+</button></div></div>' +
        '<div class="simple-tags spaced">' + c.interests.map((interest, index) =>
          '<span class="simple-tag">' + h(interest) + '<button type="button" data-remove-interest="' + index + '" aria-label="Remove">×</button></span>'
        ).join('') + '</div><div class="bullet-help spaced"><span>Tip</span><div>Interests are optional. Include them only when they add personality or support your professional story.</div></div>';
    }

    document.getElementById('sectionEditor').innerHTML =
      editorHeading('Additional information', 'Add only the information that strengthens this application.') + nav + body;
  }

  function defaultEntry(array) {
    const id = uuid();
    const entries = {
      experience: { id, role: '', company: '', location: '', start_date: '', end_date: '', current: false, bullets: [] },
      education: { id, degree: '', school: '', location: '', start_date: '', end_date: '', details: '' },
      skills: { id, name: '', level: 'Proficient' },
      projects: { id, name: '', role: '', url: '', start_date: '', end_date: '', description: '' },
      certifications: { id, name: '', issuer: '', date: '', url: '' },
      languages: { id, name: '', level: 'Professional' },
      references: { id, name: '', position: '', company: '', email: '', phone: '' },
    };
    return entries[array];
  }

  // Typing fires a state change per keystroke. Rebuilding the entire preview
  // that often makes the editor feel sluggish and can fight with scrolling,
  // so rapid edits are coalesced into one render. Non-typing paths (template
  // changes, undo/redo, initial load) still render immediately.
  function schedulePreview() {
    if (previewTimer) clearTimeout(previewTimer);
    previewTimer = setTimeout(() => {
      previewTimer = null;
      renderPreview();
    }, 120);
  }

  function renderPreview() {
    if (previewTimer) {
      clearTimeout(previewTimer);
      previewTimer = null;
    }

    const preview = document.getElementById('resumePreview');
    if (!preview) return;

    // Replacing the markup can clamp the scroll position, which reads as the
    // preview jumping while the user edits. Restore where they were.
    const scroller = preview.closest('.preview-scroll');
    const scrollTop = scroller ? scroller.scrollTop : null;

    preview.innerHTML = window.LunettiResume.renderResume(state, { placeholders: true });
    applyZoom();

    if (scroller && scrollTop !== null) {
      scroller.scrollTop = scrollTop;
    }
  }

  function updateProgress() {
    const progress = window.LunettiResume.calculateProgress(state.content);
    state.completion = progress;
    document.getElementById('completionPercent').textContent = progress + '%';
    document.getElementById('completionBar').style.width = progress + '%';
    document.getElementById('progressSmallValue').textContent = progress + '%';
    document.getElementById('progressRingSmall').style.setProperty('--progress', progress);

    const levels = progress >= 90
      ? ['Application ready', 'Great work. Tailor keywords and run a final scan.']
      : progress >= 70
        ? ['Strong draft', 'Complete the remaining gaps to become Application Ready.']
        : progress >= 40
          ? ['Taking shape', 'Add evidence to the key incomplete sections.']
          : ['Building foundation', 'Complete sections to reach Application Ready.'];
    document.getElementById('progressLevel').textContent = levels[0];
    document.getElementById('progressRewardText').textContent = levels[1];
    document.getElementById('completionHint').textContent = levels[1];

    const c = state.content;
    const personalComplete = ['full_name', 'headline', 'email', 'phone', 'location'].every((key) => String(c.personal[key] || '').trim());
    const summaryComplete = String(c.summary || '').trim().length >= 80;
    const experienceComplete = c.experience.some((item) => item.role && item.company && item.bullets?.some((b) => String(b).trim()));
    const educationComplete = c.education.some((item) => item.degree && item.school);
    const skillsComplete = c.skills.filter((item) => String(item.name || '').trim()).length >= 5;
    const projectsComplete = c.projects.some((item) => item.name && item.description);
    const extrasComplete = c.certifications.some((item) => item.name) || c.languages.some((item) => item.name) || c.references.some((item) => item.name) || c.interests.length;
    const checks = { personal: personalComplete, summary: summaryComplete, experience: experienceComplete, education: educationComplete, skills: skillsComplete, projects: projectsComplete, extras: Boolean(extrasComplete) };
    Object.entries(checks).forEach(([key, complete]) => {
      const element = document.querySelector('[data-section-check="' + key + '"]');
      if (!element) return;
      // The chips are compact, so status shows as a glyph, with the wording
      // kept for screen readers rather than relying on the icon or colour.
      element.innerHTML = '<span aria-hidden="true">' + (complete ? '✓' : '○') + '</span>' +
        '<span class="sr-only">' + (complete ? 'Section complete' : 'Section incomplete') + '</span>';
      element.classList.toggle('complete', complete);
    });
  }

  function stateChanged(options = {}) {
    dirty = true;
    setSaveState('saving', 'Unsaved changes');
    if (options.immediatePreview) {
      renderPreview();
    } else {
      schedulePreview();
    }
    updateProgress();
    scheduleLocalBackup();
    scheduleSave();
    if (options.history) pushHistory();
  }

  function buildDocumentPayload() {
    return {
      name: state.name,
      template_key: state.template_key,
      language: state.language,
      accent_color: state.accent_color,
      font_family: state.font_family,
      job_description: state.job_description,
      content: state.content,
    };
  }

  function buildSavePayload() {
    return {
      ...buildDocumentPayload(),
      version: state.version,
    };
  }

  function documentHash() {
    return JSON.stringify(buildDocumentPayload());
  }

  function scheduleSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => saveResume(), 1200);
  }

  async function saveResume(force = false) {
    clearTimeout(saveTimer);
    if (saving) {
      saveAgain = true;
      return false;
    }
    if (!dirty && !force) return true;

    saving = true;
    const sentHash = documentHash();
    setSaveState('saving', 'Saving…');
    try {
      const response = await window.Lunetti.api(config.endpoints.save, {
        method: 'PUT',
        body: JSON.stringify(buildSavePayload()),
      });
      const saved = response.data.resume;
      state.version = saved.version;
      state.updated_at = saved.updated_at;
      state.completion = saved.completion;

      if (documentHash() === sentHash) {
        dirty = false;
        localStorage.removeItem(localKey);
        setSaveState('saved', 'Saved');
      } else {
        dirty = true;
        saveAgain = true;
      }
      return true;
    } catch (error) {
      dirty = true;
      saveLocalDraft();
      const conflict = error.status === 409;
      setSaveState('error', conflict ? 'Newer version found' : (navigator.onLine ? 'Unable to save' : 'Saved on this device'));
      if (conflict || force) window.Lunetti.toast(error.message, 'error');
      if (conflict) saveAgain = false;
      return false;
    } finally {
      saving = false;
      if (saveAgain) {
        saveAgain = false;
        scheduleSave();
      }
    }
  }

  function setSaveState(status, message) {
    const indicator = document.getElementById('saveIndicator');
    indicator.dataset.state = status;
    document.getElementById('saveStatusText').textContent = message;
  }

  function scheduleLocalBackup() {
    clearTimeout(localTimer);
    localTimer = setTimeout(saveLocalDraft, 250);
  }

  function saveLocalDraft() {
    try {
      localStorage.setItem(localKey, JSON.stringify({ savedAt: Date.now(), resume: state }));
    } catch {
      // Storage can be disabled; server autosave remains the primary path.
    }
  }

  function pushHistory(initial = false) {
    const snapshot = JSON.stringify(buildDocumentPayload());
    if (!initial && history[historyIndex] === snapshot) return;
    history = history.slice(0, historyIndex + 1);
    history.push(snapshot);
    if (history.length > 40) history.shift();
    historyIndex = history.length - 1;
    updateHistoryButtons();
  }

  function restoreHistory(index) {
    if (index < 0 || index >= history.length) return;
    historyIndex = index;
    const snapshot = JSON.parse(history[index]);
    Object.assign(state, snapshot);
    normalizeState();
    document.getElementById('resumeTitle').value = state.name;
    document.getElementById('jobDescription').value = state.job_description || '';
    renderEditor();
    renderPreview();
    updateProgress();
    updateDesignControls();
    updateJobCount();
    dirty = true;
    scheduleSave();
    scheduleLocalBackup();
    updateHistoryButtons();
  }

  function updateHistoryButtons() {
    document.getElementById('undoButton').disabled = historyIndex <= 0;
    document.getElementById('redoButton').disabled = historyIndex >= history.length - 1;
  }

  function updateDesignControls() {
    document.querySelectorAll('[data-template-key]').forEach((button) => {
      const active = button.dataset.templateKey === state.template_key;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    document.querySelectorAll('[data-accent-color]').forEach((button) => {
      const active = button.dataset.accentColor.toLowerCase() === state.accent_color.toLowerCase();
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    document.querySelectorAll('[data-density]').forEach((button) => {
      const active = button.dataset.density === (state.content.settings?.density || 'comfortable');
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    document.querySelectorAll('[data-layout]').forEach((button) => {
      const active = button.dataset.layout === state.content.settings.layout;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    document.querySelectorAll('[data-section-order]').forEach((button) => {
      const active = button.dataset.sectionOrder === state.content.settings.section_order;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    const font = document.getElementById('fontFamily');
    const language = document.getElementById('cvLanguage');
    if (font) font.value = state.font_family;
    if (language) language.value = state.language;
    document.getElementById('customColor').value = state.accent_color;
  }

  document.getElementById('sectionNav')?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-section]');
    if (!button) return;
    goToSection(button.dataset.section);
  });

  document.querySelector('[data-step-prev]')?.addEventListener('click', () => {
    const index = SECTIONS.findIndex((section) => section.key === currentSection);
    if (index > 0) goToSection(SECTIONS[index - 1].key);
  });

  document.querySelector('[data-step-next]')?.addEventListener('click', () => {
    const index = SECTIONS.findIndex((section) => section.key === currentSection);
    if (index < SECTIONS.length - 1) goToSection(SECTIONS[index + 1].key);
  });

  document.getElementById('sectionEditor')?.addEventListener('input', handleEditorInput);
  document.getElementById('sectionEditor')?.addEventListener('change', (event) => {
    handleEditorInput(event);
    pushHistory();
  });

  function handleEditorInput(event) {
    const element = event.target;
    if (element.dataset.field) {
      setByPath(state.content, element.dataset.field, element.value);
      if (element.dataset.field === 'summary') {
        const counter = document.getElementById('summaryCount');
        if (counter) counter.textContent = element.value.length + ' / 3,000';
      }
      stateChanged();
      return;
    }

    if (element.dataset.bulletArray) {
      const entry = state.content[element.dataset.bulletArray]?.[Number(element.dataset.bulletEntry)];
      if (!entry) return;
      if (!Array.isArray(entry.bullets)) entry.bullets = [];
      // Blank rows are kept while editing so the input does not disappear
      // mid-typing; the renderer and the server both drop empty bullets.
      entry.bullets[Number(element.dataset.bulletIndex)] = element.value;
      stateChanged();
      return;
    }

    if (element.dataset.array) {
      const array = element.dataset.array;
      const index = Number(element.dataset.index);
      const key = element.dataset.key;
      const entry = state.content[array]?.[index];
      if (!entry) return;
      let value = element.type === 'checkbox' ? element.checked : element.value;
      if (key === 'bullets') {
        value = String(value).split(/\r?\n/).map((item) => item.trim()).filter(Boolean);
      }
      entry[key] = value;
      if (key === 'current' && value) {
        entry.end_date = '';
        // The end-date input becomes a disabled "Present" field, so the card
        // has to be redrawn rather than just updated in place.
        rerenderEditor();
      }
      stateChanged();
    }
  }

  document.getElementById('sectionEditor')?.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-toggle-entry]');
    if (toggle) {
      const key = toggle.dataset.toggleEntry;
      if (collapsedEntries.has(key)) {
        collapsedEntries.delete(key);
      } else {
        collapsedEntries.add(key);
      }
      rerenderEditor('[data-toggle-entry="' + key.replace(/"/g, '\\"') + '"]');
      return;
    }

    const addBullet = event.target.closest('[data-add-bullet]');
    if (addBullet) {
      const array = addBullet.dataset.bulletArray;
      const entryIndex = Number(addBullet.dataset.addBullet);
      const entry = state.content[array]?.[entryIndex];
      if (!entry) return;
      if (!Array.isArray(entry.bullets)) entry.bullets = [];
      if (entry.bullets.length >= 12) return;
      entry.bullets.push('');
      rerenderEditor('[data-bullet-array="' + array + '"][data-bullet-entry="' + entryIndex + '"][data-bullet-index="' + (entry.bullets.length - 1) + '"]');
      stateChanged({ history: true });
      return;
    }

    const removeBullet = event.target.closest('[data-remove-bullet]');
    if (removeBullet) {
      const array = removeBullet.dataset.bulletArray;
      const entryIndex = Number(removeBullet.dataset.bulletEntry);
      const entry = state.content[array]?.[entryIndex];
      if (!entry || !Array.isArray(entry.bullets)) return;
      entry.bullets.splice(Number(removeBullet.dataset.removeBullet), 1);
      rerenderEditor();
      stateChanged({ history: true });
      return;
    }

    const add = event.target.closest('[data-add-entry]');
    if (add) {
      const array = add.dataset.addEntry;
      state.content[array].push(defaultEntry(array));
      // Land the caret in the new entry instead of leaving the user to find it.
      rerenderEditor('.entry-card:last-of-type .entry-card-body input, .entry-card:last-of-type .entry-card-body textarea');
      stateChanged({ history: true });
      return;
    }

    const remove = event.target.closest('[data-remove-entry]');
    if (remove) {
      const array = remove.dataset.removeEntry;
      const index = Number(remove.dataset.entryIndex);
      const entry = state.content[array][index];
      collapsedEntries.delete(entryKey(array, entry, index));
      state.content[array].splice(index, 1);
      rerenderEditor();
      stateChanged({ history: true });
      return;
    }

    const move = event.target.closest('[data-move-entry]');
    if (move) {
      const array = move.dataset.moveEntry;
      const from = Number(move.dataset.entryIndex);
      const to = from + Number(move.dataset.direction);
      if (to < 0 || to >= state.content[array].length) return;
      const [entry] = state.content[array].splice(from, 1);
      state.content[array].splice(to, 0, entry);
      rerenderEditor('[data-move-entry="' + array + '"][data-entry-index="' + to + '"][data-direction="' + move.dataset.direction + '"]');
      stateChanged({ history: true });
      return;
    }

    const extra = event.target.closest('[data-extra-section]');
    if (extra) {
      currentExtra = extra.dataset.extraSection;
      renderExtras();
      return;
    }

    if (event.target.closest('[data-open-summary-assistant]')) {
      prefillSummaryModal();
      window.Lunetti.openModal('summaryAssistantModal');
      return;
    }

    const improve = event.target.closest('[data-improve-entry]');
    if (improve) {
      const entry = state.content.experience[Number(improve.dataset.improveEntry)];
      document.getElementById('bulletSource').value = entry?.bullets?.[0] || '';
      document.getElementById('bulletOutcome').value = '';
      document.getElementById('bulletAssistantModal').dataset.targetEntry = improve.dataset.improveEntry;
      window.Lunetti.openModal('bulletAssistantModal');
      return;
    }

    if (event.target.closest('[data-add-interest]')) addInterest();
    const removeInterest = event.target.closest('[data-remove-interest]');
    if (removeInterest) {
      state.content.interests.splice(Number(removeInterest.dataset.removeInterest), 1);
      renderExtras();
      stateChanged({ history: true });
    }
  });

  document.getElementById('sectionEditor')?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && event.target.id === 'interestInput') {
      event.preventDefault();
      addInterest();
    }
  });

  function addInterest() {
    const input = document.getElementById('interestInput');
    const value = input?.value.trim();
    if (!value) return;
    state.content.interests.push(value);
    renderExtras();
    stateChanged({ history: true });
  }

  document.getElementById('resumeTitle')?.addEventListener('input', (event) => {
    state.name = event.target.value.trimStart();
    stateChanged();
  });
  document.getElementById('resumeTitle')?.addEventListener('change', () => pushHistory());

  document.querySelectorAll('[data-editor-mode]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-editor-mode]').forEach((item) => {
        const active = item === button;
        item.classList.toggle('active', active);
        item.setAttribute('aria-selected', String(active));
      });
      const design = button.dataset.editorMode === 'design';
      document.getElementById('editorContentMode').hidden = design;
      document.getElementById('editorDesignMode').hidden = !design;
    });
  });

  document.querySelectorAll('[data-template-key]').forEach((button) => {
    button.addEventListener('click', () => {
      state.template_key = button.dataset.templateKey;
      const template = config.templates.find((item) => item.template_key === state.template_key);
      if (template) state.accent_color = template.color;
      // Each template has a shape it was designed for, so picking one adopts
      // that shape. The layout buttons below still override it afterwards.
      const design = TEMPLATES[state.template_key];
      if (design) {
        state.content.settings.layout = design.layout;
        state.content.settings.section_order = design.order;
      }
      updateDesignControls();
      stateChanged({ history: true, immediatePreview: true });
    });
  });

  document.querySelectorAll('[data-accent-color]').forEach((button) => {
    button.addEventListener('click', () => {
      state.accent_color = button.dataset.accentColor;
      updateDesignControls();
      stateChanged({ history: true, immediatePreview: true });
    });
  });

  document.getElementById('customColor')?.addEventListener('input', (event) => {
    state.accent_color = event.target.value;
    updateDesignControls();
    stateChanged();
  });
  document.getElementById('customColor')?.addEventListener('change', () => pushHistory());

  document.querySelectorAll('[data-resume-setting]').forEach((element) => {
    element.addEventListener('change', () => {
      state[element.dataset.resumeSetting] = element.value;
      stateChanged({ history: true, immediatePreview: true });
    });
  });

  document.querySelectorAll('[data-density]').forEach((button) => {
    button.addEventListener('click', () => {
      state.content.settings.density = button.dataset.density;
      updateDesignControls();
      stateChanged({ history: true, immediatePreview: true });
    });
  });

  document.querySelectorAll('[data-layout]').forEach((button) => {
    button.addEventListener('click', () => {
      state.content.settings.layout = button.dataset.layout;
      updateDesignControls();
      stateChanged({ history: true, immediatePreview: true });
    });
  });

  document.querySelectorAll('[data-section-order]').forEach((button) => {
    button.addEventListener('click', () => {
      state.content.settings.section_order = button.dataset.sectionOrder;
      updateDesignControls();
      stateChanged({ history: true, immediatePreview: true });
    });
  });

  function applyZoom() {
    zoom = Math.max(0.35, Math.min(1.2, zoom));
    const preview = document.getElementById('resumePreview');
    const scale = document.getElementById('previewScale');
    preview.style.transform = 'scale(' + zoom + ')';

    // A CSS transform scales what is painted but not the space the element
    // occupies, so the scroller keeps reserving the page at full size however
    // far it is zoomed out. Down the page that left a long empty gap to scroll
    // through, and across it left the document pushed off to the right with
    // room to scroll that holds nothing. Margins take back the difference.
    //
    // The origin is the top centre, so the vertical correction all falls below
    // the document while the horizontal one is split between its two sides.
    preview.style.margin = '0px';
    const documentHeight = preview.offsetHeight;
    const documentWidth = preview.offsetWidth;
    preview.style.marginBottom = Math.round(documentHeight * (zoom - 1)) + 'px';
    preview.style.marginInline = Math.round(documentWidth * (zoom - 1) / 2) + 'px';

    // With the box now the size it looks, the stylesheet's max-content width
    // and 100% floor size the scrolling area on their own.
    scale.style.width = '';
    // A floor of one page keeps an empty CV from collapsing; anything longer
    // is sized by the document itself through the margins above.
    scale.style.minHeight = Math.round(1123 * zoom + 95) + 'px';
    document.getElementById('zoomLabel').textContent = Math.round(zoom * 100) + '%';
  }

  function fitPage() {
    const scroll = document.getElementById('previewScroll');
    if (!scroll) return;
    zoom = Math.min((scroll.clientWidth - 45) / 794, (scroll.clientHeight - 50) / 1123, 0.92);
    applyZoom();
    scroll.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
  }

  document.getElementById('zoomOutButton')?.addEventListener('click', () => { zoom -= 0.08; applyZoom(); });
  document.getElementById('zoomInButton')?.addEventListener('click', () => { zoom += 0.08; applyZoom(); });
  document.getElementById('fitButton')?.addEventListener('click', fitPage);
  window.addEventListener('resize', () => setTimeout(applyZoom, 50));

  document.getElementById('undoButton')?.addEventListener('click', () => restoreHistory(historyIndex - 1));
  document.getElementById('redoButton')?.addEventListener('click', () => restoreHistory(historyIndex + 1));

  document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey)) return;
    if (event.key.toLowerCase() === 's') {
      event.preventDefault();
      saveResume(true).then((ok) => ok && window.Lunetti.toast('CV saved.'));
    }
    if (event.key.toLowerCase() === 'z') {
      event.preventDefault();
      restoreHistory(historyIndex + (event.shiftKey ? 1 : -1));
    }
  });

  document.getElementById('exportJsonButton')?.addEventListener('click', async () => {
    await saveResume();
    const backup = {
      schema_version: 1,
      exported_at: new Date().toISOString(),
      application: 'BrightCV',
      resume: buildSavePayload(),
    };
    const blob = new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = safeFilename(state.name) + '.json';
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 500);
    recordExport('json');
    window.Lunetti.toast('CV backup downloaded.');
  });

  // ------------------------------------------------------------------
  // Importing an existing CV
  //
  // The file (or pasted text) is read on the server, which returns the parsed
  // content plus a summary of what it found. Nothing touches the CV until the
  // writer looks at that summary and confirms, because no parser reads every
  // layout correctly and an import that silently overwrote a CV would be far
  // worse than one that asks first.
  // ------------------------------------------------------------------

  const IMPORT_LABELS = {
    experience: 'Roles',
    education: 'Qualifications',
    skills: 'Skills',
    projects: 'Projects',
    certifications: 'Certifications',
    languages: 'Languages',
    references: 'References',
    interests: 'Interests',
  };

  let importedContent = null;

  function resetImportDialog() {
    importedContent = null;
    const result = document.getElementById('importResult');
    const apply = document.getElementById('applyImportButton');
    const fileName = document.getElementById('importFileName');
    const skippedNote = document.getElementById('importSkipped');
    if (skippedNote) skippedNote.hidden = true;
    if (result) result.hidden = true;
    if (apply) apply.hidden = true;
    if (fileName) {
      fileName.hidden = true;
      fileName.textContent = '';
    }
  }

  function showImportSummary(data) {
    const detected = data.detected || {};
    const rows = [];

    ['full_name', 'headline', 'email', 'phone', 'location'].forEach((key) => {
      if (detected[key]) {
        rows.push([{ full_name: 'Name', headline: 'Job title', email: 'Email', phone: 'Phone', location: 'Location' }[key], detected[key]]);
      }
    });
    if (detected.summary_words) rows.push(['Summary', detected.summary_words + ' words']);
    Object.entries(IMPORT_LABELS).forEach(([key, label]) => {
      if (detected[key]) rows.push([label, String(detected[key])]);
    });

    const list = document.getElementById('importDetected');
    list.innerHTML = rows.length
      ? rows.map(([label, value]) => '<div><dt>' + h(label) + '</dt><dd>' + h(value) + '</dd></div>').join('')
      : '<div><dt>Nothing recognised</dt><dd>Try pasting the text instead.</dd></div>';

    // Sections BrightCV has no home for are named rather than forced into the
    // nearest category, so nothing is quietly dropped or misfiled.
    const skipped = Array.isArray(detected.skipped) ? detected.skipped : [];
    const note = document.getElementById('importSkipped');
    if (skipped.length) {
      note.textContent = 'Not imported, because BrightCV has no field for it: '
        + skipped.join(', ') + '. Copy anything you still want across by hand.';
      note.hidden = false;
    } else {
      note.hidden = true;
      note.textContent = '';
    }

    document.getElementById('importSource').textContent = data.source || '';
    document.getElementById('importTargetName').textContent = state.name;
    document.getElementById('importResult').hidden = false;
    document.getElementById('applyImportButton').hidden = rows.length === 0;
  }

  // The tool cluster collapses into a menu on narrow screens, so undo, redo,
  // import, and backup stay reachable on a phone instead of being hidden.
  const moreButton = document.getElementById('builderMoreButton');
  const builderTools = document.getElementById('builderTools');

  function setToolsOpen(open) {
    if (!builderTools || !moreButton) return;
    builderTools.classList.toggle('open', open);
    moreButton.setAttribute('aria-expanded', String(open));
  }

  moreButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    setToolsOpen(!builderTools.classList.contains('open'));
  });

  // Choosing an action closes the menu, as does tapping away or pressing Escape.
  builderTools?.addEventListener('click', (event) => {
    if (event.target.closest('button')) setToolsOpen(false);
  });

  document.addEventListener('click', (event) => {
    if (!builderTools?.classList.contains('open')) return;
    if (!event.target.closest('#builderTools, #builderMoreButton')) setToolsOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && builderTools?.classList.contains('open')) {
      setToolsOpen(false);
      moreButton.focus();
    }
  });

  document.getElementById('importButton')?.addEventListener('click', () => {
    resetImportDialog();
    window.Lunetti.openModal('importCvModal');
  });

  document.querySelectorAll('[data-import-tab]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-import-tab]').forEach((item) => {
        const active = item === button;
        item.classList.toggle('active', active);
        item.setAttribute('aria-selected', String(active));
      });
      document.querySelectorAll('[data-import-view]').forEach((view) => {
        view.classList.toggle('active', view.dataset.importView === button.dataset.importTab);
      });
      resetImportDialog();
    });
  });

  document.getElementById('importFile')?.addEventListener('change', (event) => {
    resetImportDialog();
    const file = event.target.files?.[0];
    const label = document.getElementById('importFileName');
    if (file && label) {
      label.textContent = file.name + ' · ' + Math.max(1, Math.round(file.size / 1024)) + ' KB';
      label.hidden = false;
    }
  });

  const importDrop = document.getElementById('importDrop');
  ['dragenter', 'dragover'].forEach((type) => importDrop?.addEventListener(type, (event) => {
    event.preventDefault();
    importDrop.classList.add('dragging');
  }));
  ['dragleave', 'drop'].forEach((type) => importDrop?.addEventListener(type, () => importDrop.classList.remove('dragging')));
  importDrop?.addEventListener('drop', (event) => {
    event.preventDefault();
    const file = event.dataTransfer?.files?.[0];
    if (!file) return;
    const input = document.getElementById('importFile');
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
    input.dispatchEvent(new Event('change'));
  });

  document.getElementById('readImportButton')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const usingText = document.querySelector('[data-import-view="text"]')?.classList.contains('active');
    const file = document.getElementById('importFile').files?.[0];
    const text = document.getElementById('importText').value.trim();

    if (usingText && text.length < 60) {
      window.Lunetti.toast('Paste more of your CV so the sections can be recognised.', 'error');
      return;
    }
    if (!usingText && !file) {
      window.Lunetti.toast('Choose a CV file first.', 'error');
      return;
    }

    const body = new FormData();
    if (usingText) {
      body.append('cv_text', text);
    } else {
      body.append('cv_file', file);
    }

    button.disabled = true;
    const original = button.innerHTML;
    button.innerHTML = '<span class="spinner"></span> Reading…';
    try {
      const response = await window.Lunetti.api(config.endpoints.import, { method: 'POST', body });
      importedContent = response.data.content;
      showImportSummary(response.data);
    } catch (error) {
      resetImportDialog();
      window.Lunetti.toast(error.message || 'That CV could not be read.', 'error');
    } finally {
      button.disabled = false;
      button.innerHTML = original;
    }
  });

  document.getElementById('applyImportButton')?.addEventListener('click', () => {
    if (!importedContent) return;

    // Design settings belong to this CV, not to the file being imported, so
    // only the written content is replaced.
    const settings = clone(state.content.settings);
    state.content = importedContent;
    normalizeState();
    state.content.settings = settings;

    document.getElementById('resumeTitle').value = state.name;
    renderEditor();
    renderPreview();
    updateProgress();
    updateDesignControls();
    stateChanged({ history: true });
    window.Lunetti.closeModal('importCvModal');
    resetImportDialog();
    window.Lunetti.toast('CV imported. Check each section before exporting.');
  });

  function safeFilename(value) {
    return String(value || 'cv').trim().replace(/[^\p{L}\p{N}_-]+/gu, '-').replace(/^-+|-+$/g, '').slice(0, 80) || 'cv';
  }

  document.getElementById('printButton')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    const original = button.innerHTML;
    button.innerHTML = '<span class="spinner"></span> Preparing…';
    const saved = await saveResume(true);
    if (saved) {
      window.open(config.endpoints.print, '_blank', 'noopener');
    }
    button.disabled = false;
    button.innerHTML = original;
  });

  async function recordExport(format) {
    try {
      await window.Lunetti.api(config.endpoints.export, {
        method: 'POST',
        body: JSON.stringify({ format }),
      });
    } catch {
      // Export itself should still succeed if analytics recording is unavailable.
    }
  }

  document.querySelectorAll('[data-assistant-tab]').forEach((button) => {
    button.addEventListener('click', () => switchAssistantTab(button.dataset.assistantTab));
  });

  function switchAssistantTab(tab) {
    document.querySelectorAll('[data-assistant-tab]').forEach((button) => {
      const active = button.dataset.assistantTab === tab;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', String(active));
    });
    document.querySelectorAll('[data-assistant-view]').forEach((view) => view.classList.toggle('active', view.dataset.assistantView === tab));
  }

  document.querySelectorAll('[data-smart-action]').forEach((button) => {
    button.addEventListener('click', async () => {
      const action = button.dataset.smartAction;
      if (action === 'summary') {
        prefillSummaryModal();
        window.Lunetti.openModal('summaryAssistantModal');
        return;
      }
      if (action === 'bullet') {
        document.getElementById('bulletAssistantModal').dataset.targetEntry = '0';
        document.getElementById('bulletSource').value = state.content.experience[0]?.bullets?.[0] || '';
        document.getElementById('bulletOutcome').value = '';
        window.Lunetti.openModal('bulletAssistantModal');
        return;
      }
      await requestAssistant(action, action === 'keywords' ? { job_description: state.job_description } : {});
    });
  });

  function prefillSummaryModal() {
    document.getElementById('summaryTargetRole').value = state.content.personal.headline || '';
    document.getElementById('summaryYears').value = state.content.experience.filter((item) => item.role || item.company).length || '';
    document.getElementById('summaryValue').value = '';
  }

  document.getElementById('generateSummaryButton')?.addEventListener('click', async (event) => {
    const input = {
      target_role: document.getElementById('summaryTargetRole').value,
      years: document.getElementById('summaryYears').value,
      value: document.getElementById('summaryValue').value,
    };
    window.Lunetti.closeModal('summaryAssistantModal');
    await requestAssistant('summary', input, (result) => {
      state.content.summary = result.text;
      if (currentSection === 'summary') renderEditor();
      stateChanged({ history: true });
    });
  });

  document.getElementById('generateBulletButton')?.addEventListener('click', async () => {
    const targetIndex = Number(document.getElementById('bulletAssistantModal').dataset.targetEntry || 0);
    const input = {
      text: document.getElementById('bulletSource').value,
      outcome: document.getElementById('bulletOutcome').value,
    };
    window.Lunetti.closeModal('bulletAssistantModal');
    await requestAssistant('bullet', input, (result) => {
      if (!state.content.experience[targetIndex]) {
        state.content.experience.push(defaultEntry('experience'));
      }
      state.content.experience[targetIndex].bullets = state.content.experience[targetIndex].bullets || [];
      if (state.content.experience[targetIndex].bullets.length) {
        state.content.experience[targetIndex].bullets[0] = result.text;
      } else {
        state.content.experience[targetIndex].bullets.push(result.text);
      }
      if (currentSection === 'experience') renderEditor();
      stateChanged({ history: true });
    });
  });

  async function requestAssistant(action, input, apply = null) {
    switchAssistantTab('assistant');
    const resultBox = document.getElementById('assistantResult');
    const content = document.getElementById('assistantResultContent');
    resultBox.hidden = false;
    content.innerHTML = '<p><span class="spinner inline-spinner"></span> Preparing a focused suggestion…</p>';
    document.getElementById('applySuggestion').hidden = true;

    try {
      if (!(await saveResume())) {
        throw new Error('Save or reconcile your CV before requesting a suggestion.');
      }
      const response = await window.Lunetti.api(config.endpoints.assistant, {
        method: 'POST',
        body: JSON.stringify({ action, input }),
      });
      const result = response.data.result;
      showAssistantResult(result, apply);
    } catch (error) {
      content.innerHTML = '<p>' + h(error.message) + '</p>';
      window.Lunetti.toast(error.message, 'error');
    }
  }

  function showAssistantResult(result, apply) {
    const content = document.getElementById('assistantResultContent');
    let html = result.text ? '<p>' + h(result.text) + '</p>' : '';
    if (Array.isArray(result.items) && result.items.length) {
      html += '<ul>' + result.items.map((item) => '<li>' + h(item) + '</li>').join('') + '</ul>';
    }
    if (Array.isArray(result.keywords) && result.keywords.length) {
      html += '<div class="keyword-cloud missing">' + result.keywords.map((item) => '<span>' + h(item) + '</span>').join('') + '</div>';
    }
    if (result.tip) html += '<p class="muted result-tip">' + h(result.tip) + '</p>';
    content.innerHTML = html || '<p>Review your content and keep only suggestions that accurately describe your experience.</p>';
    pendingSuggestion = apply ? () => apply(result) : null;
    document.getElementById('applySuggestion').hidden = !pendingSuggestion;
    document.getElementById('assistantResult').hidden = false;
  }

  document.getElementById('applySuggestion')?.addEventListener('click', () => {
    if (!pendingSuggestion) return;
    pendingSuggestion();
    pendingSuggestion = null;
    document.getElementById('applySuggestion').hidden = true;
    window.Lunetti.toast('Suggestion applied.');
  });

  document.getElementById('dismissSuggestion')?.addEventListener('click', () => {
    document.getElementById('assistantResult').hidden = true;
    pendingSuggestion = null;
  });

  document.getElementById('jobDescription')?.addEventListener('input', (event) => {
    state.job_description = event.target.value;
    updateJobCount();
    stateChanged();
  });
  document.getElementById('jobDescription')?.addEventListener('change', () => pushHistory());

  function updateJobCount() {
    document.getElementById('jobDescriptionCount').textContent = String(state.job_description || '').length.toLocaleString();
  }

  document.getElementById('runAtsButton')?.addEventListener('click', () => runAts(false));
  document.getElementById('matchJobButton')?.addEventListener('click', () => runAts(true));

  async function runAts(showKeywords) {
    const button = showKeywords ? document.getElementById('matchJobButton') : document.getElementById('runAtsButton');
    const original = button.textContent;
    button.disabled = true;
    button.innerHTML = '<span class="spinner"></span> Analyzing…';
    try {
      if (!(await saveResume())) {
        throw new Error('Save or reconcile your CV before running an ATS scan.');
      }
      const response = await window.Lunetti.api(config.endpoints.ats, {
        method: 'POST',
        body: JSON.stringify({ job_description: state.job_description }),
      });
      const report = response.data.report;
      state.ats_score = report.score;
      renderAtsReport(report);
      if (showKeywords) {
        switchAssistantTab('job');
        renderKeywords(report);
      } else {
        switchAssistantTab('ats');
      }
      window.Lunetti.toast('ATS analysis complete.');
    } catch (error) {
      window.Lunetti.toast(error.message, 'error');
    } finally {
      button.disabled = false;
      button.textContent = original;
    }
  }

  function renderAtsReport(report) {
    document.getElementById('atsScore').textContent = report.score;
    document.getElementById('atsRing').style.setProperty('--score', report.score);
    // The toolbar's own "Robot score" meter mirrors the same number, so a
    // fresh scan is visible without opening the review panel to see it.
    const toolbarScore = document.getElementById('toolbarAtsScore');
    const toolbarMeter = document.getElementById('toolbarAtsMeter');
    if (toolbarScore) toolbarScore.textContent = report.score;
    if (toolbarMeter) toolbarMeter.style.width = report.score + '%';
    document.getElementById('atsGrade').textContent = report.grade;
    document.getElementById('atsScoreMessage').textContent = report.score >= 85
      ? 'A strong CV—finish with role-specific tailoring.'
      : report.score >= 70
        ? 'Good foundation with a few useful improvements.'
        : 'Work through the recommendations below.';
    const maximums = {
      'Contact details': 15,
      'Professional summary': 15,
      'Experience impact': 25,
      'Relevant skills': 15,
      'Education': 10,
      'Readability': 10,
      'Job keywords': 10,
    };
    document.getElementById('atsCategories').innerHTML = Object.entries(report.categories).map(([name, value]) => {
      const max = maximums[name] || 10;
      return '<div class="ats-category"><span>' + h(name) + '</span><b>' + Number(value) + '/' + max +
        '</b><div><i style="width:' + Math.min(100, Math.round((Number(value) / max) * 100)) + '%"></i></div></div>';
    }).join('');
    document.getElementById('atsRecommendations').innerHTML = report.recommendations.map((item, index) =>
      '<div class="recommendation"><span>' + (index + 1) + '</span><p>' + h(item) + '</p></div>'
    ).join('') || '<div class="recommendation"><span>Done</span><p>Your CV has a strong foundation. Tailor it for each application.</p></div>';
  }

  function renderKeywords(report) {
    const result = document.getElementById('keywordResult');
    result.hidden = false;
    document.getElementById('matchedKeywords').innerHTML = (report.matched_keywords || []).map((word) => '<span>' + h(word) + '</span>').join('') || '<span>None yet</span>';
    document.getElementById('missingKeywords').innerHTML = (report.missing_keywords || []).map((word) => '<span>' + h(word) + '</span>').join('') || '<span>No major gaps found</span>';
  }

  document.addEventListener('focusin', (event) => {
    if (event.target.matches('input[type="text"], input[type="email"], input[type="tel"], textarea')) lastFocusedInput = event.target;
  });

  document.getElementById('voiceButton')?.addEventListener('click', () => {
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Recognition) {
      window.Lunetti.toast('Voice input is not supported by this browser. Chrome and Edge work best.', 'error');
      return;
    }
    if (!lastFocusedInput || !document.body.contains(lastFocusedInput)) {
      window.Lunetti.toast('Select a text field first, then start voice input.', 'error');
      return;
    }
    if (speechRecognition) {
      speechRecognition.stop();
      return;
    }

    speechRecognition = new Recognition();
    speechRecognition.lang = state.language === 'fr' ? 'fr-FR' : state.language === 'es' ? 'es-ES' : 'en-GH';
    speechRecognition.interimResults = false;
    speechRecognition.continuous = false;
    const voiceButton = document.getElementById('voiceButton');
    voiceButton.textContent = 'Listening…';
    voiceButton.classList.add('btn-danger');
    speechRecognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      const separator = lastFocusedInput.value && !/\s$/.test(lastFocusedInput.value) ? ' ' : '';
      lastFocusedInput.value += separator + transcript;
      lastFocusedInput.dispatchEvent(new Event('input', { bubbles: true }));
      lastFocusedInput.dispatchEvent(new Event('change', { bubbles: true }));
    };
    speechRecognition.onerror = () => window.Lunetti.toast('Voice input stopped before text was captured.', 'error');
    speechRecognition.onend = () => {
      speechRecognition = null;
      voiceButton.textContent = 'Start';
      voiceButton.classList.remove('btn-danger');
    };
    speechRecognition.start();
  });

  document.querySelectorAll('[data-toggle-panel]').forEach((button) => {
    button.addEventListener('click', () => openPanel(button.dataset.togglePanel));
  });
  document.querySelectorAll('[data-close-panel]').forEach((button) => {
    button.addEventListener('click', closePanels);
  });
  document.getElementById('mobilePanelOverlay')?.addEventListener('click', closePanels);

  function openPanel(name) {
    closePanels();
    document.getElementById(name + 'Panel')?.classList.add('open');
    document.getElementById('mobilePanelOverlay').classList.add('open');
    document.querySelectorAll('[data-toggle-panel]').forEach((button) => {
      const active = button.dataset.togglePanel === name;
      button.setAttribute('aria-expanded', String(active));
      if (button.closest('.builder-mobile-switch')) button.classList.toggle('active', active);
    });
    const previewButton = document.querySelector('.builder-mobile-switch [data-close-panel="preview"]');
    previewButton?.classList.remove('active');
    previewButton?.removeAttribute('aria-current');
  }

  function closePanels() {
    document.getElementById('editorPanel').classList.remove('open');
    document.getElementById('assistantPanel').classList.remove('open');
    document.getElementById('mobilePanelOverlay').classList.remove('open');
    document.querySelectorAll('[data-toggle-panel]').forEach((button) => {
      button.setAttribute('aria-expanded', 'false');
      if (button.closest('.builder-mobile-switch')) button.classList.remove('active');
    });
    const previewButton = document.querySelector('.builder-mobile-switch [data-close-panel="preview"]');
    previewButton?.classList.add('active');
    previewButton?.setAttribute('aria-current', 'page');
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.querySelector('.editor-panel.open, .assistant-panel.open')) closePanels();
  });

  window.addEventListener('online', () => {
    if (dirty) saveResume();
  });

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden' && dirty) {
      saveLocalDraft();
      saveResume();
    }
  });

  window.addEventListener('beforeunload', (event) => {
    if (!dirty) return;
    saveLocalDraft();
    event.preventDefault();
    event.returnValue = '';
  });
})();
