<?php

/*************************************************
 * builder.php — Enhanced Resume Builder with ALL Template Fields
 * - Complete field coverage for all template variations
 * - Auto-detects sidebar width to prevent overlap
 * - Compact vertical stepper + form + preview layout
 *************************************************/
$dbname = 'lunettistar_db';
$username = 'root';
$password = '';
try {
  $pdo = new PDO("mysql:dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
  http_response_code(500);
  die("DB connection failed: " . $e->getMessage());
}

$resumeId   = isset($_GET['resume_id']) ? (int)$_GET['resume_id'] : 1;
$templateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;

if ($templateId <= 0) {
  $q = $pdo->prepare("SELECT template_id FROM resume_selected_template WHERE resume_id=? LIMIT 1");
  $q->execute([$resumeId]);
  $templateId = (int)($q->fetchColumn() ?: 0);
}
$templateName = '';
if ($templateId > 0) {
  $q = $pdo->prepare("SELECT name FROM resume_templates WHERE id=? AND is_active=1 LIMIT 1");
  $q->execute([$templateId]);
  $templateName = (string)($q->fetchColumn() ?: '');
}
$previewUrl = $templateId > 0 ? "Templates/template_preview.php?template_id=" . urlencode($templateId) : "";
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Resume Builder — Live</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f6f7fb;
      --ink: #111827;
      --muted: #6b7280;
      --line: #e5e7eb;
      --primary: #4f46e5;
      --ok: #10b981;
      --navy: #0b1240;
      --sidebar: 220px;
      --left-pad: 16px;
      --stepper-w: 140px;
      --form-w: 420px;
    }

    html,
    body {
      height: 100%;
      margin: 0;
      color: var(--ink);
      font: 14px/1.6 "Inter", system-ui, Segoe UI, Roboto, Arial;
    }

    * {
      box-sizing: border-box
    }

    .page,
    .container,
    .content,
    .main-content {
      max-width: none !important;
      width: auto !important
    }

    /* TOP HEADER */
    .top-header {
      background: var(--navy);
      color: white;
      padding: 16px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-radius: 19px;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .app-title {
      font-size: 20px;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .live-badge {
      background: #10b981;
      color: white;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .breadcrumb {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: rgba(255, 255, 255, 0.7);
    }

    .breadcrumb-separator {
      opacity: 0.5;
    }

    .breadcrumb-current {
      color: #a78bfa;
      font-weight: 600;
    }

    .builder-root {
      padding: var(--left-pad);
      padding-right: var(--left-pad);
      min-height: calc(100vh - 72px);
    }

    .grid {
      display: grid;
      grid-template-columns: var(--stepper-w) var(--form-w) 1fr;
      gap: 16px;
      align-items: start;
    }

    @media(max-width:1400px) {
      :root {
        --form-w: 380px
      }
    }

    @media(max-width:1200px) {
      :root {
        --stepper-w: 100px;
        --form-w: 340px
      }
    }

    @media(max-width:1100px) {
      .grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .stepper {
        flex-direction: row !important;
        overflow-x: auto;
      }
    }

    /* Stepper column */
    .stepper {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      padding-top: 10px;
      gap: 8px;
      position: sticky;
      top: 16px;
      align-self: start;
    }

    .step {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      font-weight: 500;
      color: white;
      font-size: 13px;
      transition: all .2s ease;
      padding: 8px 10px;
      border-radius: 8px;
      width: 100%;
      background: #0b1240;
    }

    .step:hover {
      background: #1a2459
    }

    .step.active {
      color: var(--primary);
      font-weight: 700;
    }

    .step .dot {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      border: 2px solid var(--line);
      font-size: 12px;
      font-weight: 700;
      color: var(--muted);
      flex-shrink: 0;
    }

    .step.active .dot {
      border-color: var(--primary);
      color: var(--primary);
      background: #fff
    }

    .step.done .dot {
      border-color: var(--ok);
      color: #fff;
      background: var(--ok)
    }

    /* Cards */
    .card {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
    }

    .card .hd {
      padding: 14px 18px;
      border-bottom: 1px solid var(--line);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
    }

    .card .bd {
      padding: 18px
    }

    .btn {
      padding: 8px 14px;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: #fff;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      transition: all .2s ease;
      color: var(--ink);
      white-space: nowrap;
    }

    .btn:hover {
      background: var(--bg);
      border-color: var(--muted)
    }

    .btn-danger {
      background: #dc2626;
      color: #fff;
      border-color: #dc2626
    }

    .btn-danger:hover {
      background: #b91c1c
    }

    /* Form column */
    .form {
      max-height: calc(100vh - 80px);
      display: flex;
      flex-direction: column;
    }

    .form .bd {
      flex: 1;
      overflow-y: auto;
      scrollbar-width: thin;
    }

    .form .bd::-webkit-scrollbar {
      width: 6px
    }

    .form .bd::-webkit-scrollbar-thumb {
      background: var(--line);
      border-radius: 3px
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .form-group label {
      display: block;
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: .05em;
      margin-bottom: 7px;
      font-weight: 600;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 11px 13px;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: #fff;
      font-size: 14px;
      outline: 0;
      transition: all .2s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, .1);
    }

    .col-span-2 {
      grid-column: 1/-1
    }

    .panel {
      display: none
    }

    .panel.active {
      display: block
    }

    .actions {
      display: flex;
      justify-content: space-between;
      margin-top: 12px
    }

    .repeater-item {
      background: #f9fafb;
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 12px;
    }

    .repeater-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .repeater-number {
      font-weight: 700;
      color: var(--primary);
    }

    /* Preview column */
    .viewer {
      max-height: calc(100vh - 80px);
      display: flex;
      flex-direction: column;
    }

    .viewer .bd {
      padding: 18px;
      flex: 1;
      overflow: hidden;
    }

    .viewer-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .badge {
      background: #e0e7ff;
      color: #3730a3;
      border-radius: 999px;
      padding: 4px 11px;
      font-size: 12px;
      font-weight: 700;
    }

    .device {
      background: #9aa8b8;
      border-radius: 12px;
      border: 1px solid #7f90a3;
      padding: 20px;
      height: 100%;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }

    .frame-wrap {
      position: relative;
      background: white;
      border-radius: 8px;
      border: none;
      height: calc(100vh - 200px);
      overflow: auto;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    scrollbar-width:thin;
    }

    .frame-wrap::-webkit-scrollbar {
      width: 8px;
      height: 8px
    }

    .frame-wrap::-webkit-scrollbar-thumb {
      background: #7f90a3;
      border-radius: 4px
    }

    .paper-plane {
      position: absolute;
      left: 0;
      top: 0;
      width: 840px;
      height: 1188px;
      transform-origin: top left;
    }

    .paper-plane iframe {
      width: 100%;
      height: 100%;
      border: 0;
      background: #fff;
      display: block;
    }

    .zoom-val {
      min-width: 55px;
      text-align: center;
      font-weight: 700;
      font-size: 13px;
    }

    /* Section hint banners */
    .section-hint {
      background: linear-gradient(135deg, #f0f4ff 0%, #e8f5e9 100%);
      border: 1px solid #c7d2fe;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 13px;
      font-weight: 600;
      color: #3730a3;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* Field tags */
    .field-tag {
      display: inline-block;
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 999px;
      vertical-align: middle;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-left: 4px;
    }

    .template-tag {
      background: #fef3c7;
      color: #92400e;
      border: 1px solid #fcd34d;
    }

    .hint-inline {
      font-size: 11px;
      font-weight: 400;
      color: var(--muted);
      font-style: italic;
    }

    .field-note {
      font-size: 11px;
      color: #6b7280;
      margin-top: 5px;
      line-height: 1.4;
    }

    /* Sub-section titles inside panels */
    .sub-section-title {
      font-size: 13px;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid var(--line);
    }

    /* Save button */
    .btn-save {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }

    .btn-save:hover {
      background: #4338ca;
      border-color: #4338ca;
    }

    .btn-add {
      background: #f0f4ff;
      color: var(--primary);
      border-color: #c7d2fe;
      font-weight: 700;
      width: 100%;
      text-align: center;
      padding: 10px;
    }

    .btn-add:hover {
      background: #e0e7ff;
    }

    .btn-primary-outline {
      background: #f0f4ff;
      color: var(--primary);
      border-color: #c7d2fe;
      font-weight: 700;
    }

    .btn-primary-outline:hover {
      background: #e0e7ff;
    }

    /* Form footer nav */
    .form-footer {
      padding: 12px 18px;
      border-top: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      background: #fafafa;
      border-radius: 0 0 12px 12px;
    }

    /* Save status bar */
    #saveStatus.success {
      background: #d1fae5;
      color: #065f46;
    }

    #saveStatus.error {
      background: #fee2e2;
      color: #991b1b;
    }

    #saveStatus.saving {
      background: #e0e7ff;
      color: #3730a3;
    }

    /* Repeater polish */
    .repeater-item {
      position: relative;
    }

    .repeater-item+.repeater-item {
      margin-top: 16px;
    }
  </style>
</head>

<body>

  <!-- TOP HEADER -->
  <div class="top-header">
    <div class="header-left">
      <div class="app-title">Resume Builder</div>
      <span class="live-badge">● LIVE</span>
    </div>
    <div class="breadcrumb">
      <span>Resume</span>
      <span class="breadcrumb-separator">›</span>
      <span>Builder</span>
      <?php if ($templateName): ?>
        <span class="breadcrumb-separator">›</span>
        <span>Template: <span class="breadcrumb-current"><?= htmlspecialchars($templateName) ?></span></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="builder-root" id="builderRoot">

    <div class="grid">
      <!-- Stepper -->
      <div class="stepper" id="steps">
        <div class="step active" data-step="header">
          <div class="dot">1</div>
          <div>Header</div>
        </div>
        <div class="step" data-step="contact">
          <div class="dot">2</div>
          <div>Contact</div>
        </div>
        <div class="step" data-step="summary">
          <div class="dot">3</div>
          <div>Summary</div>
        </div>
        <div class="step" data-step="experience">
          <div class="dot">4</div>
          <div>Experience</div>
        </div>
        <div class="step" data-step="education">
          <div class="dot">5</div>
          <div>Education</div>
        </div>
        <div class="step" data-step="skills">
          <div class="dot">6</div>
          <div>Skills</div>
        </div>
        <div class="step" data-step="additional">
          <div class="dot">7</div>
          <div>Additional</div>
        </div>
      </div>

      <!-- Form -->
      <div class="card form">
        <div class="hd">
          <strong id="formTitle">Header</strong>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-save" id="saveResume"> Save</button>
            <button class="btn" id="exportJson">Export JSON</button>
            <label class="btn">Import JSON<input id="importJson" type="file" accept="application/json" hidden></label>
          </div>
        </div>
        <!-- Save status bar -->
        <div id="saveStatus" style="display:none;padding:8px 18px;font-size:12px;font-weight:600;border-bottom:1px solid var(--line)"></div>
        <div class="bd">
          <form id="resumeForm">

            <!-- ① HEADER -->
            <div class="panel active" data-panel="header">
              <div class="section-hint"> Fills the top of your resume: name, title & photo</div>
              <div class="form-grid">
                <div class="form-group">
                  <label>First Name</label>
                  <input name="first_name" placeholder="e.g. Mark">
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input name="last_name" placeholder="e.g. James">
                </div>
                <div class="form-group col-span-2">
                  <label>Full Name <span class="hint-inline">(leave blank to auto-combine above)</span></label>
                  <input name="name" placeholder="e.g. Mark James">
                </div>
                <div class="form-group col-span-2">
                  <label>Job Title / Headline <span class="field-tag template-tag">shown in template</span></label>
                  <input name="headline" placeholder="e.g. Marketing Professional">
                </div>
                <div class="form-group col-span-2">
                  <label>Current Role / Status</label>
                  <input name="status" placeholder="e.g. Senior Marketing Manager">
                </div>
                <div class="form-group col-span-2">
                  <label>Profile Photo URL <span class="field-tag template-tag">shown in template</span></label>
                  <input name="photo" placeholder="https://example.com/photo.jpg">
                  <div class="field-note">Paste a direct image URL. Use a square headshot for best results.</div>
                </div>
              </div>
            </div>

            <!-- ② CONTACT -->
            <div class="panel" data-panel="contact">
              <div class="section-hint"> Contact details shown below your photo in the template</div>
              <div class="form-grid">
                <div class="form-group">
                  <label>Phone <span class="field-tag template-tag">shown in template</span></label>
                  <input name="phone" placeholder="+233 555 123 456">
                </div>
                <div class="form-group">
                  <label>Secondary Phone</label>
                  <input name="phone1" placeholder="+233 555 789 012">
                </div>
                <div class="form-group col-span-2">
                  <label>Email <span class="field-tag template-tag">shown in template</span></label>
                  <input name="email" type="email" placeholder="you@email.com">
                </div>
                <div class="form-group col-span-2">
                  <label>Website / Portfolio <span class="field-tag template-tag">shown in template</span></label>
                  <input name="website" placeholder="www.yourwebsite.com">
                </div>
                <div class="form-group col-span-2">
                  <label>LinkedIn</label>
                  <input name="linkedin" placeholder="linkedin.com/in/yourname">
                </div>
                <div class="form-group">
                  <label>City <span class="field-tag template-tag">shown in template</span></label>
                  <input name="city" placeholder="Accra">
                </div>
                <div class="form-group">
                  <label>Country</label>
                  <input name="country" placeholder="Ghana">
                </div>
                <div class="form-group col-span-2">
                  <label>Location Override <span class="hint-inline">(leave blank to auto-generate from City + Country)</span></label>
                  <input name="location" placeholder="e.g. Accra, Ghana">
                </div>
                <div class="form-group col-span-2">
                  <label>Address Line 1</label>
                  <input name="address_line1" placeholder="Street, District">
                </div>
                <div class="form-group col-span-2">
                  <label>Address Line 2</label>
                  <input name="address_line2" placeholder="City, Country">
                </div>
              </div>
            </div>

            <!-- ③ SUMMARY -->
            <div class="panel" data-panel="summary">
              <div class="section-hint"> The "Summary" section shown prominently in your template</div>
              <div class="form-group">
                <label>Professional Summary <span class="field-tag template-tag">shown in template</span></label>
                <textarea name="summary" rows="7" placeholder="Write a brief overview of your professional background, key achievements, and what you bring to the table..."></textarea>
                <div class="field-note">This appears in the large Summary box in your template. Keep it 3–5 sentences.</div>
              </div>
              <div class="form-group" style="margin-top:14px">
                <label>Profile / Bio <span class="hint-inline">(used by some templates instead of Summary)</span></label>
                <textarea name="profile" rows="4" placeholder="Alternative profile text — leave blank to mirror Summary"></textarea>
              </div>
              <div class="form-group" style="margin-top:14px">
                <label>Resume Bio <span class="hint-inline">(third variation for full template compatibility)</span></label>
                <textarea name="resume" rows="3" placeholder="Short bio — leave blank to mirror Summary"></textarea>
              </div>
            </div>

            <!-- ④ EXPERIENCE -->
            <div class="panel" data-panel="experience">
              <div class="section-hint"> "Work Experience" section — add each job as a separate entry</div>
              <div id="expList"></div>
              <div class="actions">
                <button type="button" class="btn btn-add" id="addExp">+ Add Work Experience</button>
              </div>
            </div>

            <!-- ⑤ EDUCATION -->
            <div class="panel" data-panel="education">
              <div class="section-hint"> "Education" section — add each qualification separately</div>
              <div id="eduList"></div>
              <div class="actions">
                <button type="button" class="btn btn-add" id="addEdu">+ Add Education</button>
              </div>
            </div>

            <!-- ⑥ SKILLS -->
            <div class="panel" data-panel="skills">
              <div class="section-hint"> Skills list shown in your template</div>
              <div class="form-group">
                <label>Skills <span class="field-tag template-tag">shown in template</span></label>
                <textarea name="skills_csv" rows="4" placeholder="Digital Marketing, SEO, Analytics, Social Media, Content Strategy, Google Ads"></textarea>
                <div class="field-note">Separate each skill with a comma. They appear as tags or a list in your template.</div>
              </div>
            </div>

            <!-- ⑦ ADDITIONAL -->
            <div class="panel" data-panel="additional">
              <div class="section-hint"> Extra sections some templates display</div>

              <div class="sub-section-title"> Languages</div>
              <div class="form-group">
                <label>Languages <span class="hint-inline">(comma-separated)</span></label>
                <textarea name="languages_csv" rows="2" placeholder="English, French, Spanish"></textarea>
              </div>

              <div class="sub-section-title" style="margin-top:20px"> Interests & Hobbies</div>
              <div class="form-grid">
                <div class="form-group col-span-2">
                  <label>Interests <span class="hint-inline">(comma-separated)</span></label>
                  <textarea name="interests_csv" rows="2" placeholder="Travel, Music, Photography, Reading, Sports"></textarea>
                </div>
                <div class="form-group col-span-2">
                  <label>Interests <span class="hint-inline">(free text override)</span></label>
                  <input name="interests" placeholder="Or type a formatted interests string here">
                </div>
              </div>

              <div class="sub-section-title" style="margin-top:20px">Certifications</div>
              <div class="form-group">
                <label>Certifications</label>
                <textarea name="certifications" rows="3" placeholder="Google Analytics Certified&#10;HubSpot Content Marketing&#10;Facebook Blueprint"></textarea>
                <div class="field-note">One per line, or comma-separated.</div>
              </div>

              <div class="sub-section-title" style="margin-top:20px"> Additional Links</div>
              <div class="form-group">
                <label>Links <span class="hint-inline">(Portfolio, GitHub, etc.)</span></label>
                <input name="links" placeholder="github.com/yourname, dribbble.com/yourname">
              </div>

              <div class="sub-section-title" style="margin-top:20px"> Mentor / Reference</div>
              <div class="form-grid">
                <div class="form-group col-span-2">
                  <label>Mentor Name</label>
                  <input name="mentor_name" placeholder="Michael R. Magee">
                </div>
                <div class="form-group col-span-2">
                  <label>Mentor Title / Position</label>
                  <input name="mentor_title" placeholder="Senior Marketing Director">
                </div>
                <div class="form-group col-span-2">
                  <label>Mentor Contact</label>
                  <input name="mentor_contact" placeholder="+233 555 000 000">
                </div>
                <div class="form-group col-span-2">
                  <label>Mentor <span class="hint-inline">(full text override)</span></label>
                  <input name="mentor" placeholder="Leave blank to auto-combine fields above">
                </div>
              </div>
            </div>

          </form>
        </div>
        <!-- Bottom nav -->
        <div class="form-footer">
          <button class="btn" id="prevStep">← Back</button>
          <span id="stepIndicator" style="font-size:12px;color:var(--muted);font-weight:600">Step 1 of 7</span>
          <button class="btn btn-primary-outline" id="nextStep">Next →</button>
        </div>
      </div>

      <!-- Preview -->
      <div class="card viewer">
        <div class="hd viewer-head">
          <div><strong>Preview</strong><?= $templateName ? ' <span class="badge">' . htmlspecialchars($templateName) . '</span>' : '' ?></div>
          <div style="display:flex;gap:8px;align-items:center">
            <button class="btn" id="zoomOut">−</button>
            <span class="zoom-val" id="zoomVal">100%</span>
            <button class="btn" id="zoomIn">+</button>
            <?php if ($previewUrl): ?><button class="btn" id="openTab">Open</button><?php endif; ?>
          </div>
        </div>
        <div class="bd">
          <div class="device">
            <div class="frame-wrap" id="frameWrap">
              <div class="paper-plane" id="plane">
                <?php if ($previewUrl): ?>
                  <iframe id="previewFrame" src="<?= htmlspecialchars($previewUrl) ?>" title="Preview"></iframe>
                <?php else: ?>
                  <div style="background:#fff;width:840px;height:1188px;display:flex;align-items:center;justify-content:center;color:#9ca3af">No template selected.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /grid -->
  </div><!-- /builder-root -->

  <script>
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
    const storageKey = 'resume_<?= (int)$resumeId ?>_tpl_<?= (int)$templateId ?>';
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
        resume_id: <?= (int)$resumeId ?>,
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
      a.download = 'resume_<?= (int)$resumeId ?>.json';
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
  </script>
</body>

</html>