<?php
session_start();
$serverName = "DESKTOP-I9LLCAD\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { 
    die(print_r(sqlsrv_errors(), true));
}

if(empty($_SESSION['cart'])) { 
    header('Location: index.php'); 
    exit; 
}

// Calculate totals with service charge and discount
$subtotal = 0;
$itemCount = 0;
$cartItems = $_SESSION['cart'] ?? [];

foreach($cartItems as $item) {
    $itemCount += $item['qty'];
    $subtotal += $item['price'] * $item['qty'];
}

// Calculate service charge (5%)
$serviceCharge = $subtotal * 0.05;

// Calculate senior discount (10%) if applied
$seniorDiscount = isset($_SESSION['senior_discount']) ? ($subtotal * 0.10) : 0;

// Calculate grand total
$grandTotal = $subtotal + $serviceCharge - $seniorDiscount;

// Ensure grand total doesn't go negative
if($grandTotal < 0) {
    $grandTotal = 0;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customer = [
        'name' => $_POST['name'] ?? 'Guest',
        'contact' => $_POST['contact'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];

    $customerName = $customer['name'];
    $contact = $customer['contact'];
    $notes = $customer['notes'];
    $totalAmount = $grandTotal; // Use the calculated grand total
    
    // Store discount information in notes if applied
    if(isset($_SESSION['senior_discount'])) {
        $notes .= (empty($notes) ? '' : '\n') . "Senior Discount (10%) applied: -₱" . number_format($seniorDiscount, 2);
        $notes .= '\nService Charge (5%): ₱' . number_format($serviceCharge, 2);
        $notes .= '\nSubtotal: ₱' . number_format($subtotal, 2);
    }

    // Insert transaction
    $insertSql = "
        INSERT INTO TRANSACTIONS (CUSTOMERNAME, CONTACT, TOTALAMOUNT, NOTES, CREATEDATE, STATUS)
        VALUES ('$customerName', '$contact', '$totalAmount', '$notes', GETDATE(), 'Pending');
        SELECT SCOPE_IDENTITY() AS TRANSACTIONID;
    ";
    
    $insertResult = sqlsrv_query($conn, $insertSql);

    if ($insertResult === false) {
        die("Insert failed: " . print_r(sqlsrv_errors(), true));
    }

    // Get the transaction ID
    $transactionId = null;
    
    // Move to the next result set to get the ID
    sqlsrv_next_result($insertResult);
    if ($row = sqlsrv_fetch_array($insertResult, SQLSRV_FETCH_ASSOC)) {
        $transactionId = $row['TRANSACTIONID'];
    }

    // Alternative method if SCOPE_IDENTITY doesn't work
    if (!$transactionId) {
        $getIdSql = "SELECT TOP 1 TRANSACTIONID FROM TRANSACTIONS ORDER BY CREATEDATE DESC";
        $getIdResult = sqlsrv_query($conn, $getIdSql);
        if ($getIdResult !== false && $idRow = sqlsrv_fetch_array($getIdResult, SQLSRV_FETCH_ASSOC)) {
            $transactionId = $idRow['TRANSACTIONID'];
        }
    }

    if (!$transactionId) {
        die("Failed to get transaction ID. Please check your database configuration.");
    }

    // Insert transaction items
    foreach($_SESSION['cart'] as $it) {
        $pid = $it['id'];
        $pname = $it['name'];
        $price = $it['price'];
        $qty = $it['qty'];

        $itemSql = "
            INSERT INTO TRANSACTIONITEMS 
            (TRANSACTIONID, PRODUCTID, PRODUCTNAME, PRICE, QUANTITY)
            VALUES ('$transactionId', '$pid', '$pname', '$price', '$qty')
        ";

        $itemResult = sqlsrv_query($conn, $itemSql);

        if ($itemResult === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }

    // Clear discount from session after checkout
    unset($_SESSION['senior_discount']);
    
    // Store transaction ID in session for receipt page
    $_SESSION['last_txn'] = $transactionId;
    
    // Clear cart after successful checkout
    unset($_SESSION['cart']);

    // Redirect to receipt page with transaction ID
    header("Location: receipt.php?id={$transactionId}");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout • Forresto</title>
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
            --admin-purple: #9C27B0;
            --success-green: #4CAF50;
            --danger-red: #F44336;
        }
        
        body {
            color: var(--text-dark);
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 400;
            min-height: 100vh;
            position: relative;
            background: rgba(255, 255, 255, 0.85);
        }
        
        /* Background Carousel */
        .background-carousel {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .background-carousel .carousel-inner,
        .background-carousel .carousel-item {
            height: 100vh;
        }
        
        .background-carousel img {
            object-fit: cover;
            width: 100%;
            height: 100%;
            filter: brightness(0.7);
        }
        
        /* Checkout Container */
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        /* Checkout Header */
        .checkout-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            margin: 2rem auto 3rem;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .checkout-header::before {
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
        
        .checkout-title {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 2.8rem;
            color: var(--evening-twilight);
            margin-bottom: 0.5rem;
            line-height: 1.2;
            text-align: center;
        }
        
        .checkout-subtitle {
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-size: 1.2rem;
            color: #666;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto 2rem;
            text-align: center;
        }
        
        /* Form Styles */
        .checkout-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .form-label {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--evening-twilight);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--afternoon-green);
            border-radius: 12px;
            color: var(--text-dark);
            padding: 0.85rem 1rem;
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: white;
            border-color: var(--evening-amber);
            box-shadow: 0 0 0 3px rgba(255, 183, 77, 0.2);
        }
        
        /* Order Summary */
        .order-summary {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .summary-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--evening-twilight);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .order-items {
            margin-bottom: 2rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .item-name {
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .item-quantity {
            color: #666;
            font-size: 0.9rem;
        }
        
        .item-total {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--evening-amber);
        }
        
        /* Summary Breakdown */
        .summary-breakdown {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .breakdown-label {
            font-family: 'Montserrat', Helvetica, sans-serif;
            color: #666;
        }
        
        .breakdown-value {
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .discount-row {
            color: var(--admin-purple);
            font-weight: 700;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-top: 2px solid var(--evening-amber);
            margin-top: 1rem;
        }
        
        .total-label {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--evening-twilight);
            font-size: 1.3rem;
        }
        
        .total-value {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            color: var(--evening-amber);
            font-size: 2rem;
        }
        
        /* Alerts */
        .alert-checkout {
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 2px solid;
            backdrop-filter: blur(10px);
            animation: slideIn 0.5s ease;
            font-family: 'Montserrat', Helvetica, sans-serif;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), transparent);
            border-color: var(--success-green);
            color: var(--success-green);
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(179, 229, 252, 0.2), transparent);
            border-color: var(--afternoon-sky);
            color: var(--evening-twilight);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Buttons */
        .btn-checkout-submit {
            background: linear-gradient(135deg, var(--evening-amber), #FF9800);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 5px 20px rgba(255, 183, 77, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-checkout-submit:hover {
            background: linear-gradient(135deg, #FFB74D, var(--evening-amber));
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
        }
        
        .btn-back {
            border: 2px solid var(--afternoon-green);
            color: var(--afternoon-green);
            border-radius: 12px;
            padding: 0.85rem 2rem;
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            background: transparent;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: var(--afternoon-green);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Navigation */
        .navigation-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        /* Hide carousel controls */
        .background-carousel .carousel-control-prev,
        .background-carousel .carousel-control-next,
        .background-carousel .carousel-indicators {
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .checkout-header, .checkout-form, .order-summary {
                padding: 2rem;
            }
            
            .checkout-title {
                font-size: 2.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                padding: 1rem;
            }
            
            .checkout-header, .checkout-form, .order-summary {
                padding: 1.5rem;
            }
            
            .checkout-title {
                font-size: 1.8rem;
            }
            
            .checkout-subtitle {
                font-size: 1.1rem;
            }
            
            .navigation-links {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .btn-back {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .checkout-title {
                font-size: 1.5rem;
            }
            
            .order-item {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
            
            .item-name, .item-total {
                text-align: center;
            }
        }
    </style>
</head>
<body>
<!-- Background Carousel -->
<div id="backgroundCarousel" class="carousel slide background-carousel" data-bs-ride="carousel" data-bs-interval="5000">
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="https://images.unsplash.com/photo-1442512595331-e89e73853f31?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" class="d-block w-100" alt="Morning light in café">
    </div>
    <div class="carousel-item">
      <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="Relaxed afternoon atmosphere">
    </div>
    <div class="carousel-item">
      <img src="https://images.unsplash.com/photo-1516733968668-dbdce39c4651?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="Evening golden hour">
    </div>
  </div>
</div>

<div class="checkout-container">
  <!-- Checkout Header -->
  <div class="checkout-header">
    <h1 class="checkout-title">
      <i class="fas fa-shopping-bag me-2"></i>
      Complete Your Order
    </h1>
    <p class="checkout-subtitle">
      Review your items and provide your details to complete your Forresto experience
    </p>
  </div>

  <!-- Alerts -->
  <?php if(isset($_SESSION['senior_discount'])): ?>
  <div class="alert-checkout alert-success">
    <div class="d-flex align-items-center">
      <i class="fas fa-user-tie fa-2x me-3"></i>
      <div>
        <h4 class="mb-1">Senior Discount Applied!</h4>
        <p class="mb-0">10% discount has been applied to your order.</p>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <div class="alert-checkout alert-info">
    <div class="d-flex align-items-center">
      <i class="fas fa-info-circle fa-2x me-3"></i>
      <div>
        <h4 class="mb-1">Service Charge</h4>
        <p class="mb-0">A 5% service charge is applied to all orders for our exceptional service.</p>
      </div>
    </div>
  </div>

  <!-- Order Summary -->
  <div class="order-summary">
    <h3 class="summary-title">
      <i class="fas fa-receipt me-2"></i>
      Order Summary
    </h3>
    
    <div class="order-items">
      <?php foreach($cartItems as $item): 
          $itemTotal = $item['price'] * $item['qty'];
      ?>
        <div class="order-item">
          <div class="item-name">
            <?= htmlspecialchars($item['name']) ?>
            <div class="item-quantity">Quantity: <?= $item['qty'] ?> × ₱<?= number_format($item['price'], 2) ?></div>
          </div>
          <div class="item-total">₱<?= number_format($itemTotal, 2) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <div class="summary-breakdown">
      <div class="breakdown-row">
        <span class="breakdown-label">Subtotal</span>
        <span class="breakdown-value">₱<?= number_format($subtotal, 2) ?></span>
      </div>
      
      <div class="breakdown-row">
        <span class="breakdown-label">Service Charge (5%)</span>
        <span class="breakdown-value">₱<?= number_format($serviceCharge, 2) ?></span>
      </div>
      
      <?php if(isset($_SESSION['senior_discount'])): ?>
      <div class="breakdown-row discount-row">
        <span class="breakdown-label">
          <i class="fas fa-tag me-1"></i>
          Senior Discount (10%)
        </span>
        <span class="breakdown-value">-₱<?= number_format($seniorDiscount, 2) ?></span>
      </div>
      <?php endif; ?>
      
      <div class="total-row">
        <span class="total-label">Total Amount</span>
        <span class="total-value">₱<?= number_format($grandTotal, 2) ?></span>
      </div>
    </div>
  </div>

  <!-- Checkout Form -->
  <div class="checkout-form">
    <form method="POST" id="checkoutForm">
      <div class="mb-4">
        <label class="form-label">
          <i class="fas fa-user me-2"></i>
          Customer Name
        </label>
        <input type="text" 
               name="name" 
               class="form-control" 
               required 
               placeholder="Enter your full name"
               value="<?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?>">
      </div>
      
      <div class="mb-4">
        <label class="form-label">
          <i class="fas fa-phone me-2"></i>
          Contact Number (Optional)
        </label>
        <input type="text" 
               name="contact" 
               class="form-control" 
               placeholder="Enter contact number">
      </div>
      
      <div class="mb-4">
        <label class="form-label">
          <i class="fas fa-sticky-note me-2"></i>
          Special Instructions
        </label>
        <textarea name="notes" 
                  class="form-control" 
                  rows="3" 
                  placeholder="Any special requests or notes for your order..."></textarea>
      </div>
      
      <button type="submit" class="btn-checkout-submit">
        <i class="fas fa-check-circle me-2"></i>
        Place Order
      </button>
    </form>
  </div>

  <!-- Navigation Links -->
  <div class="navigation-links">
    <a href="cart.php" class="btn-back">
      <i class="fas fa-arrow-left me-2"></i>
      Back to Cart
    </a>
    <a href="index.php" class="btn-back">
      <i class="fas fa-utensils me-2"></i>
      Return to Menu
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize the carousel with autoplay
    const carousel = new bootstrap.Carousel(document.getElementById('backgroundCarousel'), {
      interval: 5000,
      wrap: true,
      pause: false
    });
    
    // Form validation
    const checkoutForm = document.getElementById('checkoutForm');
    checkoutForm.addEventListener('submit', function(e) {
      const nameInput = document.querySelector('input[name="name"]');
      if (!nameInput.value.trim()) {
        e.preventDefault();
        nameInput.focus();
        
        // Add error styling
        nameInput.style.borderColor = '#F44336';
        nameInput.style.boxShadow = '0 0 0 3px rgba(244, 67, 54, 0.2)';
        
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert-checkout mt-3';
        errorDiv.style.borderColor = '#F44336';
        errorDiv.style.color = '#F44336';
        errorDiv.style.background = 'linear-gradient(135deg, rgba(244, 67, 54, 0.1), transparent)';
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Please enter your name.';
        
        // Remove any existing error
        const existingError = nameInput.parentNode.querySelector('.alert-checkout');
        if (existingError) {
          existingError.remove();
        }
        
        nameInput.parentNode.appendChild(errorDiv);
        
        // Remove error after 3 seconds
        setTimeout(() => {
          errorDiv.remove();
          nameInput.style.borderColor = '';
          nameInput.style.boxShadow = '';
        }, 3000);
        
        return false;
      }
      
      // Add loading state to button
      const submitBtn = checkoutForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing Order...';
      submitBtn.disabled = true;
      
      // Auto-submit after 5 seconds even if disabled
      setTimeout(() => {
        if (submitBtn.disabled) {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        }
      }, 5000);
    });
    
    // Add focus effects
    const formInputs = document.querySelectorAll('.form-control');
    formInputs.forEach(input => {
      input.addEventListener('focus', function() {
        this.style.transform = 'translateY(-2px)';
      });
      
      input.addEventListener('blur', function() {
        this.style.transform = 'translateY(0)';
      });
    });
  });
</script>
</body>
</html>