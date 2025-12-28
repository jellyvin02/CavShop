<?php
session_start();
header('Content-Type: application/json');

if (!isset($_FILES['payment_proof'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$uploadDir = 'uploads/payment_proofs/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['payment_proof'];
$fileName = time() . '_' . basename($file['name']);
$targetPath = $uploadDir . $fileName;

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG and PNG allowed']);
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit;
}

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Store payment information in database
    // You'll need to implement this part based on your database structure
    $paymentMethod = $_POST['payment_method'];
    $amount = $_POST['amount'];
    $userId = $_SESSION['user_id'] ?? null;
    
    // TODO: Add database insertion code here
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment proof uploaded successfully',
        'file_path' => $targetPath
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}
?>