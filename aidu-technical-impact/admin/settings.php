<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();

$admin_title = 'Site Settings';

$defaults = [
    'company_name' => 'AID-U-TECHNICAL IMPACT',
    'tagline' => 'PRECISE AND CONCISE',
    'phone' => '',
    'email' => '',
    'whatsapp' => '',
    'address' => 'Accra, Ghana',
    'website' => '',
    'why_image' => 'assets/images/why-site-context.jpg',
    'hero_title' => 'Surveying, Draftsmanship and Technical Solutions',
    'hero_text' => '',
    'about_text' => '',
    'footer_text' => 'ARCHITECTURAL | ENGINEERING | SURVEYING',
    'primary_cta' => 'Request a Consultation'
];

$row = db_row('SELECT * FROM settings WHERE id = 1 LIMIT 1');
$s = array_merge($defaults, $row ?? []);
if ($row === null) {
    flash('error', 'The saved website settings could not be loaded, so the standard defaults are shown below. Saving this form will create the settings record.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $data = [];
        foreach (array_keys($defaults) as $key) {
            if ($key === 'why_image') {
                continue;
            }
            $data[$key] = trim((string)($_POST[$key] ?? ''));
        }

        if ($data['company_name'] === '') {
            throw new UserMessageException('A company name is required — it appears in the page title, header and footer.');
        }

        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new UserMessageException('That email address does not look right. Use the form name@example.com, or leave it blank.');
        }

        if ($data['website'] !== '' && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            throw new UserMessageException('That website address does not look right. Include https:// at the start, or leave it blank.');
        }

        $whyImage = $s['why_image'];
        if (isset($_FILES['why_image']) && ($_FILES['why_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploaded = save_upload(
                $_FILES['why_image'],
                'assets/uploads/settings',
                [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ],
                10 * 1024 * 1024,
                true
            );

            if ($uploaded !== null) {
                if (!empty($s['why_image']) && $s['why_image'] !== 'assets/images/why-site-context.jpg') {
                    delete_file($s['why_image']);
                }
                $whyImage = $uploaded;
            }
        }

        // 13 placeholders for the 13 bound values below. There used to be 14,
        // so every attempt to save the site settings failed with
        // "Column count doesn't match value count at row 1".
        $sql = 'INSERT INTO settings (
                    id, company_name, tagline, phone, email, whatsapp, address,
                    website, why_image, hero_title, hero_text, about_text,
                    footer_text, primary_cta
                ) VALUES (
                    1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
                ON DUPLICATE KEY UPDATE
                    company_name = VALUES(company_name),
                    tagline = VALUES(tagline),
                    phone = VALUES(phone),
                    email = VALUES(email),
                    whatsapp = VALUES(whatsapp),
                    address = VALUES(address),
                    website = VALUES(website),
                    why_image = VALUES(why_image),
                    hero_title = VALUES(hero_title),
                    hero_text = VALUES(hero_text),
                    about_text = VALUES(about_text),
                    footer_text = VALUES(footer_text),
                    primary_cta = VALUES(primary_cta)';

        db()->prepare($sql)->execute([
            $data['company_name'],
            $data['tagline'],
            $data['phone'],
            $data['email'],
            $data['whatsapp'],
            $data['address'],
            $data['website'],
            $whyImage,
            $data['hero_title'],
            $data['hero_text'],
            $data['about_text'],
            $data['footer_text'],
            $data['primary_cta']
        ]);

        flash('success', 'Website settings saved successfully.');
        header('Location: ' . url('admin/settings.php'));
        exit;
    } catch (Throwable $e) {
        flash_exception($e);
        header('Location: ' . url('admin/settings.php'));
        exit;
    }
}

require __DIR__ . '/../includes/admin_header.php';
?>

<section class="admin-card">
    <div class="admin-card-head">
        <div>
            <h2>Company details</h2>
            <p>These details are used throughout the public website.</p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-cols">
            <?php
            $fields = [
                ['company_name', 'Company name'],
                ['tagline', 'Tagline'],
                ['phone', 'Phone'],
                ['email', 'Email'],
                ['whatsapp', 'WhatsApp number'],
                ['address', 'Address'],
                ['website', 'Website URL'],
                ['hero_title', 'Homepage hero title'],
                ['primary_cta', 'Primary CTA text']
            ];
            foreach ($fields as [$key, $label]):
            ?>
                <div>
                    <label for="<?= e($key) ?>"><?= e($label) ?></label>
                    <input id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($s[$key]) ?>">
                </div>
            <?php endforeach; ?>

            <div class="wide">
                <label for="why_image">Why AID-U image — REAL SITE / BUILDING / ROAD CONTEXT</label>
                <input id="why_image" type="file" name="why_image" accept=".jpg,.jpeg,.png,.webp">
                <small>Maximum 10 MB. This image appears in the Why AID-U section and can be changed here.</small>

                <?php if (!empty($s['why_image'])): ?>
                    <div class="setting-image-preview">
                        <img src="<?= e(asset_url($s['why_image'])) ?>" alt="Current Why AID-U image">
                    </div>
                <?php endif; ?>
            </div>

            <div class="wide">
                <label for="hero_text">Hero text</label>
                <textarea id="hero_text" name="hero_text" rows="4"><?= e($s['hero_text']) ?></textarea>
            </div>

            <div class="wide">
                <label for="about_text">About text</label>
                <textarea id="about_text" name="about_text" rows="6"><?= e($s['about_text']) ?></textarea>
            </div>

            <div class="wide">
                <label for="footer_text">Footer text</label>
                <textarea id="footer_text" name="footer_text" rows="3"><?= e($s['footer_text']) ?></textarea>
            </div>
        </div>

        <button class="admin-button" type="submit">Save Website Settings</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
