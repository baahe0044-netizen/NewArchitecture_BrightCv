<?php
require __DIR__ . '/../config/bootstrap.php';
require_admin();

$admin_title = 'Enquiries';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);

        if ($id < 1) {
            throw new UserMessageException('That enquiry could not be identified, so nothing was changed.');
        }

        if ($action === 'status') {
            $status = (string) ($_POST['status'] ?? 'Read');
            $allowed = ['New', 'Read', 'Replied'];
            if (!in_array($status, $allowed, true)) {
                throw new UserMessageException('That status is not one of New, Read or Replied.');
            }
            db()->prepare('UPDATE contact_messages SET status=? WHERE id=?')->execute([$status, $id]);
            flash('success', 'Enquiry status updated.');
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM contact_messages WHERE id=?')->execute([$id]);
            flash('success', 'Enquiry deleted.');
        }

        header('Location: ' . url('admin/messages.php'));
        exit;
    } catch (Throwable $e) {
        flash_exception($e);
        header('Location: ' . url('admin/messages.php'));
        exit;
    }
}

$items = db_rows('SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC');

require __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-card">
    <div class="admin-card-head">
        <div>
            <h2>Client enquiries</h2>
            <p>Enquiries submitted through the public contact form are stored here.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Service</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>WhatsApp</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr>
                        <td colspan="7">No enquiries yet. Messages sent through the public Contact page appear here automatically.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $x): ?>
                        <?php
                        $waMessage = 'Hello ' . site_setting('company_name', 'AID-U-TECHNICAL IMPACT') .
                            ', regarding my enquiry submitted on your website.';
                        $clientPhone = preg_replace('/\D+/', '', (string) ($x['phone'] ?? ''));
                        $waHref = $clientPhone !== ''
                            ? 'https://wa.me/' . $clientPhone . '?text=' . rawurlencode($waMessage)
                            : '#';
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($x['name']) ?></strong>
                                <small><?= e($x['email']) ?><br><?= e($x['phone']) ?></small>
                            </td>
                            <td><?= e($x['service']) ?></td>
                            <td><?= nl2br(e($x['message'])) ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <?php foreach (['New', 'Read', 'Replied'] as $status): ?>
                                            <option value="<?= e($status) ?>" <?= ($x['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <?php if ($clientPhone !== ''): ?>
                                    <a class="table-action" href="<?= e($waHref) ?>" target="_blank" rel="noopener">
                                        <i class="fa-brands fa-whatsapp"></i> Chat
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= e($x['created_at']) ?></td>
                            <td>
                                <form method="post" data-confirm="Delete this enquiry?">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
                                    <button class="danger-button" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
