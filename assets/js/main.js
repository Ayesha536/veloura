/* ============================================================
   VELOURA — Storefront JavaScript
   Cart & wishlist via fetch(), UI niceties, scroll reveal.
   ============================================================ */
(function () {
  'use strict';

  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var BASE = document.body.getAttribute('data-base') || '/';

  /* ---------- helpers ---------- */
  function toast(message, type) {
    var wrap = document.querySelector('.flash-wrap');
    if (!wrap) return;
    var el = document.createElement('div');
    el.className = 'alert alert-' + (type === 'error' ? 'danger' : (type || 'success')) +
      ' alert-dismissible fade show lux-alert';
    el.setAttribute('role', 'alert');
    el.innerHTML = message +
      '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    wrap.appendChild(el);
    setTimeout(function () {
      if (el.parentNode && window.bootstrap) {
        var t = bootstrap.Alert.getOrCreateInstance(el);
        t.close();
      } else if (el.parentNode) {
        el.parentNode.removeChild(el);
      }
    }, 3500);
  }

  function updateCartBadges(count) {
    document.querySelectorAll('[aria-label="Cart"] .icon-badge').forEach(function (b) { b.remove(); });
    if (count > 0) {
      document.querySelectorAll('[aria-label="Cart"]').forEach(function (link) {
        var s = document.createElement('span');
        s.className = 'icon-badge';
        s.textContent = count;
        link.appendChild(s);
      });
    }
    // mobile nav cart text
    var mobileCart = document.querySelector('.mobile-links a[href*="cart.php"]');
    if (mobileCart) {
      mobileCart.innerHTML = '<i class="bi bi-bag-check me-2"></i>Cart (' + count + ')';
    }
  }

  function updateWishlistBadges(count) {
    document.querySelectorAll('[aria-label="Wishlist"] .icon-badge').forEach(function (b) { b.remove(); });
    if (count > 0) {
      document.querySelectorAll('[aria-label="Wishlist"]').forEach(function (link) {
        var s = document.createElement('span');
        s.className = 'icon-badge';
        s.textContent = count;
        link.appendChild(s);
      });
    }
  }

  /* ---------- navbar scroll ---------- */
  var nav = document.getElementById('mainNav');
  function onScroll() {
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 12);
    var btt = document.getElementById('backToTop');
    if (btt) btt.classList.toggle('show', window.scrollY > 480);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  var backToTop = document.getElementById('backToTop');
  if (backToTop) backToTop.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* ---------- reveal on scroll ---------- */
  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('visible');
          io.unobserve(en.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('visible'); });
  }

  /* ---------- add to cart (AJAX) ---------- */
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.add-cart-btn, .add-cart-detail');
    if (!btn || btn.disabled) return;
    ev.preventDefault();

    var productId = btn.getAttribute('data-product');
    var qtyInput = document.getElementById('qtyInput');
    var qty = btn.classList.contains('add-cart-detail') && qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

    var original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding…';

    // cart page form has its own flow; this path is for card buttons
    var fd = new URLSearchParams();
    fd.append('action', 'add');
    fd.append('product_id', productId);
    fd.append('qty', qty);
    fetch(BASE + 'ajax/cart.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd,
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          toast('<i class="bi bi-check-circle me-1"></i>' + res.message, 'success');
          updateCartBadges(res.cart_count || 0);
        } else {
          toast(res.message || 'Could not add to cart.', 'error');
          if (typeof res.cart_count === 'number') updateCartBadges(res.cart_count);
        }
      })
      .catch(function () { toast('Network error — please try again.', 'error'); })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = original;
      });
  });

  /* ---------- buy now ---------- */
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.buy-now-btn');
    if (!btn || btn.disabled) return;
    ev.preventDefault();

    var productId = btn.getAttribute('data-product');
    var qtyInput = document.getElementById('qtyInput');
    var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

    var fd = new URLSearchParams();
    fd.append('action', 'add');
    fd.append('product_id', productId);
    fd.append('qty', qty);
    fetch(BASE + 'ajax/cart.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd,
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          window.location.href = BASE + 'checkout.php';
        } else {
          toast(res.message || 'Could not add to cart.', 'error');
        }
      })
      .catch(function () { toast('Network error — please try again.', 'error'); });
  });

  /* ---------- wishlist toggle (AJAX) ---------- */
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.wishlist-btn');
    if (!btn) return;
    ev.preventDefault();

    if (btn.getAttribute('data-need-login') === '1') {
      toast('Please sign in to save pieces to your wishlist.', 'info');
      setTimeout(function () { window.location.href = BASE + 'login.php'; }, 900);
      return;
    }

    var fd = new URLSearchParams();
    fd.append('product_id', btn.getAttribute('data-product'));
    fetch(BASE + 'ajax/wishlist.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd,
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
      .then(function (_ref) {
        var res = _ref.json;
        if (res.login_required) {
          toast(res.message, 'info');
          setTimeout(function () { window.location.href = BASE + 'login.php'; }, 900);
          return;
        }
        if (res.success) {
          var on = res.in_wishlist;
          document.querySelectorAll('.wishlist-btn[data-product="' + btn.getAttribute('data-product') + '"]').forEach(function (b) {
            b.classList.toggle('active', on);
            var icon = b.querySelector('i');
            if (icon) icon.className = 'bi bi-' + (on ? 'heart-fill' : 'heart');
            var label = b.textContent.trim().match(/Wishlist|In Wishlist/);
            if (label) b.innerHTML = '<i class="bi bi-' + (on ? 'heart-fill' : 'heart') + ' me-1"></i>' + (on ? 'In Wishlist' : 'Wishlist');
          });
          toast(res.message, 'success');
          updateWishlistBadges(res.wishlist_count || 0);
        } else {
          toast(res.message || 'Action failed.', 'error');
        }
      })
      .catch(function () { toast('Network error — please try again.', 'error'); });
  });

  /* ---------- quantity selectors ---------- */
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.qty-btn');
    if (!btn) return;
    ev.preventDefault();
    var wrap = btn.closest('.qty-selector');
    var input = wrap ? wrap.querySelector('.qty-input') : null;
    if (!input) return;
    var min = parseInt(input.getAttribute('min'), 10) || 1;
    var max = parseInt(input.getAttribute('max'), 10) || 999;
    var val = parseInt(input.value, 10) || min;
    var delta = parseInt(btn.getAttribute('data-delta'), 10) || 1;
    val = Math.min(max, Math.max(min, val + delta));
    input.value = val;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  /* ---------- cart page: live quantity update via AJAX ---------- */
  var cartQtyInputs = document.querySelectorAll('.cart-qty-input');
  if (cartQtyInputs.length) {
    var timer = null;
    cartQtyInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          var fd = new URLSearchParams();
          fd.append('action', 'update');
          fd.append('product_id', input.getAttribute('data-product'));
          fd.append('qty', input.value);
          fetch(BASE + 'ajax/cart.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: fd,
            credentials: 'same-origin'
          })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (res.success) {
                window.location.reload();
              } else {
                toast(res.message || 'Could not update quantity.', 'error');
              }
            })
            .catch(function () { toast('Network error.', 'error'); });
        }, 550);
      });
    });
  }

  /* ---------- product gallery thumbs ---------- */
  var galleryMain = document.getElementById('galleryMain');
  if (galleryMain) {
    document.querySelectorAll('.gallery-thumb').forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        document.querySelectorAll('.gallery-thumb').forEach(function (t) { t.classList.remove('active'); });
        thumb.classList.add('active');
        galleryMain.style.opacity = '0';
        setTimeout(function () {
          galleryMain.src = thumb.getAttribute('data-src');
          galleryMain.style.opacity = '1';
        }, 160);
      });
    });
  }

  /* ---------- shop sort auto-submit ---------- */
  var sortSelect = document.getElementById('sortSelect');
  if (sortSelect) {
    sortSelect.addEventListener('change', function () {
      document.getElementById('sortForm').submit();
    });
  }

  /* ---------- checkout: payment toggle ---------- */
  var payCod = document.getElementById('payCod');
  var payCard = document.getElementById('payCard');
  var cardFields = document.getElementById('cardFields');
  function toggleCardFields() {
    if (cardFields && payCard) {
      cardFields.classList.toggle('d-none', !payCard.checked);
    }
  }
  if (payCod) payCod.addEventListener('change', toggleCardFields);
  if (payCard) payCard.addEventListener('change', toggleCardFields);
  toggleCardFields();

  /* ---------- newsletter ---------- */
  var newsletterForm = document.getElementById('newsletterForm');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var emailInput = document.getElementById('newsletterEmail');
      var msg = document.getElementById('newsletterMsg');
      var btn = document.getElementById('newsletterBtn');
      var email = (emailInput.value || '').trim();
      if (!email) return;

      var fd = new URLSearchParams();
      fd.append('email', email);
      btn.disabled = true;
      fetch(BASE + 'ajax/subscribe.php', {
        method: 'POST',
        headers: { 'X-CSRF-Token': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
        body: fd,
        credentials: 'same-origin'
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          msg.textContent = res.message;
          msg.className = 'form-text ' + (res.success ? 'text-success' : 'text-danger');
          if (res.success) emailInput.value = '';
        })
        .catch(function () { msg.textContent = 'Network error — please try again.'; })
        .finally(function () { btn.disabled = false; });
    });
  }

  /* ---------- password visibility toggles ---------- */
  document.querySelectorAll('.toggle-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-target'));
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = '<i class="bi bi-' + (show ? 'eye-slash' : 'eye') + '"></i>';
    });
  });

  /* ---------- client-side form validation hint ---------- */
  document.querySelectorAll('form[novalidate]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!form.checkValidity()) {
        ev.preventDefault();
        ev.stopPropagation();
        var firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) firstInvalid.reportValidity();
      }
    });
  });
})();
