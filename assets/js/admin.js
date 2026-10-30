/* ============================================================
   VELOURA — Admin JavaScript
   Sidebar toggle, delete confirmations.
   ============================================================ */
(function () {
  'use strict';

  /* ---------- sidebar (mobile) ---------- */
  var toggle = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('adminSidebar');
  var overlay = document.getElementById('adminOverlay');

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
  }

  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('show', sidebar.classList.contains('open'));
    });
  }
  if (overlay) overlay.addEventListener('click', closeSidebar);

  /* ---------- delete confirmation ---------- */
  document.addEventListener('submit', function (ev) {
    var form = ev.target.closest('.confirm-delete');
    if (!form) return;
    var name = form.getAttribute('data-name') || 'this item';
    var ok = window.confirm('Delete ' + name + '? This cannot be undone.');
    if (!ok) ev.preventDefault();
  });

  /* ---------- image input preview ---------- */
  var imgInput = document.getElementById('pfImage');
  var preview = document.getElementById('imgPreview');
  if (imgInput && preview) {
    imgInput.addEventListener('change', function () {
      var file = imgInput.files && imgInput.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (e) {
        if (preview.tagName === 'IMG') {
          preview.src = e.target.result;
        } else {
          var img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'form-img-preview';
          img.id = 'imgPreview';
          img.alt = 'Preview';
          preview.replaceWith(img);
        }
      };
      reader.readAsDataURL(file);
    });
  }
})();
