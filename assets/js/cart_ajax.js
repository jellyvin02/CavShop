/**
 * AJAX Shopping Cart Implementation for CavShop
 * Handles all cart interactions without page reloads
 */

'use strict';

const CartManager = {
    // Configuration
    endpoints: {
        manage: 'manage_cart.php',
    },

    // Selectors
    selectors: {
        cartBox: '.cart-box',
        cartList: '.cart-box-ul',
        cartCount: '.shopping-cart-btn .count',
        forms: 'form[action="manage_cart.php"]',
        removeBtns: '.remove-button',
        qtyInputs: '.iquantity',
        addToCartBtn: 'button[name="Add_To_Cart"]'
    },

    init() {
        this.bindEvents();
        console.log('Cart Manager Initialized');
    },

    bindEvents() {
        // Delegate events for elements inside cart (using cartBox as stable container)
        const cartBox = document.querySelector(this.selectors.cartBox);
        if (cartBox) {
            cartBox.addEventListener('click', (e) => {
                const removeBtn = e.target.closest(this.selectors.removeBtns);
                if (removeBtn) {
                    e.preventDefault();
                    // Inline onclick is handled via global wrapper, but we catch clicks here too
                    // if they come from templates without onclick
                }

                // Toggle cart with close button if replaced dynamically
                const closeBtn = e.target.closest('.cart-close-btn');
                if (closeBtn && window.cartToggleFunc) {
                    window.cartToggleFunc();
                }
            });
        }

        // Intercept all "Add to Cart" form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.matches(this.selectors.forms)) {
                // specific check for add to cart forms
                if (e.target.querySelector('input[name="Add_To_Cart"]')) {
                    e.preventDefault();
                    this.addToCart(new FormData(e.target));
                }
            }
        });
    },

    // Load Cart HTML
    async loadCart() {
        try {
            const response = await fetch(`${this.endpoints.manage}?get_cart_html=true`);
            const data = await response.json();

            if (data.html) {
                const cartBox = document.querySelector(this.selectors.cartBox);
                if (cartBox) {
                    cartBox.innerHTML = data.html;
                }

                // Update Badge Count
                const counters = document.querySelectorAll(this.selectors.cartCount);
                counters.forEach(el => {
                    if (el.textContent !== data.count.toString()) {
                        el.textContent = data.count;
                        this.animateBadge(el);
                    }
                });
            }
        } catch (error) {
            console.error('Error loading cart:', error);
        }
    },

    animateBadge(el) {
        el.classList.remove('pop');
        void el.offsetWidth; // Trigger reflow
        el.classList.add('pop');
        setTimeout(() => el.classList.remove('pop'), 400);
    },

    // Add Item
    async addToCart(formData) {
        // Add ajax flag
        formData.append('ajax', 'true');
        formData.append('Add_To_Cart', 'true');

        try {
            const response = await fetch(this.endpoints.manage, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                // Auto-close the modal immediately for better UX
                if (window.hideProductModal) window.hideProductModal();

                await this.loadCart();
                this.showToast('Item added to cart!', 'success');
            } else {
                this.showToast('Failed to add item', 'error');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
        }
    },

    // Update Quantity
    async updateQuantity(key, newQty) {
        if (newQty < 1 || newQty > 10) return;

        const formData = new FormData();
        formData.append('Mod_Quantity', newQty);
        formData.append('cart_key', key);
        formData.append('ajax', 'true');

        try {
            const response = await fetch(this.endpoints.manage, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                await this.loadCart();
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
        }
    },

    // Remove Item
    async removeItem(key) {
        // Trigger remove animation
        const itemElement = document.getElementById(`cart-item-${key}`);
        if (itemElement) {
            itemElement.classList.add('removing');
        }

        // Wait for animation to complete
        await new Promise(resolve => setTimeout(resolve, 300));

        const formData = new FormData();
        formData.append('remove_item', 'true');
        formData.append('cart_key', key);
        formData.append('ajax', 'true');

        try {
            const response = await fetch(this.endpoints.manage, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                await this.loadCart();
                this.showToast('Item removed', 'success');
            }
        } catch (error) {
            console.error('Error removing item:', error);
            if (itemElement) itemElement.classList.remove('removing');
        }
    },

    openCart() {
        const cartBox = document.querySelector(this.selectors.cartBox);
        if (cartBox && !cartBox.classList.contains('active')) {
            cartBox.classList.add('active');
        }
    },

    showToast(message, type = 'success') {
        const duration = 1000;
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        const color = type === 'success' ? '#286816' : '#dc3545';

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${icon} toast-icon" style="color: ${color}"></i>
                <div class="toast-message">${message}</div>
            </div>
            <div class="toast-progress" style="background: ${color}; animation-duration: ${duration}ms"></div>
        `;

        document.body.appendChild(toast);

        // Use timeout to allow transition
        setTimeout(() => toast.classList.add('show'), 10);

        // Remove after delay
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 1000);
        }, duration);
    }
};

// Global wrappers for inline onclick handlers from PHP HTML
window.updateCartQuantity = (key, change) => {
    const input = document.querySelector(`#cart-item-${key} .iquantity`);
    if (input) {
        const currentVal = parseInt(input.value);
        CartManager.updateQuantity(key, currentVal + change);
    }
};

window.removeCartItem = (key) => {
    CartManager.removeItem(key);
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    CartManager.init();
});
