<?php
session_start();
require_once 'includes/connection.php';

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

// Ensure the product ID is set in the URL or POST request
if (isset($_GET['product_id']) || isset($_POST['product_id'])) {
    $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : $_POST['product_id'];

    // Prevent SQL injection by ensuring the product ID is an integer
    $product_id = intval($product_id);

    // Fetch the current product details
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_result = $stmt->get_result();

    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Product not found!";
        header("Location: adminmenu.php");
        exit;
    }

    $stmt->close();
} else {
    $_SESSION['error_message'] = "Product ID not provided!";
    header("Location: adminmenu.php");
    exit;
}

// Handle form submission for updating the product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_name = $_POST['name'];
    $product_description = $_POST['description'];
    $product_price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $category_id = $_POST['category_id'];
    
    // Handle colors - merge checkboxes and custom input
    $selected_colors = isset($_POST['colors']) && is_array($_POST['colors']) ? $_POST['colors'] : [];
    $custom_colors_str = isset($_POST['custom_colors']) ? trim($_POST['custom_colors']) : '';
    if ($custom_colors_str !== '') {
        $custom_colors_array = array_map('trim', explode(',', $custom_colors_str));
        $selected_colors = array_merge($selected_colors, $custom_colors_array);
    }
    $product_colors = implode(',', array_unique(array_filter($selected_colors)));

    // Handle sizes - merge checkboxes and custom input
    $selected_sizes = isset($_POST['available_sizes']) && is_array($_POST['available_sizes']) ? $_POST['available_sizes'] : [];
    $custom_sizes_str = isset($_POST['custom_sizes']) ? trim($_POST['custom_sizes']) : '';
    if ($custom_sizes_str !== '') {
        $custom_sizes_array = array_map('trim', explode(',', $custom_sizes_str));
        $selected_sizes = array_merge($selected_sizes, $custom_sizes_array);
    }
    $product_sizes = implode(',', array_unique(array_filter($selected_sizes)));

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

    $product_image = $_FILES['image_url']['name'];

    // If a new image is uploaded, move the file to the appropriate directory
    if ($product_image) {
        $image_path = 'uploads/' . basename($product_image);
        move_uploaded_file($_FILES['image_url']['tmp_name'], $image_path);
    } else {
        $image_path = $product['image_url']; // Keep the old image if no new one is uploaded
    }

    // Update the product in the database
    $update_query = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image_url = ?, colors = ?, available_sizes = ?, featured_type = ?, product_status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssdisssssi", $product_name, $product_description, $product_price, $category_id, $image_path, $product_colors, $product_sizes, $featured_type, $product_status, $product_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Product updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating product!";
    }

    $stmt->close();
    header("Location: adminmenu.php");
    exit;
}

// Fetch categories for the category dropdown
$category_query = "SELECT * FROM categories";
$category_result = mysqli_query($conn, $category_query);
?>

<?php require "includes/adminside.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        /* Popup Message Styles */
       /* Popup Message Styles */
/* Popup Message Styles */
/* Popup Message Styles */
.popup-message {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 15px 30px;
    background-color: hsl(115, 29%, 40%); /* Green background */
    color: white;
    border-radius: 5px;
    font-size: 16px;
    display: none; /* Initially hidden */
    z-index: 1000;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
    font-family: 'Arial', sans-serif; /* Arial font added */
}
.popup-message.error {
    background-color: #f44336; /* Red for error */
}

/* Modal and form styling */
.main-container {
    max-width: 1900px;
    padding: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    font-family: 'Arial', sans-serif; /* Arial font added */
}

h2 {
    color: hsl(115, 29%, 40%); /* Green for headings */
    font-family: 'Arial', sans-serif; /* Arial font added */
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    font-size: 16px;
    font-weight: bold;
    display: block;
    color: hsl(115, 29%, 40%); /* Green for labels */
    font-family: 'Arial', sans-serif; /* Arial font added */
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 16px;
    margin-top: 8px;
    font-family: 'Arial', sans-serif; /* Arial font added */
}

.form-control:focus {
    outline: none;
    border-color: hsl(115, 29%, 40%); /* Green focus border */
}

.btn {
    padding: 12px 25px;
    background-color: hsl(115, 29%, 40%); /* Green for buttons */
    color: white;
    font-size: 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-family: 'Arial', sans-serif; /* Arial font added */
}

.btn:hover {
    background-color: hsl(115, 29%, 35%); /* Slightly darker green for hover */
}

.img-thumbnail {
    margin-top: 15px;
    max-width: 150px;
    border-radius: 9px;
}

/* Button Styling for Modal and Page */
.action-btn {
    font-size: 16px;
    padding: 8px 15px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    border: none;
    background: none;
    display: flex;
    flex-direction: column; /* Stack icons vertically */
    justify-content: center; /* Center icons */
    align-items: center; /* Center icons */
    gap: 8px; /* Add spacing between icons */
    font-family: 'Arial', sans-serif; /* Arial font added */
}

/* Table styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    border-radius: 14px;
    overflow: hidden;
    font-family: 'Arial', sans-serif; /* Arial font added */
}

th, td {
    padding: 12px;
    text-align: left;
    font-family: 'Arial', sans-serif; /* Arial font added */
    color: hsl(115, 29%, 40%); /* Green text for table content */
}

th {
    background-color: hsl(115, 29%, 40%); /* Green background for header */
    color: #fff;
}

td {
    font-size: 14px;
    background-color: #ffffff;
}

tr:nth-child(odd) td {
    background-color: #f0f8f0;
}

tr:nth-child(even) td {
    background-color: #ffffff;
}

tr:hover td {
    background-color: #e1f5e1;
}


    /* Checkbox Chip Styling */
    .checkbox-chip {
        display: inline-block;
        margin-right: 8px;
        margin-bottom: 8px;
        position: relative;
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
        cursor: pointer;
    }

    .checkbox-chip input:checked + span {
        background-color: #e8f5e9;
        border-color: #28a745;
        color: #155724;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.1);
    }

    /* Color Swatch Styling */
    .color-swatch-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
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
        border: 1px solid #eee;
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
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <main>
        <section class="edit-product-section">
            <div class="main-container">
                <h2>Edit Product</h2>

                <!-- Display Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="popup-message"><?= $_SESSION['success_message'] ?></div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="popup-message error"><?= $_SESSION['error_message'] ?></div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" required><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" name="price" id="price" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" required step="0.01">
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <?php while ($category = mysqli_fetch_assoc($category_result)): ?>
                                <option value="<?= $category['id'] ?>" <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <?php 
                        // Parse existing colors and sizes
                        $existing_colors = !empty($product['colors']) ? array_map('trim', explode(',', $product['colors'])) : [];
                        $existing_sizes = !empty($product['available_sizes']) ? array_map('trim', explode(',', $product['available_sizes'])) : [];
                        
                        $standard_colors = ['Black', 'White', 'Navy Blue', 'Red', 'Green', 'Gray', 'Brown', 'Beige'];
                        $standard_sizes = ['XS', 'S', 'M', 'L', 'XL', '2XL'];
                        
                        // Separate custom values
                        $custom_colors = array_diff($existing_colors, $standard_colors);
                        $custom_sizes = array_diff($existing_sizes, $standard_sizes);
                        ?>

                        <div class="form-group">
                            <label class="static-label">Available Colors</label>
                            <div class="color-swatch-group">
                                <?php foreach ($standard_colors as $color): 
                                    $hex = get_color_hex($color);
                                ?>
                                    <label class="color-swatch-option" title="<?= htmlspecialchars($color) ?>">
                                        <input type="checkbox" name="colors[]" value="<?= $color ?>" <?= in_array($color, $existing_colors) ? 'checked' : '' ?>>
                                        <span class="color-swatch" style="background-color: <?= $hex ?>; <?= ($color == 'White') ? 'border:2px solid #ddd;' : 'border:2px solid transparent;' ?>"></span>
                                        <span class="color-name"><?= $color ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-group" style="margin-top: 10px;">
                                <input type="text" name="custom_colors" id="custom_colors" class="form-control" value="<?= htmlspecialchars(implode(', ', $custom_colors)) ?>" placeholder="e.g. Gold, Silver">
                                <small style="color: #666; font-size: 13px;">Custom Colors (comma separated)</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="static-label">Available Sizes</label>
                            <div style="padding: 10px 0;">
                                <?php foreach ($standard_sizes as $size): ?>
                                    <label class="checkbox-chip">
                                        <input type="checkbox" name="available_sizes[]" value="<?= $size ?>" <?= in_array($size, $existing_sizes) ? 'checked' : '' ?>>
                                        <span><?= $size ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-group" style="margin-top: 10px;">
                                <input type="text" name="custom_sizes" id="custom_sizes" class="form-control" value="<?= htmlspecialchars(implode(', ', $custom_sizes)) ?>" placeholder="e.g. 28, 30, 32, One Size">
                                <small style="color: #666; font-size: 13px;">Custom Sizes (comma separated)</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="featured_type">Featured Section</label>
                        <select name="featured_type" id="featured_type" class="form-control">
                            <option value="" <?= empty($product['featured_type']) ? 'selected' : '' ?>>Not Featured</option>
                            <option value="best_seller" <?= ($product['featured_type'] ?? '') === 'best_seller' ? 'selected' : '' ?>>Best Seller</option>
                            <option value="trending" <?= ($product['featured_type'] ?? '') === 'trending' ? 'selected' : '' ?>>Trending Now</option>
                            <option value="popular" <?= ($product['featured_type'] ?? '') === 'popular' ? 'selected' : '' ?>>Popular Picks</option>
                            <option value="hot_deal" <?= ($product['featured_type'] ?? '') === 'hot_deal' ? 'selected' : '' ?>>Hot Deal</option>
                        </select>
                        <small style="color: #666; font-size: 13px;">Select which section this product should appear in on the homepage</small>
                    </div>

                    <div class="form-group">
                        <label for="product_status">Product Status Badge</label>
                        <select name="product_status" id="product_status" class="form-control">
                            <option value="" <?= empty($product['product_status']) ? 'selected' : '' ?>>No Badge</option>
                            <option value="sale" <?= ($product['product_status'] ?? '') === 'sale' ? 'selected' : '' ?>>SALE</option>
                            <option value="new" <?= ($product['product_status'] ?? '') === 'new' ? 'selected' : '' ?>>NEW</option>
                            <option value="hot" <?= ($product['product_status'] ?? '') === 'hot' ? 'selected' : '' ?>>HOT</option>
                            <option value="sold_out" <?= ($product['product_status'] ?? '') === 'sold_out' ? 'selected' : '' ?>>SOLD OUT</option>
                        </select>
                        <small style="color: #666; font-size: 13px;">Display a status badge on the product card</small>
                    </div>

                    <div class="form-group">
                        <label for="image_url">Product Image</label>
                        <input type="file" name="image_url" id="image_url" class="form-control">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Current Image" class="img-thumbnail mt-3">
                    </div>

                    <button type="submit" name="update_product" class="btn">Update Product</button>
                </form>
            </div>
        </section>
    </main>

    <script>
        // You can add any necessary JS functions here (e.g., for validation, popup handling)
    </script>
</body>
</html>
