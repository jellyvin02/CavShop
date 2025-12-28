function subTotal() {
    console.log("Running subTotal()");

    var gt = 0;

    // Adjust the quantity based on the action
    if (typeof action !== 'undefined' && typeof currentQuantity !== 'undefined' && typeof quantityInput !== 'undefined') {
        if (action === "add" && currentQuantity < 10) {
            quantityInput.value = currentQuantity + 1;
        } else if (action === "subtract" && currentQuantity > 1) {
            quantityInput.value = currentQuantity - 1;
        }
    }

    // Use AJAX to update the cart on the server side
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "manage_cart.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Successfully updated the cart, update subtotal
            // subTotal(); // Recursive call? Be careful.
        }
    };

    if (typeof quantityInput !== 'undefined') {
        xhr.send("Item_name=" + encodeURIComponent(quantityInput.name) +
            "&Mod_Quantity=" + encodeURIComponent(quantityInput.value));
    }
}

// Update Modal Price Calculation
function updateModalPrice() {
    const basePrice = parseFloat(document.getElementById('modalBasePrice').value) || 0;
    const selectedSize = document.querySelector('input[name="size"]:checked');
    const quantity = parseInt(document.getElementById('modalQuantity').value) || 1;

    // Calculate size price with proper modifier
    const sizeModifier = selectedSize ? parseFloat(selectedSize.dataset.priceModifier) || 1.0 : 1.0;
    const sizedPrice = basePrice * sizeModifier;
    const sizeAdjustment = sizedPrice - basePrice;

    // Calculate toppings total (if any)
    const toppingsTotal = Array.from(document.querySelectorAll('input[name="toppings[]"]:checked'))
        .reduce((sum, topping) => sum + (parseFloat(topping.dataset.price) || 0), 0);

    // Calculate final total
    const totalPrice = (sizedPrice + toppingsTotal) * quantity;

    // Update displays
    const baseSubtotal = document.getElementById('baseSubtotal');
    if (baseSubtotal) baseSubtotal.textContent = basePrice.toFixed(2);

    const adjustmentEl = document.getElementById('sizeAdjustment');
    if (adjustmentEl) {
        if (sizeAdjustment >= 0) {
            adjustmentEl.textContent = '+₱' + sizeAdjustment.toFixed(2);
            adjustmentEl.style.color = '#286816';
        } else {
            adjustmentEl.textContent = '-₱' + Math.abs(sizeAdjustment).toFixed(2);
            adjustmentEl.style.color = '#dc3545';
        }
    }

    const modalTotal = document.getElementById('modalTotalPrice');
    if (modalTotal) modalTotal.textContent = totalPrice.toFixed(2);

    // Also update legacy IDs if they exist
    const sizeSub = document.getElementById('sizeSubtotal');
    if (sizeSub) sizeSub.textContent = sizedPrice.toFixed(2);
    const toppingsSub = document.getElementById('toppingsSubtotal');
    if (toppingsSub) toppingsSub.textContent = toppingsTotal.toFixed(2);
}

// Adjust Quantity
function adjustQuantity(action) {
    const quantityInput = document.getElementById('modalQuantity');
    let currentQuantity = parseInt(quantityInput.value) || 1;

    if (action === 'add' && currentQuantity < 10) {
        quantityInput.value = currentQuantity + 1;
    } else if (action === 'subtract' && currentQuantity > 1) {
        quantityInput.value = currentQuantity - 1;
    }

    updateModalPrice();
}

// Update Product Image based on selected color
function updateProductImageByColor(colorName) {
    const modal = document.getElementById('productModal');
    const productImage = document.getElementById('modalProductImage');
    const defaultImage = document.getElementById('modalImageUrl').value;

    if (!modal || !productImage) return;

    // Default to base image
    let newImage = defaultImage;

    // Check for color-specific image
    if (modal.dataset.colorImages) {
        try {
            const colorImages = JSON.parse(modal.dataset.colorImages);
            if (colorImages && colorImages[colorName]) {
                newImage = colorImages[colorName];
            }
        } catch (e) {
            console.error("Error parsing color images JSON", e);
        }
    }

    productImage.src = newImage;
}

// Helper to setup listeners for dynamic elements
function setupOptionListeners() {
    // Color selection
    const colorOptions = document.querySelectorAll('.color-option');
    colorOptions.forEach(option => {
        option.onclick = function () {
            document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            const input = this.querySelector('input');
            if (input) {
                input.checked = true;
                // Update image based on color
                const colorValue = input.value;
                updateProductImageByColor(colorValue);
            }
        };
    });

    // Size selection
    const sizeOptions = document.querySelectorAll('.size-option');
    sizeOptions.forEach(option => {
        option.onclick = function () {
            document.querySelectorAll('.size-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            const input = this.querySelector('input');
            if (input) {
                input.checked = true;
                updateModalPrice();
            }
        };
    });

    // Quantity input manual change
    const quantityInput = document.getElementById('modalQuantity');
    if (quantityInput) {
        quantityInput.onchange = function () {
            let value = parseInt(this.value) || 1;
            if (value < 1) value = 1;
            if (value > 10) value = 10;
            this.value = value;
            updateModalPrice();
        };
    }
}

function getColorCode(colorName) {
    const map = {
        'Navy Blue': '#001f3f',
        'Black': '#000000',
        'White': '#FFFFFF',
        'Red': '#DC3545',
        'Green': '#28a745',
        'Gray': '#6c757d',
        'Brown': '#8B4513',
        'Beige': '#F5F5DC'
    };
    return map[colorName] || colorName;
}

function getSizeModifier(size) {
    const map = {
        'XS': 0.9, 'S': 0.95, 'M': 1.0, 'L': 1.1, 'XL': 1.2, '2XL': 1.3
    };
    return map[size] || 1.0;
}

function getSizeName(size) {
    const map = {
        'XS': 'Extra Small', 'S': 'Small', 'M': 'Medium', 'L': 'Large',
        'XL': 'Extra Large', '2XL': 'Double XL'
    };
    return map[size] || size;
}

function openProductModal(itemName, basePrice, imageUrl, description, colorsStr, sizesStr, colorImagesJSON = null) {
    const modal = document.getElementById('productModal');
    if (!modal) return;

    // Store color images map in dataset
    if (colorImagesJSON) {
        // If it's an object, stringify it, otherwise keep if string
        modal.dataset.colorImages = typeof colorImagesJSON === 'object' ? JSON.stringify(colorImagesJSON) : colorImagesJSON;
    } else {
        delete modal.dataset.colorImages;
    }

    // Set basic info
    document.getElementById('modalItemName').value = itemName;
    document.getElementById('modalBasePrice').value = basePrice;
    document.getElementById('modalImageUrl').value = imageUrl;
    document.getElementById('modalTitle').textContent = itemName;
    document.getElementById('modalProductImage').src = imageUrl;
    document.getElementById('modalProductDescription').textContent = description;
    document.getElementById('modalProductTitle').textContent = itemName;
    document.getElementById('modalStartPrice').textContent = parseFloat(basePrice).toFixed(2);

    // Dynamic Colors
    const colorContainer = document.querySelector('.color-options-grid');
    if (colorContainer) {
        colorContainer.innerHTML = '';
        const colors = (colorsStr && colorsStr.trim() !== '') ? colorsStr.split(',').map(s => s.trim()) : ['Black', 'White', 'Navy Blue'];

        colors.forEach((colorEntry, index) => {
            let displayColor = colorEntry;
            let hexCode = null;

            if (colorEntry.includes(':')) {
                const parts = colorEntry.split(':');
                displayColor = parts[0].trim();
                hexCode = parts[1].trim();
            }

            const isChecked = index === 0;
            const finalHex = hexCode || getColorCode(displayColor);

            const html = `
                <label class="color-option ${isChecked ? 'selected' : ''}">
                    <input type="radio" name="color" value="${displayColor}" ${isChecked ? 'checked' : ''}>
                    <span class="color-swatch" style="background-color: ${finalHex}; ${displayColor === 'White' ? 'border:1px solid #ddd;' : ''}"></span>
                    <span class="color-label">${displayColor}</span>
                </label>`;
            colorContainer.insertAdjacentHTML('beforeend', html);
        });
    }

    // Dynamic Sizes
    const sizeContainer = document.querySelector('.size-options-grid');
    if (sizeContainer) {
        sizeContainer.innerHTML = '';
        const sizes = (sizesStr && sizesStr.trim() !== '') ? sizesStr.split(',').map(s => s.trim()) : ['XS', 'S', 'M', 'L', 'XL', '2XL'];

        // Find 'M' to set default, otherwise first
        let defaultIndex = sizes.indexOf('M');
        if (defaultIndex === -1 && sizes.length > 0) defaultIndex = 0;

        sizes.forEach((size, index) => {
            const isChecked = index === defaultIndex;
            const modifier = getSizeModifier(size);
            const html = `
                <label class="size-option ${isChecked ? 'selected' : ''}">
                    <input type="radio" name="size" value="${size}" data-price-modifier="${modifier}" ${isChecked ? 'checked' : ''}>
                    <span class="size-box">
                        <span class="size-letter">${size}</span>
                        <span class="size-name">${getSizeName(size)}</span>
                    </span>
                </label>`;
            sizeContainer.insertAdjacentHTML('beforeend', html);
        });
    }

    // Quantity Reset
    const qty = document.getElementById('modalQuantity');
    if (qty) qty.value = 1;

    // Re-attach listeners to new elements
    setupOptionListeners();

    // Update image for initial selected color
    const initialColor = document.querySelector('input[name="color"]:checked');
    if (initialColor) {
        updateProductImageByColor(initialColor.value);
    }

    // Show Modal
    modal.style.display = "block";
    document.body.style.overflow = "hidden";

    // Initial Price Update
    updateModalPrice();
}

// Global function to close the product modal
window.hideProductModal = function () {
    const modal = document.getElementById('productModal');
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = "";
    }
};

// Global Event Listeners (One-time setup)
document.addEventListener('DOMContentLoaded', function () {
    // Modal close handler
    const modal = document.getElementById('productModal');
    const closeBtn = document.querySelector('#productModal .close');

    if (closeBtn && modal) {
        closeBtn.onclick = function () {
            window.hideProductModal();
        }
    }

    // Close modal when clicking outside
    if (modal) {
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
                document.body.style.overflow = "";
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.style.display === 'block') {
                modal.style.display = "none";
                document.body.style.overflow = "";
            }
        });
    }

    // Remove duplicate showSuccessToast function, keep only one version
    const toast = document.getElementById("success-toast");
    if (toast) {
        toast.classList.add("show");
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => toast.remove(), 1000);
        }, 1000);
    }
});

// Ensure subTotal is called after page load
window.onload = function () {
    if (typeof subTotal === 'function') subTotal();
};

// Ripple Effect Handler
document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', function (e) {
        const ripple = this.querySelector('.ripple');
        // Only trigger if it's not the submit button inside form
        if (this.type === 'submit') return;

        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
        ripple.style.top = e.clientY - rect.top - size / 2 + 'px';
        ripple.classList.add('ripple-active');

        setTimeout(() => {
            ripple.classList.remove('ripple-active');
            ripple.style.width = ripple.style.height = '0px';
            ripple.style.opacity = '0';
        }, 800);
    });
});

// Scroll to Top functionality
const scrollBtn = document.getElementById('scrollToTop');
if (scrollBtn) {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollBtn.classList.add('visible');
        } else {
            scrollBtn.classList.remove('visible');
        }
    });

    scrollBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Facet Navigation JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const categoryFilters = document.querySelectorAll('.category-filter');
    const priceFilters = document.querySelectorAll('.price-filter');
    const sizeFilters = document.querySelectorAll('.size-filter');
    const colorFilters = document.querySelectorAll('.color-filter');
    const productCards = document.querySelectorAll('.product-card');
    const clearAllFiltersBtn = document.getElementById('clearAllFilters');
    const sortSelect = document.getElementById('sortProducts');
    const productCountEl = document.getElementById('productCount');
    const noResults = document.getElementById('noResults');
    const activeFiltersSection = document.getElementById('activeFilters');
    const filterTagsContainer = document.getElementById('filterTags');
    const searchInput = document.getElementById('searchInput');

    // Collapsible sections
    const facetSections = document.querySelectorAll('.facet-section');
    if (facetSections.length > 0) {
        facetSections.forEach(section => {
            const header = section.querySelector('.facet-section-header');
            if (header) {
                header.addEventListener('click', function () {
                    section.classList.toggle('collapsed');
                });
            }
        });
    }

    // Mobile Filter Logic
    const mobileFilterToggle = document.getElementById('mobileFilterToggle');
    const closeMobileFilter = document.getElementById('closeMobileFilter');
    const facetNavigation = document.getElementById('facetNavigation');
    const filterOverlay = document.getElementById('filterOverlay');

    function toggleMobileFilter(show) {
        if (!facetNavigation) return;

        if (show) {
            facetNavigation.classList.add('active');
            if (filterOverlay) filterOverlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling background
        } else {
            facetNavigation.classList.remove('active');
            if (filterOverlay) filterOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (mobileFilterToggle) {
        mobileFilterToggle.addEventListener('click', () => toggleMobileFilter(true));
    }

    if (closeMobileFilter) {
        closeMobileFilter.addEventListener('click', () => toggleMobileFilter(false));
    }

    if (filterOverlay) {
        filterOverlay.addEventListener('click', () => toggleMobileFilter(false));
    }

    // Only proceed if elements exist
    if (!productCards.length) return;

    // Initialize counts
    updateFacetCounts();

    // Category filter change
    categoryFilters.forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    // Price filter change
    priceFilters.forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    // Size filter change
    sizeFilters.forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    // Color filter change
    colorFilters.forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    // Clear all filters
    if (clearAllFiltersBtn) {
        clearAllFiltersBtn.addEventListener('click', function () {
            categoryFilters.forEach(f => f.checked = false);
            priceFilters.forEach(f => f.checked = false);
            sizeFilters.forEach(f => f.checked = false);
            colorFilters.forEach(f => f.checked = false);
            if (searchInput) {
                searchInput.value = '';
                const clearSearchBtn = document.getElementById('clearSearch');
                if (clearSearchBtn) clearSearchBtn.style.display = 'none';
            }
            applyFilters();
        });
    }

    // Search input change
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    // Sort products
    if (sortSelect) {
        sortSelect.addEventListener('change', sortProducts);
    }

    function applyFilters() {
        const selectedCategories = Array.from(categoryFilters)
            .filter(f => f.checked)
            .map(f => f.value);

        const selectedPrices = Array.from(priceFilters)
            .filter(f => f.checked)
            .map(f => f.value);

        const selectedSizes = Array.from(sizeFilters)
            .filter(f => f.checked)
            .map(f => f.value);

        const selectedColors = Array.from(colorFilters)
            .filter(f => f.checked)
            .map(f => f.value);

        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';

        let visibleCount = 0;

        productCards.forEach(card => {
            const cardName = card.dataset.name.toLowerCase();
            const cardCategory = card.dataset.category;
            const cardPrice = parseFloat(card.dataset.price);
            const cardSizes = card.dataset.sizes ? card.dataset.sizes.split(',').map(s => s.trim()) : [];
            const cardColors = card.dataset.colors ? card.dataset.colors.split(',').map(c => c.trim()) : [];

            let searchMatch = searchTerm === '' || cardName.includes(searchTerm);
            let categoryMatch = selectedCategories.length === 0 || selectedCategories.includes(cardCategory);
            let priceMatch = selectedPrices.length === 0 || selectedPrices.some(range => {
                const [min, max] = range.split('-').map(Number);
                return cardPrice >= min && cardPrice <= max;
            });
            let sizeMatch = selectedSizes.length === 0 || selectedSizes.some(s => cardSizes.includes(s));
            let colorMatch = selectedColors.length === 0 || selectedColors.some(c => cardColors.includes(c));

            if (searchMatch && categoryMatch && priceMatch && sizeMatch && colorMatch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        // Update product count
        if (productCountEl) productCountEl.textContent = `${visibleCount} product${visibleCount !== 1 ? 's' : ''}`;

        // Show/hide no results message
        if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';

        // Update active filters
        updateActiveFilters(selectedCategories, selectedPrices, selectedSizes, selectedColors);

        // Update facet counts
        updateFacetCounts();
    }

    // Expose applyFilters globally so search.js can trigger it
    window.applyFacetFilters = applyFilters;

    function updateActiveFilters(categories, prices, sizes, colors) {
        if (!filterTagsContainer || !activeFiltersSection) return;

        filterTagsContainer.innerHTML = '';

        if (categories.length === 0 && prices.length === 0 && sizes.length === 0 && colors.length === 0) {
            activeFiltersSection.style.display = 'none';
            return;
        }

        activeFiltersSection.style.display = 'block';

        categories.forEach(catId => {
            const filter = document.querySelector(`.category-filter[value="${catId}"]`);
            if (filter) {
                const tag = createFilterTag(filter.dataset.category, () => {
                    filter.checked = false;
                    applyFilters();
                });
                filterTagsContainer.appendChild(tag);
            }
        });

        prices.forEach(priceRange => {
            const filter = document.querySelector(`.price-filter[value="${priceRange}"]`);
            if (filter) {
                const label = filter.nextElementSibling.textContent;
                const tag = createFilterTag(label, () => {
                    filter.checked = false;
                    applyFilters();
                });
                filterTagsContainer.appendChild(tag);
            }
        });

        sizes.forEach(size => {
            const filter = document.querySelector(`.size-filter[value="${size}"]`);
            if (filter) {
                const tag = createFilterTag(`Size: ${size}`, () => {
                    filter.checked = false;
                    applyFilters();
                });
                filterTagsContainer.appendChild(tag);
            }
        });

        colors.forEach(color => {
            const filter = document.querySelector(`.color-filter[value="${color}"]`);
            if (filter) {
                const tag = createFilterTag(`Color: ${color}`, () => {
                    filter.checked = false;
                    applyFilters();
                });
                filterTagsContainer.appendChild(tag);
            }
        });
    }

    function createFilterTag(text, onRemove) {
        const tag = document.createElement('span');
        tag.className = 'filter-tag';
        tag.innerHTML = `${text} <i class="fas fa-times"></i>`;
        tag.querySelector('i').addEventListener('click', onRemove);
        return tag;
    }

    function updateFacetCounts() {
        // Update category counts
        categoryFilters.forEach(filter => {
            const categoryId = filter.value;
            const count = Array.from(productCards).filter(card =>
                !card.classList.contains('hidden') && card.dataset.category === categoryId
            ).length;
            const countEl = document.querySelector(`.facet-count[data-category-id="${categoryId}"]`);
            if (countEl) countEl.textContent = count;
        });

        // Update price counts
        priceFilters.forEach(filter => {
            const [min, max] = filter.value.split('-').map(Number);
            const count = Array.from(productCards).filter(card => {
                if (card.classList.contains('hidden')) return false;
                const price = parseFloat(card.dataset.price);
                return price >= min && price <= max;
            }).length;
            const countEl = document.querySelector(`.facet-count[data-price-range="${filter.value}"]`);
            if (countEl) countEl.textContent = count;
        });

        // Update size counts
        sizeFilters.forEach(filter => {
            const size = filter.value;
            const count = Array.from(productCards).filter(card => {
                if (card.classList.contains('hidden')) return false;
                const cardSizes = card.dataset.sizes ? card.dataset.sizes.split(',').map(s => s.trim()) : [];
                return cardSizes.includes(size);
            }).length;
            // Use CSS.escape() to handle special characters like quotes in size values
            const countEl = document.querySelector(`.facet-count[data-size="${CSS.escape(size)}"]`);
            if (countEl) countEl.textContent = count;
        });

        // Update color counts
        colorFilters.forEach(filter => {
            const color = filter.value;
            const count = Array.from(productCards).filter(card => {
                if (card.classList.contains('hidden')) return false;
                const cardColors = card.dataset.colors ? card.dataset.colors.split(',').map(c => c.trim()) : [];
                return cardColors.includes(color);
            }).length;
            // Use CSS.escape() to handle special characters in color values
            const countEl = document.querySelector(`.facet-count-badge[data-color="${CSS.escape(color)}"]`);
            if (countEl) {
                countEl.textContent = count;
                countEl.style.display = count > 0 ? 'block' : 'none';
            }
        });
    }

    function sortProducts() {
        const sortValue = sortSelect.value;
        const cardsArray = Array.from(productCards);
        const grid = document.getElementById('productsGrid');
        if (!grid) return;

        cardsArray.sort((a, b) => {
            switch (sortValue) {
                case 'name-asc':
                    return a.dataset.name.localeCompare(b.dataset.name);
                case 'name-desc':
                    return b.dataset.name.localeCompare(a.dataset.name);
                case 'price-asc':
                    return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                case 'price-desc':
                    return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                default:
                    return 0;
            }
        });

        cardsArray.forEach(card => grid.appendChild(card));
    }

    // Initial product count
    if (productCountEl) productCountEl.textContent = `${productCards.length} product${productCards.length !== 1 ? 's' : ''}`;

    // Handle initial category filter from URL (e.g. from index.php)
    const urlParams = new URLSearchParams(window.location.search);
    const categoryId = urlParams.get('category');
    if (categoryId) {
        const checkbox = document.querySelector(`.category-filter[value="${categoryId}"]`);
        if (checkbox) {
            checkbox.checked = true;
            applyFilters();
        }
    }
});
// Buy Now Functionality
function buyNow(btn) {
    const form = document.getElementById('productForm');
    if (!form) return;

    // Use passed button or find it
    const buyNowBtn = btn || form.querySelector('.buy-now-btn');

    if (buyNowBtn) {
        // Trigger minimalist global loader
        if (typeof showGlobalLoader === 'function') {
            showGlobalLoader('', '');
        }

        // Disable button to prevent double clicks
        buyNowBtn.disabled = true;
    }

    const formData = new FormData(form);

    // Ensure all required fields are present
    // Base price, item name, image url are hidden inputs
    // Quantity, size, color are selected

    // Add ajax flag
    formData.append('ajax', 'true');
    formData.append('Add_To_Cart', '1'); // Ensure this is sent even if button wasn't submit

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_cart.php', true);

    xhr.onload = function () {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    // Short delay for the premium feel, matching floating cart
                    setTimeout(() => {
                        window.location.href = 'checkout.php';
                    }, 1200);
                } else {
                    if (typeof hideGlobalLoader === 'function') hideGlobalLoader();
                    alert('Error adding item to cart: ' + (response.error || 'Unknown error'));
                    if (buyNowBtn) buyNowBtn.disabled = false;
                }
            } catch (e) {
                console.error("Error parsing response", e);
                // Fallback: if not JSON, maybe it was a redirect or standard HTML response?
                // Just redirect with a delay for consistency
                setTimeout(() => {
                    window.location.href = 'checkout.php';
                }, 1200);
            }
        } else {
            handleBuyNowError(buyNowBtn);
        }
    };

    xhr.onerror = function () {
        console.error("Request failed");
        handleBuyNowError(buyNowBtn);
    };

    xhr.send(formData);
}

function handleBuyNowError(btn) {
    if (typeof hideGlobalLoader === 'function') hideGlobalLoader();
    alert('Network error. Please try again.');
    if (btn) btn.disabled = false;
}
