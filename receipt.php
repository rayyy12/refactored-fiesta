<?php
session_start();
$serverName="DESKTOP-I9LLCAD\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { 
    die(print_r(sqlsrv_errors(), true));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id) { die('Transaction ID missing'); }

// Get transaction details
$sql = "SELECT * FROM TRANSACTIONS WHERE TRANSACTIONID = '$id'";
$result = sqlsrv_query($conn, $sql);
$txn = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

// Get transaction items
$itemsSql = "SELECT * FROM TRANSACTIONITEMS WHERE TRANSACTIONID = '$id'";
$itStmt = sqlsrv_query($conn, $itemsSql);

// Calculate subtotal from items
$subtotal = 0;
$itemsData = [];
while($it = sqlsrv_fetch_array($itStmt, SQLSRV_FETCH_ASSOC)) {
    $sub = $it['PRICE'] * $it['QUANTITY'];
    $subtotal += $sub;
    $itemsData[] = $it;
}

// Re-fetch items for display (reset pointer)
$itStmt = sqlsrv_query($conn, $itemsSql);

// Parse notes to extract service charge and discount info
$notes = $txn['NOTES'] ?? '';
$serviceCharge = 0;
$seniorDiscount = 0;

// Extract values from notes if they exist
if (!empty($notes)) {
    // Look for service charge in notes
    if (preg_match('/Service Charge \(5%\): ₱([\d,]+\.\d{2})/', $notes, $matches)) {
        $serviceCharge = (float)str_replace(',', '', $matches[1]);
    }
    
    // Look for senior discount in notes
    if (preg_match('/Senior Discount \(10%\) applied: -₱([\d,]+\.\d{2})/', $notes, $matches)) {
        $seniorDiscount = (float)str_replace(',', '', $matches[1]);
    }
}

// If not found in notes, calculate based on stored total
if ($serviceCharge == 0 && $seniorDiscount == 0) {
    // Try to calculate from the stored total
    $storedTotal = $txn['TOTALAMOUNT'];
    
    // Check if this looks like it includes service charge and discount
    // This is a fallback calculation
    $serviceCharge = $subtotal * 0.05;
    $seniorDiscount = ($storedTotal - $subtotal - $serviceCharge) * -1;
    
    // Ensure non-negative values
    if ($seniorDiscount < 0) $seniorDiscount = 0;
}

// Calculate grand total (should match stored total)
$calculatedTotal = $subtotal + $serviceCharge - $seniorDiscount;

// Format date
$createDate = $txn['CREATEDATE']->format('Y-m-d H:i:s');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt • Forresto</title>
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
    --receipt-purple: #8a2be2;
    --receipt-blue: #4a8fe7;
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
  
  .receipt-container {
    max-width: 900px;
    margin: 40px auto;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(25px);
    border-radius: 24px;
    padding: 3rem;
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
    animation: fadeIn 0.8s ease;
  }
  
  .receipt-container::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    right: -50%;
    bottom: -50%;
    background: radial-gradient(circle at 20% 80%, rgba(179, 229, 252, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 213, 79, 0.1) 0%, transparent 50%);
    pointer-events: none;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .receipt-header {
    text-align: center;
    padding-bottom: 2rem;
    margin-bottom: 2rem;
    position: relative;
    border-bottom: 3px solid var(--afternoon-green);
  }
  
  .receipt-header::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 100px;
    height: 3px;
    background: linear-gradient(90deg, var(--evening-amber), transparent);
  }
  
  .receipt-title {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 3.5rem;
    color: var(--evening-twilight);
    margin-bottom: 0.5rem;
    letter-spacing: 0.5px;
    position: relative;
    padding-left: 4rem;
  }
  
  .receipt-title::before {
    content: '🌿';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    font-size: 3rem;
    animation: gentleSway 4s infinite ease-in-out;
  }
  
  @keyframes gentleSway {
    0%, 100% { transform: translateY(-50%) rotate(0deg); }
    50% { transform: translateY(-50%) rotate(5deg); }
  }
  
  .receipt-subtitle {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    color: var(--evening-amber);
    text-align: center;
    font-style: italic;
    margin-bottom: 1.5rem;
  }
  
  .transaction-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
    padding: 2rem;
    background: rgba(165, 214, 167, 0.1);
    border-radius: 16px;
    border: 2px solid rgba(165, 214, 167, 0.3);
  }
  
  .info-item {
    display: flex;
    flex-direction: column;
  }
  
  .info-label {
    color: var(--evening-twilight);
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .info-value {
    color: var(--evening-twilight);
    font-weight: 500;
    font-size: 1.2rem;
  }
  
  .table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--text-dark);
    --bs-table-border-color: rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  }
  
  .table th {
    background: rgba(165, 214, 167, 0.2);
    color: var(--evening-twilight);
    font-weight: 600;
    border-bottom: 2px solid var(--afternoon-green);
    padding: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-family: 'Montserrat', sans-serif;
  }
  
  .table td {
    padding: 1.5rem;
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
  
  .price-cell {
    color: var(--evening-amber);
    font-weight: 600;
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
  }
  
  .summary-section {
    background: rgba(255, 255, 255, 0.9);
    padding: 2.5rem;
    border-radius: 16px;
    margin: 3rem 0;
    border: 2px solid rgba(165, 214, 167, 0.3);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
  }
  
  .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  }
  
  .summary-row:last-child {
    border-bottom: none;
  }
  
  .summary-label {
    color: var(--evening-twilight);
    font-size: 1.1rem;
    font-weight: 500;
    font-family: 'Montserrat', sans-serif;
  }
  
  .summary-value {
    color: var(--evening-twilight);
    font-weight: 600;
    font-size: 1.1rem;
    font-family: 'Montserrat', sans-serif;
  }
  
  .summary-subtotal .summary-label {
    color: var(--evening-twilight);
  }
  
  .summary-service .summary-label {
    color: var(--receipt-blue);
  }
  
  .summary-service .summary-value {
    color: var(--receipt-blue);
    font-weight: 700;
  }
  
  .summary-discount .summary-label {
    color: var(--receipt-purple);
  }
  
  .summary-discount .summary-value {
    color: var(--receipt-purple);
    font-weight: 700;
  }
  
  .summary-total {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 3px solid var(--evening-amber);
  }
  
  .total-label {
    font-size: 1.5rem;
    color: var(--evening-twilight);
    font-weight: 700;
    font-family: 'Playfair Display', serif;
  }
  
  .total-value {
    font-size: 2rem;
    color: var(--evening-amber);
    font-weight: 900;
    font-family: 'Playfair Display', serif;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .alert-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 25px;
    border-radius: 50px;
    font-weight: 600;
    margin: 1rem 0.5rem;
    font-size: 1rem;
    font-family: 'Montserrat', sans-serif;
    border: 2px solid;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  }
  
  .alert-service {
    background: linear-gradient(135deg, rgba(74, 143, 231, 0.1), rgba(74, 143, 231, 0.05));
    border-color: var(--receipt-blue);
    color: var(--receipt-blue);
  }
  
  .alert-discount {
    background: linear-gradient(135deg, rgba(138, 43, 226, 0.1), rgba(138, 43, 226, 0.05));
    border-color: var(--receipt-purple);
    color: var(--receipt-purple);
  }
  
  .action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 3rem;
    flex-wrap: wrap;
  }
  
  .btn-print {
    background: linear-gradient(135deg, var(--afternoon-green), #81C784);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 14px 30px;
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    box-shadow: 0 5px 20px rgba(165, 214, 167, 0.3);
  }
  
  .btn-print:hover {
    background: linear-gradient(135deg, #66BB6A, var(--afternoon-green));
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(165, 214, 167, 0.4);
    color: white;
  }
  
  .btn-back {
    background: linear-gradient(135deg, var(--evening-amber), #FFA726);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 14px 30px;
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    box-shadow: 0 5px 20px rgba(255, 183, 77, 0.3);
  }
  
  .btn-back:hover {
    background: linear-gradient(135deg, #FF9800, var(--evening-amber));
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
    color: white;
  }
  
  .btn-admin {
    background: linear-gradient(135deg, var(--receipt-purple), #9c27b0);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 14px 30px;
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    box-shadow: 0 5px 20px rgba(138, 43, 226, 0.3);
  }
  
  .btn-admin:hover {
    background: linear-gradient(135deg, #7b1fa2, var(--receipt-purple));
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(138, 43, 226, 0.4);
    color: white;
  }
  
  .notes-container {
    background: rgba(255, 248, 225, 0.5);
    border: 2px solid rgba(255, 224, 130, 0.3);
    border-radius: 16px;
    padding: 1.5rem;
    margin-top: 2rem;
    font-family: 'Montserrat', sans-serif;
  }
  
  .footer-note {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
    text-align: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
  }
  
  @media (max-width: 768px) {
    .receipt-container {
      padding: 1.5rem;
      margin: 20px;
    }
    
    .receipt-title {
      font-size: 2.5rem;
      padding-left: 3rem;
    }
    
    .receipt-title::before {
      font-size: 2.2rem;
    }
    
    .transaction-info {
      grid-template-columns: 1fr;
      padding: 1.5rem;
    }
    
    .summary-section {
      padding: 1.5rem;
    }
    
    .total-value {
      font-size: 1.6rem;
    }
    
    .action-buttons {
      flex-direction: column;
    }
    
    .btn-print, .btn-back, .btn-admin {
      width: 100%;
      justify-content: center;
    }
  }
  
  @media print {
    body {
      background: white !important;
      color: black !important;
      padding: 0 !important;
    }
    
    .receipt-container {
      box-shadow: none !important;
      border: 1px solid #ddd !important;
      margin: 0 !important;
      padding: 20px !important;
      max-width: 100% !important;
    }
    
    .btn-print, .btn-back, .btn-admin, .action-buttons {
      display: none !important;
    }
    
    .alert-badge {
      border: 1px solid #ccc !important;
      background: #f8f9fa !important;
      color: #495057 !important;
      box-shadow: none !important;
    }
  }
  
  h1, h2, h3, h4, h5, h6 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
  }
  
  p, span, div, input, button, td, th {
    font-family: 'Montserrat', Helvetica, sans-serif;
  }
  
  .section-divider {
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--afternoon-green), transparent);
    margin: 2rem 0;
    opacity: 0.5;
  }
</style>
</head>
<body>
<div class="receipt-container">
    <div class="receipt-header">
        <h1 class="receipt-title">Forresto</h1>
        <div class="receipt-subtitle">Transaction Receipt</div>
        
        <?php if($serviceCharge > 0): ?>
        <div class="alert-badge alert-service">
            <i class="fas fa-concierge-bell"></i>
            Service Charge (5%) Applied
        </div>
        <?php endif; ?>
        
        <?php if($seniorDiscount > 0): ?>
        <div class="alert-badge alert-discount">
            <i class="fas fa-user-tie"></i>
            Senior Discount (10%) Applied
        </div>
        <?php endif; ?>
    </div>
    
    <div class="transaction-info">
        <div class="info-item">
            <span class="info-label">Transaction #</span>
            <span class="info-value"><?=htmlspecialchars($txn['TRANSACTIONID'])?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Date & Time</span>
            <span class="info-value"><?= $createDate ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Customer Name</span>
            <span class="info-value"><?=htmlspecialchars($txn['CUSTOMERNAME'])?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Status</span>
            <span class="info-value" style="color: var(--evening-amber); font-weight: bold;">
                <?=htmlspecialchars($txn['STATUS'])?>
            </span>
        </div>
        <?php if(!empty($txn['CONTACT'])): ?>
        <div class="info-item">
            <span class="info-label">Contact</span>
            <span class="info-value"><?=htmlspecialchars($txn['CONTACT'])?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($it = sqlsrv_fetch_array($itStmt, SQLSRV_FETCH_ASSOC)): 
                    $itemSubtotal = $it['PRICE'] * $it['QUANTITY'];
                ?>
                <tr>
                    <td><?=htmlspecialchars($it['PRODUCTNAME'])?></td>
                    <td><?= $it['QUANTITY'] ?></td>
                    <td class="price-cell">₱<?= number_format($it['PRICE'],2) ?></td>
                    <td class="price-cell">₱<?= number_format($itemSubtotal,2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div class="summary-section">
        <div class="summary-row summary-subtotal">
            <span class="summary-label">Subtotal</span>
            <span class="summary-value">₱<?= number_format($subtotal, 2) ?></span>
        </div>
        
        <div class="summary-row summary-service">
            <span class="summary-label">
                <i class="fas fa-concierge-bell me-2"></i>
                Service Charge (5%)
            </span>
            <span class="summary-value">₱<?= number_format($serviceCharge, 2) ?></span>
        </div>
        
        <?php if($seniorDiscount > 0): ?>
        <div class="summary-row summary-discount">
            <span class="summary-label">
                <i class="fas fa-tag me-2"></i>
                Senior Discount (10%)
            </span>
            <span class="summary-value">-₱<?= number_format($seniorDiscount, 2) ?></span>
        </div>
        <?php endif; ?>
        
        <div class="summary-row summary-total">
            <span class="total-label">Total Amount</span>
            <span class="total-value">₱<?= number_format($calculatedTotal, 2) ?></span>
        </div>
    </div>
    
    <?php if(!empty($notes) && !preg_match('/(Service Charge|Senior Discount)/', $notes)): ?>
    <div class="notes-container">
        <i class="fas fa-sticky-note me-2" style="color: var(--evening-amber);"></i>
        <strong>Order Notes:</strong><br>
        <?= nl2br(htmlspecialchars(str_replace('\n', "\n", $notes))) ?>
    </div>
    <?php endif; ?>
    
    <div class="section-divider"></div>
    
    <div class="action-buttons">
        <a href="javascript:window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print Receipt
        </a>
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
        <?php if(isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
        <a href="admin_dashboard.php" class="btn-admin">
            <i class="fas fa-crown"></i> Admin Dashboard
        </a>
        <?php endif; ?>
    </div>
    
    <div class="footer-note">
        <i class="fas fa-star me-2" style="color: var(--evening-amber);"></i>
        Thank you for visiting Forresto! 
        <i class="fas fa-star ms-2" style="color: var(--evening-amber);"></i><br>
        <small>A sanctuary in the city where people can take a breath, reset, and rediscover balance.</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation to receipt container
        const receipt = document.querySelector('.receipt-container');
        receipt.style.opacity = '0';
        receipt.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            receipt.style.transition = 'all 0.5s ease';
            receipt.style.opacity = '1';
            receipt.style.transform = 'translateY(0)';
        }, 100);
        
        // Add hover effect to table rows
        const tableRows = document.querySelectorAll('.table tbody tr');
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
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + P to print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Ctrl + B to go back
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                window.location.href = 'index.php';
            }
        });
        
        // Add print stylesheet
        const printStyles = document.createElement('style');
        printStyles.innerHTML = `
            @media print {
                body * {
                    visibility: hidden;
                }
                .receipt-container, .receipt-container * {
                    visibility: visible;
                }
                .receipt-container {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    box-shadow: none !important;
                    border: 1px solid #ddd !important;
                    margin: 0 !important;
                    padding: 20px !important;
                }
                .action-buttons, .btn-print, .btn-back, .btn-admin {
                    display: none !important;
                }
                .alert-badge {
                    border: 1px solid #ccc !important;
                    background: #f8f9fa !important;
                    color: #495057 !important;
                    box-shadow: none !important;
                }
                .summary-section {
                    box-shadow: none !important;
                }
            }
        `;
        document.head.appendChild(printStyles);
    });
</script>
</body>
</html>