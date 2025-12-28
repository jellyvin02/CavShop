<?php
session_start();

// Add CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid request verification.";
        header("Location: manage_menu.php");
        exit;
    }
}

require_once 'includes/connection.php';

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Add error logging function
function log_error($message, $error_details) {
    error_log(date('[Y-m-d H:i:s] ') . $message . ': ' . $error_details . PHP_EOL, 3, 'error.log');
}

// Add input sanitization function
function sanitize_input($input) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($input));
}

// Handle adding a new category
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $image_url = '';

    // Handle category image upload
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['category_image']['tmp_name']);
        finfo_close($file_info);

        if (in_array($mime_type, $allowed_types) && $_FILES['category_image']['size'] <= $max_size) {
            $upload_dir = 'uploads/categories/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $extension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('cat_') . '_' . time() . '.' . $extension;
            $image_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $image_path)) {
                $image_url = $image_path;
            }
        }
    }

    $query = "INSERT INTO categories (name, image_url) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $category_name, $image_url);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Category added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding category.";
    }
    mysqli_stmt_close($stmt);
}

// Handle deleting a category
if (isset($_POST['delete_category'])) {
    $category_id = filter_var($_POST['category_id_to_delete'], FILTER_VALIDATE_INT);
    if ($category_id !== false) {
        // Fetch image_url to delete file
        $file_query = mysqli_prepare($conn, "SELECT image_url FROM categories WHERE id = ?");
        mysqli_stmt_bind_param($file_query, "i", $category_id);
        mysqli_stmt_execute($file_query);
        $file_result = mysqli_stmt_get_result($file_query);
        if ($file_row = mysqli_fetch_assoc($file_result)) {
            if (!empty($file_row['image_url']) && file_exists($file_row['image_url'])) {
                unlink($file_row['image_url']);
            }
        }
        mysqli_stmt_close($file_query);

        $query = "DELETE FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Category deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting category.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Function to map color names to hex codes
function get_color_hex($color_name) {
    $color_map = [
        'Black' => '#000000',
        'White' => '#FFFFFF',
        'Navy Blue' => '#001f3f',
        'Red' => '#DC3545',
        'Green' => '#28a745',
        'Gray' => '#6c757d',
        'Brown' => '#8B4513',
        'Beige' => '#F5F5DC'
    ];
    return $color_map[$color_name] ?? '#ddd';
}

// Function to generate unique product code
function generateProductCode($conn, $category_id = null) {
    // Get category prefix if category_id is provided
    $prefix = 'PROD';
    if ($category_id) {
        $cat_query = mysqli_prepare($conn, "SELECT name FROM categories WHERE id = ?");
        if ($cat_query) {
            mysqli_stmt_bind_param($cat_query, "i", $category_id);
            mysqli_stmt_execute($cat_query);
            $cat_result = mysqli_stmt_get_result($cat_query);
            if ($cat_row = mysqli_fetch_assoc($cat_result)) {
                // Use first 4 characters of category name as prefix (uppercase)
                $category_name = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $cat_row['name']), 0, 4));
                if (strlen($category_name) >= 2) {
                    $prefix = $category_name;
                }
            }
            mysqli_stmt_close($cat_query);
        }
    }
    
    // Find the highest number for this prefix
    $code_query = "SELECT product_code FROM products WHERE product_code LIKE ? ORDER BY product_code DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $code_query);
    $search_pattern = $prefix . '%';
    mysqli_stmt_bind_param($stmt, "s", $search_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $next_num = 1;
    if ($row = mysqli_fetch_assoc($result)) {
        // Extract number from existing code (e.g., PIZZA001 -> 001 -> 1)
        $existing_code = $row['product_code'];
        if (preg_match('/(\d+)$/', $existing_code, $matches)) {
            $next_num = intval($matches[1]) + 1;
        }
    }
    mysqli_stmt_close($stmt);
    
    // Generate code with zero-padded number (001, 002, etc.)
    $product_code = $prefix . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    
    // Double-check uniqueness
    $check_query = mysqli_prepare($conn, "SELECT id FROM products WHERE product_code = ?");
    if ($check_query) {
        mysqli_stmt_bind_param($check_query, "s", $product_code);
        mysqli_stmt_execute($check_query);
        $check_result = mysqli_stmt_get_result($check_query);
        if (mysqli_num_rows($check_result) > 0) {
            // If somehow exists, increment
            $next_num++;
            $product_code = $prefix . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        }
        mysqli_stmt_close($check_query);
    }
    
    return $product_code;
}

// Handle adding a new product
if (isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
    // Handle colors - accept array or string
    // Handle colors - accept array and/or custom string
    $selected_colors = isset($_POST['colors']) && is_array($_POST['colors']) ? $_POST['colors'] : [];
    $custom_colors_str = isset($_POST['custom_colors']) ? trim($_POST['custom_colors']) : '';
    
    if ($custom_colors_str !== '') {
        $custom_colors_array = array_map('trim', explode(',', $custom_colors_str));
        $selected_colors = array_merge($selected_colors, $custom_colors_array);
    }
    
    // Remove empty and duplicates
    $selected_colors = array_unique(array_filter($selected_colors));
    $colors = trim(implode(',', $selected_colors));

    // Handle sizes - accept array and/or custom string
    $selected_sizes = isset($_POST['available_sizes']) && is_array($_POST['available_sizes']) ? $_POST['available_sizes'] : [];
    $custom_sizes_str = isset($_POST['custom_sizes']) ? trim($_POST['custom_sizes']) : '';
    
    if ($custom_sizes_str !== '') {
        $custom_sizes_array = array_map('trim', explode(',', $custom_sizes_str));
        $selected_sizes = array_merge($selected_sizes, $custom_sizes_array);
    }
    
    // Remove empty and duplicates
    $selected_sizes = array_unique(array_filter($selected_sizes));
    $available_sizes = trim(implode(',', $selected_sizes));
    
    // Handle featured type
    $featured_type = isset($_POST['featured_type']) && !empty($_POST['featured_type']) ? trim($_POST['featured_type']) : null;
    $valid_featured_types = ['best_seller', 'trending', 'popular', 'hot_deal'];
    if ($featured_type !== null && !in_array($featured_type, $valid_featured_types)) {
        $featured_type = null;
    }
    
    // Handle product status (Sale, New, Hot, Sold Out badges)
    $product_status = isset($_POST['product_status']) && !empty($_POST['product_status']) ? trim($_POST['product_status']) : null;
    $valid_statuses = ['sale', 'new', 'hot', 'sold_out'];
    if ($product_status !== null && !in_array($product_status, $valid_statuses)) {
        $product_status = null;
    }
    
    // Improved input validation
    if (empty($product_name) || strlen($product_name) > 255) {
        $_SESSION['error_message'] = "Product name is required and must be less than 255 characters.";
        header("Location: manage_menu.php");
        exit;
    }
    
    // Auto-generate product code
    $product_code = generateProductCode($conn, $category_id);

    if ($price === false || $price <= 0 || $price > 999999.99) {
        $_SESSION['error_message'] = "Please enter a valid price between 0 and 999,999.99";
        header("Location: manage_menu.php");
        exit;
    }

    // Verify category exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) === 0) {
        $_SESSION['error_message'] = "Invalid category selected.";
        header("Location: manage_menu.php");
        exit;
    }

    // Handle default image file upload with improved security
    $image_url = '';
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['image_url']['tmp_name']);
        finfo_close($file_info);

        if (!in_array($mime_type, $allowed_types)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
        } elseif ($_FILES['image_url']['size'] > $max_size) {
            $_SESSION['error_message'] = "File is too large. Maximum size is 5MB.";
        } else {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $extension = pathinfo($_FILES['image_url']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $image_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image_url']['tmp_name'], $image_path)) {
                $image_url = $image_path;
            } else {
                $_SESSION['error_message'] = "Error uploading image.";
            }
        }
    }

    // Handle color-specific image uploads
    $color_images = [];
    if (isset($_FILES['color_images']) && !empty($selected_colors)) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $upload_dir = 'uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($selected_colors as $color) {
            // Sanitize color name for array key
            $color_key = preg_replace('/[^a-zA-Z0-9]/', '_', $color);
            
            if (isset($_FILES['color_images']['name'][$color_key]) && 
                $_FILES['color_images']['error'][$color_key] == 0) {
                
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $_FILES['color_images']['tmp_name'][$color_key]);
                finfo_close($file_info);
                
                if (in_array($mime_type, $allowed_types) && 
                    $_FILES['color_images']['size'][$color_key] <= $max_size) {
                    
                    $extension = pathinfo($_FILES['color_images']['name'][$color_key], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . $color_key . '_' . time() . '.' . $extension;
                    $image_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['color_images']['tmp_name'][$color_key], $image_path)) {
                        $color_images[$color] = $image_path;
                    }
                }
            }
        }
    }
    
    // Convert color images array to JSON
    $color_images_json = !empty($color_images) ? json_encode($color_images) : null;

    if ($image_url) {
        $query = "INSERT INTO products (name, product_code, description, price, category_id, image_url, colors, available_sizes, featured_type, product_status, color_images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssdissssss", $product_name, $product_code, $description, $price, $category_id, $image_url, $colors, $available_sizes, $featured_type, $product_status, $color_images_json);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Product added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding product.";
            // Clean up uploaded files if database insert fails
            if (file_exists($image_url)) {
                unlink($image_url);
            }
            foreach ($color_images as $img_path) {
                if (file_exists($img_path)) {
                    unlink($img_path);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle AJAX request for category name (for product code generation)
if (isset($_GET['get_category_name'])) {
    $cat_id = filter_var($_GET['get_category_name'], FILTER_VALIDATE_INT);
    if ($cat_id) {
        $stmt = mysqli_prepare($conn, "SELECT name FROM categories WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $cat_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                header('Content-Type: application/json');
                echo json_encode(['category_name' => $row['name']]);
                mysqli_stmt_close($stmt);
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['category_name' => '']);
    exit;
}

// Fetch categories and products using prepared statements
$categories_result = mysqli_query($conn, "SELECT id, name, image_url FROM categories ORDER BY name");
$products_result = mysqli_query($conn, "SELECT id, name, product_code, price, category_id, image_url FROM products ORDER BY name");

// Add this before the HTML output
if (mysqli_errno($conn)) {
    log_error("Database error", mysqli_error($conn));
    $_SESSION['error_message'] = "A database error occurred. Please try again.";
}
?>
<?php require "includes/adminside.php"; ?>
<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Menu Management</h1>
        <p>Add and manage your products and categories</p>
    </div>
    
    <div class="dashboard-grid">
        <!-- Product Management Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2>Add New Product</h2>
                <span class="card-icon"><i class="fas fa-plus-circle"></i></span>
            </div>
            <form method="POST" action="manage_menu.php" enctype="multipart/form-data" class="modern-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-group">
                    <input type="text" name="product_name" id="product_name" required placeholder=" ">
                    <label for="product_name">Product Name</label>
                </div>
                
                <div class="form-group">
                    <input type="text" name="product_code" id="product_code" readonly placeholder=" " style="background-color: #f8f9fa; cursor: not-allowed;">
                    <label for="product_code">Product Code <small>(Auto-generated)</small></label>
                </div>
                
                <div class="form-group">
                    <textarea name="description" id="description" placeholder=" "></textarea>
                    <label for="description" class="textarea-label">Description</label>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <input type="number" name="price" id="price" step="0.01" required placeholder=" ">
                        <label for="price">Price</label>
                    </div>
                    
                    <div class="form-group half">
                        <select name="category_id" id="category_id" required>
                            <option value="">Select Category</option>
                            <?php mysqli_data_seek($categories_result, 0); ?>
                            <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label class="static-label">Available Colors</label>
                        <div class="color-swatch-group">
                            <?php 
                            $standard_colors = ['Black', 'White', 'Navy Blue', 'Red', 'Green', 'Gray', 'Brown', 'Beige'];
                            foreach ($standard_colors as $color): 
                                $hex = get_color_hex($color);
                            ?>
                                <label class="color-swatch-option" title="<?= htmlspecialchars($color) ?>">
                                    <input type="checkbox" name="colors[]" value="<?= $color ?>" <?= in_array($color, ['Black', 'White', 'Navy Blue']) ? 'checked' : '' ?>>
                                    <span class="color-swatch" style="background-color: <?= $hex ?>; <?= ($color == 'White') ? 'border:2px solid #ddd;' : 'border:2px solid transparent;' ?>"></span>
                                    <span class="color-name"><?= $color ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <input type="text" name="custom_colors" id="custom_colors" placeholder="e.g. Gold:#FFD700, Silver:#C0C0C0">
                            <label for="custom_colors" class="static-label" style="font-weight: normal; font-size: 0.85rem;">Custom Colors (comma separated)</label>
                        </div>
                    </div>
                    <div class="form-group half">
                        <label class="static-label">Available Sizes</label>
                        <div class="checkbox-group">
                            <?php 
                            $standard_sizes = ['XS', 'S', 'M', 'L', 'XL', '2XL'];
                            foreach ($standard_sizes as $size): 
                            ?>
                                <label class="checkbox-chip">
                                    <input type="checkbox" name="available_sizes[]" value="<?= $size ?>">
                                    <span><?= $size ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <input type="text" name="custom_sizes" id="custom_sizes" placeholder="e.g. 28, 30, 32, One Size">
                            <label for="custom_sizes" class="static-label" style="font-weight: normal; font-size: 0.85rem;">Custom Sizes (comma separated)</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="static-label">Featured Section</label>
                    <select name="featured_type" id="featured_type" class="form-select-styled">
                        <option value="">Not Featured</option>
                        <option value="best_seller">Best Seller</option>
                        <option value="trending">Trending Now</option>
                        <option value="popular">Popular Picks</option>
                        <option value="hot_deal">Hot Deal</option>
                    </select>
                    <small class="form-hint">Select which section this product should appear in on the homepage</small>
                </div>
                
                <div class="form-group">
                    <label class="static-label">Product Status Badge</label>
                    <select name="product_status" id="product_status" class="form-select-styled">
                        <option value="">No Badge</option>
                        <option value="sale">SALE</option>
                        <option value="new">NEW</option>
                        <option value="hot">HOT</option>
                        <option value="sold_out">SOLD OUT</option>
                    </select>
                    <small class="form-hint">Display a status badge on the product card</small>
                </div>
                
                <div class="form-group file-upload">
                    <input type="file" name="image_url" id="image_url" accept="image/*" required>
                    <label for="image_url">
                        <i class="upload-icon">📁</i>
                        <span>Choose Default Image (Required)</span>
                    </label>
                </div>
                
                <!-- Dynamic Color Images Section -->
                <div id="colorImagesSection" class="color-images-section" style="display: none;">
                    <label class="static-label">Color-Specific Images <small>(Optional - Upload different images for each color)</small></label>
                    <div id="colorImageUploads" class="color-image-uploads">
                        <!-- Dynamically populated by JavaScript -->
                    </div>
                </div>
                
                <button type="button" name="add_product" class="btn-primary" onclick="handleProductSubmit(this)">Add Product</button>
            </form>
        </div>

        <!-- Category Management Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2>Category Management</h2>
                <span class="card-icon"><i class="fas fa-list"></i></span>
            </div>
            
            <form method="POST" action="manage_menu.php" class="modern-form" id="categoryForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-group">
                    <input type="text" name="category_name" id="category_name" required placeholder=" ">
                    <label for="category_name">New Category Name</label>
                </div>
                <div class="form-group file-upload" style="margin-top: 15px;">
                     <input type="file" name="category_image" id="category_image" accept="image/*">
                     <label for="category_image">
                         <i class="upload-icon">📁</i>
                         <span>Choose Category Image</span>
                     </label>
                </div>
                <button type="button" class="btn-secondary" onclick="handleCategorySubmit(this)">Add Category</button>
            </form>

            <div class="category-list">
                <h3>Existing Categories</h3>
                <div class="category-grid">
                    <?php mysqli_data_seek($categories_result, 0); ?>
                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                        <div class="category-item">
                            <div class="category-info-mini">
                                <?php if (!empty($category['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($category['image_url']) ?>" alt="" class="cat-thumb">
                                <?php else: ?>
                                    <div class="cat-thumb-placeholder"><i class="fas fa-folder"></i></div>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($category['name']) ?></span>
                            </div>
                            <form method="POST" action="manage_menu.php" class="delete-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="category_id_to_delete" value="<?= $category['id'] ?>">
                                <button type="button" class="btn-delete" 
                                        onclick="handleDeleteCategory(this)" data-category-id="<?= $category['id'] ?>">×</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    <div id="customModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
            </div>
            <div class="modal-divider"></div>
            <div class="modal-body">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-confirm" id="modalConfirm">
                    <i class="fas fa-check"></i> Confirm
                </button>
                <button class="modal-btn modal-cancel" id="modalCancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</main>

<style>
    /* Add Font Awesome */
    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

    /* Base styles */
    body, h1, h2 {
        font-family: 'Inter', sans-serif;
        background-color: #f5f7fa;
        color: #1a1f36;
    }

    h1 {
        color: hsl(115, 29%, 45%);
        font-weight: bold;
        font-size: 36px;
        margin: 0;
        padding: 0 0 1rem 0;
    }

    .dashboard-main {
        padding: 1rem 2rem;
        margin-left: 260px;
        width: calc(100% - 260px);
        font-family: 'Inter', sans-serif;
    }

    .dashboard-header {
        margin-bottom: 2rem;
    }

    .dashboard-header h1 {
        color: hsl(115, 29%, 45%);
        font-size: 36px;
        margin-bottom: 0.5rem;
        text-align: left;
    }

    .dashboard-header p {
        color: #555;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 2rem;
    }

    .dashboard-card {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .card-header h2 {
        color: hsl(115, 29%, 45%);
        font-size: 1.5rem;
        font-weight: bold;
    }

    .card-icon {
        color: #155724;
        font-size: 1.5rem;
        opacity: 0.9;
        transition: opacity 0.3s;
    }

    .card-icon:hover {
        opacity: 1;
    }

    .modern-form .form-group {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .modern-form input:not([type="file"]),
    .modern-form select,
    .modern-form textarea {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: #f8faff;
        font-size: 0.95rem;
        color: #333;
    }

    .modern-form label {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        padding: 0 0.2rem;
        color: #64748b;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .modern-form input:focus,
    .modern-form select:focus,
    .modern-form textarea:focus {
        border-color: #28a745;
        outline: none;
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
        background: #fff;
    }

    .modern-form input:focus + label,
    .modern-form input:not(:placeholder-shown) + label,
    .modern-form select:focus + label,
    .modern-form select:valid + label {
        top: 0;
        font-size: 0.8rem;
        color: #155724;
        background: #fff;
        padding: 0 6px;
        font-weight: 500;
    }

    .modern-form textarea {
        min-height: 100px;
        resize: vertical;
    }

    .modern-form .textarea-label {
        top: 0;
        transform: translateY(-50%);
        background: white;
        font-size: 0.8rem;
    }

    .modern-form textarea:focus + .textarea-label,
    .modern-form textarea:not(:placeholder-shown) + .textarea-label {
        color: #155724;
    }

    .modern-form textarea::placeholder {
        opacity: 0;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .file-upload {
        position: relative;
        text-align: center;
        padding: 2rem;
        border: 2px dashed #ccc;
        border-radius: 5px;
        cursor: pointer;
        border-color: #28a745;
    }

    .file-upload:hover {
        border-color: #218838;
        background-color: #f8f9fa;
    }

    .file-upload input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 10;
    }

    .upload-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: #155724;
    }

    .btn-primary,
    .btn-secondary {
        width: 100%;
        padding: 14px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #28a745 0%, #208e3b 100%);
        color: white;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
    }

    .btn-primary:hover,
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(40, 167, 69, 0.25);
        background: linear-gradient(135deg, #2eb84d 0%, #22993f 100%);
    }

    .btn-delete {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        cursor: pointer;
        opacity: 0.9;
        transition: opacity 0.3s;
    }

    .btn-delete:hover {
        opacity: 1;
        transform: scale(1.1);
    }
    
    /* Checkbox Chip Styling */
    .modern-form label.static-label,
    .modern-form label.checkbox-chip,
    .modern-form label.color-swatch-option {
        position: relative !important;
        left: auto;
        top: auto;
        transform: none;
        background: transparent;
        padding: 0;
        width: auto;
        pointer-events: auto;
    }

    .static-label {
        display: block;
        margin-bottom: 12px;
        color: #4a5568;
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 5px 0;
    }

    .checkbox-chip {
        position: relative;
        cursor: pointer;
        user-select: none;
    }

    .checkbox-chip input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .checkbox-chip span {
        display: inline-block;
        padding: 8px 16px;
        background-color: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 20px;
        font-size: 14px;
        color: #555;
        transition: all 0.2s ease;
    }

    .checkbox-chip input:checked + span {
        background-color: #e8f5e9;
        border-color: #28a745;
        color: #155724;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.1);
    }

    .checkbox-chip:hover span {
        background-color: #e9ecef;
    }

    .checkbox-chip input:checked:hover + span {
        background-color: #d4edda;
    }

    /* Featured Type Select Styling */
    .form-select-styled {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8faff;
        font-size: 0.95rem;
        color: #333;
        cursor: pointer;
        transition: all 0.3s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
    }

    .form-select-styled:focus {
        border-color: #28a745;
        outline: none;
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
        background-color: #fff;
    }

    .form-hint {
        display: block;
        margin-top: 6px;
        color: #6b7280;
        font-size: 0.85rem;
    }

    /* Color Swatch Styling */
    .color-swatch-group {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        padding: 10px 0;
    }

    .color-swatch-option {
        position: relative;
        cursor: pointer;
        user-select: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.2s ease;
        background: #f8f9fa;
    }

    .color-swatch-option:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }

    .color-swatch-option input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .color-swatch {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: block;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .color-swatch-option input:checked ~ .color-swatch {
        box-shadow: 0 0 0 3px #28a745, 0 2px 8px rgba(0, 0, 0, 0.2);
        transform: scale(1.1);
    }

    .color-name {
        font-size: 0.75rem;
        color: #555;
        text-align: center;
        font-weight: 500;
    }

    .color-swatch-option input:checked ~ .color-name {
        color: #155724;
        font-weight: 600;
    }
    
    .category-list h3 {
        color: hsl(115, 29%, 45%);
        font-size: 1.2rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
    }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
        max-height: 300px;
        overflow-y: auto;
        padding-right: 5px; /* Space for scrollbar */
    }

    .category-grid::-webkit-scrollbar {
        width: 6px;
    }
    
    .category-grid::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .category-grid::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .category-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 8px; /* Softer corners */
        border: 1px solid #eee;
        transition: all 0.2s ease;
    }

    .category-item:hover {
        background: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08); /* Subtle shadow on hover */
        border-color: #28a745;
        transform: translateY(-2px);
    }

    .category-item span {
        font-weight: 500;
        color: #333;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .category-list h3 {
        color: #1a1f36;
        font-weight: 600;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .category-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.8rem;
        border-bottom: 1px solid #e0e0e0;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 0.8rem;
        transition: all 0.3s ease;
    }

    .category-info-mini {
        display: flex;
        align-items: center;
        gap: 12px;
        overflow: hidden;
    }

    .cat-thumb {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        object-fit: cover;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .cat-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 1.2rem;
    }

    .delete-form {
        display: inline;
    }

    /* Error state styles */
    .form-group.error input,
    .form-group.error select,
    .form-group.error textarea {
        border-color: #dc3545;
    }

    .form-group.error label {
        color: #dc3545;
    }

    .error-message {
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: 0.3rem;
    }

    /* Add styles for validation feedback */
    .form-group.error::after {
        content: '!';
        position: absolute;
        right: -20px;
        top: 50%;
        transform: translateY(-50%);
        color: #dc3545;
        font-weight: bold;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        animation: fadeIn 0.2s;
    }

    .modal-content {
        position: relative;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 95%;
        max-width: 500px;
        padding: 0;
        border-radius: 8px;
        background: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        animation: modalIn 0.3s ease-out;
    }

    .modal-header {
        padding: 1.5rem 1rem;
        border-bottom: 1px solid #e0e0e0;
    }

    .modal-title {
        color: #333;
        font-size: 1.5rem;
        margin: 0;
        font-weight: 600;
    }

    .modal-body {
        padding: 1.5rem;
    }

    #modalMessage {
        color: #666;
        font-size: 1.1rem;
        line-height: 1.6;
        margin: 0;
    }

    .modal-footer {
        padding: 1.5rem 2rem;
        background: #f8f9fa;
        border-top: 1px solid #e0e0e0;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    .modal-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        transition: all 0.2s ease;
    }

    .modal-confirm {
        background-color: #28a745;
        color: white;
        order: 1;
    }

    .modal-confirm:hover {
        background-color: #218838;
    }

    .modal-cancel {
        background-color: #e9ecef;
        color: #495057;
        order: 2;
    }

    .modal-cancel:hover {
        background-color: #dde2e6;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: translate(-50%, -60%);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }

    @media (max-width: 1200px) {
        .dashboard-main {
            margin-left: 0;
            width: 100%;
            padding: 1rem;
        }
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    /* New Modern Toast Styles */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }

    .toast {
        display: flex;
        align-items: center;
        background: #fff;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        transform: translateX(120%);
        animation: slideIn 0.3s ease forwards;
        width: 300px;
      
    }

    .toast.error {
        border-left-color: #dc3545;
    }

    .toast-icon {
        font-size: 24px;
        margin-right: 12px;
        color: #155724;
    }

    .toast.error .toast-icon {
        color: #dc3545;
    }

    .toast-content {
        flex: 1;
    }

    .toast-message {
        color: #333;
        font-size: 14px;
        font-weight: 500;
        margin: 0;
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 0 0 12px 12px;
    }

    .toast-progress::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #28a745;
        transform-origin: left;
        animation: progress 1.3s linear forwards;
        border-radius: 0 0 12px 12px;
    }

    .toast.error .toast-progress::after {
        background: #dc3545;
    }

    @keyframes slideIn {
        from {
            transform: translateX(120%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes progress {
        to {
            transform: scaleX(0);
        }
    }

    /* Color Images Upload Section */
    .color-images-section {
        margin-top: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 2px dashed #e0e0e0;
    }

    .color-images-section .static-label {
        margin-bottom: 15px;
    }

    .color-images-section small {
        color: #888;
        font-weight: normal;
    }

    .color-image-uploads {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }

    .color-image-upload-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .color-image-upload-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        border-color: #28a745;
    }

    .color-image-upload-card .color-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        font-weight: 600;
        color: #333;
    }

    .color-image-upload-card .color-preview {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .color-image-upload-card input[type="file"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
    }

    .color-image-upload-card input[type="file"]:hover {
        border-color: #28a745;
    }

    .color-image-preview {
        margin-top: 10px;
        max-width: 100%;
        max-height: 100px;
        border-radius: 6px;
        display: none;
    }
</style>

<script>
function showModal(message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('customModal');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('modalConfirm');
        const cancelBtn = document.getElementById('modalCancel');

        modalMessage.textContent = message;
        modal.style.display = 'block';
        
        // Focus confirm button for keyboard accessibility
        confirmBtn.focus();

        const closeModal = (result) => {
            modal.style.display = 'none';
            cleanup();
            resolve(result); // Remove the setTimeout
        };

        const handleKeydown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal(false);
            }
            if (e.key === 'Enter' && e.target === confirmBtn) {
                e.preventDefault();
                closeModal(true);
            }
        };

        const cleanup = () => {
            document.removeEventListener('keydown', handleKeydown);
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
            modal.onclick = null;
        };

        document.addEventListener('keydown', handleKeydown);
        confirmBtn.onclick = () => closeModal(true);
        cancelBtn.onclick = () => closeModal(false);
        modal.onclick = (e) => {
            if (e.target === modal) closeModal(false);
        };
    });
}

async function handleCategorySubmit(button) {
    const form = button.closest('form');
    const categoryName = form.querySelector('[name="category_name"]');

    if (!categoryName.value.trim()) {
        ToastManager.showToast('Category name is required', true);
        return;
    }

    try {
        const confirmed = await showModal('Are you sure you want to add this category?');
        if (confirmed) {
            // Add hidden input for category submission
            const submitTrigger = document.createElement('input');
            submitTrigger.type = 'hidden';
            submitTrigger.name = 'add_category';
            form.appendChild(submitTrigger);
            
            // Submit the form
            form.submit();
        }
    } catch (error) {
        console.error('Error:', error);
        ToastManager.showToast('An error occurred', true);
    }
}

async function handleDeleteCategory(button) {
    try {
        const confirmed = await showModal('Are you sure you want to delete this category?');
        if (confirmed) {
            const form = button.closest('form');
            // Add hidden input for delete submission
            const submitTrigger = document.createElement('input');
            submitTrigger.type = 'hidden';
            submitTrigger.name = 'delete_category';
            form.appendChild(submitTrigger);
            
            // Submit the form
            form.submit();
        }
    } catch (error) {
        console.error('Error:', error);
        ToastManager.showToast('An error occurred', true);
    }
}

// Remove the old validateForm function for category and delete operations
// Keep only the product validation part
// Note: validateForm function removed - handleProductSubmit and handleCategorySubmit handle validation directly

// Toast manager to prevent stacking
const ToastManager = {
    container: null,
    queue: [],
    showing: false,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    async showToast(message, isError = false) {
        this.init();
        
        const toast = document.createElement('div');
        toast.className = `toast ${isError ? 'error' : ''}`;
        
        const content = document.createElement('div');
        content.className = 'toast-content';

        const icon = document.createElement('i');
        icon.className = `toast-icon fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}`;
        
        const messageEl = document.createElement('p');
        messageEl.className = 'toast-message';
        messageEl.textContent = message;
        
        const progress = document.createElement('div');
        progress.className = 'toast-progress';

        content.appendChild(messageEl);
        toast.appendChild(icon);
        toast.appendChild(content);
        toast.appendChild(progress);
        
        this.container.appendChild(toast);

        // Remove toast after animation (increased from 3000 to 3500 to account for longer animation)
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.remove(), 300);
        }, 2000); // Reduced from 3500 to 2000
    }
};

// Update event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Auto-generate product code when category is selected
    const categorySelect = document.getElementById('category_id');
    const productCodeInput = document.getElementById('product_code');
    
    if (categorySelect && productCodeInput) {
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            if (categoryId) {
                // Fetch category name via AJAX to generate code
                fetch('manage_menu.php?get_category_name=' + categoryId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.category_name) {
                            // Generate prefix from category name
                            const prefix = data.category_name.toUpperCase()
                                .replace(/[^A-Z0-9]/g, '')
                                .substring(0, 4) || 'PROD';
                            
                            // For preview, show format (actual generation happens on server)
                            productCodeInput.value = prefix + '0001 (Auto-generated)';
                            productCodeInput.setAttribute('data-prefix', prefix);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching category:', error);
                        productCodeInput.value = 'PROD0001 (Auto-generated)';
                    });
            } else {
                productCodeInput.value = '';
            }
        });
    }
    
    // Remove the form.onsubmit assignments and let the onclick handlers work
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.onchange = function() {
            const label = this.parentElement.querySelector('span');
            label.textContent = this.files[0]?.name || 'Choose an image';
        };
    });

    // Initial messages
    <?php if (isset($_SESSION['success_message'])): ?>
        ToastManager.showToast("<?= $_SESSION['success_message'] ?>");
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        ToastManager.showToast("<?= $_SESSION['error_message'] ?>", true);
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
});

// Add new product handler function
async function handleProductSubmit(button) {
    const form = button.closest('form');
    let isValid = true;
    const errors = [];

    // Product validations
    // Product validations
    const productName = form.querySelector('[name="product_name"]');
    const price = form.querySelector('[name="price"]');
    const fileInput = form.querySelector('[name="image_url"]');
    const category = form.querySelector('[name="category_id"]');
    const colorCheckboxes = form.querySelectorAll('input[name="colors[]"]:checked');
    const customColors = form.querySelector('[name="custom_colors"]');
    const sizeCheckboxes = form.querySelectorAll('input[name="available_sizes[]"]:checked');
    const customSizes = form.querySelector('[name="custom_sizes"]');

    // Reset previous errors
    form.querySelectorAll('.form-group.error').forEach(group => {
        group.classList.remove('error');
    });

    // Validate product name
    if (!productName.value.trim() || productName.value.length > 255) {
        errors.push('Product name is required and must be less than 255 characters');
        productName.closest('.form-group').classList.add('error');
        isValid = false;
    }

    // Product code is auto-generated, no validation needed

    // Validate price
    const priceValue = parseFloat(price.value);
    if (isNaN(priceValue) || priceValue <= 0 || priceValue > 999999.99) {
        errors.push('Please enter a valid price between 0 and 999,999.99');
        price.closest('.form-group').classList.add('error');
        isValid = false;
    }

    // Validate category
    if (!category.value) {
        errors.push('Please select a category');
        category.closest('.form-group').classList.add('error');
        isValid = false;
    }

    // Validate colors (check if either checkbox is selected OR custom color is entered)
    if (colorCheckboxes.length === 0 && (!customColors.value || customColors.value.trim() === '')) {
        errors.push('Please select at least one color or enter a custom color');
        isValid = false;
    }

    // Validate sizes (check if either checkbox is selected OR custom size is entered)
    if (sizeCheckboxes.length === 0 && (!customSizes.value || customSizes.value.trim() === '')) {
        errors.push('Please select at least one size or enter a custom size');
        isValid = false;
    }

    // Validate file
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const maxSize = 5 * 1024 * 1024;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (file.size > maxSize) {
            errors.push('File size must be less than 5MB');
            isValid = false;
        }
        
        if (!allowedTypes.includes(file.type)) {
            errors.push('Only JPG, PNG, and GIF files are allowed');
            isValid = false;
        }
    } else {
        errors.push('Please select an image');
        isValid = false;
    }

    if (!isValid) {
        ToastManager.showToast(errors.join('\n'), true);
        return;
    }

    try {
        const confirmed = await showModal('Are you sure you want to add this product?');
        if (confirmed) {
            // Add hidden input for product submission
            const submitTrigger = document.createElement('input');
            submitTrigger.type = 'hidden';
            submitTrigger.name = 'add_product';
            form.appendChild(submitTrigger);
            
            // Add loading state
            button.disabled = true;
            button.textContent = 'Processing...';
            
            // Submit the form
            form.submit();
        }
    } catch (error) {
        console.error('Error:', error);
        ToastManager.showToast('An error occurred', true);
        button.disabled = false;
        button.textContent = 'Add Product';
    }
}

// ===== Color Image Upload Management =====
const colorHexMap = {
    'Black': '#000000',
    'White': '#FFFFFF',
    'Navy Blue': '#001f3f',
    'Navy_Blue': '#001f3f',
    'Red': '#DC3545',
    'Green': '#28a745',
    'Gray': '#6c757d',
    'Brown': '#8B4513',
    'Beige': '#F5F5DC'
};

function getColorHex(colorName) {
    return colorHexMap[colorName] || '#ddd';
}

function sanitizeColorKey(color) {
    return color.replace(/[^a-zA-Z0-9]/g, '_');
}

function updateColorImageUploads() {
    const colorCheckboxes = document.querySelectorAll('input[name="colors[]"]:checked');
    const customColorsInput = document.getElementById('custom_colors');
    const section = document.getElementById('colorImagesSection');
    const container = document.getElementById('colorImageUploads');
    
    if (!section || !container) return;
    
    // Gather all selected colors
    let selectedColors = [];
    colorCheckboxes.forEach(cb => {
        selectedColors.push(cb.value);
    });
    
    // Add custom colors if any
    if (customColorsInput && customColorsInput.value.trim()) {
        const customColors = customColorsInput.value.split(',').map(c => c.trim()).filter(c => c);
        selectedColors = selectedColors.concat(customColors);
    }
    
    // Track active keys to manage visibility
    const activeKeys = new Set();
    const hasColors = selectedColors.length > 0;
    
    if (hasColors) {
        section.style.display = 'block';
        
        selectedColors.forEach(color => {
            const colorKey = sanitizeColorKey(color);
            activeKeys.add(colorKey);
            
            const colorName = color.includes(':') ? color.split(':')[0].trim() : color;
            const colorHex = color.includes(':') ? color.split(':')[1].trim() : getColorHex(color);
            
            // Check if card already exists
            let card = document.getElementById('upload_card_' + colorKey);
            
            if (!card) {
                card = document.createElement('div');
                card.id = 'upload_card_' + colorKey;
                card.className = 'color-image-upload-card';
                card.innerHTML = `
                    <div class="color-label">
                        <span class="color-preview" style="background-color: ${colorHex}; ${colorName === 'White' ? 'border: 1px solid #ddd;' : ''}"></span>
                        <span>${colorName}</span>
                    </div>
                    <input type="file" 
                           name="color_images[${colorKey}]" 
                           accept="image/*" 
                           onchange="previewColorImage(this, '${colorKey}')">
                    <img id="preview_${colorKey}" class="color-image-preview" alt="Preview">
                `;
                container.appendChild(card);
            }
            
            // Ensure card is visible
            card.style.display = 'block';
        });
    } else {
        section.style.display = 'none';
    }
    
    // Hide cards for colors that are no longer selected
    const allCards = container.querySelectorAll('.color-image-upload-card');
    allCards.forEach(card => {
        const idPart = card.id.replace('upload_card_', '');
        if (!activeKeys.has(idPart)) {
            card.style.display = 'none';
            // clear the input value so it doesn't get submitted
            const input = card.querySelector('input[type="file"]');
            if (input) input.value = ''; 
        }
    });
}

function previewColorImage(input, colorKey) {
    const preview = document.getElementById('preview_' + colorKey);
    if (input.files && input.files[0] && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize color image upload listeners on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add listeners to color checkboxes
    const colorCheckboxes = document.querySelectorAll('input[name="colors[]"]');
    colorCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateColorImageUploads);
    });
    
    // Add listener to custom colors input
    const customColorsInput = document.getElementById('custom_colors');
    if (customColorsInput) {
        customColorsInput.addEventListener('blur', updateColorImageUploads);
        customColorsInput.addEventListener('keyup', function(e) {
            if (e.key === ',' || e.key === 'Enter') {
                updateColorImageUploads();
            }
        });
    }
    
    // Initial update in case there are pre-selected colors
    updateColorImageUploads();
});
</script>
