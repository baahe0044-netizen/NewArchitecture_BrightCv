<?php

/**
 * template_preview.php — Live resume preview renderer
 * Fetches template CSS + HTML from DB and renders it,
 * accepting postMessage patches from the builder.
 */

$dbname   = 'lunettistar_db';
$username = 'root';
$password = '';

try {
  $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  die("Database connection failed.");
}

$templateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;

if ($templateId <= 0) {
  http_response_code(400);
  die("No template specified.");
}

$stmt = $pdo->prepare("SELECT name, css_styles, html_structure FROM resume_templates WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$templateId]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
  http_response_code(404);
  die("Template not found.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($template['name']); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    <?php echo $template['css_styles']; ?>body {
      margin: 0;
      padding: 0;
    }

    /* Placeholder text for empty data-field elements */
    [data-field]:empty::after {
      content: attr(data-placeholder);
      opacity: 0.35;
      font-style: italic;
    }
  </style>
</head>

<body>
  <?php echo $template['html_structure']; ?>

  <script>
    (function() {
      'use strict';

      var currentData = {};

      /* ── Field alias map ── */
      var ALIASES = {
        full_name: ['name', 'first_name'],
        name: ['full_name'],
        job_title: ['title', 'headline', 'status'],
        title: ['job_title', 'headline'],
        role: ['job_title', 'title'],
        company: ['organization'],
        dates: ['date_range', 'period'],
        description: ['desc', 'summary'],
        summary: ['description', 'profile', 'resume'],
        profile: ['summary', 'resume'],
        degree: ['qualification'],
        school: ['institution', 'university'],
        institution: ['school', 'university'],
        years: ['graduation_year']
      };

      function resolve(key, data) {
        if (data[key] !== undefined && data[key] !== null && data[key] !== '') return data[key];
        var alts = ALIASES[key] || [];
        for (var i = 0; i < alts.length; i++) {
          if (data[alts[i]] !== undefined && data[alts[i]] !== '') return data[alts[i]];
        }
        if (key === 'full_name' || key === 'name') {
          var fn = (data.first_name || '') + ' ' + (data.last_name || '');
          if (fn.trim()) return fn.trim();
        }
        return '';
      }

      /* ── Update simple [data-field] elements ── */
      function updateFields(data) {
        document.querySelectorAll('[data-field]').forEach(function(el) {
          var key = el.getAttribute('data-field');
          var val = resolve(key, data);
          if (val === null || val === undefined) return;
          if (el.tagName === 'IMG') {
            if (val) el.src = val;
          } else {
            el.textContent = val;
          }
        });
      }

      /* ── Update [data-repeat] sections ── */
      function updateRepeats(data) {
        document.querySelectorAll('[data-repeat]').forEach(function(container) {
          var section = container.getAttribute('data-repeat');
          var tpl = container.querySelector('template[data-tpl="' + section + '"]');
          if (!tpl) return;

          var items = data[section];

          // Remove existing clones
          Array.from(container.children).forEach(function(child) {
            if (child !== tpl) child.remove();
          });

          if (!Array.isArray(items) || items.length === 0) return;

          items.forEach(function(item) {
            var clone = document.importNode(tpl.content, true);
            clone.querySelectorAll('[data-field]').forEach(function(el) {
              var key = el.getAttribute('data-field');
              var val = key === '.' ? item : resolve(key, item);

              // Bullet list support
              if ((key === 'bullets' || key === 'responsibilities') && Array.isArray(item.bullets) && item.bullets.length) {
                var ul = document.createElement('ul');
                ul.className = 'bullets';
                item.bullets.forEach(function(b) {
                  var li = document.createElement('li');
                  li.textContent = b;
                  ul.appendChild(li);
                });
                el.innerHTML = '';
                el.appendChild(ul);
                return;
              }

              if (val !== undefined && val !== null) el.textContent = val;
            });
            container.appendChild(clone);
          });
        });
      }

      /* ── Main update ── */
      function update(data) {
        currentData = data;
        updateFields(data);
        updateRepeats(data);
      }

      /* ── postMessage listener ── */
      window.addEventListener('message', function(e) {
        if (!e.data) return;
        if (e.data.type === 'resume-patch') update(e.data.payload);
        if (e.data.type === 'focus-section') {
          var el = document.querySelector('[data-section="' + e.data.section + '"]');
          if (el) el.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });

      /* ── Signal ready & load saved data ── */
      window.parent.postMessage({
        type: 'preview-ready'
      }, '*');

      try {
        var saved = localStorage.getItem('resume_builder_state');
        if (saved) update(JSON.parse(saved));
      } catch (err) {
        /* silent */ }

    })();
  </script>
</body>

</html>