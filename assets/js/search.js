document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const autocompleteList = document.getElementById('autocompleteList');
    const clearBtn = document.getElementById('clearSearch');
    const productCards = document.querySelectorAll('.product-card');

    // Use the productsData variable injected from PHP
    let products = [];
    if (typeof productsData !== 'undefined') {
        products = productsData;
    }

    searchInput.addEventListener('input', function (e) {
        const val = this.value;

        // Toggle clear button
        clearBtn.style.display = val ? 'block' : 'none';

        // Filter Grid
        filterGrid(val);

        // Autocomplete logic
        closeAllLists();
        if (!val) return false;

        const matches = products.filter(p => p.name.toLowerCase().includes(val.toLowerCase()));

        if (matches.length > 0) {
            matches.slice(0, 5).forEach(product => {
                const item = document.createElement('div');
                item.innerHTML = `
                    <img src="${product.image_url}" class="suggestion-thumb" alt="${product.name}">
                    <div class="suggestion-info">
                        <span class="suggestion-name">${highlightMatch(product.name, val)}</span>
                        <span class="suggestion-price">₱${product.price}</span>
                    </div>
                `;
                item.addEventListener('click', function () {
                    if (typeof openProductModal === 'function') {
                        // Handle color_images safely
                        let colorImages = product.color_images || null;
                        if (colorImages && typeof colorImages === 'string') {
                            try { JSON.parse(colorImages); } catch (e) { colorImages = null; }
                        }

                        openProductModal(
                            product.name,
                            product.price,
                            product.image_url,
                            product.description,
                            product.colors || '',
                            product.available_sizes || '',
                            colorImages
                        );
                    }
                    searchInput.value = product.name;
                    filterGrid(product.name);
                    closeAllLists();
                });
                autocompleteList.appendChild(item);
            });
        }
    });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        this.style.display = 'none';
        filterGrid('');
        closeAllLists();
        searchInput.focus();
    });

    // Close list when clicking outside
    document.addEventListener('click', function (e) {
        if (e.target !== searchInput && e.target !== autocompleteList) {
            closeAllLists();
        }
    });

    function filterGrid(searchTerm) {
        if (typeof window.applyFacetFilters === 'function') {
            window.applyFacetFilters();
        } else {
            // Fallback if menu.js isn't loaded or ready
            const term = searchTerm.toLowerCase();
            let visibleCount = 0;
            productCards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                if (name.includes(term)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            const countEl = document.getElementById('productCount');
            const noResults = document.getElementById('noResults');
            if (countEl) countEl.textContent = `${visibleCount} products`;
            if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function closeAllLists() {
        autocompleteList.innerHTML = '';
    }

    function highlightMatch(text, term) {
        const regex = new RegExp(`(${term})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }
});
