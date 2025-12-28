<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/includes/connection.php'; // Ensure database connection is available

// Helper function to return JSON response
function sendJSON($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Function to calculate cart totals
function getCartTotals() {
    $count = 0;
    $total = 0;
    if (isset($_SESSION['cart'])) {
        $count = count($_SESSION['cart']);
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['Quantity'];
        }
    }
    return ['count' => $count, 'total' => $total, 'formatted_total' => '₱' . number_format($total, 2)];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- ADD TO CART ---
    if (isset($_POST['Add_To_Cart'])) {
        $item_name = $_POST['Item_name'];
        $base_price = floatval($_POST['base_price']);
        $image_url = isset($_POST['image_url']) ? $_POST['image_url'] : './assets/images/placeholder.jpg';
        $size = isset($_POST['size']) ? $_POST['size'] : 'M';
        $color = isset($_POST['color']) ? $_POST['color'] : 'Black'; 
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        // Update size price modifiers to match modal values
        $size_modifier = [
            'XS' => 0.9,
            'S' => 0.95,
            'M' => 1.0,
            'L' => 1.1,
            'XL' => 1.2,
            '2XL' => 1.3,
            'regular' => 1.0,
            'large' => 1.2,
            'party' => 1.5
        ];
        
        if (!isset($size_modifier[$size])) {
            $size_modifier[$size] = 1.0;
        }
        
        $toppings = [];
        if (isset($_POST['toppings'])) {
            if (is_array($_POST['toppings'])) {
                $toppings = $_POST['toppings'];
            } else {
                $decoded = json_decode($_POST['toppings'], true);
                $toppings = is_array($decoded) ? $decoded : [];
            }
        }
        
        $topping_prices = [
            'Pepperoni' => 25,
            'Mushrooms' => 15,
            'Onions' => 10,
            'Sausage' => 20,
            'Bacon' => 30,
            'Extra Cheese' => 20,
            'Black Olives' => 15,
            'Green Peppers' => 15,
            'Pineapple' => 20,
            'Spinach' => 15
        ];
        
        $toppings_cost = 0;
        foreach ($toppings as $topping) {
            if (isset($topping_prices[$topping])) {
                $toppings_cost += $topping_prices[$topping];
            }
        }
        
        $final_price = ($base_price * $size_modifier[$size]) + $toppings_cost;

        $item_id = $item_name . '_' . $size . '_' . $color . '_' . implode('_', $toppings);

        if (isset($_SESSION['cart'])) {
            $item_exists = false;
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['item_id'] === $item_id) {
                    $new_quantity = $item['Quantity'] + $quantity;
                    $_SESSION['cart'][$key]['Quantity'] = min(10, $new_quantity);
                    if (!isset($_SESSION['cart'][$key]['image_url']) || empty($_SESSION['cart'][$key]['image_url'])) {
                        $_SESSION['cart'][$key]['image_url'] = $image_url;
                    }
                    $item_exists = true;
                    break;
                }
            }
            
            if (!$item_exists) {
                $_SESSION['cart'][] = array(
                    'item_id' => $item_id,
                    'Item_name' => $item_name,
                    'price' => $final_price,
                    'Quantity' => $quantity,
                    'size' => $size,
                    'color' => $color,
                    'toppings' => $toppings,
                    'image_url' => $image_url
                );
            }
        } else {
            $_SESSION['cart'] = array(
                array(
                    'item_id' => $item_id,
                    'Item_name' => $item_name,
                    'price' => $final_price,
                    'Quantity' => $quantity,
                    'size' => $size,
                    'color' => $color,
                    'toppings' => $toppings,
                    'image_url' => $image_url
                )
            );
        }
        
        if(isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
            $totals = getCartTotals();
            sendJSON(['success' => true, 'message' => 'Item added to cart', 'cart_count' => $totals['count'], 'cart_total' => $totals['formatted_total']]);
        }

        $_SESSION['cart_success'] = true;
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // --- MODIFY QUANTITY ---
    if (isset($_POST['Mod_Quantity'])) {
        $new_quantity = min(10, max(1, intval($_POST['Mod_Quantity'])));
        $item_found = false;
        
        if (isset($_POST['cart_key'])) {
             $key = intval($_POST['cart_key']);
             if(isset($_SESSION['cart'][$key])) {
                 $_SESSION['cart'][$key]['Quantity'] = $new_quantity;
                 $item_found = true;
             }
        }
        // Fallback checks (legacy)
        else if (isset($_POST['item_id']) && !empty($_POST['item_id'])) {
            foreach ($_SESSION['cart'] as $key => $value) {
                if (isset($value['item_id']) && $value['item_id'] == $_POST['item_id']) {
                    $_SESSION['cart'][$key]['Quantity'] = $new_quantity;
                    $item_found = true;
                    break;
                }
            }
        }
        else if (isset($_POST['Item_name']) && !empty($_POST['Item_name'])) {
            foreach ($_SESSION['cart'] as $key => $value) {
                if (isset($value['Item_name']) && $value['Item_name'] == $_POST['Item_name']) {
                    $_SESSION['cart'][$key]['Quantity'] = $new_quantity;
                    $item_found = true;
                    break;
                }
            }
        }
        
        if ($item_found) {
            $totals = getCartTotals();
            // Calculate item total
            $item_total = 0;
             if (isset($_POST['cart_key']) && isset($_SESSION['cart'][$_POST['cart_key']])) {
                 $item_total = $_SESSION['cart'][$_POST['cart_key']]['price'] * $_SESSION['cart'][$_POST['cart_key']]['Quantity'];
             }
             
            sendJSON([
                'success' => true, 
                'cart_count' => $totals['count'], 
                'cart_total' => $totals['formatted_total'],
                'item_total' => '₱' . number_format($item_total, 2)
            ]);
        } else {
            sendJSON(['success' => false, 'error' => 'Item not found in cart']);
        }
    }

    // --- REMOVE ITEM ---
    if (isset($_POST['remove_item'])) {
        $removed = false;
        
        if (isset($_POST['cart_key'])) {
            $key = intval($_POST['cart_key']);
            if (isset($_SESSION['cart'][$key])) {
                unset($_SESSION['cart'][$key]);
                $removed = true;
            }
        }
        // Legacy fallback
        else if(isset($_POST['Item_name'])) {
             foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['Item_name'] == $_POST['Item_name']) {
                    unset($_SESSION['cart'][$key]);
                    $removed = true;
                    break;
                }
            }
        }

        if($removed) {
             $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex
             
             if(isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
                 $totals = getCartTotals();
                 sendJSON([
                     'success' => true, 
                     'message' => 'Item removed',
                     'cart_count' => $totals['count'],
                     'cart_total' => $totals['formatted_total'],
                     'cart_empty' => $totals['count'] === 0
                 ]);
             }
             
             $_SESSION['remove_success'] = true;
             header("Location: ".$_SERVER['HTTP_REFERER']);
             exit();
        }
        
        if(isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
             sendJSON(['success' => false, 'error' => 'Item not found']);
        }
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }
}

// --- GET CART HTML (for AJAX reload) ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['get_cart_html'])) {
   ob_start();
   // The styling/structure here must match header.php's cart-box-ul structure
   $cart_subtotal = 0;
   
   // Header is now handled in header.php or returned here if needed
   echo '<div class="cart-header">
            <h4 class="cart-h4">Shopping Cart</h4>
            <button class="cart-close-btn" id="close-cart"><i class="fas fa-times"></i></button>
          </div>
          
          <div id="cartLoader" class="cart-loader-overlay">
              <div class="spinner"></div>
              <div class="loading-msg">Preparing Checkout...</div>
              <div class="loading-sub">Taking you to payment</div>
          </div>';
   
   if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
       echo '<ul class="cart-box-ul">';
       foreach ($_SESSION['cart'] as $key => $value) {
           $itemTotal = $value['price'] * $value['Quantity'];
           $cart_subtotal += $itemTotal;
           
           $item_image = './assets/images/placeholder.jpg';
           if (isset($value['image_url']) && !empty($value['image_url'])) {
               $image_path = trim($value['image_url']);
               if (!empty($image_path)) {
                   if (!preg_match('/^(https?:\/\/|\/)/', $image_path)) {
                       $image_path = './' . ltrim($image_path, './');
                   }
                   $item_image = htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8');
               }
           }
           
           echo "<li class='cart-item' id='cart-item-{$key}'>
               <div class='item-container'>
                   <div class='img-box'>
                       <img src='{$item_image}' alt='" . htmlspecialchars($value['Item_name'], ENT_QUOTES, 'UTF-8') . "' class='product-img'>
                   </div>
                   <div class='item-details'>
                       <h5 class='item-name'>" . htmlspecialchars($value['Item_name']) . "</h5>
                       <div class='item-customizations'>";
                       
                       if(isset($value['color'])) echo "<div class='size-info' style='margin-right: 5px;'>Color: " . htmlspecialchars($value['color']) . "</div>";
                       if(isset($value['size'])) echo "<div class='size-info'>Size: " . htmlspecialchars(ucfirst($value['size'])) . "</div>";
                       if(isset($value['toppings']) && !empty($value['toppings'])) echo "<div class='toppings-info'>Extras: " . htmlspecialchars(implode(', ', $value['toppings'])) . "</div>";
                       
                   echo "</div>
                       <div class='price'>₱" . number_format($value['price'], 2) . "</div>
                       <div class='quantity-controls'>
                           <button type='button' onclick='updateCartQuantity({$key}, -1)' class='qty-btn'>-</button>
                           <input class='iquantity' type='number' value='" . (int)$value['Quantity'] . "' readonly>
                           <button type='button' onclick='updateCartQuantity({$key}, 1)' class='qty-btn'>+</button>
                       </div>
                   </div>
               </div>
               <div class='item-actions'>
                   <span class='itotal'>₱" . number_format($itemTotal, 2) . "</span>
                   <button onclick='removeCartItem({$key})' class='remove-button'>
                       <i class='fas fa-trash'></i>
                   </button>
               </div>
           </li>";
       }
       echo '</ul>';
       
       echo "<div class='cart-footer'>
                <div class='order-summary'>
                    <div class='summary-row'>
                        <span>Subtotal:</span>
                        <span id='cart-subtotal'>₱" . number_format($cart_subtotal, 2) . "</span>
                    </div>
                    <div class='summary-row total'>
                        <span>Total:</span>
                        <span id='cart-total'>₱" . number_format($cart_subtotal, 2) . "</span>
                    </div>
                </div>
                
                 <div class='cart-btn-group'>
                     <a href='index.php' class='btn btn-secondary'>View Menu</a>
                     <a href='javascript:void(0)' onclick='startCheckoutAnimation()' class='btn btn-primary'>Checkout</a>
                 </div>
            </div>";
   } else {
       echo "<div class='empty-cart'>
               <img src='./assets/images/empty-cart.png' alt='Empty Cart' style='width: 120px; margin: 0 auto 20px; opacity: 0.5;'>
               <p>Your cart is empty</p>
               <a href='index.php' class='browse-menu-btn'>Browse Menu</a>
           </div>";
   }
   
   $html = ob_get_clean();
   sendJSON(['html' => $html, 'count' => isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0]);
}
?>
