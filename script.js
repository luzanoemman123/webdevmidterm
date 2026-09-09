function showForm(formId) {
    document.querySelectorAll('.form-box').forEach(form => {
        form.classList.remove('active');
    });

    const form = document.getElementById(formId);

    if (form) {
        form.classList.add('active');
    }
}

// --- Cart page: +/- quantity stepper buttons. Adjusts the number input
// next to the button and fires a native 'change' event, which triggers
// the existing onchange="this.form.submit()" handler already on the
// input — so this stays consistent with the page's normal full-submit
// quantity-update flow instead of introducing a separate code path. ---
function stepQty(button, delta) {
    const wrapper = button.closest('.qty-stepper');
    if (!wrapper) return;
    const input = wrapper.querySelector('input[type="number"]');
    if (!input) return;

    const min = parseInt(input.min || '1', 10);
    let value = parseInt(input.value, 10);
    if (isNaN(value)) value = min;
    value += delta;
    if (value < min) value = min;

    input.value = value;
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

document.addEventListener('DOMContentLoaded', () => {
  const targetMap = {
    '#home': '#home',
    '#products': '#products',
    '#about': '#about',
    '#gallery': '#gallery',
    '#contact': '#contact',
  };

  const scrollToSection = (selector) => {
    const section = document.querySelector(selector);
    if (section) {
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  };

  const navItems = document.querySelectorAll('.navbar nav .nav-link');
  navItems.forEach((item) => {
    item.addEventListener('click', (event) => {
      const href = item.getAttribute('href');
      if (!href || !targetMap[href]) {
        return;
      }

      event.preventDefault();
      window.history.pushState(null, '', href);
      scrollToSection(href);
    });
  });

  const actionButtons = document.querySelectorAll('.red-btn');
  actionButtons.forEach((button) => {
    if (button.type === 'submit') {
      return;
    }

    button.addEventListener('click', () => {
      const text = button.textContent.trim();

      if (text.includes('EXPLORE GEAR')) {
        scrollToSection('.water-section');
        return;
      }

      if (text.includes('EXPLORE COLLECTION')) {
        scrollToSection('.lookbook-section');
        return;
      }

      if (text.includes('FULL COLLECTION')) {
        scrollToSection('.lookbook-section');
        return;
      }

      scrollToSection('.contact-section');
    });
  });

});
// --- Add-to-cart: intercept the form, hit add_to_cart.php via fetch,
// then show a confirmation modal and update the header cart badge instead
// of doing a full page reload. Falls back to a normal form submit if the
// fetch call fails for any reason. ---
function initAddToCart() {
    const forms = document.querySelectorAll('.product-card-form, .product-detail-form');

    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'ADDING...';
            }

            try {
                const response = await fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                const data = await response.json();

                if (data.success) {
                    updateCartBadge(data.cart_count);
                    showCartModal(data.product);
                } else {
                    alert(data.message || 'Could not add that product to your cart.');
                }
            } catch (err) {
                form.submit();
                return;
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
        });
    });
}

function updateCartBadge(count) {
    const badge = document.getElementById('cart-count-badge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-flex' : 'none';
}

function showCartModal(product) {
    const overlay = document.getElementById('cart-modal-overlay');
    if (!overlay || !product) return;

    const image = document.getElementById('cart-modal-image');
    image.src = 'images/' + product.image;
    image.alt = product.name;
    document.getElementById('cart-modal-title').textContent = product.name;
    document.getElementById('cart-modal-qty').textContent = 'Quantity: ' + product.quantity_added;
    document.getElementById('cart-modal-price').textContent =
        '₱' + Number(product.price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const subtotalEl = document.getElementById('cart-modal-subtotal');
    if (subtotalEl) {
        const subtotal = Number(product.price) * Number(product.quantity_added);
        subtotalEl.textContent =
            'Subtotal: ₱' + subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCartModal() {
    const overlay = document.getElementById('cart-modal-overlay');
    if (!overlay) return;
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', () => {
    initAddToCart();

    const closeBtn = document.getElementById('cart-modal-close');
    const continueBtn = document.getElementById('cart-modal-continue');
    const overlay = document.getElementById('cart-modal-overlay');

    if (closeBtn) closeBtn.addEventListener('click', closeCartModal);
    if (continueBtn) continueBtn.addEventListener('click', closeCartModal);
    if (overlay) {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) closeCartModal();
        });
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeCartModal();
    });
});