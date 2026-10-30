<?php
/**
 * VELOURA — Admin: contact messages inbox.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Messages';
$activeNav = 'messages';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'toggle_read') {
        db()->prepare('UPDATE contact_messages SET is_read = 1 - is_read WHERE id = ?')->execute([$id]);
    }
    if ($action === 'delete') {
        db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
        set_flash('success', 'Message deleted.');
    }
    redirect(url('admin/messages.php'));
}

$messages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100')->fetchAll();
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="panel-card">
  <div class="panel-head">
    <h2><i class="bi bi-envelope me-2"></i>Contact Messages (<?= count($messages) ?>)</h2>
  </div>
  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead><tr><th>Status</th><th>From</th><th>Subject</th><th>Received</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$messages): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Inbox is empty.</td></tr>
        <?php endif; ?>
        <?php foreach ($messages as $m): ?>
          <tr class="<?= $m['is_read'] ? '' : 'msg-unread' ?>">
            <td><?= $m['is_read']
                ? '<span class="badge rounded-pill bg-light text-dark">Read</span>'
                : '<span class="badge rounded-pill bg-primary-subtle text-primary">New</span>' ?></td>
            <td>
              <strong><?= e($m['name']) ?></strong>
              <small class="d-block text-muted"><?= e($m['email']) ?></small>
            </td>
            <td style="max-width:380px">
              <strong class="small"><?= e($m['subject']) ?></strong>
              <small class="d-block text-muted text-truncate" style="max-width:360px"><?= e($m['message']) ?></small>
            </td>
            <td><?= time_ago($m['created_at']) ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-view" data-bs-toggle="modal" data-bs-target="#msgModal<?= (int)$m['id'] ?>"><i class="bi bi-eye"></i></button>
                <form method="post" action="<?= url('admin/messages.php') ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle_read">
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <button type="submit" class="btn btn-edit" title="<?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?>">
                    <i class="bi bi-<?= $m['is_read'] ? 'envelope' : 'envelope-open' ?>"></i>
                  </button>
                </form>
                <form method="post" action="<?= url('admin/messages.php') ?>" class="d-inline confirm-delete" data-name="message from <?= e($m['name']) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <button type="submit" class="btn btn-delete"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modals -->
<?php foreach ($messages as $m): ?>
  <div class="modal fade" id="msgModal<?= (int)$m['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content lux-modal">
        <div class="modal-header">
          <h5 class="modal-title"><?= e($m['subject']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-2">From <strong><?= e($m['name']) ?></strong> &lt;<?= e($m['email']) ?>&gt; · <?= date('M j, Y g:i A', strtotime($m['created_at'])) ?></p>
          <hr>
          <p><?= nl2br(e($m['message'])) ?></p>
        </div>
        <div class="modal-footer">
          <a href="mailto:<?= e($m['email']) ?>?subject=Re: <?= e($m['subject']) ?>" class="btn lux-btn-gold"><i class="bi bi-reply me-2"></i>Reply by Email</a>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
