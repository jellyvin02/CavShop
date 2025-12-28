<?php
session_start();
require_once "includes/connection.php";

// Fetch the counts for each order status
$query_pending = "SELECT COUNT(*) AS pending_count FROM orders WHERE status='pending'";
$query_completed = "SELECT COUNT(*) AS completed_count FROM orders WHERE status='completed'";
$query_sales = "SELECT SUM(total_price) AS total_sales FROM orders WHERE status='completed'";

// Fetch return orders count (check if table exists first)
$return_count = 0;
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'returns_refunds'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    $query_returns = "SELECT COUNT(*) AS return_count FROM returns_refunds WHERE status IN ('Pending', 'Approved', 'Processed')";
    $returns_result = mysqli_query($conn, $query_returns);
    if ($returns_result) {
        $return_count = mysqli_fetch_assoc($returns_result)['return_count'];
    }
}

// Fetch recent orders (latest 9)
$query_recent_orders = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10";

// Fetch earnings per month
$query_monthly_earnings = "SELECT MONTH(created_at) AS month, SUM(total_price) AS earnings
                           FROM orders WHERE status='completed'
                           GROUP BY MONTH(created_at)
                           ORDER BY MONTH(created_at)";

$pending_result = mysqli_query($conn, $query_pending);
$completed_result = mysqli_query($conn, $query_completed);
$sales_result = mysqli_query($conn, $query_sales);
$recent_orders_result = mysqli_query($conn, $query_recent_orders);
$monthly_earnings_result = mysqli_query($conn, $query_monthly_earnings);

$pending_count = mysqli_fetch_assoc($pending_result)['pending_count'];
$completed_count = mysqli_fetch_assoc($completed_result)['completed_count'];
$total_sales = mysqli_fetch_assoc($sales_result)['total_sales'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Add Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --saffron: #6b8e4f;
            --saffron-dark: #5a7542;
            --saffron-light: #7ba05c;
            --primary-color: var(--saffron);
            --secondary-color: var(--saffron-dark);
            --background-color: #f5f7fa;
            --pending-color: #ff9800;
            --completed-color: #4caf50;
            --process-color: #9e9d24;
            --stats-up-color: #4CAF50;
            --stats-down-color: #f44336;
            --stats-neutral-color: #ff9800;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            --card-shadow-hover: 0 12px 24px rgba(0, 0, 0, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: #1a1f36;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }

        .dashboard-container {
            margin-left: 260px;
            padding: 1rem 2rem;
            width: calc(100% - 260px);
            transition: all 0.3s ease;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body.sidebar-collapsed .dashboard-container {
            margin-left: 70px;
            width: calc(100% - 70px);
            transition: all 0.3s ease;
        }

        @media (max-width: 1200px) {
            .dashboard-container {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
        }

        h1 {
            font-family: 'Inter', sans-serif;
            font-weight: bold;
            font-size: 36px;
            color: hsl(115, 29%, 45%);
            margin: 0;
            padding: 0 0 1rem 0;
            margin-bottom: 2rem;
        }

        .card {
            background: #ffffff;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }

        .card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .stats-card {
            height: 100%;
            min-height: 180px;
            position: relative;
        }

        .stats-card .card-body {
            padding: 20px;
        }

        .stats-icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: #d4edda;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stats-card i {
            font-size: 1.5rem;
            color: #155724;
        }

        .stats-card .number {
            font-family: 'Inter', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #155724;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .stats-card .label {
            font-family: 'Inter', sans-serif;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-card {
            overflow: hidden;
            border-radius: 8px;
        }

        .table-card .card-header {
            background: #ffffff;
            border-bottom: 2px solid #e9ecef;
            padding: 1.5rem;
        }

        .table thead th {
            background: #d4edda !important;
            color: #155724 !important;
            font-weight: 600 !important;
            border-bottom: 2px solid #218838 !important;
            border: none !important;
            padding: 12px 15px !important;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table tbody td {
            padding: 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
            color: #475569;
            font-size: 0.9rem;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 550;
            line-height: 1;
            min-width: 80px;
            text-transform: capitalize;
        }

        .status-chip.pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status-chip.completed {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-chip.cancelled {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .card-title {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
            color: #1a1f36;
            margin: 0;
        }

        .chart-container {
            height: 400px;
            padding: 1rem;
        }

        .stats-trend {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 550;
            margin-top: 0.75rem;
        }

        .stats-trend i {
            font-size: 0.875rem !important;
            margin-right: 6px;
        }

        .trend-up {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .trend-down {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .trend-neutral {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .stats-card .stat-footer {
            margin-top: 1rem;
            font-size: 0.85rem;
        }

        td.date-order,
        td.item-cell {
            font-weight: 500;
            color: #64748b;
        }

        /* Green numbers - Price column in table */
        .table tbody td:nth-child(3) {
            color: #155724 !important;
            font-weight: 600;
        }

        /* Animation for cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stats-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .stats-card:nth-child(1) { animation-delay: 0.1s; }
        .stats-card:nth-child(2) { animation-delay: 0.2s; }
        .stats-card:nth-child(3) { animation-delay: 0.3s; }
        .stats-card:nth-child(4) { animation-delay: 0.4s; }

        /* Responsive improvements */
        @media (max-width: 992px) {
            .stats-card .number {
                font-size: 2rem;
            }

            .stats-card i {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            .dashboard-container {
                padding: 1rem;
            }

            .card {
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>
    <?php require "includes/adminside.php"; ?>
    
    <div class="dashboard-container">
        <h1>Dashboard</h1>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="label mb-2">New Orders</div>
                                <div class="number"><?php echo $pending_count; ?></div>
                            </div>
                            <div class="stats-icon-wrapper">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="stat-footer">
                            <div class="stats-trend <?php echo $pending_count > 0 ? 'trend-up' : 'trend-neutral'; ?>">
                                <i class="fas <?php echo $pending_count > 0 ? 'fa-arrow-up' : 'fa-minus'; ?>"></i>
                                <span><?php echo $pending_count; ?> pending</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="label mb-2">Completed Orders</div>
                                <div class="number"><?php echo $completed_count; ?></div>
                            </div>
                            <div class="stats-icon-wrapper">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-footer">
                            <div class="stats-trend <?php echo $completed_count > 0 ? 'trend-up' : 'trend-neutral'; ?>">
                                <i class="fas <?php echo $completed_count > 0 ? 'fa-arrow-up' : 'fa-minus'; ?>"></i>
                                <span><?php echo $completed_count; ?> completed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="label mb-2">Return Orders</div>
                                <div class="number"><?php echo $return_count; ?></div>
                            </div>
                            <div class="stats-icon-wrapper">
                                <i class="fas fa-undo-alt"></i>
                            </div>
                        </div>
                        <div class="stat-footer">
                            <div class="stats-trend <?php echo $return_count > 0 ? 'trend-down' : 'trend-neutral'; ?>">
                                <i class="fas <?php echo $return_count > 0 ? 'fa-exclamation-circle' : 'fa-minus'; ?>"></i>
                                <span><?php echo $return_count; ?> returns</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="label mb-2">Total Sales</div>
                                <div class="number">₱<?php echo number_format($total_sales, 2); ?></div>
                            </div>
                            <div class="stats-icon-wrapper">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <div class="stat-footer">
                            <div class="stats-trend trend-up">
                                <i class="fas fa-chart-line"></i>
                                <span>Total Revenue</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card table-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Item</th>
                                        <th>Price</th>
                                        <th>Address</th>
                                        <th>Date Order</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                                        <tr>
                                            <td class="align-middle"><?php echo htmlspecialchars($order['name']); ?></td>
                                            <td class="align-middle item-cell"><?php echo htmlspecialchars($order['item']); ?></td>
                                            <td class="align-middle">₱<?php echo number_format($order['total_price'], 2); ?></td>
                                            <td class="align-middle"><?php echo htmlspecialchars($order['address']); ?></td>
                                            <td class="align-middle date-order"><?php echo date("Y, M d h:i:s A", strtotime($order['created_at'])); ?></td>
                                            <td class="align-middle">
                                                <?php
                                                $status = htmlspecialchars($order['status']);
                                                echo "<div class='status-chip " . strtolower($status) . "'>";
                                                if ($status == 'pending') {
                                                    echo '<i class="fas fa-clock"></i>';
                                                } elseif ($status == 'completed') {
                                                    echo '<i class="fas fa-check"></i>';
                                                } elseif ($status == 'cancelled') {
                                                    echo '<i class="fas fa-times"></i>';
                                                }
                                                echo ucfirst($status);
                                                echo "</div>";
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Monthly Earnings</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="earningsGraph"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Sidebar toggle handler
            $('.sidebar-toggle').on('click', function() {
                $('body').toggleClass('sidebar-collapsed');
                setTimeout(function() {
                    // Trigger window resize to fix chart
                    window.dispatchEvent(new Event('resize'));
                }, 300);
            });

            // Initialize chart
            var ctx = document.getElementById('earningsGraph').getContext('2d');
            var monthlyEarnings = Array(12).fill(0); // Initialize array with zeros

            <?php 
            mysqli_data_seek($monthly_earnings_result, 0);
            while ($row = mysqli_fetch_assoc($monthly_earnings_result)): 
            ?>
                monthlyEarnings[<?php echo $row['month'] - 1; ?>] = <?php echo $row['earnings']; ?>;
            <?php endwhile; ?>

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Monthly Earnings (₱)',
                        data: monthlyEarnings,
                        backgroundColor: function(context) {
                            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                            gradient.addColorStop(0, '#7ba05c');
                            gradient.addColorStop(1, '#5a7542');
                            return gradient;
                        },
                        borderColor: '#5a7542',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.parsed.y.toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    size: 11,
                                    weight: '500'
                                },
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    size: 11,
                                    weight: '500'
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
