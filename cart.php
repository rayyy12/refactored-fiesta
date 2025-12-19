<?php
session_start();

// Handle cart actions
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action'])) {
        if($_POST['action'] === 'update') {        
            foreach($_POST['qty'] as $i => $q) {
                $q = max(0, (int)$q);
                if($q === 0) { 
                    unset($_SESSION['cart'][$i]); 
                    $_SESSION['cart_message'] = 'Item removed from cart.';
                }
                else { 
                    $_SESSION['cart'][$i]['qty'] = $q;
                    $_SESSION['cart_message'] = 'Cart updated successfully!';
                }
            }
            $_SESSION['cart'] = array_values($_SESSION['cart'] ?? []);
        } elseif($_POST['action'] === 'clear') {
            unset($_SESSION['cart']);
            unset($_SESSION['senior_discount']);
            $_SESSION['success_message'] = 'Cart cleared successfully!';
        } elseif($_POST['action'] === 'remove' && isset($_POST['item_index'])) {
            $index = (int)$_POST['item_index'];
            if(isset($_SESSION['cart'][$index])) {
                $itemName = $_SESSION['cart'][$index]['name'];
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                $_SESSION['cart_message'] = "$itemName removed from cart.";
            }
        }
    }
    
    // Handle discount toggle via POST
    if(isset($_POST['toggle_discount'])) {
        if($_POST['toggle_discount'] === 'on') {
            $_SESSION['senior_discount'] = true;
            $_SESSION['discount_message'] = 'Senior discount (10%) applied!';
        } else {
            unset($_SESSION['senior_discount']);
            $_SESSION['discount_message'] = 'Senior discount removed.';
        }
    }
    
    header("Location: cart.php");
    exit;
}

// Calculate totals
$total = 0;
$itemCount = 0;
$cartItems = $_SESSION['cart'] ?? [];

foreach($cartItems as $item) {
    $itemCount += $item['qty'];
    $total += $item['price'] * $item['qty'];
}

// Calculate discounts
$serviceCharge = $total * 0.05;
$seniorDiscount = isset($_SESSION['senior_discount']) ? ($total * 0.10) : 0;
$grandTotal = $total + $serviceCharge - $seniorDiscount;

// Ensure grand total doesn't go negative
if($grandTotal < 0) {
    $grandTotal = 0;
}

// Check for messages
$successMessage = '';
$cartMessage = '';
$discountMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['cart_message'])) {
    $cartMessage = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
if (isset($_SESSION['discount_message'])) {
    $discountMessage = $_SESSION['discount_message'];
    unset($_SESSION['discount_message']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Your Cart • Forresto</title>
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
    
    /* Navigation */
    .navbar {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(15px);
      border-bottom: 1px solid rgba(165, 214, 167, 0.3);
      padding: 1rem 0;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 500;
    }
    
    .navbar-brand {
      font-family: 'Playfair Display', serif;
      font-weight: 800;
      font-size: 1.8rem;
      color: var(--evening-twilight);
      letter-spacing: 0.5px;
      position: relative;
      padding-left: 2.5rem;
    }
    
    .navbar-brand::before {
      content: '🌿';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.5rem;
      animation: gentleSway 4s infinite ease-in-out;
    }

    @keyframes gentleSway {
      0%, 100% { transform: translateY(-50%) rotate(0deg); }
      50% { transform: translateY(-50%) rotate(5deg); }
    }
    
    /* Cart Container */
    .cart-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    /* Cart Header */
    .cart-header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(25px);
      border-radius: 24px;
      margin: 2rem auto 3rem;
      padding: 3rem;
      border: 1px solid rgba(255, 255, 255, 0.4);
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
      position: relative;
      overflow: hidden;
      text-align: center;
    }

    .cart-header::before {
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
    
    .cart-title {
      font-family: 'Playfair Display', serif;
      font-weight: 800;
      font-size: 2.8rem;
      color: var(--evening-twilight);
      margin-bottom: 0.5rem;
      line-height: 1.2;
    }
    
    .cart-subtitle {
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-size: 1.2rem;
      color: #666;
      line-height: 1.6;
      max-width: 800px;
      margin: 0 auto 2rem;
    }
    
    /* Cart Stats */
    .cart-stats {
      display: flex;
      justify-content: center;
      gap: 2rem;
      flex-wrap: wrap;
      margin-top: 1.5rem;
    }
    
    .stat-item {
      text-align: center;
      padding: 1.5rem 2rem;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 16px;
      border: 1px solid rgba(0, 0, 0, 0.08);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }
    
    .stat-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      border-color: var(--evening-amber);
    }
    
    .stat-value {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--evening-twilight);
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      color: #666;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    /* Messages */
    .message-alert {
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      border: 2px solid;
      backdrop-filter: blur(10px);
      animation: slideIn 0.5s ease;
      font-family: 'Montserrat', Helvetica, sans-serif;
    }
    
    .message-alert.success {
      background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), transparent);
      border-color: var(--success-green);
      color: var(--success-green);
    }
    
    .message-alert.info {
      background: linear-gradient(135deg, rgba(179, 229, 252, 0.2), transparent);
      border-color: var(--afternoon-sky);
      color: var(--evening-twilight);
    }
    
    .message-alert.warning {
      background: linear-gradient(135deg, rgba(156, 39, 176, 0.1), transparent);
      border-color: var(--admin-purple);
      color: var(--admin-purple);
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
    
    /* Empty Cart */
    .empty-cart {
      text-align: center;
      padding: 4rem 2rem;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(25px);
      border-radius: 24px;
      border: 2px dashed var(--afternoon-green);
      margin-bottom: 3rem;
    }
    
    .empty-cart-icon {
      font-size: 4rem;
      color: var(--afternoon-green);
      margin-bottom: 1.5rem;
      opacity: 0.7;
    }
    
    .empty-cart-title {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--evening-twilight);
      font-size: 2rem;
      margin-bottom: 1rem;
    }
    
    /* Cart Items */
    .cart-items-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(25px);
      border-radius: 24px;
      padding: 2rem;
      border: 1px solid rgba(255, 255, 255, 0.4);
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
      margin-bottom: 2rem;
    }
    
    .cart-item {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      border: 1px solid rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }
    
    .cart-item:hover {
      border-color: var(--evening-amber);
      transform: translateX(5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .item-image {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 12px;
      border: 2px solid var(--afternoon-green);
    }
    
    .item-details {
      flex: 1;
    }
    
    .item-name {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--evening-twilight);
      font-size: 1.3rem;
      margin-bottom: 0.5rem;
    }
    
    .item-price {
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      color: #666;
      margin-bottom: 0.5rem;
    }
    
    .item-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .quantity-input {
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid var(--afternoon-green);
      border-radius: 8px;
      color: var(--text-dark);
      width: 70px;
      text-align: center;
      padding: 0.5rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .quantity-input:focus {
      outline: none;
      border-color: var(--evening-amber);
      box-shadow: 0 0 0 3px rgba(255, 183, 77, 0.2);
    }
    
    .btn-remove {
      background: linear-gradient(135deg, var(--danger-red), #D32F2F);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 0.5rem 1rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      font-size: 0.85rem;
      transition: all 0.3s ease;
    }
    
    .btn-remove:hover {
      background: linear-gradient(135deg, #D32F2F, var(--danger-red));
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
    }
    
    .item-subtotal {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--evening-amber);
      font-size: 1.2rem;
      min-width: 120px;
      text-align: right;
    }
    
    /* Senior Discount */
    .senior-discount-section {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
      border-radius: 16px;
      border: 2px solid var(--admin-purple);
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    
    .senior-discount-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px dashed rgba(156, 39, 176, 0.3);
    }
    
    .senior-discount-title {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--admin-purple);
      font-size: 1.3rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    .discount-badge {
      background: linear-gradient(135deg, var(--admin-purple), #7B1FA2);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    .discount-toggle-btn {
      background: linear-gradient(135deg, var(--admin-purple), #7B1FA2);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.6rem 1.5rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
      cursor: pointer;
    }
    
    .discount-toggle-btn:hover {
      background: linear-gradient(135deg, #AB47BC, var(--admin-purple));
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(156, 39, 176, 0.3);
    }
    
    .discount-toggle-btn.off {
      background: linear-gradient(135deg, #BDBDBD, #9E9E9E);
    }
    
    .discount-toggle-btn.off:hover {
      background: linear-gradient(135deg, #9E9E9E, #BDBDBD);
    }
    
    .senior-discount-info {
      font-family: 'Montserrat', Helvetica, sans-serif;
      color: #666;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    /* Cart Summary */
    .cart-summary {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(25px);
      border-radius: 24px;
      padding: 2rem;
      border: 1px solid rgba(255, 255, 255, 0.4);
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
      margin-top: 2rem;
    }
    
    .summary-title {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--evening-twilight);
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .summary-label {
      font-family: 'Montserrat', Helvetica, sans-serif;
      color: #666;
    }
    
    .summary-value {
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      color: var(--text-dark);
    }
    
    .summary-total {
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
    
    /* Cart Actions */
    .cart-actions {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }
    
    .btn-update {
      background: linear-gradient(135deg, var(--afternoon-green), #81C784);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.85rem 2rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
    }
    
    .btn-update:hover {
      background: linear-gradient(135deg, #81C784, var(--afternoon-green));
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(165, 214, 167, 0.3);
      text-decoration: none;
      color: white;
    }
    
    .btn-clear {
      background: linear-gradient(135deg, var(--danger-red), #D32F2F);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.85rem 2rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
    }
    
    .btn-clear:hover {
      background: linear-gradient(135deg, #D32F2F, var(--danger-red));
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(244, 67, 54, 0.3);
      text-decoration: none;
      color: white;
    }
    
    .btn-checkout {
      background: linear-gradient(135deg, var(--evening-amber), #FF9800);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.85rem 2rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      text-decoration: none;
    }
    
    .btn-checkout:hover {
      background: linear-gradient(135deg, #FFB74D, var(--evening-amber));
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(255, 183, 77, 0.3);
      color: white;
    }
    
    .btn-browse {
      background: linear-gradient(135deg, var(--afternoon-green), #81C784);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.85rem 2rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-top: 1.5rem;
    }
    
    .btn-browse:hover {
      background: linear-gradient(135deg, #81C784, var(--afternoon-green));
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(165, 214, 167, 0.3);
      color: white;
    }
    
    /* Cart Badge */
    .cart-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: var(--evening-amber);
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 0.7rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-cart-nav {
      background: linear-gradient(135deg, var(--evening-amber), #FF9800);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.6rem 1.5rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      position: relative;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
    }
    
    .btn-cart-nav:hover {
      background: linear-gradient(135deg, #FFB74D, var(--evening-amber));
      transform: translateY(-2px);
      color: white;
    }
    
    /* Buttons */
    .btn-back {
      border: 2px solid var(--afternoon-green);
      color: var(--afternoon-green);
      border-radius: 12px;
      padding: 0.6rem 1.5rem;
      font-family: 'Montserrat', Helvetica, sans-serif;
      font-weight: 600;
      transition: all 0.3s ease;
      background: transparent;
      display: inline-flex;
      align-items: center;
    }
    
    .btn-back:hover {
      background: var(--afternoon-green);
      color: white;
      transform: translateY(-2px);
      text-decoration: none;
    }
    
    /* Hide carousel controls */
    .background-carousel .carousel-control-prev,
    .background-carousel .carousel-control-next,
    .background-carousel .carousel-indicators {
      display: none;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .cart-header {
        padding: 2rem 1rem;
      }
      
      .cart-title {
        font-size: 2.2rem;
      }
      
      .cart-item {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
      }
      
      .item-controls {
        justify-content: center;
      }
      
      .item-subtotal {
        text-align: center;
        min-width: auto;
      }
      
      .cart-actions {
        flex-direction: column;
      }
      
      .cart-actions button {
        width: 100%;
      }
      
      .senior-discount-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }
    }
    
    @media (max-width: 576px) {
      .cart-title {
        font-size: 1.8rem;
      }
      
      .cart-stats {
        flex-direction: column;
        align-items: center;
      }
      
      .stat-item {
        width: 100%;
        max-width: 250px;
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

<!-- Navigation -->
<nav class="navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">Forresto</a>
    <div class="d-flex align-items-center gap-2">
      <?php if(isset($_SESSION['user'])): ?>
        <a href="logout.php" class="btn-back me-2">
          <i class="fas fa-sign-out-alt me-2"></i>
          Logout
        </a>
        <?php if($_SESSION['user']['role'] === 'admin'): ?>
          <a href="admin_dashboard.php" class="btn-cart-nav me-2">
            <i class="fas fa-crown me-2"></i>Admin
          </a>
        <?php endif; ?>
      <?php else: ?>
        <a href="login.php" class="btn-back me-2">
          <i class="fas fa-sign-in-alt me-2"></i>
          Login
        </a>
        <a href="register.php" class="btn-cart-nav me-2">
          <i class="fas fa-user-plus me-2"></i>Register
        </a>
      <?php endif; ?>
      <a href="index.php" class="btn-back">
        <i class="fas fa-arrow-left me-2"></i>
        Back to Menu
      </a>
    </div>
  </div>
</nav>

<div class="cart-container">
  <!-- Cart Header -->
  <div class="cart-header">
    <h1 class="cart-title">
      <i class="fas fa-shopping-basket me-2"></i>
      Your Cart
    </h1>
    <p class="cart-subtitle">Everything you need for a peaceful moment at Forresto</p>
    
    <div class="cart-stats">
      <div class="stat-item">
        <div class="stat-value"><?php echo $itemCount; ?></div>
        <div class="stat-label">Total Items</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">₱<?php echo number_format($total, 2); ?></div>
        <div class="stat-label">Subtotal</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?php echo count($cartItems); ?></div>
        <div class="stat-label">Unique Items</div>
      </div>
    </div>
  </div>

  <!-- Messages -->
  <?php if ($successMessage): ?>
    <div class="message-alert success">
      <div class="d-flex align-items-center">
        <i class="fas fa-check-circle fa-2x me-3"></i>
        <div>
          <h4 class="mb-1">Success!</h4>
          <p class="mb-0"><?php echo htmlspecialchars($successMessage); ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>
  
  <?php if ($cartMessage): ?>
    <div class="message-alert info">
      <div class="d-flex align-items-center">
        <i class="fas fa-info-circle fa-2x me-3"></i>
        <div>
          <h4 class="mb-1">Cart Updated</h4>
          <p class="mb-0"><?php echo htmlspecialchars($cartMessage); ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>
  
  <?php if ($discountMessage): ?>
    <div class="message-alert warning">
      <div class="d-flex align-items-center">
        <i class="fas fa-tag fa-2x me-3"></i>
        <div>
          <h4 class="mb-1">Discount Updated</h4>
          <p class="mb-0"><?php echo htmlspecialchars($discountMessage); ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if(empty($cartItems)): ?>
    <!-- Empty Cart -->
    <div class="empty-cart">
      <div class="empty-cart-icon">
        <i class="fas fa-coffee"></i>
      </div>
      <h2 class="empty-cart-title">Your Cart is Empty</h2>
      <p class="cart-subtitle">Add some items to create your perfect moment at Forresto</p>
      <a href="index.php" class="btn-browse">
        <i class="fas fa-search me-2"></i>Browse Our Menu
      </a>
    </div>
  <?php else: ?>
    <!-- Senior Discount Section -->
    <div class="senior-discount-section">
      <div class="senior-discount-header">
        <h3 class="senior-discount-title">
          <i class="fas fa-user-tie"></i>
          Senior Citizen Discount
          <span class="discount-badge">10% OFF</span>
        </h3>
        <form method="POST" style="display: inline;">
          <?php if(isset($_SESSION['senior_discount'])): ?>
            <input type="hidden" name="toggle_discount" value="off">
            <button type="submit" class="discount-toggle-btn" onclick="return confirm('Remove senior discount?')">
              <i class="fas fa-toggle-on me-1"></i>
              Discount Applied
            </button>
          <?php else: ?>
            <input type="hidden" name="toggle_discount" value="on">
            <button type="submit" class="discount-toggle-btn off" onclick="return confirmSeniorDiscount()">
              <i class="fas fa-toggle-off me-1"></i>
              Apply Discount
            </button>
          <?php endif; ?>
        </form>
      </div>
      <div class="senior-discount-info">
        <i class="fas fa-info-circle"></i>
        Apply 10% discount on your total order. Valid for senior citizens aged 60 and above.
      </div>
    </div>
    
    <!-- Cart Form -->
    <form method="POST" id="cartForm">
      <input type="hidden" name="action" value="update" id="formAction">
      
      <!-- Cart Items -->
      <div class="cart-items-container">
        <?php foreach($cartItems as $i => $it): 
          $subtotal = $it['price'] * $it['qty'];
          $imageUrl = $it['img'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800';
        ?>
        <div class="cart-item" id="item-<?php echo $i; ?>">
          <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
               alt="<?php echo htmlspecialchars($it['name']); ?>" 
               class="item-image">
          
          <div class="item-details">
            <h3 class="item-name"><?php echo htmlspecialchars($it['name']); ?></h3>
            <p class="item-price">₱<?php echo number_format($it['price'], 2); ?> each</p>
            
            <div class="item-controls">
              <input type="number" 
                     name="qty[<?php echo $i; ?>]" 
                     value="<?php echo $it['qty']; ?>" 
                     min="0" 
                     max="99" 
                     class="quantity-input"
                     onchange="updateSubtotal(<?php echo $i; ?>, <?php echo $it['price']; ?>, this.value)">
              
              <button type="button" 
                      class="btn-remove" 
                      onclick="removeItem(<?php echo $i; ?>, '<?php echo addslashes($it['name']); ?>')">
                <i class="fas fa-trash me-1"></i>Remove
              </button>
            </div>
          </div>
          
          <div class="item-subtotal" id="subtotal-<?php echo $i; ?>">
            ₱<?php echo number_format($subtotal, 2); ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Cart Summary -->
      <div class="cart-summary">
        <h3 class="summary-title">
          <i class="fas fa-receipt me-2"></i>
          Order Summary
        </h3>
        
        <div class="summary-row">
          <span class="summary-label">Subtotal</span>
          <span class="summary-value" id="summary-subtotal">₱<?php echo number_format($total, 2); ?></span>
        </div>
        
        <div class="summary-row">
          <span class="summary-label">Service Charge (5%)</span>
          <span class="summary-value" id="summary-service">₱<?php echo number_format($serviceCharge, 2); ?></span>
        </div>
        
        <?php if(isset($_SESSION['senior_discount'])): ?>
        <div class="summary-row">
          <span class="summary-label">
            <i class="fas fa-tag me-1"></i>
            Senior Discount (10%)
          </span>
          <span class="summary-value" style="color: var(--admin-purple); font-weight: 700;">
            -₱<?php echo number_format($seniorDiscount, 2); ?>
          </span>
        </div>
        <?php endif; ?>
        
        <div class="summary-total">
          <span class="total-label">Total Amount</span>
          <span class="total-value" id="grandTotal">₱<?php echo number_format($grandTotal, 2); ?></span>
        </div>
      </div>

      <!-- Cart Actions -->
      <div class="cart-actions">
        <div class="d-flex gap-2">
          <button type="submit" class="btn-update">
            <i class="fas fa-sync-alt me-2"></i>Update Cart
          </button>
          <button type="button" class="btn-clear" onclick="clearCart()">
            <i class="fas fa-trash-alt me-2"></i>Clear Cart
          </button>
        </div>
        
        <a href="checkout.php" class="btn-checkout">
          <i class="fas fa-shopping-bag me-2"></i>Proceed to Checkout
        </a>
      </div>
    </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize carousel
    const carousel = new bootstrap.Carousel(document.getElementById('backgroundCarousel'), {
      interval: 5000,
      wrap: true,
      pause: false
    });
  });
  
  // Confirm senior discount application
  function confirmSeniorDiscount() {
    return confirm('Apply 10% Senior Citizen Discount?\n\nPlease ensure you qualify for this discount (aged 60+).\n\nNote: Valid ID may be required at checkout.');
  }
  
  // Remove individual item
  function removeItem(itemId, itemName) {
    if (confirm(`Remove "${itemName}" from your cart?`)) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'remove';
      
      const indexInput = document.createElement('input');
      indexInput.type = 'hidden';
      indexInput.name = 'item_index';
      indexInput.value = itemId;
      
      form.appendChild(actionInput);
      form.appendChild(indexInput);
      document.body.appendChild(form);
      form.submit();
    }
  }
  
  // Clear entire cart
  function clearCart() {
    if (confirm('Are you sure you want to clear your entire cart?\n\nAll items and discounts will be removed.')) {
      const form = document.getElementById('cartForm');
      const actionInput = document.getElementById('formAction');
      actionInput.value = 'clear';
      form.submit();
    }
  }
</script>
</body>
</html>