'use strict';

// navbar variables
const nav = document.querySelector('.navbar-nav');
const navLinks = document.querySelectorAll('.nav-link');
const cartToggleBtn = document.querySelector('.shopping-cart-btn');
const navToggleBtn = document.querySelector('.menu-toggle-btn');
const shoppingCart = document.querySelector('.cart-box');

// nav toggle function
const navToggleFunc = function () {
  nav.classList.toggle('active');
  navToggleBtn.classList.toggle('active');
}

// shopping cart toggle function
const cartToggleFunc = function () { shoppingCart.classList.toggle('active') }
window.cartToggleFunc = cartToggleFunc;

// add event on nav-toggle-btn
// add event on nav-toggle-btn
if (navToggleBtn) {
  navToggleBtn.addEventListener('click', function () {

    // If the shopping-cart has an `active` class, it will be removed.
    if (shoppingCart && shoppingCart.classList.contains('active')) cartToggleFunc();

    navToggleFunc();

  });
}

// add event on cart-toggle-btn
// add event on cart-toggle-btn
if (cartToggleBtn) {
  cartToggleBtn.addEventListener('click', function () {

    // If the navbar-nav has an `active` class, it will be removed.
    if (nav.classList.contains('active')) navToggleFunc();

    cartToggleFunc();

  });
}

// add event on all nav-link
for (let i = 0; i < navLinks.length; i++) {

  navLinks[i].addEventListener('click', navToggleFunc);

}
// add event on cart-close-btn
const cartCloseBtn = document.querySelector('.cart-close-btn');
if (cartCloseBtn) {
  cartCloseBtn.addEventListener('click', cartToggleFunc);
}

// Close cart when clicking outside
// Close cart when clicking outside
document.addEventListener('click', function (event) {
  if (shoppingCart && shoppingCart.classList.contains('active')) {
    if (!shoppingCart.contains(event.target) && (cartToggleBtn && !cartToggleBtn.contains(event.target))) {
      cartToggleFunc();
    }
  }
});

// Checkout Animation
window.startCheckoutAnimation = function () {
  const cartLoader = document.getElementById('cartLoader');

  if (cartLoader) {
    cartLoader.classList.add('active');

    // Short delay for the premium feel
    setTimeout(() => {
      window.location.href = 'checkout.php';
    }, 1200);
  } else {
    window.location.href = 'checkout.php';
  }
};

// Global Loader Helper
window.showGlobalLoader = function (text, subtext) {
  const overlay = document.getElementById('globalLoadingOverlay');
  const loadingText = document.getElementById('globalLoadingText');
  const loadingSubtext = document.getElementById('globalLoadingSubtext');

  if (overlay) {
    // Show/hide text elements based on whether content is provided
    if (loadingText) {
      loadingText.innerText = text || '';
      loadingText.style.display = text ? 'block' : 'none';
    }
    if (loadingSubtext) {
      loadingSubtext.innerText = subtext || '';
      loadingSubtext.style.display = subtext ? 'block' : 'none';
    }
    overlay.classList.add('active');
  }
};

window.hideGlobalLoader = function () {
  const overlay = document.getElementById('globalLoadingOverlay');
  if (overlay) overlay.classList.remove('active');
};

// Handle back/forward cache to prevent stuck loader
window.addEventListener('pageshow', function (event) {
  if (event.persisted) {
    hideGlobalLoader();
  }
});
