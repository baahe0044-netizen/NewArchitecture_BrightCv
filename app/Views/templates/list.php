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
    <link rel="stylesheet" href="Public/assets/templates/template.css">
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
        <?php $displayTemplates = isset($displayTemplates) ? $displayTemplates : []; ?>
        <span class="template-count"><?php $count = count($displayTemplates); echo $count; ?> template<?php echo $count !== 1 ? 's' : ''; ?> available</span>
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
</body>

</html>