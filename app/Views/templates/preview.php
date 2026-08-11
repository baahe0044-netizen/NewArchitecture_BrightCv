<?php
$template = $template ?? ($data['template'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <?php View::partial('components/theme_init'); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($template['name'] ?? 'Preview') ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Dynamic CSS stored in the database -->
    <style>
        <?= $template['css_styles'] ?>
    </style>

    <!-- Shared template CSS -->
    <link rel="stylesheet" href="/public/assets/templates/template.css">
</head>

<body>

    <?= $template['html_structure']; ?>

    <script src="/public/assets/templates/template.js"></script>

</body>

</html>