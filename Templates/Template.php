<?php
// ====== DB CONFIG ======
$dbname   = 'lunettistar_db';
$username = 'root';
$password = '';

try {
  $pdo = new PDO("mysql:dbname=$dbname;charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  die("Database connection failed: " . $e->getMessage());
}

// ====== Ensure selection table exists ======
$pdo->exec("
  CREATE TABLE IF NOT EXISTS resume_selected_template (
    resume_id INT NOT NULL,
    template_id INT NOT NULL,
    PRIMARY KEY (resume_id),
    KEY idx_template (template_id)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
");

$resumeId = isset($_REQUEST['resume_id']) ? (int)$_REQUEST['resume_id'] : 1;

// ====== PREVIEW ENDPOINT ======
if (isset($_GET['action']) && $_GET['action'] === 'preview') {
  $tplId = (int)($_GET['template_id'] ?? 0);
  $stmt  = $pdo->prepare("SELECT name, css_styles, html_structure FROM resume_templates
                            WHERE is_active=1 AND LENGTH(css_styles)>0 AND LENGTH(html_structure)>0 AND id=? LIMIT 1");
  $stmt->execute([$tplId]);
  $tpl = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$tpl) {
    http_response_code(404);
    echo "Template not found or inactive.";
    exit;
  }
  header('Content-Type: text/html; charset=utf-8');
?>
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Preview — <?php echo htmlspecialchars($tpl['name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
      <?php echo $tpl['css_styles']; ?>
    </style>
    <style>
      html,
      body {
        background: #fff;
        margin: 0
      }
    </style>
  </head>

  <body><?php echo $tpl['html_structure']; ?></body>

  </html><?php
          exit;
        }

        // ====== USE ENDPOINT ======
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'use') {
          if (function_exists('ob_get_length')) {
            while (ob_get_level()) {
              ob_end_clean();
            }
          }
          header('Content-Type: application/json; charset=utf-8');
          try {
            $tplId    = (int)($_POST['template_id'] ?? 0);
            $reqResId = isset($_POST['resume_id']) ? (int)$_POST['resume_id'] : $resumeId;
            if ($tplId <= 0) {
              http_response_code(400);
              echo json_encode(['ok' => false, 'error' => 'Invalid template id']);
              exit;
            }
            $stmt = $pdo->prepare("REPLACE INTO resume_selected_template (resume_id, template_id) VALUES (?, ?)");
            $stmt->execute([$reqResId, $tplId]);
            echo json_encode(['ok' => true, 'template_id' => $tplId, 'resume_id' => $reqResId]);
          } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Server exception', 'detail' => $e->getMessage()]);
          }
          exit;
        }

        // ====== Fetch templates ======
        try {
          $stmt = $pdo->prepare("
      SELECT id, name, description
      FROM resume_templates
      WHERE is_active = 1 AND LENGTH(css_styles) > 0 AND LENGTH(html_structure) > 0
      ORDER BY id
    ");
          $stmt->execute();
          $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
          $templates = [];
          $error_message = "Error fetching templates: " . $e->getMessage();
        }

        $selId = null;
        try {
          $s = $pdo->prepare("SELECT template_id FROM resume_selected_template WHERE resume_id=? LIMIT 1");
          $s->execute([$resumeId]);
          if ($row = $s->fetch(PDO::FETCH_ASSOC)) $selId = (int)$row['template_id'];
        } catch (PDOException $e) {
        }

        function mapDbTemplateToDisplay($dbTemplate)
        {
          $name = $dbTemplate['name'];
          $category = 'Professional';
          $tag = 'Professional';
          if (stripos($name, 'creative') !== false || stripos($name, 'orange') !== false) {
            $category = 'Creative';
            $tag = 'Creative';
          } elseif (stripos($name, 'modern') !== false || stripos($name, 'blue') !== false) {
            $category = 'Modern';
            $tag = 'Modern';
          } elseif (stripos($name, 'classic') !== false || stripos($name, 'traditional') !== false) {
            $category = 'Classic';
            $tag = 'Classic';
          } elseif (stripos($name, 'black') !== false || stripos($name, 'two') !== false) {
            $category = 'Modern';
            $tag = 'Modern';
          }
          return [
            'id'          => (int)$dbTemplate['id'],
            'name'        => $name,
            'description' => $dbTemplate['description'] ?: 'Professional resume template',
            'category'    => $category,
            'tag'         => $tag,
          ];
        }

        $default_templates = [
          ['id' => 1, 'name' => 'Professional', 'description' => 'Clean, ATS-friendly design', 'category' => 'Professional', 'tag' => 'Professional'],
          ['id' => 2, 'name' => 'Modern Blue', 'description' => 'Contemporary layout with blue accents', 'category' => 'Modern', 'tag' => 'Modern'],
          ['id' => 3, 'name' => 'Creative Orange', 'description' => 'Bold design for creative professionals', 'category' => 'Creative', 'tag' => 'Creative'],
        ];
        $displayTemplates = !empty($templates) ? array_map('mapDbTemplateToDisplay', $templates) : $default_templates;

        $scriptPath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $query = $_GET;
        $query['resume_id'] = $resumeId;
        $baseURL = $scriptPath . '?' . http_build_query($query);
          ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LunettiStar — Choose Your Template</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ── Design tokens ─────────────────────────────────────────── */
    :root {
      --ink: #0f1117;
      --ink-2: #3d4250;
      --ink-3: #6b7280;
      --line: #e4e6ea;
      --line-2: #f0f1f3;
      --surface: #ffffff;
      --bg: #f5f6f8;
      --accent: #1a56db;
      --accent-dk: #1447b3;
      --accent-lt: #eff4ff;
      --green: #0d9a6e;
      --green-lt: #e6f7f2;
      --radius: 10px;
      --radius-lg: 16px;
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, .07), 0 1px 2px rgba(0, 0, 0, .04);
      --shadow-md: 0 4px 16px rgba(0, 0, 0, .08), 0 1px 4px rgba(0, 0, 0, .04);
      --shadow-lg: 0 12px 40px rgba(0, 0, 0, .10), 0 2px 8px rgba(0, 0, 0, .05);
    }

    /* ── Reset ─────────────────────────────────────────────────── */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      font-size: 15px;
      -webkit-font-smoothing: antialiased;
    }

    body {
      font-family: 'DM Sans', system-ui, sans-serif;
      background: var(--bg);
      color: var(--ink);
      line-height: 1.6;
      min-height: 100vh;
    }

    /* ── Page chrome ───────────────────────────────────────────── */
    .page-header {
      background: #0b1240;
      border-bottom: 1px solid var(--line);
      padding: 28px 40px 0;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .page-header-inner {
      max-width: 1320px;
      margin: 0 auto;
    }

    .page-title-row {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 24px;
      margin-bottom: 24px;
    }

    .page-title {
      font-family: 'DM Serif Display', Georgia, serif;
      font-size: 2rem;
      font-weight: 400;
      color: White;
      letter-spacing: -0.5px;
      line-height: 1.1;
    }

    .page-title span {
      font-style: italic;
      color: var(--accent);
    }

    .page-subtitle {
      font-size: 0.9rem;
      color: white;
      margin-top: 4px;
      font-weight: 400;
    }

    .template-count {
      font-size: 0.8rem;
      font-weight: 600;
      color: white;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      white-space: nowrap;
      align-self: center;
    }

    /* ── Filter tabs ───────────────────────────────────────────── */
    .filter-tabs {
      display: flex;
      gap: 0;
      border-top: 1px solid var(--line);
      margin-top: 4px;
    }

    .filter-tab {
      padding: 12px 20px;
      border: none;
      background: none;
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.85rem;
      font-weight: 500;
      color: white;
      border-bottom: 2px solid transparent;
      margin-bottom: -1px;
      transition: color .18s, border-color .18s;
      letter-spacing: 0.01em;
    }

    .filter-tab:hover {
      color: var(--accent);
    }

    .filter-tab.active {
      color: var(--accent);
      border-bottom-color: var(--accent);
      font-weight: 600;
    }

    /* ── Main content ──────────────────────────────────────────── */
    .container {
      max-width: 1320px;
      margin: 0 auto;
      padding: 40px 40px 80px;
    }

    /* ── Inline preview panel ──────────────────────────────────── */
    #inlinePreview {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius-lg);
      overflow: hidden;
      margin-bottom: 40px;
      box-shadow: var(--shadow-lg);
      display: none;
      animation: fadeIn .2s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-6px);
      }

      to {
        opacity: 1;
        transform: none;
      }
    }

    .preview-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 20px;
      border-bottom: 1px solid var(--line);
      background: var(--line-2);
    }

    .preview-bar-title {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--ink-2);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .preview-bar-title::before {
      content: '';
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--green);
    }

    .preview-bar-actions {
      display: flex;
      gap: 8px;
    }

    .preview-frame-wrap {
      height: 72vh;
    }

    #inlinePreviewFrame {
      width: 100%;
      height: 100%;
      border: 0;
      background: #fff;
    }

    /* ── Template grid ─────────────────────────────────────────── */
    .template-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 24px;
    }

    /* ── Template card ─────────────────────────────────────────── */
    .template-card {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius-lg);
      overflow: hidden;
      transition: box-shadow .22s ease, border-color .22s ease, transform .22s ease;
      box-shadow: var(--shadow-sm);
      position: relative;
    }

    .template-card:hover {
      box-shadow: var(--shadow-lg);
      border-color: #c5d3f0;
      transform: translateY(-3px);
    }

    .template-card.is-selected {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(26, 86, 219, .12), var(--shadow-md);
    }

    /* Thumbnail area */
    .card-thumb {
      height: 240px;
      background: #f8f9fb;
      position: relative;
      overflow: hidden;
      cursor: pointer;
      border-bottom: 1px solid var(--line);
    }

    .card-thumb:hover .thumb-overlay {
      opacity: 1;
    }

    .half-frame {
      width: 1000px;
      height: 1400px;
      border: 0;
      transform-origin: top left;
      transform: scale(0.268);
      pointer-events: none;
      display: block;
    }

    .thumb-overlay {
      position: absolute;
      inset: 0;
      background: rgba(15, 17, 23, .42);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity .2s ease;
    }

    .thumb-overlay-btn {
      background: white;
      color: var(--ink);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 9px 18px;
      border-radius: 999px;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      letter-spacing: 0.02em;
      box-shadow: 0 2px 12px rgba(0, 0, 0, .25);
    }

    .thumb-overlay-btn i {
      font-size: 11px;
    }

    /* Selected badge */
    .selected-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: var(--green);
      color: white;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 999px;
      letter-spacing: 0.04em;
      display: flex;
      align-items: center;
      gap: 4px;
      box-shadow: 0 2px 8px rgba(13, 154, 110, .3);
    }

    /* Category pill on thumb */
    .card-category-pill {
      position: absolute;
      bottom: 10px;
      left: 10px;
      background: rgba(255, 255, 255, .92);
      color: var(--ink-2);
      font-size: 10px;
      font-weight: 600;
      padding: 3px 9px;
      border-radius: 999px;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      backdrop-filter: blur(4px);
      border: 1px solid rgba(255, 255, 255, .6);
    }

    /* Card body */
    .card-body {
      padding: 18px 20px 20px;
    }

    .card-name {
      font-size: 1rem;
      font-weight: 700;
      color: var(--ink);
      margin-bottom: 4px;
      letter-spacing: -0.2px;
    }

    .card-desc {
      font-size: 0.83rem;
      color: var(--ink-3);
      line-height: 1.5;
      margin-bottom: 16px;
      min-height: 2.5em;
    }

    .card-actions {
      display: flex;
      gap: 8px;
    }

    /* ── Buttons ───────────────────────────────────────────────── */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 9px 16px;
      border-radius: var(--radius);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.83rem;
      font-weight: 600;
      cursor: pointer;
      border: 1.5px solid var(--line);
      background: var(--surface);
      color: var(--ink-2);
      transition: all .18s ease;
      white-space: nowrap;
      letter-spacing: 0.01em;
      text-decoration: none;
    }

    .btn:hover {
      background: var(--line-2);
      border-color: #d1d5db;
      color: var(--ink);
    }

    .btn:disabled {
      opacity: .5;
      cursor: not-allowed;
      pointer-events: none;
    }

    .btn i {
      font-size: 11px;
    }

    .btn-ghost {
      flex: 1;
      background: transparent;
      border-color: var(--line);
      color: var(--ink-3);
    }

    .btn-ghost:hover {
      background: var(--line-2);
      color: var(--ink);
    }

    .btn-primary {
      flex: 1.4;
      background: var(--accent);
      color: white;
      border-color: var(--accent);
    }

    .btn-primary:hover {
      background: var(--accent-dk);
      border-color: var(--accent-dk);
    }

    .btn-selected {
      flex: 1.4;
      background: var(--green-lt);
      color: var(--green);
      border-color: #a7e3d1;
      cursor: default;
    }

    .btn-sm {
      padding: 7px 13px;
      font-size: 0.8rem;
    }

    /* ── Error banner ──────────────────────────────────────────── */
    .error-banner {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
      border-radius: var(--radius);
      padding: 12px 16px;
      margin-bottom: 24px;
      font-size: 0.875rem;
    }

    /* ── Empty state ───────────────────────────────────────────── */
    .empty-state {
      grid-column: 1/-1;
      text-align: center;
      padding: 64px 24px;
      color: var(--ink-3);
    }

    .empty-state-icon {
      width: 48px;
      height: 48px;
      border: 2px solid var(--line);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      color: var(--ink-3);
    }

    .empty-state h3 {
      font-size: 1rem;
      font-weight: 600;
      color: var(--ink-2);
      margin-bottom: 6px;
    }

    .empty-state p {
      font-size: 0.875rem;
    }

    /* ── Footer ────────────────────────────────────────────────── */
    .page-footer {
      text-align: center;
      padding: 32px 0 16px;
      border-top: 1px solid var(--line);
      margin-top: 16px;
      font-size: 0.8rem;
      color: var(--ink-3);
      letter-spacing: 0.01em;
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 768px) {
      .page-header {
        padding: 20px 20px 0;
      }

      .container {
        padding: 24px 20px 60px;
      }

      .page-title {
        font-size: 1.5rem;
      }

      .filter-tab {
        padding: 10px 14px;
        font-size: 0.8rem;
      }

      .template-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }
    }
  </style>
</head>

<body>

  <!-- ═══ PAGE HEADER ════════════════════════════════════════════ -->
  <header class="page-header">
    <div class="page-header-inner">
      <div class="page-title-row">
        <div>
          <h1 class="page-title">Choose your <span>template</span></h1>
          <p class="page-subtitle">Select a design, preview it, then start building your resume.</p>
        </div>
        <span class="template-count"><?php echo count($displayTemplates); ?> template<?php echo count($displayTemplates) !== 1 ? 's' : ''; ?> available</span>
      </div>

      <nav class="filter-tabs" role="tablist">
        <button class="filter-tab active" data-category="all" role="tab">All</button>
        <button class="filter-tab" data-category="Professional" role="tab">Professional</button>
        <button class="filter-tab" data-category="Modern" role="tab">Modern</button>
        <button class="filter-tab" data-category="Creative" role="tab">Creative</button>
        <button class="filter-tab" data-category="Classic" role="tab">Classic</button>
      </nav>
    </div>
  </header>

  <!-- ═══ MAIN ════════════════════════════════════════════════════ -->
  <main class="container">

    <?php if (isset($error_message)): ?>
      <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Inline Preview Panel -->
    <div id="inlinePreview">
      <div class="preview-bar">
        <span class="preview-bar-title" id="inlinePreviewTitle">Template Preview</span>
        <div class="preview-bar-actions">
          <button class="btn btn-sm" id="inlinePreviewOpen">
            <i class="fas fa-arrow-up-right-from-square"></i> Open in new tab
          </button>
          <button class="btn btn-sm" id="inlinePreviewClose">
            <i class="fas fa-xmark"></i> Close
          </button>
        </div>
      </div>
      <div class="preview-frame-wrap">
        <iframe id="inlinePreviewFrame" referrerpolicy="no-referrer" title="Template preview"></iframe>
      </div>
    </div>

    <!-- Template Grid -->
    <div class="template-grid" id="templateGrid">
      <?php foreach ($displayTemplates as $tpl): ?>
        <?php $isSelected = ($selId === (int)$tpl['id']); ?>
        <div class="template-card <?php echo $isSelected ? 'is-selected' : ''; ?>"
          data-template-id="<?php echo (int)$tpl['id']; ?>"
          data-category="<?php echo htmlspecialchars($tpl['category']); ?>">

          <!-- Thumbnail -->
          <div class="card-thumb" onclick="previewTemplate(<?php echo (int)$tpl['id']; ?>, <?php echo json_encode($tpl['name']); ?>)">
            <iframe class="half-frame"
              data-src="Templates/template_preview.php?template_id=<?php echo (int)$tpl['id']; ?>"
              referrerpolicy="no-referrer"
              aria-hidden="true"
              tabindex="-1"></iframe>

            <div class="thumb-overlay">
              <button class="thumb-overlay-btn">
                <i class="fas fa-expand"></i> Preview
              </button>
            </div>

            <?php if ($isSelected): ?>
              <div class="selected-badge">
                <i class="fas fa-check"></i> Selected
              </div>
            <?php endif; ?>

            <span class="card-category-pill"><?php echo htmlspecialchars($tpl['category']); ?></span>
          </div>

          <!-- Body -->
          <div class="card-body">
            <div class="card-name"><?php echo htmlspecialchars($tpl['name']); ?></div>
            <div class="card-desc"><?php echo htmlspecialchars($tpl['description']); ?></div>
            <div class="card-actions">
              <button class="btn btn-ghost"
                onclick="previewTemplate(<?php echo (int)$tpl['id']; ?>, <?php echo json_encode($tpl['name']); ?>)">
                <i class="fas fa-eye"></i> Preview
              </button>
              <?php if ($isSelected): ?>
                <button class="btn btn-selected" disabled>
                  <i class="fas fa-check"></i> In Use
                </button>
              <?php else: ?>
                <button class="btn btn-primary"
                  onclick="selectTemplate(<?php echo (int)$tpl['id']; ?>, <?php echo json_encode($tpl['name']); ?>)">
                  Use Template
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (empty($displayTemplates)): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><i class="fas fa-file-lines"></i></div>
          <h3>No templates found</h3>
          <p>Add active templates to your database to get started.</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="page-footer">
      &copy; <?php echo date('Y'); ?> BRIGHT CV Builder &mdash; Professional Resume Templates
    </div>

  </main>

  <script>
    const BASE_URL = (function() {
      const url = new URL(window.location.href);
      const p = url.searchParams;
      if (!p.get('resume_id')) p.set('resume_id', '<?php echo (int)$resumeId; ?>');
      p.delete('action');
      p.delete('template_id');
      return url.pathname + '?' + p.toString();
    })();

    /* ── Category filter ── */
    document.querySelectorAll('.filter-tab').forEach(tab => {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        this.classList.add('active');
        this.setAttribute('aria-selected', 'true');

        const cat = this.dataset.category;
        document.querySelectorAll('.template-card').forEach(card => {
          card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
        });
      });
    });

    /* ── Preview ── */
    function previewTemplate(templateId, templateName) {
      const url = `Templates/template_preview.php?template_id=${encodeURIComponent(templateId)}`;
      const panel = document.getElementById('inlinePreview');
      const frame = document.getElementById('inlinePreviewFrame');
      const title = document.getElementById('inlinePreviewTitle');
      const opener = document.getElementById('inlinePreviewOpen');

      frame.src = url;
      title.textContent = templateName;
      opener.onclick = () => window.open(url, '_blank', 'noopener');

      panel.style.display = 'block';
      panel.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }

    document.getElementById('inlinePreviewClose').addEventListener('click', () => {
      const panel = document.getElementById('inlinePreview');
      document.getElementById('inlinePreviewFrame').src = 'about:blank';
      panel.style.display = 'none';
    });

    /* ── Select template ── */
    async function selectTemplate(templateId, templateName) {
      const form = new FormData();
      form.append('action', 'use');
      form.append('template_id', String(templateId));
      form.append('resume_id', '<?php echo (int)$resumeId; ?>');

      try {
        const resp = await fetch(BASE_URL, {
          method: 'POST',
          body: form,
          credentials: 'same-origin'
        });
        const data = await resp.json();
        if (resp.ok && data && data.ok) {
          window.location.href = `index.php?page=resume/builder&resume_id=${data.resume_id}&template_id=${data.template_id}`;
        } else {
          alert('Could not save template selection: ' + (data?.error || 'Unknown error'));
        }
      } catch (err) {
        alert('Network error: ' + (err?.message || ''));
      }
    }

    /* ── Lazy-load iframe thumbnails ── */
    (function() {
      const frames = document.querySelectorAll('.half-frame');
      if (!('IntersectionObserver' in window)) {
        frames.forEach(f => {
          if (!f.src || f.src === window.location.href) f.src = f.dataset.src;
        });
        return;
      }
      const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            const f = e.target;
            if (!f.src || f.src === window.location.href) f.src = f.dataset.src;
            obs.unobserve(f);
          }
        });
      }, {
        rootMargin: '200px',
        threshold: 0.01
      });
      frames.forEach(f => io.observe(f));
    })();
  </script>
</body>

</html>