<?php
session_start();
require "includes/header.php"; 
require_once "includes/connection.php"; 

// Fetch categories
$categories_result = mysqli_query($conn, "SELECT * FROM categories");
$categories_array = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

// Fetch all products with category info
$all_products_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       ORDER BY c.id, p.name";
$all_products_result = mysqli_query($conn, $all_products_query);
$all_products = mysqli_fetch_all($all_products_result, MYSQLI_ASSOC);

// Extract all unique colors and sizes for facet filtering
$all_colors = [];
$all_sizes = [];
foreach ($all_products as $product) {
    if (!empty($product['colors'])) {
        $colors = explode(',', $product['colors']);
        foreach ($colors as $color) {
            $c = trim($color);
            if (!empty($c)) $all_colors[] = $c;
        }
    }
    if (!empty($product['available_sizes'])) {
        $sizes = explode(',', $product['available_sizes']);
        foreach ($sizes as $size) {
            $s = trim($size);
            if (!empty($s)) $all_sizes[] = $s;
        }
    }
}
$unique_colors = array_unique($all_colors);
sort($unique_colors);
$unique_sizes = array_unique($all_sizes);
sort($unique_sizes);

function get_facet_color_hex($color_name) {
    if (strpos($color_name, ':') !== false) {
        $parts = explode(':', $color_name);
        return trim($parts[1]);
    }
    $map = [
        'Navy Blue' => '#001f3f', 'Black' => '#000000', 'White' => '#FFFFFF',
        'Red' => '#DC3545', 'Green' => '#28a745', 'Gray' => '#6c757d',
        'Brown' => '#8B4513', 'Beige' => '#F5F5DC'
    ];
    return $map[$color_name] ?? '#ddd';
}

// Check for cart success and remove success
if (isset($_SESSION['cart_success']) || isset($_SESSION['remove_success'])) {
    $message = isset($_SESSION['cart_success']) ? "Item added to cart!" : "Item removed from cart!";
    $icon = isset($_SESSION['cart_success']) ? "check-circle" : "minus-circle";
    
    echo '<div id="success-toast" class="toast">
            <div class="toast-content">
                <i class="fas fa-' . $icon . ' toast-icon"></i>
                <div class="toast-message">' . $message . '</div>
            </div>
            <div class="toast-progress"></div>
          </div>';
          
    unset($_SESSION['cart_success']);
    unset($_SESSION['remove_success']);
}
?>

<link rel="stylesheet" href="assets/css/menu.css">
<div class="filter-overlay" id="filterOverlay"></div>
<!-- Home Section -->
<section class="home" id="home">
    <div class="home-left">
        <!-- Content for the home-left section can be added here -->
    </div>
</section>

<!-- Product Section -->
<section class="product" id="menu">
    <!-- Facet Navigation Panel -->
    <div class="facet-navigation" id="facetNavigation">
        <div class="mobile-filter-header">
            <h3>Filters</h3>
            <button id="closeMobileFilter" class="close-mobile-filter">&times;</button>
        </div>
        <div class="facet-header">
            <h3><i class="fas fa-filter"></i> Filter Products</h3>
        </div>

        <div class="facet-section">
            <div class="facet-section-header">
                <h4>Categories</h4>
                <span class="collapse-icon"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="facet-options">
                <?php foreach ($categories_array as $category): ?>
                    <label class="facet-option">
                        <input type="checkbox" class="category-filter" value="<?= $category['id'] ?>" data-category="<?= htmlspecialchars($category['name']) ?>">
                        <span class="facet-label"><?= htmlspecialchars($category['name']) ?></span>
                        <span class="facet-count" data-category-id="<?= $category['id'] ?>">0</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="facet-section">
            <div class="facet-section-header">
                <h4>Price Range</h4>
                <span class="collapse-icon"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="facet-options">
                <label class="facet-option">
                    <input type="checkbox" class="price-filter" value="0-500">
                    <span class="facet-label">Under ₱500</span>
                    <span class="facet-count" data-price-range="0-500">0</span>
                </label>
                <label class="facet-option">
                    <input type="checkbox" class="price-filter" value="500-1000">
                    <span class="facet-label">₱500 - ₱1,000</span>
                    <span class="facet-count" data-price-range="500-1000">0</span>
                </label>
                <label class="facet-option">
                    <input type="checkbox" class="price-filter" value="1000-2000">
                    <span class="facet-label">₱1,000 - ₱2,000</span>
                    <span class="facet-count" data-price-range="1000-2000">0</span>
                </label>
                <label class="facet-option">
                    <input type="checkbox" class="price-filter" value="2000-99999">
                    <span class="facet-label">Over ₱2,000</span>
                    <span class="facet-count" data-price-range="2000-99999">0</span>
                </label>
            </div>
        </div>

        <div class="facet-section">
            <div class="facet-section-header">
                <h4>Colors</h4>
                <span class="collapse-icon"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="facet-options color-facet-grid">
                <?php foreach ($unique_colors as $color): 
                    $color_display = $color;
                    if (strpos($color, ':') !== false) {
                        $parts = explode(':', $color);
                        $color_display = trim($parts[0]);
                    }
                    $hex = get_facet_color_hex($color);
                ?>
                    <label class="facet-color-option" title="<?= htmlspecialchars($color_display) ?>">
                        <input type="checkbox" class="color-filter" value="<?= htmlspecialchars($color) ?>">
                        <span class="color-swatch-facet" style="background-color: <?= $hex ?>; <?= ($color_display == 'White') ? 'border:1px solid #ddd;' : '' ?>"></span>
                        <span class="facet-count-badge" data-color="<?= htmlspecialchars($color) ?>">0</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="facet-section">
            <div class="facet-section-header">
                <h4>Sizes</h4>
                <span class="collapse-icon"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="facet-options">
                <?php foreach ($unique_sizes as $size): ?>
                    <label class="facet-option">
                        <input type="checkbox" class="size-filter" value="<?= htmlspecialchars($size) ?>">
                        <span class="facet-label"><?= htmlspecialchars($size) ?></span>
                        <span class="facet-count" data-size="<?= htmlspecialchars($size) ?>">0</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="active-filters" id="activeFilters" style="display: none;">
            <div class="active-filters-header">
                <h4>Active Filters</h4>
                <button class="clear-all-filters" id="clearAllFilters">
                    <i class="fas fa-times-circle"></i> Clear All
                </button>
            </div>
            <div class="filter-tags" id="filterTags"></div>
        </div>
    </div>

    <!-- Products Display Area -->
    <div class="products-container">
        <!-- Shop by Category Grid -->


        <div class="products-header">
            <button id="mobileFilterToggle" class="mobile-filter-toggle">
                <i class="fas fa-filter"></i> Filter
            </button>
            <h2>All Products</h2>
            
            <div class="search-container">
                <div class="search-input-group">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Search products..." autocomplete="off">
                    <button id="clearSearch" class="clear-search-btn"><i class="fas fa-times"></i></button>
                </div>
                <div id="autocompleteList" class="autocomplete-items"></div>
            </div>

            <div class="view-controls">
                <span id="productCount">0 products</span>
                <select id="sortProducts" class="sort-select">
                    <option value="default">Default</option>
                    <option value="name-asc">Name: A-Z</option>
                    <option value="name-desc">Name: Z-A</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                </select>
            </div>
        </div>

        <div class="products-grid" id="productsGrid">
            <?php foreach ($all_products as $product): 
                // Prepare status badge
                $status = $product['product_status'] ?? '';
                $badgeLabels = ['sale' => 'SALE', 'new' => 'NEW', 'hot' => 'HOT', 'sold_out' => 'SOLD OUT'];
                $badgeLabel = $badgeLabels[$status] ?? '';
            ?>
            <div class="product-card" 
                 data-category="<?= $product['category_id'] ?>" 
                 data-price="<?= $product['price'] ?>"
                 data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                 data-colors="<?= htmlspecialchars($product['colors'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 data-sizes="<?= htmlspecialchars($product['available_sizes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 <?php
                $jsArgs = [
                    $product['name'],
                    (float)$product['price'],
                    $product['image_url'],
                    $product['description'] ?? '',
                    $product['colors'] ?? '',
                    $product['available_sizes'] ?? ''
                ];
                $jsArgsStr = implode(', ', array_map('json_encode', $jsArgs));
                $colorImages = $product['color_images'] ?? 'null';
                $jsArgsStr .= ', ' . ($colorImages === 'null' ? 'null' : json_encode($colorImages));
                ?>
                 onclick='openProductModal(<?= htmlspecialchars($jsArgsStr) ?>)'>
                <div class="img-box">
                <?php if (!empty($badgeLabel)): ?>
                    <span class="product-status-badge status-<?= $status ?>"><?= $badgeLabel ?></span>
                <?php endif; ?>
                <img src="<?= $product['image_url'] ?>" 
                     alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" 
                     class="product-img" 
                     width="200" 
                     loading="lazy">
                </div>
                    <div class="product-content">
                        <h3 class="product-name"><?= $product['name'] ?></h3>
                        <p class="product-text"><?= $product['description'] ?></p>
                        <div class="price-and-sold">
                            <p class="product-price"><span class="small">₱</span><?= $product['price'] ?></p>
                            <p class="product-sold" style="color: #888; font-size: 0.75rem;"><i class="fas fa-arrow-trend-up" style="color: #ffa900; font-size: 0.7rem;"></i> <?= $product['sold'] ?? rand(50, 500) ?> sold</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="no-results" id="noResults" style="display: none;">
            <i class="fas fa-search fa-3x"></i>
            <h3>No products found</h3>
            <p>Try adjusting your filters</p>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" class="scroll-top-btn" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>
</section>

<!-- Product Modal -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Product Details</h2>
        
        <form action="manage_cart.php" method="POST" class="product-form" id="productForm">
            <input type="hidden" name="Item_name" id="modalItemName">
            <input type="hidden" name="base_price" id="modalBasePrice">
            <input type="hidden" name="image_url" id="modalImageUrl">
            <input type="hidden" name="Add_To_Cart" value="1">
            
            <div class="bottom-section">
                <div class="left-panel">
                    <img id="modalProductImage" src="" alt="Product Image" class="preview-image">
                    <div class="modal-product-info">
                        <h3 class="modal-product-name" id="modalProductTitle"></h3>
                        <p id="modalProductDescription" class="preview-description"></p>
                        <div class="modal-price-info">
                            <span class="starts-at">Starting at</span>
                            <p class="base-price">
                                <span class="currency">₱</span>
                                <span id="modalStartPrice">0.00</span>
                            </p>
                        </div>
                    </div>
                    

                </div>
                
                <div class="right-panel">
                    <!-- Color Selection -->
                    <div class="customize-section">
                        <h3>Select Color</h3>
                        <div class="color-options-grid">
                            <label class="color-option selected">
                                <input type="radio" name="color" value="Black" checked>
                                <span class="color-swatch" style="background-color: #000000;"></span>
                                <span class="color-label">Black</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="color" value="White">
                                <span class="color-swatch white-swatch" style="background-color: #FFFFFF;"></span>
                                <span class="color-label">White</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="color" value="Navy Blue">
                                <span class="color-swatch" style="background-color: #001f3f;"></span>
                                <span class="color-label">Navy Blue</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="color" value="Red">
                                <span class="color-swatch" style="background-color: #DC3545;"></span>
                                <span class="color-label">Red</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="color" value="Green">
                                <span class="color-swatch" style="background-color: #28a745;"></span>
                                <span class="color-label">Green</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="color" value="Gray">
                                <span class="color-swatch" style="background-color: #6c757d;"></span>
                                <span class="color-label">Gray</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="color" value="Brown">
                                <span class="color-swatch" style="background-color: #8B4513;"></span>
                                <span class="color-label">Brown</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="color" value="Beige">
                                <span class="color-swatch" style="background-color: #F5F5DC;"></span>
                                <span class="color-label">Beige</span>
                            </label>
                        </div>
                    </div>

                    <!-- Size Selection -->
                    <div class="customize-section">
                        <h3>Select Size</h3>
                        <div class="size-options-grid">
                            <label class="size-option">
                                <input type="radio" name="size" value="XS" data-price-modifier="0.9">
                                <span class="size-box">
                                    <span class="size-letter">XS</span>
                                    <span class="size-name">Extra Small</span>
                                </span>
                            </label>
                            <label class="size-option">
                                <input type="radio" name="size" value="S" data-price-modifier="0.95">
                                <span class="size-box">
                                    <span class="size-letter">S</span>
                                    <span class="size-name">Small</span>
                                </span>
                            </label>
                            <label class="size-option selected">
                                <input type="radio" name="size" value="M" data-price-modifier="1.0" checked>
                                <span class="size-box">
                                    <span class="size-letter">M</span>
                                    <span class="size-name">Medium</span>
                                </span>
                            </label>
                            <label class="size-option">
                                <input type="radio" name="size" value="L" data-price-modifier="1.1">
                                <span class="size-box">
                                    <span class="size-letter">L</span>
                                    <span class="size-name">Large</span>
                                </span>
                            </label>
                            <label class="size-option">
                                <input type="radio" name="size" value="XL" data-price-modifier="1.2">
                                <span class="size-box">
                                    <span class="size-letter">XL</span>
                                    <span class="size-name">Extra Large</span>
                                </span>
                            </label>
                            <label class="size-option">
                                <input type="radio" name="size" value="2XL" data-price-modifier="1.3">
                                <span class="size-box">
                                    <span class="size-letter">2XL</span>
                                    <span class="size-name">Double XL</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Quantity selector (Moved) -->
                    <div class="quantity-section-moved">
                        <h3 class="option-title">Quantity</h3>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="adjustQuantity('subtract')">-</button>
                            <input type="number" name="quantity" id="modalQuantity" class="quantity-input" value="1" min="1" max="10" readonly>
                            <button type="button" class="quantity-btn" onclick="adjustQuantity('add')">+</button>
                        </div>
                    </div>

                    <div class="price-actions-container">
                        <div class="total-price">
                            <div class="price-breakdown">
                                <div class="subtotal-line">
                                    <span>Base Price:</span>
                                    <span>₱<span id="baseSubtotal">0.00</span></span>
                                </div>
                                <div class="subtotal-line">
                                    <span>Size Adjustment:</span>
                                    <span id="sizeAdjustment">+₱0.00</span>
                                </div>
                                <div class="total-line">
                                    <span>Total Amount:</span>
                                    <span>₱<span id="modalTotalPrice">0.00</span></span>
                                </div>
                            </div>
                        </div>
            
                        <div class="cart-actions">
                            <button type="button" class="buy-now-btn" onclick="buyNow(this)">
                                Buy Now
                            </button>
                            <button type="submit" class="add-to-cart-btn">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="./assets/js/vizza.js"></script>

<script>
    const productsData = <?php echo json_encode($all_products); ?>;
</script>
<script src="./assets/js/search.js"></script>

 <script src="./assets/js/menu.js"></script>
  
<?php include './includes/footer.php'; ?>

</main>
</body>
</html>

