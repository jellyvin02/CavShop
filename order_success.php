<?php
session_start();
if (!isset($_SESSION['last_order_success'])) {
    echo "<script>window.location.href='menu.php';</script>";
    exit();
}
$order = $_SESSION['last_order_success'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - CavShop Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/vizza.css">
    
    <style>
        :root {
            --brand-saffron: hsl(115, 29%, 45%);
            --brand-saffron-dark: #028a0f;
            --brand-saffron-light: hsla(115, 29%, 45%, 0.1);
        }

        body {
            background-color: #f4f6f8;
            font-family: "Rubik", sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-page-wrapper {
            width: 100%;
            padding: 40px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-container {
            width: 100%;
            max-width: 1100px;
            padding: 0 40px;
        }

        /* ANIMATIONS */
        .fade-in-up {
            animation: fadeInUp 0.8s cubic-bezier(0.2, 1, 0.3, 1) both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* CARD STYLES */
        .success-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            min-height: 500px;
        }

        .row { display: flex; flex-wrap: wrap; margin: 0; }
        .col-left { flex: 0 0 40%; border-right: 1px solid #f0f0f0; padding: 3.5rem 3rem; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; }
        .col-right { flex: 0 0 60%; padding: 3rem; background: #fdfdfd; }

        .confirmation-content { text-align: center; width: 100%; }

        /* Logo Inside Card */
        .card-logo {
            margin-bottom: 1.5rem;
        }
        .card-logo img {
            width: 120px;
            height: auto;
        }

        .icon-pulse {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, var(--brand-saffron) 0%, var(--brand-saffron-dark) 100%);
            color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 36px; margin: 0 auto 1.5rem;
            box-shadow: 0 15px 30px hsla(115, 29%, 45%, 0.25);
            animation: iconPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes iconPop { 0% { transform: scale(0); } 80% { transform: scale(1.1); } 100% { transform: scale(1); } }

        .premium-heading { font-size: 2.2rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem; letter-spacing: -1px; }

        .status-badge {
            display: inline-flex; align-items: center; gap: 10px;
            background: var(--brand-saffron-light); padding: 6px 16px; border-radius: 50px;
            border: 1px solid hsla(115, 29%, 45%, 0.1); margin-bottom: 1rem;
        }
        .status-dot { width: 8px; height: 8px; background: var(--brand-saffron); border-radius: 50%; }
        .status-dot.pulse { animation: pulseStatus 1.5s infinite; }
        @keyframes pulseStatus {
            0% { box-shadow: 0 0 0 0 hsla(115, 29%, 45%, 0.4); }
            70% { box-shadow: 0 0 0 10px hsla(115, 29%, 45%, 0); }
            100% { box-shadow: 0 0 0 0 hsla(115, 29%, 45%, 0); }
        }
        .status-text { font-size: 0.85rem; font-weight: 700; color: var(--brand-saffron); text-transform: uppercase; letter-spacing: 0.5px; }

        .premium-subtext { color: #666; margin-bottom: 1.5rem; font-size: 1rem; line-height: 1.5; }

        .action-buttons { display: flex; flex-direction: column; gap: 12px; width: 100%; max-width: 250px; margin: 0 auto; }

        .btn-premium {
            border-radius: 12px; padding: 15px; font-weight: 600; font-size: 0.95rem;
            transition: all 0.25s ease; text-align: center; text-decoration: none; cursor: pointer;
        }
        .btn-primary { background: var(--brand-saffron); color: white; border: none; box-shadow: 0 5px 20px hsla(115, 29%, 45%, 0.25); }
        .btn-primary:hover { filter: saturate(1.1) brightness(0.9); transform: translateY(-2px); box-shadow: 0 8px 25px hsla(115, 29%, 45%, 0.3); }
        .btn-ghost { background: transparent; color: #666; border: 2px solid #eee; }
        .btn-ghost:hover { background: #fafafa; border-color: #ddd; color: #333; }

        /* Receipt */
        .receipt-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .receipt-header-title { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #333; }
        .receipt-header-title i { color: var(--brand-saffron); }

        .print-btn {
            font-size: 0.85rem; color: #666; display: flex; align-items: center; gap: 6px;
            padding: 8px 12px; border-radius: 8px; border: 1px solid #eee; background: white; cursor: pointer; transition: all 0.2s;
        }
        .print-btn:hover { background: #f8f9fa; color: #333; border-color: #ddd; }

        .receipt-paper {
            background: white; border: 1px solid #e0e0e0; border-radius: 16px;
            padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.02);
        }
        .receipt-meta { display: flex; justify-content: space-between; font-size: 0.9rem; color: #888; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; }
        .meta-value { color: #333; font-weight: 600; font-family: monospace; letter-spacing: 1px; }

        .receipt-items { display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; }
        .receipt-item { display: flex; justify-content: space-between; align-items: center; }
        .item-info { display: flex; align-items: center; gap: 12px; }
        .qty-bubble {
            background: var(--brand-saffron-light); color: var(--brand-saffron);
            font-size: 0.8rem; font-weight: 700; width: 24px; height: 24px;
            display: flex; align-items: center; justify-content: center; border-radius: 6px;
        }
        .item-title { font-weight: 500; color: #333; }
        .item-cost { font-weight: 600; color: #333; }

        .divider { border-bottom: 2px dashed #eee; margin: 20px 0; }

        .summary-line { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem; color: #666; }
        .total-line { margin-top: 15px; padding-top: 15px; font-size: 1.4rem; font-weight: 800; color: var(--brand-saffron); }

        /* Responsive */
        @media (max-width: 900px) {
            .col-left, .col-right { flex: 0 0 100%; border-right: none; }
            .col-left { border-bottom: 1px solid #f0f0f0; padding: 4rem 1.5rem 3rem; }
            .col-right { padding: 3rem 1.5rem; }
            .card-logo { margin-bottom: 1.5rem; }
            .success-card { min-height: auto; }
        }

        /* PRINT */
        @media print {
            body { background: white; padding: 0; }
            .col-left, .print-btn { display: none !important; }
            .success-page-wrapper { padding: 0; }
            .main-container { max-width: 100%; padding: 0; }
            .success-card { box-shadow: none; border: none; }
            .col-right { width: 100%; padding: 0; background: white; }
            .receipt-paper { border: 2px solid #000; padding: 40px; }
        }
    </style>
</head>
<body>

    <div class="success-page-wrapper">
        <div class="main-container">
            <div class="success-card fade-in-up">
                <div class="row">
                    <!-- Left: Confirmation -->
                    <div class="col-left">
                        <!-- Relocated Logo Inside -->
                        <div class="card-logo">
                            <a href="index.php">
                                <img src="./assets/images/logo.png" alt="CavShop Logo">
                            </a>
                        </div>

                        <div class="confirmation-content">
                            <div class="icon-pulse">
                                <i class="fas fa-check"></i>
                            </div>
                            <h1 class="premium-heading">Order Placed!</h1>
                            
                            <div class="status-badge">
                                <span class="status-dot pulse"></span>
                                <span class="status-text">Processing</span>
                            </div>

                            <p class="premium-subtext">
                                Thank you for your trust! <strong><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></strong>, your order is being prepared with care.
                            </p>
                            
                            <div class="action-buttons">
                                <a href="yourorder.php" class="btn-premium btn-primary">Track Order</a>
                                <a href="menu.php" class="btn-premium btn-ghost">Order Again</a>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Receipt -->
                    <div class="col-right">
                        <div class="receipt-header">
                            <div class="receipt-header-title">
                                <i class="fas fa-receipt"></i>
                                <span>ORDER SUMMARY</span>
                            </div>
                            <button onclick="window.print()" class="print-btn">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                        
                        <div class="receipt-paper">
                            <div class="receipt-meta">
                                <span>Order Number</span>
                                <span class="meta-value">#<?= htmlspecialchars($order['order_id']) ?></span>
                            </div>

                            <div class="receipt-items">
                                <?php if (!empty($order['items'])): ?>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="receipt-item">
                                            <div class="item-info">
                                                <span class="qty-bubble"><?= $item['qty'] ?></span>
                                                <span class="item-title"><?= htmlspecialchars($item['name']) ?></span>
                                            </div>
                                            <span class="item-cost">₱<?= number_format($item['price'] * $item['qty'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="divider"></div>

                            <div class="receipt-footer">
                                <div class="summary-line">
                                    <span>Payment Method</span>
                                    <span style="color: #333; font-weight: 500; text-transform: uppercase;"><?= htmlspecialchars($order['payment_method']) ?></span>
                                </div>
                                <div class="summary-line total-line">
                                    <span>Total Amount</span>
                                    <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
