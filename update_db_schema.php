<?php
include 'includes/connection.php';

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Check and add payment_status column if it doesn't exist
    $check = $conn->query("SHOW COLUMNS FROM archived_orders LIKE 'payment_status'");
    
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE archived_orders ADD COLUMN payment_status VARCHAR(50) DEFAULT 'Unpaid' AFTER payment_method";
        if ($conn->query($sql)) {
            echo "Successfully added payment_status column to archived_orders table.\n";
        } else {
            echo "Error adding payment_status column: " . $conn->error . "\n";
        }
    } else {
        echo "Column payment_status already exists in archived_orders table.\n";
    }
    
    // Check and add payment_proof column if it doesn't exist
    $check = $conn->query("SHOW COLUMNS FROM archived_orders LIKE 'payment_proof'");
    
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE archived_orders ADD COLUMN payment_proof VARCHAR(255) DEFAULT NULL AFTER payment_status";
        if ($conn->query($sql)) {
            echo "Successfully added payment_proof column to archived_orders table.\n";
        } else {
            echo "Error adding payment_proof column: " . $conn->error . "\n";
        }
    } else {
        echo "Column payment_proof already exists in archived_orders table.\n";
    }
    
    echo "\nDatabase schema update complete!\n";
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
