<?php
/**
 * VELOURA — Admin: site content & settings manager.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Site Content';
$activeNav = 'content';

// Editable settings grouped for the UI
$groups = [
    'General' => [
        ['key' => 'announcement', 'label' => 'Announcement Bar', 'type' => 'text', 'hint' => 'Shown at the very top of every page'],
        ['key' => 'footer_about', 'label' => 'Footer About Text', 'type' => 'textarea'],
        ['key' => 'shipping_fee', 'label' => 'Shipping Fee', 'type' => 'number', 'hint' => 'e.g. 12.00'],
        ['key' => 'free_shipping_threshold', 'label' => 'Free Shipping Threshold', 'type' => 'number', 'hint' => 'e.g. 150'],
    ],
    'About Page' => [
        ['key' => 'about_text', 'label' => 'About Story Text', 'type' => 'textarea', 'hint' => 'Supports blank lines for new paragraphs'],
    ],
    'Contact Page' => [
        ['key' => 'contact_address', 'label' => 'Address', 'type' => 'textarea'],
        ['key' => 'contact_phone', 'label' => 'Phone', 'type' => 'text'],
        ['key' => 'contact_email', 'label' => 'Email', 'type' => 'text'],
        ['key' => 'contact_hours', 'label' => 'Opening Hours', 'type' => 'textarea'],
    ],
    'Social Links' => [
        ['key' => 'social_instagram', 'label' => 'Instagram URL', 'type' => 'text'],
        ['key' => 'social_facebook', 'label' => 'Facebook URL', 'type' => 'text'],
        ['key' => 'social_pinterest', 'label' => 'Pinterest URL', 'type' => 'text'],
        ['key' => 'social_twitter', 'label' => 'X / Twitter URL', 'type' => 'text'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $allowedKeys = [];
    foreach ($groups as $fields) {
        foreach ($fields as $f) $allowedKeys[] = $f['key'];
    }
    $saved = 0;
    foreach ($_POST as $key => $value) {
        if (in_array($key, $allowedKeys, true) && is_string($value)) {
            save_setting($key, trim($value));
            $saved++;
        }
    }
    set_flash('success', $saved . ' content setting(s) saved successfully.');
    redirect(url('admin/content.php'));
}

include __DIR__ . '/../includes/admin_header.php';
?>

<form method="post" action="<?= url('admin/content.php') ?>">
  <?= csrf_field() ?>
  <div class="row g-4">
    <?php $i = 0; foreach ($groups as $groupName => $fields): ?>
      <div class="col-lg-6">
        <div class="panel-card h-100">
          <div class="panel-head"><h2><i class="bi bi-sliders me-2"></i><?= e($groupName) ?></h2></div>
          <div class="panel-body">
            <?php foreach ($fields as $f):
              $val = get_setting($f['key'], ''); ?>
              <div class="mb-3">
                <label class="form-label lux-label" for="set_<?= e($f['key']) ?>"><?= e($f['label']) ?></label>
                <?php if ($f['type'] === 'textarea'): ?>
                  <textarea class="form-control" rows="4" id="set_<?= e($f['key']) ?>" name="<?= e($f['key']) ?>"><?= e($val) ?></textarea>
                <?php elseif ($f['type'] === 'number'): ?>
                  <input type="number" step="0.01" min="0" class="form-control" id="set_<?= e($f['key']) ?>" name="<?= e($f['key']) ?>" value="<?= e($val) ?>">
                <?php else: ?>
                  <input type="text" class="form-control" id="set_<?= e($f['key']) ?>" name="<?= e($f['key']) ?>" value="<?= e($val) ?>">
                <?php endif; ?>
                <?php if (!empty($f['hint'])): ?><div class="form-text"><?= e($f['hint']) ?></div><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php $i++; endforeach; ?>
  </div>

  <div class="text-end mt-4">
    <button type="submit" class="btn lux-btn-gold px-5 py-3"><i class="bi bi-check2 me-2"></i>Save All Content</button>
  </div>
</form>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
