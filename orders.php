<?php
session_start();
$serverName="DESKTOP-I9LLCAD\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','staff'])) {
    header("Location: login.php");
    exit;
}

// Mark as completed
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    $sql = "UPDATE TRANSACTIONS SET STATUS='Completed' WHERE TRANSACTIONID=$id";
    sqlsrv_query($conn, $sql);
    header("Location: orders.php");
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pending Orders • Forresto</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --morning-sun: #FFF8E1;
        --morning-light: #FFE082;
        --afternoon-sky: #B3E5FC;
        --afternoon-green: #A5D6A7;
        --evening-gold: #FFD54F;
        --evening-amber: #FFB74D;
        --evening-twilight: #5D4037;
        --text-dark: #333333;
        --text-light: #FFFFFF;
        --shadow-light: rgba(0, 0, 0, 0.1);
        --success-green: #28a745;
        --pending-orange: #fd7e14;
    }
    
    body {
        color: var(--text-dark);
        font-family: 'Montserrat', Helvetica, sans-serif;
        font-weight: 400;
        min-height: 100vh;
        background: linear-gradient(135deg, rgba(255, 248, 225, 0.95) 0%, rgba(179, 229, 252, 0.95) 100%),
                    url('https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
        background-size: cover;
        background-attachment: fixed;
        padding: 20px;
    }
    
    .admin-navbar {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(15px);
        border-bottom: 1px solid rgba(165, 214, 167, 0.3);
        padding: 1rem 0;
        transition: all 0.3s ease;
        font-family: 'Montserrat', Helvetica, sans-serif;
        font-weight: 500;
        margin-bottom: 2rem;
        border-radius: 0 0 20px 20px;
    }

    .admin-navbar:hover {
        background: rgba(255, 255, 255, 1);
    }
    
    .admin-brand {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 2rem;
        color: var(--evening-twilight);
        letter-spacing: 0.5px;
        position: relative;
        padding-left: 2.5rem;
    }
    
    .admin-brand::before {
        content: '👑';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.8rem;
        animation: gentleSway 4s infinite ease-in-out;
    }

    @keyframes gentleSway {
        0%, 100% { transform: translateY(-50%) rotate(0deg); }
        50% { transform: translateY(-50%) rotate(5deg); }
    }
    
    .user-greeting {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: var(--evening-twilight);
        font-size: 1.1rem;
    }
    
    .orders-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    
    .page-header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(25px);
        border-radius: 24px;
        margin: 2rem auto;
        padding: 3rem;
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
        position: relative;
        overflow: hidden;
        animation: fadeIn 0.8s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 20% 80%, rgba(179, 229, 252, 0.15) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(255, 213, 79, 0.15) 0%, transparent 50%);
        pointer-events: none;
    }
    
    .page-title {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 3rem;
        color: var(--evening-twilight);
        margin-bottom: 1rem;
        line-height: 1.2;
    }
    
    .page-subtitle {
        font-family: 'Montserrat', Helvetica, sans-serif;
        font-weight: 400;
        font-size: 1.2rem;
        color: #555;
        line-height: 1.6;
        margin-bottom: 2rem;
    }
    
    .pending-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, rgba(253, 126, 20, 0.1), rgba(255, 193, 7, 0.05));
        color: var(--pending-orange);
        border: 2px solid var(--pending-orange);
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }
    
    .btn-back {
        background: linear-gradient(135deg, var(--evening-amber), #FFA726);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 1rem 2.5rem;
        font-family: 'Montserrat', Helvetica, sans-serif;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(255, 183, 77, 0.3);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-back:hover {
        background: linear-gradient(135deg, #FF9800, var(--evening-amber));
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
        color: white;
    }
    
    .btn-outline-light {
        border: 2px solid var(--afternoon-green);
        color: var(--afternoon-green);
        border-radius: 50px;
        padding: 0.7rem 1.5rem;
        font-family: 'Montserrat', Helvetica, sans-serif;
        font-weight: 600;
        transition: all 0.3s ease;
        background: transparent;
    }
    
    .btn-outline-light:hover {
        background: var(--afternoon-green);
        color: white;
        transform: translateY(-2px);
    }
    
    .orders-list {
        margin-top: 3rem;
    }
    
    .order-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }
    
    .order-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--pending-orange), #FF9800);
    }
    
    .order-card:hover {
        transform: translateY(-8px) scale(1.01);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        border-color: var(--pending-orange);
    }
    
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid rgba(165, 214, 167, 0.3);
    }
    
    .order-id {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 1.5rem;
        color: var(--evening-twilight);
    }
    
    .order-amount {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 1.8rem;
        color: var(--evening-amber);
    }
    
    .order-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .info-group {
        display: flex;
        flex-direction: column;
    }
    
    .info-label {
        font-size: 0.9rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.3rem;
        font-weight: 600;
    }
    
    .info-value {
        font-weight: 600;
        color: var(--evening-twilight);
        font-size: 1.1rem;
    }
    
    .notes-box {
        background: rgba(255, 248, 225, 0.5);
        border: 2px solid rgba(255, 224, 130, 0.3);
        border-radius: 12px;
        padding: 1.2rem;
        margin: 1.5rem 0;
        font-style: italic;
    }
    
    .btn-view-items {
        background: linear-gradient(135deg, var(--afternoon-sky), #64B5F6);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 1.5rem;
        font-family: 'Montserrat', Helvetica, sans-serif;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(100, 181, 246, 0.2);
    }
    
    .btn-view-items:hover {
        background: linear-gradient(135deg, #4A8FE7, var(--afternoon-sky));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(74, 143, 231, 0.3);
        color: white;
    }
    
    .items-table-container {
        margin-top: 2rem;
        background: rgba(165, 214, 167, 0.05);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(165, 214, 167, 0.2);
    }
    
    .table {
        --bs-table-bg: transparent;
        --bs-table-color: var(--text-dark);
        --bs-table-border-color: rgba(0, 0, 0, 0.08);
        margin-bottom: 0;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.08);
    }
    
    .table th {
        background: rgba(165, 214, 167, 0.2);
        color: var(--evening-twilight);
        font-weight: 600;
        border-bottom: 2px solid var(--afternoon-green);
        padding: 1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: 'Montserrat', sans-serif;
        font-size: 0.9rem;
    }
    
    .table td {
        padding: 1rem;
        border-color: rgba(0, 0, 0, 0.05);
        vertical-align: middle;
        font-family: 'Montserrat', sans-serif;
    }
    
    .table tbody tr:nth-child(even) {
        background: rgba(165, 214, 167, 0.05);
    }
    
    .table tbody tr:hover {
        background: rgba(255, 213, 79, 0.1);
    }
    
    .item-price {
        color: var(--evening-amber);
        font-weight: 600;
        font-family: 'Playfair Display', serif;
    }
    
    .btn-complete {
        background: linear-gradient(135deg, var(--afternoon-green), #81C784);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 1rem 2rem;
        font-family: 'Montserrat', Helvetica, sans-serif;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(165, 214, 167, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    
    .btn-complete:hover {
        background: linear-gradient(135deg, #66BB6A, var(--afternoon-green));
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(165, 214, 167, 0.4);
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        border: 2px dashed var(--afternoon-green);
        margin-top: 2rem;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--afternoon-green);
        margin-bottom: 1.5rem;
    }
    
    .section-divider {
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--afternoon-green), transparent);
        margin: 3rem 0;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .page-header {
            padding: 2rem;
        }
        
        .page-title {
            font-size: 2.5rem;
        }
        
        .admin-brand {
            font-size: 1.6rem;
            padding-left: 2rem;
        }
        
        .admin-brand::before {
            font-size: 1.4rem;
        }
        
        .order-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .order-info {
            grid-template-columns: 1fr;
        }
        
        .order-amount {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .page-title {
            font-size: 2rem;
        }
        
        .orders-container {
            padding: 1rem;
        }
    }
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg admin-navbar">
    <div class="container">
        <a class="navbar-brand admin-brand" href="admin_dashboard.php">Forresto Orders</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center gap-3">
                <span class="user-greeting me-3">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
                <a href="admin_dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="orders-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-clock me-2"></i>
            Pending Orders
        </h1>
        <p class="page-subtitle">Monitor and manage all incoming orders waiting to be prepared</p>
        
        <div class="pending-badge">
            <i class="fas fa-hourglass-half me-1"></i>
            Awaiting Preparation
        </div>
        
        <div class="section-divider"></div>
        
        <?php
        // Fetch pending orders
        $sql = "SELECT * FROM TRANSACTIONS WHERE STATUS='Pending' ORDER BY CREATEDATE DESC";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            echo "<div class='alert alert-danger'>Error loading orders. Please try again.</div>";
            echo "<pre>";
            print_r(sqlsrv_errors());
            echo "</pre>";
            exit;
        }
        
        $hasOrders = false;
        
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $hasOrders = true;
            
            $tid = $row['TRANSACTIONID'];
            $name = htmlspecialchars($row['CUSTOMERNAME']);
            $contact = htmlspecialchars($row['CONTACT']);
            $total = number_format($row['TOTALAMOUNT'], 2);
            $notes = htmlspecialchars($row['NOTES']);
            $date = $row['CREATEDATE']->format("Y-m-d H:i:s");
            
            // Calculate time ago
            $timeAgo = 'Recently';
            $txDate = $row['CREATEDATE'];
            if ($txDate instanceof DateTime) {
                $now = new DateTime();
                $interval = $txDate->diff($now);
                
                if ($interval->y > 0) $timeAgo = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                elseif ($interval->m > 0) $timeAgo = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                elseif ($interval->d > 0) $timeAgo = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                elseif ($interval->h > 0) $timeAgo = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                elseif ($interval->i > 0) $timeAgo = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                else $timeAgo = 'Just now';
            }
        ?>
        
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <span class="order-id">
                            <i class="fas fa-hashtag me-1"></i>
                            Order #<?= $tid ?>
                        </span>
                        <span class="ms-3" style="color: #666; font-size: 0.9rem;">
                            <i class="fas fa-clock me-1"></i>
                            <?= $timeAgo ?>
                        </span>
                    </div>
                    <div class="order-amount">₱<?= $total ?></div>
                </div>
                
                <div class="order-info">
                    <div class="info-group">
                        <span class="info-label">Customer</span>
                        <span class="info-value">
                            <i class="fas fa-user me-1"></i>
                            <?= $name ?: 'Walk-in Customer' ?>
                        </span>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">Contact</span>
                        <span class="info-value">
                            <i class="fas fa-phone me-1"></i>
                            <?= $contact ?: 'N/A' ?>
                        </span>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">Date & Time</span>
                        <span class="info-value">
                            <i class="fas fa-calendar me-1"></i>
                            <?= $date ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($notes)): ?>
                <div class="notes-box">
                    <i class="fas fa-sticky-note me-2"></i>
                    <strong>Order Notes:</strong> <?= $notes ?>
                </div>
                <?php endif; ?>
                
                <button class="btn btn-view-items mb-3" data-bs-toggle="collapse" data-bs-target="#items<?= $tid ?>">
                    <i class="fas fa-list-alt me-2"></i>
                    View Order Items
                </button>
                
                <div id="items<?= $tid ?>" class="collapse">
                    <div class="items-table-container">
                        <h5 style="font-family: 'Playfair Display', serif; color: var(--evening-twilight); margin-bottom: 1.5rem;">
                            <i class="fas fa-shopping-basket me-2"></i>
                            Ordered Items
                        </h5>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Unit Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch items for this order
                                    $itemSql = "SELECT * FROM TRANSACTIONITEMS WHERE TRANSACTIONID=$tid";
                                    $items = sqlsrv_query($conn, $itemSql);
                                    
                                    if ($items === false) {
                                        echo "<tr><td colspan='4' class='text-center text-muted'>Error loading items</td></tr>";
                                    } else {
                                        $itemTotal = 0;
                                        while ($it = sqlsrv_fetch_array($items, SQLSRV_FETCH_ASSOC)) {
                                            $p = htmlspecialchars($it['PRODUCTNAME']);
                                            $pr = number_format($it['PRICE'], 2);
                                            $q = $it['QUANTITY'];
                                            $subtotal = number_format($it['PRICE'] * $q, 2);
                                            $itemTotal += $it['PRICE'] * $q;
                                            
                                            echo "<tr>
                                                    <td><strong>{$p}</strong></td>
                                                    <td class='item-price'>₱{$pr}</td>
                                                    <td>{$q}</td>
                                                    <td class='item-price'>₱{$subtotal}</td>
                                                  </tr>";
                                        }
                                        
                                        // Display item total
                                        echo "<tr style='background: rgba(255, 213, 79, 0.1);'>
                                                <td colspan='3' class='text-end'><strong>Items Total:</strong></td>
                                                <td class='item-price'><strong>₱" . number_format($itemTotal, 2) . "</strong></td>
                                              </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <a href="orders.php?complete=<?= $tid ?>" class="btn btn-complete"
                   onclick="return confirm('Mark Order #<?= $tid ?> as completed? This cannot be undone.');">
                    <i class="fas fa-check-circle me-2"></i>
                    Mark as Completed
                </a>
            </div>
        
        <?php
        }
        
        if (!$hasOrders) {
            echo '<div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3 class="mb-3" style="font-family: \'Playfair Display\', serif; color: var(--evening-twilight);">No Pending Orders</h3>
                    <p class="mb-4">All orders have been processed. Great work!</p>
                    <p class="text-muted">New orders will appear here automatically.</p>
                  </div>';
        }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation to order cards
        const orderCards = document.querySelectorAll('.order-card');
        orderCards.forEach((card, index) => {
            card.style.animation = 'fadeIn 0.8s ease forwards';
            card.style.animationDelay = (index * 0.1) + 's';
            card.style.opacity = '0';
        });
        
        // Add hover effect to order cards
        orderCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.01)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Add click effect to view items buttons
        const viewButtons = document.querySelectorAll('.btn-view-items');
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (icon.classList.contains('fa-list-alt')) {
                    icon.classList.remove('fa-list-alt');
                    icon.classList.add('fa-eye-slash');
                    this.innerHTML = this.innerHTML.replace('View Order Items', 'Hide Order Items');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-list-alt');
                    this.innerHTML = this.innerHTML.replace('Hide Order Items', 'View Order Items');
                }
            });
        });
        
        // Add animation to table rows
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(255, 213, 79, 0.1)';
            });
            
            row.addEventListener('mouseleave', function() {
                if (this.rowIndex % 2 === 0) {
                    this.style.backgroundColor = 'rgba(165, 214, 167, 0.05)';
                } else {
                    this.style.backgroundColor = 'transparent';
                }
            });
        });
    });
</script>
</body>
</html>