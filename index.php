<?php
session_start();
require_once "includes/connection.php";
require "includes/header.php";
?>
    <link rel="stylesheet" href="./assets/css/menu.css">
    <style>

    :root {
        --saffron: hsl(115, 29%, 45%);
        --container-max: 1200px;
        --card-radius: 14px;
        --card-border: rgba(0,0,0,0.08);
    }

    * { box-sizing: border-box; }
    body { background: #fafbfc; }

    /* Layout Components */
    .container {
        background: transparent !important;
        box-shadow: none !important;
    }

    .section-title {
        font-size: 2.0rem;
        font-family: "Dancing Script", cursive;
        margin-bottom: 25px;
        text-align: center;
        color: var(--saffron);
        letter-spacing: 0.01em;
        font-weight: 700;
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .section-title::after {
        content: '';
        display: block;
        width: 60px;
        height: 4px;
        background: var(--saffron);
        margin: 5px 0 0;
        border-radius: 2px;
    }

    /* Categories Section */
    .categories-section {
        padding: 25px 0;
        background: #ffffff;
        margin-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .categories-header-container {
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 30px;
    }

    .categories-header-container .section-title {
        text-align: left;
        margin-bottom: 20px;
    }

    .categories-carousel-wrapper {
        position: relative;
        max-width: 100%;
        margin: 0 auto;
        padding: 0 50px;
    }

    .categories-carousel-track {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        scroll-behavior: smooth;
        padding: 10px 5px 20px;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .categories-carousel-track::-webkit-scrollbar { display: none; }

    .category-card {
        flex: 0 0 160px;
        min-width: 160px;
        background: white;
        padding: 15px;
        border-radius: 16px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.06);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .category-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }

    .category-card:hover::before { left: 100%; }

    .category-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 30px rgba(107, 142, 79, 0.2);
        border-color: rgba(107, 142, 79, 0.3);
    }

    .category-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        color: var(--saffron);
        display: inline-block;
        transition: transform 0.3s;
    }

    .category-card:hover .category-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .category-card h3 {
        margin: 8px 0 0;
        font-size: 1rem;
        color: #1a1a1a;
        font-weight: 500;
        transition: color 0.3s;
    }

    .category-card:hover h3 { color: var(--saffron); }

    .category-image-wrapper {
        font-size: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 110px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .category-image-wrapper img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }

    .cat-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        color: var(--saffron);
        font-size: 1.2rem;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }

    .cat-nav-btn:hover {
        background: var(--saffron);
        color: white;
        box-shadow: 0 6px 15px rgba(107, 142, 79, 0.3);
    }

    .prev-btn { left: 0; }
    .next-btn { right: 0; }

    /* Featured Sections */
    .featured-section {
        max-width: 1440px;
        margin: 0 auto 30px;
        padding: 30px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .featured-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
        border-bottom: 1px solid #f3f4f6;
        padding-bottom: 15px;
    }

    .featured-section-title {
        font-size: 2.00rem;
        font-family: "Dancing Script", cursive;
        font-weight: 900;
        color: var(--saffron);
        margin: 0;
        letter-spacing: 0.01em;
    }

    .featured-section-subtitle {
        font-size: 0.95rem;
        color: #6b7280;
        margin: 5px 0 0;
        font-weight: 400;
    }

    /* Common Button Patterns */
    .featured-view-all {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #f9fafb;
        color: #374151;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        border: 1px solid #e5e7eb;
    }

    .featured-view-all:hover {
        background: #111827;
        color: #ffffff;
        border-color: #111827;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .view-all {
        text-decoration: none;
        font-weight: 800;
        color: var(--saffron);
        border: 2px solid var(--saffron);
        background: #fff;
        padding: 12px 24px;
        border-radius: 30px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 4px 15px rgba(107, 142, 79, 0.15);
        white-space: nowrap;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .view-all:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(107, 142, 79, 0.25);
        background: var(--saffron);
        color: white;
        border-color: transparent;
    }

    .hero-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: var(--saffron);
        color: white;
        padding: 16px 40px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 25px rgba(40, 104, 22, 0.2);
        transform: translateY(20px);
        opacity: 0;
        animation: fadeUp 0.8s forwards 0.9s;
    }

    .hero-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(40, 104, 22, 0.35);
        background: #286816;
    }

    /* Hero Carousel */
    .hero-carousel-container {
        position: relative;
        margin: 130px auto 30px;
        max-width: 1400px;
        padding: 0 20px;
        z-index: 1;
    }

    .hero-carousel {
        position: relative;
        width: 100%;
        height: 550px;
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .carousel-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.8s ease-in-out, visibility 0.8s;
        z-index: 0;
    }

    .carousel-slide.active {
        opacity: 1;
        visibility: visible;
        z-index: 10;
    }

    .slide-1 { background: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%); }
    .slide-2 { background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%); }
    .slide-3 { background: linear-gradient(120deg, #a1c4fd 0%, #c2e9fb 100%); }
    .slide-4 { background: linear-gradient(120deg, #84fab0 0%, #8fd3f4 100%); }

    .slide-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
        height: 100%;
        padding: 0 40px;
        gap: 40px;
    }

    .slide-text {
        flex: 1;
        max-width: 600px;
        z-index: 2;
        padding-top: 20px;
        text-align: left;
    }

    .slide-badge {
        display: inline-block;
        background: rgba(0, 0, 0, 0.05);
        color: var(--saffron);
        padding: 8px 16px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
        margin-bottom: 20px;
        backdrop-filter: blur(5px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
        transform: translateY(20px);
        opacity: 0;
        animation: fadeUp 0.8s forwards 0.3s;
    }

    .slide-text h1 {
        font-size: 4rem;
        line-height: 1.1;
        margin-bottom: 20px;
        font-weight: 800;
        color: #1a1a1a;
        letter-spacing: -0.03em;
        transform: translateY(20px);
        opacity: 0;
        animation: fadeUp 0.8s forwards 0.5s;
    }

    .slide-text p {
        font-size: 1.25rem;
        margin-bottom: 35px;
        color: #555;
        line-height: 1.6;
        font-weight: 500;
        max-width: 90%;
        transform: translateY(20px);
        opacity: 0;
        animation: fadeUp 0.8s forwards 0.7s;
    }

    .slide-image {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        height: 100%;
        z-index: 1;
    }

    .slide-image img {
        max-width: 100%;
        max-height: 85%;
        object-fit: contain;
        filter: drop-shadow(0 20px 40px rgba(0,0,0,0.2));
        opacity: 0;
        transform: translateX(40px);
        animation: slideInRight 1s cubic-bezier(0.2, 0.8, 0.2, 1) forwards 0.5s;
    }

    .carousel-slide.active .slide-image img {
        animation: slideInRight 1s cubic-bezier(0.2, 0.8, 0.2, 1) forwards 0.5s, float 6s ease-in-out infinite 1.5s;
    }

    .carousel-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.4);
        color: #ffffff;
        width: 55px;
        height: 55px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        cursor: pointer;
        z-index: 100;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        opacity: 0.5;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        backdrop-filter: blur(10px);
    }

    .hero-carousel-container:hover .carousel-arrow { opacity: 1; }
    .carousel-arrow:active { transform: translateY(-50%) scale(1.05); }

    .carousel-arrow.left { left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0; }
    .carousel-arrow.right { right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; }

    .carousel-dot.active { background: var(--saffron); width: 30px; }

    /* Modal & UI Elements */
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow: auto;
        backdrop-filter: blur(8px);
    }

    .modal-content {
        background-color: #fff;
        margin: 2% auto;
        padding: 30px;
        width: 95%;
        max-width: 1100px;
        position: relative;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-height: 90vh;
        overflow-y: auto;
        animation: modalSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .close {
        position: absolute;
        right: 25px;
        top: 20px;
        font-size: 28px;
        font-weight: bold;
        color: #666;
        cursor: pointer;
        transition: color 0.3s;
        z-index: 1001;
    }

    .close:hover { color: var(--saffron); }

    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--saffron);
        color: white;
        padding: 18px 28px;
        border-radius: 12px;
        z-index: 2001;
        box-shadow: 0 8px 30px rgba(107, 142, 79, 0.4);
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideInRight 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    /* Modal Panels */
    .bottom-section { display: grid; grid-template-columns: 350px 1fr; gap: 20px; margin-top: 20px; }
    .left-panel, .right-panel { display: flex; flex-direction: column; gap: 12px; background: transparent; }
    .preview-image { width: 100%; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    
    .modal-product-info { text-align: center; padding: 12px; background: #f8f9fa; border-radius: 8px; }
    .modal-product-name { font-size: 22px; font-weight: 600; color: var(--saffron); margin-bottom: 10px; }
    .preview-description { font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 10px; }
    
    .base-price { font-size: 28px; font-weight: bold; color: var(--saffron); display: flex; align-items: baseline; gap: 2px; }
    .base-price .currency { font-size: 18px; }

    /* Forms */
    .customize-section { background: #f8f9fa; padding: 15px; border-radius: 8px; }
    .customize-section h3 { margin-bottom: 10px; font-size: 16px; color: #333; display: flex; align-items: center; gap: 8px; }

    .color-options-grid { display: flex; flex-wrap: wrap; gap: 12px; }
    .color-option { display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; padding: 10px; border-radius: 8px; border: 2px solid transparent; background: #fff; }
    .color-option.selected { border-color: var(--saffron); background: #f0f7f0; }
    .color-swatch { width: 30px; height: 30px; border-radius: 50%; border: 2px solid #e0e0e0; }
    .color-option.selected .color-swatch { border-color: var(--saffron); box-shadow: 0 0 0 3px rgba(40, 104, 22, 0.2); }

    .size-options-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
    .size-box { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 8px 5px; border: 2px solid #e0e0e0; border-radius: 8px; background: #fff; transition: all 0.3s; min-height: 50px; }
    .size-option.selected .size-box { border-color: var(--saffron); background: #f0f7f0; box-shadow: 0 2px 8px rgba(40, 104, 22, 0.15); }

    .total-line { font-size: 18px; font-weight: 600; color: var(--saffron); border-top: 2px solid #e0e0e0; padding-top: 10px; }

    /* Animations */
    @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
    @keyframes slideInRight { to { opacity: 1; transform: translateX(0); } }
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
    @keyframes modalSlideIn { from { opacity: 0; transform: scale(0.9) translateY(-30px); } to { opacity: 1; transform: scale(1) translateY(0); } }

    /* Responsive */
    @media (max-width: 992px) {
        .hero-carousel { height: auto; min-height: 600px; }
        .slide-container { flex-direction: column-reverse; text-align: center; padding: 40px 20px; }
        .slide-text { text-align: center; max-width: 100%; }
        .slide-text h1 { font-size: 2.5rem; }
        .slide-image { height: 250px; width: 100%; }
    }

    @media (max-width: 768px) {
        .section-title { font-size: 1.8rem; }
        .categories-grid { grid-template-columns: repeat(2, 1fr); }
        .bottom-section { grid-template-columns: 1fr; }
        .size-options-grid { grid-template-columns: repeat(3, 1fr); }
    }

    .tab-btn {
        padding: 12px 28px;
        border: 2px solid transparent;
        background: white;
        font-size: 1rem;
        font-weight: 700;
        color: #6b7280;
        cursor: pointer;
        border-radius: 35px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .tab-btn:hover {
        background: #f8f9fa;
        color: var(--saffron, hsl(115, 29%, 45%));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .tab-btn.active {
        background: var(--saffron, hsl(115, 29%, 45%));
        color: white;
        box-shadow: 0 6px 20px rgba(107, 142, 79, 0.3);
        transform: translateY(-2px);
        border-color: transparent;
    }

    .tab-content {
        display: none;
        animation: fadeInUp 0.4s ease-out;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- Hero Carousel Section -->
<section class="hero-carousel-container">
    <div class="hero-carousel">
        <!-- Slide 1 -->
        <div class="carousel-slide slide-1 active">
            <div class="slide-container">
                <div class="slide-text">
                    <span class="slide-badge">🎉 Grand Opening Special</span>
                    <h1>Premium Selection<br>Curated for You</h1>
                    <p>Discover the finest products in Cavite. CavShop is your home for quality, style, and unbeatable value!</p>
                    <a href="menu.php" class="hero-btn">Order Now <i class="fas fa-shopping-cart"></i></a>
                </div>
                <div class="slide-image">
                    <img src="assets/images/categories1.png" alt="Grand Opening Product">
                </div>
            </div>
        </div>

        <!-- Slide 2 -->
        <div class="carousel-slide slide-2">
            <div class="slide-container">
                <div class="slide-text">
                    <span class="slide-badge">🔥 Hot Deal</span>
                    <h1>Bundle & Save<br>Exclusive Daily Deals</h1>
                    <p>Don't miss our biggest offers! Grab your favorite essentials and enjoy massive savings. Limited time only!</p>
                    <a href="menu.php?deals=1" class="hero-btn">Grab Deal <i class="fas fa-tag"></i></a>
                </div>
                <div class="slide-image">
                    <img src="assets/images/categories2.png" alt="Product Deal">
                </div>
            </div>
        </div>

        <!-- Slide 3 -->
        <div class="carousel-slide slide-3">
            <div class="slide-container">
                <div class="slide-text">
                    <span class="slide-badge">🆕 New Arrival</span>
                    <h1>New Arrivals<br>The Latest Trends</h1>
                    <p>Explore our newly launched collections. Crafted with quality for the modern lifestyle experience!</p>
                    <a href="menu.php?category=specials" class="hero-btn">See Trends <i class="fas fa-magic"></i></a>
                </div>
                <div class="slide-image">
                     <img src="assets/images/categories3.png" alt="New Flavors">
                </div>
            </div>
        </div>

        <!-- Slide 4 -->
        <div class="carousel-slide slide-4">
            <div class="slide-container">
                <div class="slide-text">
                    <span class="slide-badge">🚚 Fast Delivery</span>
                    <h1>Fast & Secure<br>Doorstep Delivery</h1>
                    <p>Shopping made easy! Enjoy reliable and secure delivery within Cavite. Order now and we'll handle the rest!</p>
                    <a href="menu.php" class="hero-btn">Start Shopping <i class="fas fa-truck-fast"></i></a>
                </div>
                <div class="slide-image">
                 <img src="assets/images/categories5.png" alt="Fast Delivery">
                </div>
            </div>
        </div>

        <!-- Navigation Arrows -->
        <button class="carousel-arrow left" onclick="changeSlide(-1)">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="carousel-arrow right" onclick="changeSlide(1)">
            <i class="fas fa-chevron-right"></i>
        </button>

    </div>
</section>

<!-- Categories Section - Now Dynamic -->
<section class="categories-section">
    <div class="categories-header-container">
        <h2 class="section-title">Categories</h2>
    </div>
    <div class="categories-carousel-wrapper">
        <button class="cat-nav-btn prev-btn" onclick="scrollCategories(-1)"><i class="fas fa-chevron-left"></i></button>
        <div class="categories-carousel-track" id="categoriesTrack">
            <?php
            // Fetch categories from database
            $cat_query = "SELECT * FROM categories ORDER BY name ASC";
            $cat_result = mysqli_query($conn, $cat_query);
            
            // Category icons mapping
            $category_icons = [
                'smartphones' => '📱',
                'laptops' => '💻',
                'headphones' => '🎧',
                'cameras' => '📷',
                'watches' => '⌚',
                'accessories' => '🔌',
                'electronics' => '🔋',
                'clothing' => '👕',
                'shoes' => '👟',
                'bags' => '👜',
                'default' => '🛍️'
            ];
            
            if ($cat_result && mysqli_num_rows($cat_result) > 0):
                while ($cat = mysqli_fetch_assoc($cat_result)):
                    $cat_name_lower = strtolower($cat['name']);
                    $icon = isset($category_icons[$cat_name_lower]) ? $category_icons[$cat_name_lower] : $category_icons['default'];
            ?>
                <div class="category-card" onclick="showGlobalLoader('', ''); setTimeout(() => window.location.href='menu.php?category=<?php echo $cat['id']; ?>', 300)">
                    <?php if (!empty($cat['image_url'])): ?>
                        <div class="category-image-wrapper">
                            <img src="<?php echo htmlspecialchars($cat['image_url']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                        </div>
                    <?php else: ?>
                        <div class="category-icon"><?php echo $icon; ?></div>
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                </div>
            <?php 
                endwhile;
            endif;
            ?>
        </div>
        <button class="cat-nav-btn next-btn" onclick="scrollCategories(1)"><i class="fas fa-chevron-right"></i></button>
    </div>

    <!-- Categories carousel track logic removed from inline style to main CSS block -->

    <script>
        function scrollCategories(direction) {
            const track = document.getElementById('categoriesTrack');
            // If direction is 1 (Next), move first item to end
            // If direction is -1 (Prev), move last item to start
            if (direction === 1) {
                track.appendChild(track.firstElementChild);
            } else {
                track.insertBefore(track.lastElementChild, track.firstElementChild);
            }
            // Reset scroll position to ensure consistent view
            track.scrollLeft = 0;
        }
    </script>
</section>

    <?php
    // Helper function to render product grid
    function renderProductGrid($products) {
        if (empty($products)) {
            echo '<p style="text-align:center; padding:40px; color:#666;">No products available</p>';
            return;
        }
        
        echo '<div class="products-grid">';
        foreach ($products as $product) {
            $id = $product['id'];
            $name = $product['name'];
            $price = $product['price'];
            $displayPrice = number_format($price, 2);
            $image = !empty($product['image_url']) ? $product['image_url'] : './assets/images/placeholder.jpg';
            $description = $product['description'] ?? '';
            $colors = $product['colors'] ?? '';
            $sizes = $product['available_sizes'] ?? '';
            $colorImages = $product['color_images'] ?? 'null';
            $sold = $product['sold'] ?? rand(50, 500);
            $status = $product['product_status'] ?? '';
            
            // Safe JSON encoding for JS arguments
            $jsArgs = [
                $name,
                (float)$price,
                $image,
                $description,
                $colors,
                $sizes
            ];
            $jsArgsStr = implode(', ', array_map('json_encode', $jsArgs));
            // add colorImages separately because it might be the literal null
            $jsArgsStr .= ', ' . ($colorImages === 'null' ? 'null' : json_encode($colorImages));

            // Generate status badge HTML
            $statusBadge = '';
            if (!empty($status)) {
                $badgeLabels = ['sale' => 'SALE', 'new' => 'NEW', 'hot' => 'HOT', 'sold_out' => 'SOLD OUT'];
                $badgeLabel = $badgeLabels[$status] ?? '';
                if ($badgeLabel) {
                    $statusBadge = "<span class='product-status-badge status-{$status}'>{$badgeLabel}</span>";
                }
            }
            
            ?>
            <div class='product-card' onclick='openProductModal(<?= htmlspecialchars($jsArgsStr) ?>)'>
                <div class='img-box'>
                    <?= $statusBadge ?>
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>" class='product-img' onerror="this.src='./assets/images/placeholder.jpg'" loading='lazy'>
                </div>
                <div class='product-content'>
                    <h3 class='product-name'><?= htmlspecialchars($name) ?></h3>
                    <p class="product-text"><?= htmlspecialchars($description) ?></p>
                    <div class='price-and-sold'>
                        <p class='product-price'><span class="small">₱</span><?= $displayPrice ?></p>
                        <p class='product-sold'><i class='fas fa-arrow-trend-up'></i> <?= $sold ?> sold</p>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }

    // ====== FETCH PRODUCTS FROM DATABASE ======
    // Each section shows products marked with a specific featured_type
    // Falls back to random products if no products are marked for that section

    // Best Sellers - Products marked as best_seller
    $best_sellers_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          WHERE p.featured_type = 'best_seller'
                          ORDER BY p.id DESC 
                          LIMIT 8";
    $best_sellers_result = mysqli_query($conn, $best_sellers_query);
    $best_sellers = ($best_sellers_result && mysqli_num_rows($best_sellers_result) > 0) 
        ? mysqli_fetch_all($best_sellers_result, MYSQLI_ASSOC) 
        : [];
    
    // Fallback: if no best sellers marked, show random products
    if (empty($best_sellers)) {
        $fallback_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          ORDER BY RAND() 
                          LIMIT 8";
        $fallback_result = mysqli_query($conn, $fallback_query);
        $best_sellers = ($fallback_result && mysqli_num_rows($fallback_result) > 0) 
            ? mysqli_fetch_all($fallback_result, MYSQLI_ASSOC) 
            : [];
    }

    // Trending Now - Products marked as trending
    $new_arrivals_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          WHERE p.featured_type = 'trending'
                          ORDER BY p.id DESC 
                          LIMIT 8";
    $new_arrivals_result = mysqli_query($conn, $new_arrivals_query);
    $new_arrivals = ($new_arrivals_result && mysqli_num_rows($new_arrivals_result) > 0) 
        ? mysqli_fetch_all($new_arrivals_result, MYSQLI_ASSOC) 
        : [];
    
    // Fallback: show newest products
    if (empty($new_arrivals)) {
        $fallback_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          ORDER BY p.id DESC 
                          LIMIT 8";
        $fallback_result = mysqli_query($conn, $fallback_query);
        $new_arrivals = ($fallback_result && mysqli_num_rows($fallback_result) > 0) 
            ? mysqli_fetch_all($fallback_result, MYSQLI_ASSOC) 
            : [];
    }

    // Popular Picks - Products marked as popular
    $popular_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     WHERE p.featured_type = 'popular'
                     ORDER BY p.id DESC 
                     LIMIT 8";
    $popular_result = mysqli_query($conn, $popular_query);
    $popular = ($popular_result && mysqli_num_rows($popular_result) > 0) 
        ? mysqli_fetch_all($popular_result, MYSQLI_ASSOC) 
        : [];
    
    // Fallback: show random products
    if (empty($popular)) {
        $fallback_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          ORDER BY RAND() 
                          LIMIT 8";
        $fallback_result = mysqli_query($conn, $fallback_query);
        $popular = ($fallback_result && mysqli_num_rows($fallback_result) > 0) 
            ? mysqli_fetch_all($fallback_result, MYSQLI_ASSOC) 
            : [];
    }

    // Hot Deals - Products marked as hot_deal
    $hot_deals_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       WHERE p.featured_type = 'hot_deal'
                       ORDER BY p.id DESC 
                       LIMIT 8";
    $hot_deals_result = mysqli_query($conn, $hot_deals_query);
    $hot_deals = ($hot_deals_result && mysqli_num_rows($hot_deals_result) > 0) 
        ? mysqli_fetch_all($hot_deals_result, MYSQLI_ASSOC) 
        : [];
    
    // Fallback: show lowest priced products
    if (empty($hot_deals)) {
        $fallback_query = "SELECT p.*, c.name as category_name, c.id as category_id 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          ORDER BY p.price ASC 
                          LIMIT 8";
        $fallback_result = mysqli_query($conn, $fallback_query);
        $hot_deals = ($fallback_result && mysqli_num_rows($fallback_result) > 0) 
            ? mysqli_fetch_all($fallback_result, MYSQLI_ASSOC) 
            : [];
    }
    ?>

    <!-- Best Sellers Section -->
    <div class="featured-section">
        <div class="featured-section-header">
            <div>
                <h2 class="featured-section-title">Best Sellers</h2>
                <p class="featured-section-subtitle">The most loved items by our community this month.</p>
            </div>
            <a href="menu.php" class="featured-view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php renderProductGrid($best_sellers); ?>
    </div>

    <!-- Trending Now Section -->
    <div class="featured-section">
        <div class="featured-section-header">
            <div>
                <h2 class="featured-section-title">Trending Now</h2>
                <p class="featured-section-subtitle">Stay ahead with the latest hits and viral favorites.</p>
            </div>
            <a href="menu.php" class="featured-view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php renderProductGrid($new_arrivals); ?>
    </div>

    <!-- Popular Picks Section -->
    <div class="featured-section">
        <div class="featured-section-header">
            <div>
                <h2 class="featured-section-title">Popular Picks</h2>
                <p class="featured-section-subtitle">Reliable choices curated by our expert team.</p>
            </div>
            <a href="menu.php" class="featured-view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php renderProductGrid($popular); ?>
    </div>

    <!-- Hot Deals Section -->
    <div class="featured-section">
        <div class="featured-section-header">
            <div>
                <h2 class="featured-section-title">Hot Deals</h2>
                <p class="featured-section-subtitle">Unbeatable values on your favorite items for a limited time.</p>
            </div>
            <a href="menu.php" class="featured-view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php renderProductGrid($hot_deals); ?>
    </div>

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
                            <!-- Populated by JS -->
                        </div>
                    </div>

                    <!-- Size Selection -->
                    <div class="customize-section">
                        <h3>Select Size</h3>
                        <div class="size-options-grid">
                            <!-- Populated by JS -->
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


    <!-- Product modal and toast styles moved to main CSS block -->

<script>
// ====== INITIALIZE PAGE ======
// Ensure payment modal is closed on page load
document.addEventListener('DOMContentLoaded', function() {
    const paymentModal = document.getElementById('paymentModal');
    if (paymentModal) {
        paymentModal.classList.remove('active');
    }
});

// ====== CAROUSEL FUNCTIONS ======
let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const dots = document.querySelectorAll('.carousel-dot');
let autoSlideInterval;

function showSlide(index) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    if (index >= slides.length) currentSlide = 0;
    else if (index < 0) currentSlide = slides.length - 1;
    else currentSlide = index;
    
    slides[currentSlide].classList.add('active');
    if (dots.length > 0 && dots[currentSlide]) {
        dots[currentSlide].classList.add('active');
    }
}

function changeSlide(direction) {
    showSlide(currentSlide + direction);
    resetAutoSlide();
}

function goToSlide(index) {
    showSlide(index);
    resetAutoSlide();
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    autoSlideInterval = setInterval(() => showSlide(currentSlide + 1), 5000);
}

autoSlideInterval = setInterval(() => showSlide(currentSlide + 1), 5000);

// Pause on hover
document.querySelector('.hero-carousel')?.addEventListener('mouseenter', () => clearInterval(autoSlideInterval));
document.querySelector('.hero-carousel')?.addEventListener('mouseleave', resetAutoSlide);

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') changeSlide(-1);
    if (e.key === 'ArrowRight') changeSlide(1);
});

// ====== TAB FUNCTIONS ======
function openTab(evt, tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}

// Product modal logic is handled by menu.js

function closeQuickView() {
    window.hideProductModal();
}

// Close modal on backdrop click
document.getElementById('productModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeQuickView();
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('productModal')?.style.display === 'block') closeQuickView();
});

// Add to cart logic streamlined via menu.js and global showToast
function addToCartAjax(productId, productName, productPrice, quantity, imageUrl) {
    <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
        showToast('Please login to add items to cart', 'warning');
        setTimeout(() => window.location.href = 'login.php', 1500);
        return;
    <?php endif; ?>
    
    const formData = new FormData();
    formData.append('Add_To_Cart', '1');
    formData.append('Item_name', productName);
    formData.append('base_price', productPrice);
    formData.append('quantity', quantity);
    formData.append('image_url', imageUrl || './assets/images/placeholder.jpg');
    formData.append('size', 'M');
    formData.append('toppings', '[]');
    formData.append('total_price', productPrice * quantity);
    
    fetch('manage_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        showToast('Added to cart successfully!', 'success');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding to cart', 'error');
    });
}

function showToast(message, type = 'success') {
    // Remove existing toasts
    document.querySelectorAll('.toast-notification').forEach(t => t.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    
    const colors = {
        success: 'hsl(115, 29%, 45%)',
        error: '#dc3545',
        warning: '#ffc107'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle'
    };
    
    toast.style.background = colors[type] || colors.success;
    toast.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i> ${message}`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease-out reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<script src="./assets/js/menu.js"></script>
<?php require "includes/footer.php"; ?>
