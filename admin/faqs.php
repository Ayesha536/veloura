<?php
/**
 * VELOURA — Admin: FAQ CRUD.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'FAQs';
$activeNav = 'faqs';

$editFaq = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $question = trim((string)($_POST['question'] ?? ''));
        $answer   = trim((string)($_POST['answer'] ?? ''));
        $sort = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (mb_strlen($question) < 5 || mb_strlen($answer) < 5) {
            set_flash('error', 'Question and answer must each be at least 5 characters.');
            redirect(url('admin/faqs.php'));
        }

        if ($id > 0) {
            db()->prepare('UPDATE faqs SET question=?, answer=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$question, $answer, $sort, $isActive, $id]);
            set_flash('success', 'FAQ updated.');
        } else {
            db()->prepare('INSERT INTO faqs (question, answer, sort_order, is_active) VALUES (?,?,?,?)')
                ->execute([$question, $answer, $sort, $isActive]);
            set_flash('success', 'FAQ added.');
        }
    }

    if ($action === 'delete') {
        db()->prepare('DELETE FROM faqs WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        set_flash('success', 'FAQ deleted.');
    }

    redirect(url('admin/faqs.php'));
}

if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM faqs WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editFaq = $stmt->fetch() ?: null;
}

$faqs = db()->query('SELECT * FROM faqs ORDER BY sort_order ASC, id ASC')->fetchAll();
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel-card">
      <div class="panel-head">
        <h2><i class="bi bi-<?= $editFaq ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $editFaq ? 'Edit FAQ' : 'Add FAQ' ?></h2>
      </div>
      <form method="post" action="<?= url('admin/faqs.php') ?>" class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editFaq['id'] ?? 0) ?>">
        <div class="mb-3">
          <label class="form-label lux-label" for="faqQ">Question *</label>
          <input type="text" id="faqQ" name="question" class="form-control" value="<?= e($editFaq['question'] ?? '') ?>" required maxlength="250">
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="faqA">Answer *</label>
          <textarea id="faqA" name="answer" rows="5" class="form-control" required><?= e($editFaq['answer'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="faqSort">Sort Order</label>
          <input type="number" id="faqSort" name="sort_order" class="form-control" value="<?= (int)($editFaq['sort_order'] ?? 0) ?>">
        </div>
        <div class="form-check lux-switch mb-3">
          <input class="form-check-input" type="checkbox" id="faqActive" name="is_active" value="1" <?= ($editFaq === null || (int)$editFaq['is_active'] === 1) ? 'checked' : '' ?>>
          <label class="form-check-label" for="faqActive">Published</label>
        </div>
        <div class="d-grid gap-2">
          <button type="submit" class="btn lux-btn-gold"><?= $editFaq ? 'Update FAQ' : 'Add FAQ' ?></button>
          <?php if ($editFaq): ?><a href="<?= url('admin/faqs.php') ?>" class="btn btn-outline-secondary">Cancel Edit</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="panel-card">
      <div class="panel-head"><h2><i class="bi bi-patch-question me-2"></i>FAQs (<?= count($faqs) ?>)</h2></div>
      <div class="table-responsive">
        <table class="table admin-table align-middle">
          <thead><tr><th style="width:45%">Question</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php if (!$faqs): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No FAQs yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($faqs as $f): ?>
              <tr>
                <td>
                  <strong><?= e($f['question']) ?></strong>
                  <small class="d-block text-muted text-truncate" style="max-width:420px"><?= e($f['answer']) ?></small>
                </td>
                <td><?= (int)$f['sort_order'] ?></td>
                <td><?= $f['is_active'] ? '<span class="badge rounded-pill bg-success-subtle text-success">Published</span>' : '<span class="badge rounded-pill bg-secondary-subtle text-secondary">Draft</span>' ?></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('admin/faqs.php?edit=' . (int)$f['id']) ?>" class="btn btn-edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="<?= url('admin/faqs.php') ?>" class="d-inline confirm-delete" data-name="FAQ: <?= e($f['question']) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
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
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
