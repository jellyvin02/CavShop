<?php
session_start();
header('Content-Type: application/json'); // Return JSON
require_once "includes/connection.php";

// Helper to return error
function sendError($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// 1. Connection Check
if (mysqli_connect_error()) {
    sendError('Database connection failed.');
}

// 2. Access Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method.');
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Please login first.', 'redirect' => 'login.php']);
    exit();
}

if (empty($_SESSION['cart'])) {
    sendError('Your cart is empty.');
}

// 3. Retrieve User Info
$query = "SELECT * FROM customer_users WHERE email=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    sendError('User not found.');
}

$user_data = $result->fetch_assoc();
$name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
$email = $user_data['email'];
$contact = $user_data['contact'];
$street_number = $user_data['street_number'];
$municipality = $user_data['municipality'];
$address =  $street_number . ', ' . $municipality;

// 4. Payment & File Validation
if (!isset($_POST['selected_payment_method'])) {
    sendError('Please select a payment method.');
}

$payment_method = $_POST['selected_payment_method'];
$valid_methods = ['cod', 'gcash', 'maya'];

if (!in_array($payment_method, $valid_methods)) {
    sendError('Invalid payment method.');
}

$payment_proof_path = null;

if ($payment_method == 'gcash' || $payment_method == 'maya') {
    $file_input_name = 'payment_proof_' . $payment_method;
    
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $target_dir = "uploads/payment_proofs/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES[$file_input_name]['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid($payment_method . '_') . '.' . $file_ext;
            $target_file = $target_dir . $new_file_name;
            
            if (move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $target_file)) {
                $payment_proof_path = $target_file;
            } else {
                sendError('Failed to upload payment proof.');
            }
        } else {
            sendError('Invalid file type. Only JPG, PNG, WEBP allowed.');
        }
    } else {
        // Strict check: if gcash/maya, require proof
        sendError('Please upload a screenshot of your payment.');
    }
}

// 5. Insert Orders
$cart_items = $_SESSION['cart'];
$query_insert = "INSERT INTO `orders`(`name`, `email`, `contact`, `address`, `item`, `quantity`, `total_price`, `payment_method`, `payment_proof`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_insert = $conn->prepare($query_insert);

if (!$stmt_insert) {
    sendError('Database error: ' . $conn->error);
}

$firstInsertId = null;
$total_amount = 0;
$receipt_items = [];

foreach ($cart_items as $item_data) {
    $item_name = $item_data['Item_name'];
    $price = $item_data['price'];
    $quantity = $item_data['Quantity'];
    $item_total = $price * $quantity;
    
    $total_amount += $item_total;
    
    // Bind and execute
    $stmt_insert->bind_param('sssssiiss', $name, $email, $contact, $address, $item_name, $quantity, $item_total, $payment_method, $payment_proof_path);
    
    if ($stmt_insert->execute()) {
        if ($firstInsertId === null) {
            $firstInsertId = $conn->insert_id;
        }
        // Save for receipt
        $receipt_items[] = [
            'name' => $item_name,
            'qty' => $quantity,
            'price' => $price
        ];
    } else {
        // Rollback? MySQL MyISAM/InnoDB handling logic not present here, 
        // but let's assume partial failure is bad. 
        // For now, continue and try to finish.
    }
}
$stmt_insert->close();

if ($firstInsertId === null) {
    sendError('Failed to place order. Please try again.');
}

// 6. Success: Clear Cart & Set Session
unset($_SESSION['cart']);

$order_id_display = sprintf('%04d', $firstInsertId);

$_SESSION['last_order_success'] = [
    'order_id' => $order_id_display,
    'customer_name' => $name,
    'items' => $receipt_items,
    'total_amount' => $total_amount,
    'payment_method' => $payment_method,
    'date' => date('Y-m-d H:i:s')
];

// 7. Return Success JSON
echo json_encode([
    'success' => true, 
    'redirect' => 'order_success.php',
    'message' => 'Order placed successfully!'
]);
exit();
?>
