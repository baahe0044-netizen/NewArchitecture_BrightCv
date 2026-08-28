<?php
require __DIR__ . '/config/bootstrap.php';

$page_title = 'Contact';
$error = '';
$sent = false;
$waMsg = '';

$services = db_rows('SELECT * FROM services WHERE active=1 ORDER BY sort_order, id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $service = trim((string) ($_POST['service'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '') {
            throw new UserMessageException('Please enter your name so we know who to reply to.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new UserMessageException('That email address does not look right. Please check it and try again.');
        }
        if ($phone !== '' && strlen(preg_replace('/\D+/', '', $phone) ?? '') < 9) {
            throw new UserMessageException('That phone number looks too short. Enter it in full, for example 0244000000.');
        }
        if ($message === '') {
            throw new UserMessageException('Please tell us briefly what you need, so we can respond properly.');
        }
        if (mb_strlen($message) > 5000) {
            throw new UserMessageException('Your enquiry is very long. Please shorten it to under 5,000 characters.');
        }

        $stmt = db()->prepare(
            'INSERT INTO contact_messages (name, email, phone, service, message, status) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $phone, $service, $message, 'New']);

        $waMsg = "Hello " . site_setting('company_name', 'AID-U-TECHNICAL IMPACT') . ", I have a new enquiry.\n\n" .
            "Name: {$name}\n" .
            "Email: {$email}\n" .
            "Phone: {$phone}\n" .
            "Service: {$service}\n" .
            "Enquiry: {$message}";

        $sent = true;
        $_POST = [];
    } catch (Throwable $e) {
        // Validation wording reaches the visitor as written. Anything technical
        // (an SQL failure, for example) is logged and replaced with a calm
        // sentence plus a reference code - never a raw exception message.
        $error = inline_exception_message(
            $e,
            'We could not save your enquiry just now. Nothing you did caused this.'
        );
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow">GET IN TOUCH</span>
        <h1>Let's discuss your next project.</h1>
        <p>Tell us about your land, building, road or technical drafting requirements.</p>
    </div>
</section>

<section class="section">
    <div class="container contact-grid">
        <div class="info-stack">
            <div class="info-card">
                <i class="fa-solid fa-phone"></i>
                <h3>Phone</h3>
                <p><?= e(site_setting('phone', 'Add your phone in Admin → Settings')) ?></p>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-envelope"></i>
                <h3>Email</h3>
                <p><?= e(site_setting('email', 'Add your email in Admin → Settings')) ?></p>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-location-dot"></i>
                <h3>Office</h3>
                <p><?= e(site_setting('address', 'Add your address in Admin → Settings')) ?></p>
            </div>
        </div>

        <div class="contact-form">
            <?php if ($sent): ?>
                <div class="notice success">
                    <strong>Thank you &mdash; your enquiry has been received.</strong>
                    We will reply to the email address you gave us.
                    <?php $waLink = whatsapp_url($waMsg); ?>
                    <?php if ($waLink !== ''): ?>
                        <div class="hero-actions">
                            <a class="button" href="<?= e($waLink) ?>" target="_blank" rel="noopener">
                                <i class="fa-brands fa-whatsapp"></i>
                                Continue on WhatsApp
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($error !== ''): ?>
                <div class="notice error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="form-grid">
                    <div class="field">
                        <label for="name">Name *</label>
                        <input id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="email">Email *</label>
                        <input id="email" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="service">Service</label>
                        <select id="service" name="service">
                            <option value="">Select a service</option>
                            <?php foreach ($services as $sv): ?>
                                <option value="<?= e($sv['title']) ?>" <?= ($_POST['service'] ?? '') === $sv['title'] ? 'selected' : '' ?>>
                                    <?= e($sv['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="message">Enquiry *</label>
                        <textarea id="message" name="message" required placeholder="Tell us about your land, building, road or project..."><?= e(trim((string) ($_POST['message'] ?? ''))) ?></textarea>
                    </div>
                </div>

                <button class="button" type="submit">
                    Save Enquiry <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
