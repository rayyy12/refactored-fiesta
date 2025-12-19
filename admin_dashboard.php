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

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch statistics from database
$todaySales = 0;
$totalProducts = 0;
$pendingOrders = 0;
$monthRevenue = 0;
$totalCustomers = 0;

try {
    // Get today's sales
    $todaySql = "SELECT SUM(TOTALAMOUNT) as today_sales 
                 FROM TRANSACTIONS 
                 WHERE CAST(CREATEDATE AS DATE) = CAST(GETDATE() AS DATE)";
    $todayResult = sqlsrv_query($conn, $todaySql);
    if ($todayResult !== false) {
        $todayRow = sqlsrv_fetch_array($todayResult, SQLSRV_FETCH_ASSOC);
        $todaySales = $todayRow['today_sales'] ?? 0;
    }
    
    // Get total products
    $productsSql = "SELECT COUNT(*) as total_products FROM MENU";
    $productsResult = sqlsrv_query($conn, $productsSql);
    if ($productsResult !== false) {
        $productsRow = sqlsrv_fetch_array($productsResult, SQLSRV_FETCH_ASSOC);
        $totalProducts = $productsRow['total_products'] ?? 0;
    }
    
    // Get pending orders (transactions with 'Pending' status)
    $pendingSql = "SELECT COUNT(*) as pending_orders 
                   FROM TRANSACTIONS 
                   WHERE STATUS = 'Pending'";
    $pendingResult = sqlsrv_query($conn, $pendingSql);
    if ($pendingResult !== false) {
        $pendingRow = sqlsrv_fetch_array($pendingResult, SQLSRV_FETCH_ASSOC);
        $pendingOrders = $pendingRow['pending_orders'] ?? 0;
    }
    
    // Get month revenue
    $monthSql = "SELECT SUM(TOTALAMOUNT) as month_revenue 
                 FROM TRANSACTIONS 
                 WHERE MONTH(CREATEDATE) = MONTH(GETDATE()) 
                 AND YEAR(CREATEDATE) = YEAR(GETDATE())";
    $monthResult = sqlsrv_query($conn, $monthSql);
    if ($monthResult !== false) {
        $monthRow = sqlsrv_fetch_array($monthResult, SQLSRV_FETCH_ASSOC);
        $monthRevenue = $monthRow['month_revenue'] ?? 0;
    }
    
    // Get total customers
    $customersSql = "SELECT COUNT(DISTINCT USERID) as total_customers FROM TRANSACTIONS";
    $customersResult = sqlsrv_query($conn, $customersSql);
    if ($customersResult !== false) {
        $customersRow = sqlsrv_fetch_array($customersResult, SQLSRV_FETCH_ASSOC);
        $totalCustomers = $customersRow['total_customers'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard • Forresto</title>
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
    content: '👑';
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

  .admin-badge {
    background: linear-gradient(135deg, var(--admin-purple), #7B1FA2);
    color: white;
    padding: 0.3rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
  }
  
  /* Dashboard Container */
  .dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
  }
  
  /* Dashboard Header */
  .dashboard-header {
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

  .dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 80%, rgba(179, 229, 252, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(156, 39, 176, 0.15) 0%, transparent 50%);
    pointer-events: none;
  }
  
  .dashboard-title {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 2.8rem;
    color: var(--evening-twilight);
    margin-bottom: 0.5rem;
    line-height: 1.2;
  }
  
  .dashboard-subtitle {
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-size: 1.2rem;
    color: #666;
    line-height: 1.6;
    max-width: 800px;
    margin-bottom: 2rem;
  }
  
  /* Admin Greeting */
  .admin-info {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  }
  
  .admin-name {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--evening-twilight);
    font-size: 1.3rem;
    margin-bottom: 0.5rem;
  }
  
  .admin-role {
    color: var(--admin-purple);
    font-weight: 600;
    font-size: 1rem;
  }
  
  /* Statistics Grid */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  }
  
  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: var(--evening-amber);
  }
  
  .stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
  }
  
  .stat-value {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 2.2rem;
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
  
  /* Feature Cards Grid */
  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
  }
  
  .feature-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(25px);
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
  }
  
  .feature-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
    border-color: var(--evening-amber);
  }
  
  .feature-icon {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    background: linear-gradient(45deg, var(--admin-purple), var(--evening-amber));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-align: center;
  }
  
  .feature-title {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--evening-twilight);
    font-size: 1.4rem;
    margin-bottom: 1rem;
    text-align: center;
  }
  
  .feature-description {
    font-family: 'Montserrat', Helvetica, sans-serif;
    color: #666;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    flex-grow: 1;
    text-align: center;
  }
  
  /* Buttons */
  .btn-admin {
    background: linear-gradient(135deg, var(--admin-purple), #7B1FA2);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 0.85rem 2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(156, 39, 176, 0.3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
  }
  
  .btn-admin:hover {
    background: linear-gradient(135deg, #AB47BC, var(--admin-purple));
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(156, 39, 176, 0.4);
    text-decoration: none;
    color: white;
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
  }
  
  .btn-back:hover {
    background: var(--afternoon-green);
    color: white;
    transform: translateY(-2px);
    text-decoration: none;
  }
  
  .btn-logout {
    background: linear-gradient(135deg, var(--evening-amber), #FF9800);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 0.85rem 2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(255, 183, 77, 0.3);
    display: inline-flex;
    align-items: center;
  }
  
  .btn-logout:hover {
    background: linear-gradient(135deg, #FFB74D, var(--evening-amber));
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
    text-decoration: none;
    color: white;
  }
  
  /* Hide carousel controls */
  .background-carousel .carousel-control-prev,
  .background-carousel .carousel-control-next,
  .background-carousel .carousel-indicators {
    display: none;
  }
  
  /* Animations */
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  /* Responsive Design */
  @media (max-width: 992px) {
    .dashboard-header {
      padding: 2rem;
    }
    
    .dashboard-title {
      font-size: 2.2rem;
    }
    
    .features-grid {
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
  }
  
  @media (max-width: 768px) {
    .dashboard-container {
      padding: 1rem;
    }
    
    .dashboard-header {
      padding: 1.5rem;
    }
    
    .dashboard-title {
      font-size: 1.8rem;
    }
    
    .dashboard-subtitle {
      font-size: 1.1rem;
    }
    
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .features-grid {
      grid-template-columns: 1fr;
    }
  }
  
  @media (max-width: 576px) {
    .stats-grid {
      grid-template-columns: 1fr;
    }
    
    .dashboard-title {
      font-size: 1.5rem;
    }
    
    .navbar-brand {
      font-size: 1.4rem;
      padding-left: 2rem;
    }
    
    .navbar-brand::before {
      font-size: 1.2rem;
    }
  }
</style>
</head>

<body>
<!-- Background Image Carousel -->
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
    <a class="navbar-brand" href="index.php">Forresto Admin</a>
    <div class="d-flex align-items-center gap-3">
      <span class="admin-badge">
        <i class="fas fa-user-tie me-1"></i>
        <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
      </span>
      <a href="index.php" class="btn-back">
        <i class="fas fa-arrow-left me-2"></i>
        Back to Café
      </a>
      <a href="logout.php" class="btn-logout">
        <i class="fas fa-sign-out-alt me-2"></i>Logout
      </a>
    </div>
  </div>
</nav>

<div class="dashboard-container">
  <!-- Dashboard Header -->
  <div class="dashboard-header">
    <h1 class="dashboard-title">
      <i class="fas fa-chart-line me-2"></i>
      Admin Dashboard
    </h1>
    <p class="dashboard-subtitle">
      Manage your sanctuary's operations, monitor performance, and ensure every moment at Forresto remains peaceful and perfect.
    </p>
    
    <!-- Admin Info -->
    <div class="admin-info">
      <div class="admin-name">
        <i class="fas fa-user-tie me-2"></i>
        <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
      </div>
      <div class="admin-role">
        <i class="fas fa-crown me-1"></i>
        Administrator
      </div>
    </div>
    
    <!-- Statistics Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--success-green);">
          <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-value" id="todaySales">₱<?php echo number_format($todaySales, 2); ?></div>
        <div class="stat-label">Today's Sales</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--afternoon-green);">
          <i class="fas fa-utensils"></i>
        </div>
        <div class="stat-value" id="totalProducts"><?php echo $totalProducts; ?></div>
        <div class="stat-label">Menu Items</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--evening-amber);">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-value" id="pendingOrders"><?php echo $pendingOrders; ?></div>
        <div class="stat-label">Pending Orders</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--admin-purple);">
          <i class="fas fa-chart-bar"></i>
        </div>
        <div class="stat-value" id="monthRevenue">₱<?php echo number_format($monthRevenue, 2); ?></div>
        <div class="stat-label">Month Revenue</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--morning-light);">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-value" id="totalCustomers"><?php echo $totalCustomers; ?></div>
        <div class="stat-label">Active Customers</div>
      </div>
    </div>
  </div>
  
  <!-- Features Grid - Only 5 cards now -->
  <div class="features-grid">
    <!-- Menu Management -->
    <div class="feature-card">
      <div class="feature-icon">
        <i class="fas fa-utensils"></i>
      </div>
      <h3 class="feature-title">Menu Management</h3>
      <p class="feature-description">
        View, add, edit, or delete menu items. Keep your sanctuary's offerings fresh and inviting.
      </p>
      <a href="productlist.php" class="btn-admin">
        <i class="fas fa-edit me-2"></i>Manage Products
      </a>
    </div>
    
    <!-- Sales Analytics -->
    <div class="feature-card">
      <div class="feature-icon">
        <i class="fas fa-chart-line"></i>
      </div>
      <h3 class="feature-title">Sales Analytics</h3>
      <p class="feature-description">
        View detailed sales reports, analyze trends, and make data-driven decisions for your sanctuary.
      </p>
      <a href="reports.php" class="btn-admin">
        <i class="fas fa-chart-bar me-2"></i>View Reports
      </a>
    </div>
    
    <!-- Order Management -->
    <div class="feature-card">
      <div class="feature-icon">
        <i class="fas fa-shopping-cart"></i>
      </div>
      <h3 class="feature-title">Order Management</h3>
      <p class="feature-description">
        Track and manage customer orders, update order status, and ensure smooth order fulfillment.
      </p>
      <a href="orders.php" class="btn-admin">
        <i class="fas fa-list-alt me-2"></i>Manage Orders
      </a>
    </div>
    
    <!-- Staff Management -->
    <div class="feature-card">
      <div class="feature-icon">
        <i class="fas fa-users-cog"></i>
      </div>
      <h3 class="feature-title">Staff Management</h3>
      <p class="feature-description">
        Manage staff accounts, assign roles, and monitor team performance to maintain sanctuary excellence.
      </p>
      <a href="accounts.php" class="btn-admin">
        <i class="fas fa-user-friends me-2"></i>Manage Staff
      </a>
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
    
    // Animate statistics numbers
    function animateValue(element, start, end, duration, prefix = '', suffix = '') {
      let startTimestamp = null;
      const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        
        if (prefix === '₱') {
          element.textContent = prefix + value.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + suffix;
        } else {
          element.textContent = prefix + value.toLocaleString() + suffix;
        }
        
        if (progress < 1) {
          window.requestAnimationFrame(step);
        }
      };
      window.requestAnimationFrame(step);
    }
    
    // Get current values from the page
    const todaySalesEl = document.getElementById('todaySales');
    const totalProductsEl = document.getElementById('totalProducts');
    const pendingOrdersEl = document.getElementById('pendingOrders');
    const monthRevenueEl = document.getElementById('monthRevenue');
    const totalCustomersEl = document.getElementById('totalCustomers');
    
    // Extract numeric values
    const todaySales = parseFloat(todaySalesEl.textContent.replace('₱', '').replace(/,/g, '')) || 0;
    const totalProducts = parseInt(totalProductsEl.textContent) || 0;
    const pendingOrders = parseInt(pendingOrdersEl.textContent) || 0;
    const monthRevenue = parseFloat(monthRevenueEl.textContent.replace('₱', '').replace(/,/g, '')) || 0;
    const totalCustomers = parseInt(totalCustomersEl.textContent) || 0;
    
    // Animate the values after a short delay
    setTimeout(() => {
      // Reset to 0 for animation
      todaySalesEl.textContent = '₱0.00';
      totalProductsEl.textContent = '0';
      pendingOrdersEl.textContent = '0';
      monthRevenueEl.textContent = '₱0.00';
      totalCustomersEl.textContent = '0';
      
      // Animate to actual values
      animateValue(todaySalesEl, 0, todaySales, 1500, '₱');
      animateValue(totalProductsEl, 0, totalProducts, 1000);
      animateValue(pendingOrdersEl, 0, pendingOrders, 1000);
      animateValue(monthRevenueEl, 0, monthRevenue, 2000, '₱');
      animateValue(totalCustomersEl, 0, totalCustomers, 1200);
    }, 500);
    
    // Add fade-in animations to cards
    const cards = document.querySelectorAll('.feature-card, .stat-card');
    cards.forEach((card, index) => {
      card.style.animation = `fadeIn 0.8s ease ${index * 0.1}s forwards`;
      card.style.opacity = '0';
    });
  });
</script>
</body>
</html>