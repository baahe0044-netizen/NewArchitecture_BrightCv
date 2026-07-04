
    /* Sidebar detection */
    (function() {
      function measure() {
        const sb = document.querySelector('.sidebar') || document.querySelector('#sidebar');
        const px = sb ? (sb.getBoundingClientRect().width || 220) : 220;
        document.documentElement.style.setProperty('--sidebar', px + 'px');
      }
      measure();
      new ResizeObserver(measure).observe(document.body);
    })();

    /* ---------- zoom & fit ---------- */
    let userZoom = 1.15;
    const VIRTUAL_W = 840,
      VIRTUAL_H = 1188;
    const wrap = document.getElementById('frameWrap');
    const plane = document.getElementById('plane');
    const frame = document.getElementById('previewFrame');

    function fit() {
      if (!wrap || !plane) return;
      const w = wrap.clientWidth,
        h = wrap.clientHeight;
      const base = Math.min(w / VIRTUAL_W, h / VIRTUAL_H);
      const s = Math.max(0.5, Math.min(2, base * userZoom));
      plane.style.transform = `scale(${s})`;
      plane.style.left = '0px';
      plane.style.top = '0px';
      document.getElementById('zoomVal').textContent = Math.round((s / base) * 100) + '%';
    }

    new ResizeObserver(fit).observe(wrap);
    window.addEventListener('load', fit);
    window.addEventListener('resize', fit);

    document.getElementById('zoomIn').onclick = () => {
      userZoom = Math.min(2, userZoom + 0.05);
      fit();
    };
    document.getElementById('zoomOut').onclick = () => {
      userZoom = Math.max(0.5, userZoom - 0.05);
      fit();
    };
    document.getElementById('openTab')?.addEventListener('click', () => {
      if (frame?.src) window.open(frame.src, '_blank', 'noopener');
    });

    /* ---------- stepper ---------- */
    const stepsEl = document.getElementById('steps');
    const formTitle = document.getElementById('formTitle');
    const STEPS = ['header', 'contact', 'summary', 'experience', 'education', 'skills', 'additional'];
    let currentStepIdx = 0;

    const STEP_TITLES = {
      header: '① Header & Name',
      contact: '② Contact Information',
      summary: '③ Summary / Profile',
      experience: '④ Work Experience',
      education: '⑤ Education',
      skills: '⑥ Skills',
      additional: '⑦ Additional Info'
    };

    function setStep(step) {
      currentStepIdx = STEPS.indexOf(step);
      if (currentStepIdx < 0) currentStepIdx = 0;

      document.querySelectorAll('.step').forEach(s => s.classList.toggle('active', s.dataset.step === step));
      document.querySelectorAll('.panel').forEach(p => p.classList.toggle('active', p.dataset.panel === step));
      formTitle.textContent = STEP_TITLES[step] || step;

      // Update footer indicators
      document.getElementById('stepIndicator').textContent = `Step ${currentStepIdx+1} of ${STEPS.length}`;
      document.getElementById('prevStep').disabled = currentStepIdx === 0;
      document.getElementById('nextStep').textContent = currentStepIdx === STEPS.length - 1 ? '✓ Done' : 'Next →';

      frame?.contentWindow?.postMessage({
        type: 'focus-section',
        section: step
      }, '*');
    }

    stepsEl.addEventListener('click', e => {
      const s = e.target.closest('.step');
      if (!s) return;
      setStep(s.dataset.step);
    });

    document.getElementById('prevStep').addEventListener('click', () => {
      if (currentStepIdx > 0) setStep(STEPS[currentStepIdx - 1]);
    });
    document.getElementById('nextStep').addEventListener('click', () => {
      if (currentStepIdx < STEPS.length - 1) setStep(STEPS[currentStepIdx + 1]);
    });

    /* ---------- state + repeaters ---------- */
    const form = document.getElementById('resumeForm');
    const storageKey =
    'resume_' +
    window.resumeConfig.resumeId +
    '_tpl_' +
    window.resumeConfig.templateId;
    let state = {
      experience: [],
      education: [],
      skills: [],
      languages: []
    };

    /* HTML-escape helper for rendering values into innerHTML */
    function esc(v) {
      return String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    try {
      const saved = localStorage.getItem(storageKey);
      if (saved) state = Object.assign(state, JSON.parse(saved) || {});
    } catch (e) {}

    /* ---------- Save to Database ---------- */
    const saveStatusEl = document.getElementById('saveStatus');

    function showStatus(msg, type = 'saving') {
      saveStatusEl.className = type;
      saveStatusEl.textContent = msg;
      saveStatusEl.style.display = 'block';
      if (type !== 'saving') setTimeout(() => {
        saveStatusEl.style.display = 'none';
      }, 3000);
    }

    async function saveResumeToDb() {
      showStatus('⏳ Saving…', 'saving');
      const payload = collect();

      // Build the ajax_resume.php compatible payload
      const body = {
        resume_id: window.resumeConfig.resumeId,
        title: payload.name || payload.headline || 'My Resume',
        personal: {
          full_name: payload.name || '',
          email: payload.email || '',
          phone: payload.phone || '',
          location: payload.location || '',
          linkedin: payload.linkedin || '',
          summary: payload.summary || ''
        },
        experience: (payload.experience || []).map(e => ({
          job_title: e.title || e.job_title || '',
          company: e.company || '',
          start_date: e.start_date || '',
          end_date: e.end_date || '',
          description: e.summary || e.description || (Array.isArray(e.bullets) ? e.bullets.join('\n') : '')
        })),
        education: (payload.education || []).map(e => ({
          degree: e.degree || '',
          field_of_study: e.field_of_study || '',
          institution: e.school || e.institution || '',
          graduation_year: e.graduation_year || e.years || ''
        })),
        skills: Array.isArray(payload.skills) ? payload.skills.join(', ') : (payload.skills || '')
      };

      try {
        const res = await fetch('ajax_resume.php?action=save_resume_full', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
          showStatus('✅ Saved successfully!', 'success');
        } else {
          showStatus('❌ Save failed: ' + (data.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        showStatus('❌ Network error: ' + err.message, 'error');
      }
    }

    document.getElementById('saveResume').addEventListener('click', saveResumeToDb);

    const expList = document.getElementById('expList'),
      eduList = document.getElementById('eduList');

    function renderExp() {
      expList.innerHTML = '';
      if ((state.experience || []).length === 0) {
        expList.innerHTML = '<div style="text-align:center;padding:24px;color:var(--muted);font-size:13px;background:#f9fafb;border-radius:8px;border:1px dashed var(--line)">No work experience added yet.<br>Click <strong>+ Add Work Experience</strong> below to start.</div>';
        return;
      }
      (state.experience || []).forEach((it, i) => {
        const row = document.createElement('div');
        row.className = 'repeater-item';
        row.innerHTML = `
      <div class="repeater-header">
        <span class="repeater-number">💼 Position #${i+1}</span>
        <button type="button" class="btn btn-danger" data-act="del" style="padding:4px 10px;font-size:12px">✕ Remove</button>
      </div>
      <div class="form-grid">
        <div class="form-group col-span-2">
          <label>Job Title / Position <span class="field-tag template-tag">shown in template</span></label>
          <input data-k="title" value="${esc(it.title)}" placeholder="e.g. Senior Marketing Manager">
        </div>
        <div class="form-group">
          <label>Company Name <span class="field-tag template-tag">shown in template</span></label>
          <input data-k="company" value="${esc(it.company)}" placeholder="e.g. Acme Corp">
        </div>
        <div class="form-group">
          <label>City / Location</label>
          <input data-k="city" value="${esc(it.city)}" placeholder="e.g. Accra">
        </div>
        <div class="form-group">
          <label>Start Date <span class="field-tag template-tag">shown in template</span></label>
          <input data-k="start_date" value="${esc(it.start_date)}" placeholder="e.g. Jan 2020">
        </div>
        <div class="form-group">
          <label>End Date <span class="field-tag template-tag">shown in template</span></label>
          <input data-k="end_date" value="${esc(it.end_date)}" placeholder="e.g. Present">
        </div>
        <div class="form-group col-span-2">
          <label>Date Range Override <span class="hint-inline">(leave blank to auto-combine Start + End)</span></label>
          <input data-k="dates" value="${esc(it.dates)}" placeholder="e.g. 2020 – Present">
        </div>
        <div class="form-group col-span-2">
          <label>Job Description / Summary <span class="field-tag template-tag">shown in template</span></label>
          <textarea rows="3" data-k="summary" placeholder="Briefly describe your role and key responsibilities...">${esc(it.summary)}</textarea>
        </div>
        <div class="form-group col-span-2">
          <label>Key Responsibilities <span class="hint-inline">(one per line — shown as bullet points)</span></label>
          <textarea rows="4" data-k="bullets" placeholder="Led a team of 5 marketers&#10;Grew social following by 40%&#10;Managed $50k monthly ad budget">${Array.isArray(it.bullets)?it.bullets.join('\n'):''}</textarea>
        </div>
      </div>`;
        row.addEventListener('input', ev => {
          const k = ev.target.getAttribute('data-k');
          if (!k) return;
          state.experience[i] = Object.assign({}, state.experience[i] || {}, {
            [k]: k === 'bullets' ? (ev.target.value.split('\n').map(s => s.trim()).filter(Boolean)) : ev.target.value
          });
          schedulePatch();
        });
        row.querySelector('[data-act="del"]').onclick = () => {
          state.experience.splice(i, 1);
          renderExp();
          schedulePatch();
        };
        expList.appendChild(row);
      });
    }

    function renderEdu() {
      eduList.innerHTML = '';
      if ((state.education || []).length === 0) {
        eduList.innerHTML = '<div style="text-align:center;padding:24px;color:var(--muted);font-size:13px;background:#f9fafb;border-radius:8px;border:1px dashed var(--line)">No education added yet.<br>Click <strong>+ Add Education</strong> below to start.</div>';
        return;
      }
      (state.education || []).forEach((it, i) => {
        const row = document.createElement('div');
        row.className = 'repeater-item';
        row.innerHTML = `
      <div class="repeater-header">
        <span class="repeater-number">🎓 Education #${i+1}</span>
        <button type="button" class="btn btn-danger" data-act="del" style="padding:4px 10px;font-size:12px">✕ Remove</button>
      </div>
      <div class="form-grid">
        <div class="form-group col-span-2">
          <label>Degree / Certificate <span class="field-tag template-tag">shown in template</span></label>
          <input data-k="degree" value="${esc(it.degree)}" placeholder="e.g. BSc Marketing">
        </div>
        <div class="form-group col-span-2">
          <label>Field of Study</label>
          <input data-k="field_of_study" value="${esc(it.field_of_study)}" placeholder="e.g. Marketing & Communications">
        </div>
        <div class="form-group col-span-2">
          <label>Institution / School <span class="field-tag template-tag">shown in template</span></label>
          <input data-k="school" value="${esc(it.school)}" placeholder="e.g. University of Ghana">
        </div>
        <div class="form-group">
          <label>Graduation Year <span class="field-tag template-tag">shown in template</span></label>
          <input data-k="graduation_year" value="${esc(it.graduation_year)}" placeholder="e.g. 2019">
        </div>
        <div class="form-group">
          <label>Year Range <span class="hint-inline">(override)</span></label>
          <input data-k="years" value="${esc(it.years)}" placeholder="e.g. 2015 – 2019">
        </div>
        <div class="form-group col-span-2">
          <label>Notes / Achievements</label>
          <textarea rows="2" data-k="notes" placeholder="e.g. First Class Honours, Dean's List...">${esc(it.notes)}</textarea>
        </div>
      </div>`;
        row.addEventListener('input', ev => {
          const k = ev.target.getAttribute('data-k');
          if (!k) return;
          state.education[i] = Object.assign({}, state.education[i] || {}, {
            [k]: ev.target.value
          });
          schedulePatch();
        });
        row.querySelector('[data-act="del"]').onclick = () => {
          state.education.splice(i, 1);
          renderEdu();
          schedulePatch();
        };
        eduList.appendChild(row);
      });
    }

    document.getElementById('addExp').onclick = () => {
      (state.experience = state.experience || []).push({
        title: '',
        company: '',
        city: '',
        start_date: '',
        end_date: '',
        dates: '',
        summary: '',
        bullets: []
      });
      renderExp();
      schedulePatch();
    };

    document.getElementById('addEdu').onclick = () => {
      (state.education = state.education || []).push({
        degree: '',
        field_of_study: '',
        school: '',
        graduation_year: '',
        years: '',
        notes: ''
      });
      renderEdu();
      schedulePatch();
    };

    /* hydrate inputs */
    (function init() {
      const el = form.elements;
      const fields = [
        'first_name', 'last_name', 'name', 'headline', 'status', 'photo',
        'phone', 'phone1', 'email', 'website', 'linkedin',
        'city', 'country', 'address_line1', 'address_line2', 'location',
        'summary', 'resume', 'profile',
        'mentor_name', 'mentor_title', 'mentor_contact', 'mentor',
        'interests', 'links', 'certifications'
      ];
      fields.forEach(k => {
        if (el[k] && state[k] != null) el[k].value = state[k];
      });
      if (Array.isArray(state.skills)) el['skills_csv'].value = state.skills.join(', ');
      if (Array.isArray(state.interests)) el['interests_csv'].value = state.interests.join(', ');
      if (Array.isArray(state.languages)) el['languages_csv'].value = state.languages.join(', ');
      renderExp();
      renderEdu();
      setStep('header'); // init nav state
    })();

    /* collect + alias (for all templates) */
    function collect() {
      const f = new FormData(form);
      const o = Object.fromEntries(f.entries());

      // Process skills
      o.skills = (o.skills_csv || '').split(',').map(s => s.trim()).filter(Boolean);

      // Process interests
      const interestsCsv = (o.interests_csv || '').split(',').map(s => s.trim()).filter(Boolean);
      if (interestsCsv.length > 0) {
        o.interests = interestsCsv;
      } else if (o.interests) {
        // Keep existing interests format
      } else {
        o.interests = [];
      }

      // Process languages
      o.languages = (o.languages_csv || '').split(',').map(s => s.trim()).filter(Boolean);

      // Name generation
      const first = o.first_name || '',
        last = o.last_name || '';
      if (!o.name) {
        o.name = (first || last) ? (first + ' ' + last).trim() : '';
      }

      // Location generation
      if (!o.location) {
        o.location = [o.city, o.country].filter(Boolean).join(', ');
      }

      // Aliases for template compatibility
      o.phone1 = o.phone1 || o.phone || '';
      o.profile = o.profile || o.summary || o.resume || '';
      o.resume = o.resume || o.summary || '';

      // Mentor formatting
      if (o.mentor_name || o.mentor_title || o.mentor_contact) {
        const parts = [o.mentor_name, o.mentor_title, o.mentor_contact].filter(Boolean);
        if (!o.mentor) o.mentor = parts.join(' - ');
      }

      // Interests formatting (for templates that expect string)
      if (Array.isArray(o.interests) && o.interests.length > 0) {
        o.interests_text = o.interests.join(' • ');
      }

      // Add experience and education arrays
      o.experience = state.experience || [];
      o.education = state.education || [];

      // Format experience dates for templates
      o.experience.forEach(exp => {
        if (!exp.dates && (exp.start_date || exp.end_date)) {
          exp.dates = [exp.start_date, exp.end_date].filter(Boolean).join(' - ');
        }
        // Add aliases
        exp.role = exp.role || exp.title || '';
        exp.job_title = exp.job_title || exp.title || '';
        exp.description = exp.description || exp.summary || '';
      });

      // Format education years
      o.education.forEach(edu => {
        if (!edu.years && edu.graduation_year) {
          edu.years = edu.graduation_year;
        }
        // Add aliases
        edu.institution = edu.institution || edu.school || '';
      });

      return o;
    }

    /* postMessage patch (debounced) */
    function postPatch() {
      const payload = collect();
      try {
        localStorage.setItem(storageKey, JSON.stringify(payload));
      } catch (e) {}
      frame?.contentWindow?.postMessage({
        type: 'resume-patch',
        payload
      }, '*');
    }

    let debounce;

    function schedulePatch() {
      clearTimeout(debounce);
      debounce = setTimeout(postPatch, 120);
    }

    form.addEventListener('input', schedulePatch);

    /* import/export */
    document.getElementById('exportJson').onclick = () => {
      const b = new Blob([JSON.stringify(collect(), null, 2)], {
        type: 'application/json'
      });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(b);
      a.download =
    'resume_' +
    window.resumeConfig.resumeId +
    '.json';
      a.click();
    };

    document.getElementById('importJson').addEventListener('change', async e => {
      const f = e.target.files[0];
      if (!f) return;
      const txt = await f.text();
      try {
        const obj = JSON.parse(txt) || {};
        Object.assign(state, obj);
        const el = form.elements;
        const fields = [
          'first_name', 'last_name', 'name', 'headline', 'status', 'photo',
          'phone', 'phone1', 'email', 'website', 'linkedin',
          'city', 'country', 'address_line1', 'address_line2', 'location',
          'summary', 'resume', 'profile',
          'mentor_name', 'mentor_title', 'mentor_contact', 'mentor',
          'interests', 'links', 'certifications'
        ];
        fields.forEach(k => {
          if (el[k] && obj[k] != null) el[k].value = obj[k];
        });
        if (Array.isArray(obj.skills)) el['skills_csv'].value = obj.skills.join(', ');
        if (Array.isArray(obj.interests)) el['interests_csv'].value = obj.interests.join(', ');
        if (Array.isArray(obj.languages)) el['languages_csv'].value = obj.languages.join(', ');
        renderExp();
        renderEdu();
        postPatch();
      } catch (err) {
        alert('Invalid JSON file');
      }
      e.target.value = '';
    });

    frame?.addEventListener('load', () => {
      fit();
      postPatch();
      frame.contentWindow?.postMessage({
        type: 'focus-section',
        section: 'header'
      }, '*');
    });
  