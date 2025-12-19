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

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forresto • A Sanctuary in the City</title>
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
  
  /* Overlay to make content readable */
  .content-overlay {
      position: relative;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 2rem;
  }
  
  .navbar {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(15px);
    border-bottom: 1px solid rgba(165, 214, 167, 0.3);
    padding: 1rem 0;
    transition: all 0.3s ease;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 500;
  }

  .navbar:hover {
    background: rgba(255, 255, 255, 1);
  }
  
  .navbar-brand {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 2rem;
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
    font-size: 1.8rem;
    animation: gentleSway 4s infinite ease-in-out;
  }

  @keyframes gentleSway {
    0%, 100% { transform: translateY(-50%) rotate(0deg); }
    50% { transform: translateY(-50%) rotate(5deg); }
  }

  /* New Hero Section Layout */
  .hero-section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(25px);
    border-radius: 24px;
    margin: 2rem auto;
    padding: 4rem 3rem;
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
    min-height: 600px;
    display: flex;
    align-items: center;
  }

  .hero-section::before {
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

  .hero-logo-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 2rem;
    position: relative;
  }

  .hero-logo {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 5rem;
    color: var(--evening-twilight);
    text-align: center;
    margin-bottom: 1.5rem;
    line-height: 1;
    position: relative;
  }

  .hero-logo::before {
    content: '';
    position: absolute;
    top: -3rem;
    left: 50%;
    transform: translateX(-50%);
    font-size: 4rem;
    animation: gentleSway 4s infinite ease-in-out;
  }

  .hero-subtitle {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    color: var(--evening-amber);
    text-align: center;
    font-style: italic;
    margin-bottom: 3rem;
    max-width: 400px;
    line-height: 1.4;
  }

  .hero-logo-divider {
    width: 200px;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--afternoon-green), transparent);
    margin: 2rem 0;
  }

  .hero-logo-quote {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    color: var(--evening-twilight);
    text-align: center;
    font-style: italic;
    max-width: 300px;
    line-height: 1.6;
    opacity: 0.8;
  }

  .hero-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 2rem;
    border-left: 2px solid rgba(165, 214, 167, 0.3);
  }

  .hero-content-title {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 3rem;
    color: var(--evening-twilight);
    margin-bottom: 2rem;
    line-height: 1.2;
  }

  .hero-content-description {
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 400;
    font-size: 1.2rem;
    color: #555;
    line-height: 1.8;
    margin-bottom: 2.5rem;
    flex-grow: 1;
  }

  .atmosphere-list {
    list-style: none;
    padding: 0;
    margin: 2rem 0;
  }

  .atmosphere-list li {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--evening-twilight);
    margin-bottom: 1rem;
    padding-left: 2.5rem;
    position: relative;
  }

  .atmosphere-list li::before {
    content: '✨';
    position: absolute;
    left: 0;
    top: 0;
    font-size: 1.2rem;
  }

  .atmosphere-quote {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    color: var(--evening-amber);
    font-style: italic;
    margin: 2.5rem 0;
    padding: 1.5rem;
    border-left: 4px solid var(--evening-amber);
    background: rgba(255, 213, 79, 0.05);
    border-radius: 0 10px 10px 0;
  }

  .hero-search-box {
    max-width: 100%;
    margin-top: 2rem;
  }
  
  .hero-search-box input {
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid var(--afternoon-green);
    border-radius: 50px;
    color: var(--text-dark);
    padding: 1.1rem 2rem;
    font-size: 1.1rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 400;
    transition: all 0.3s ease;
    width: 100%;
  }
  
  .hero-search-box input:focus {
    background: white;
    border-color: var(--evening-amber);
    box-shadow: 0 0 0 4px rgba(255, 183, 77, 0.15);
  }
  
  .hero-search-box button {
    background: var(--afternoon-green);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.85rem 2.5rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 1rem;
  }
  
  .hero-search-box button:hover {
    background: var(--evening-amber);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 183, 77, 0.3);
  }
  
  .category-title {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 2.5rem;
    color: var(--evening-twilight);
    margin: 4rem 0 2.5rem;
    padding-bottom: 1.2rem;
    border-bottom: 3px solid;
    border-image: linear-gradient(90deg, var(--afternoon-green), var(--evening-amber)) 1;
    position: relative;
  }

  .category-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100px;
    height: 3px;
    background: linear-gradient(90deg, var(--evening-amber), transparent);
  }
  
  .menu-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  }
  
  .menu-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
    border-color: var(--evening-amber);
  }
  
  .menu-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 16px;
    margin-bottom: 1.5rem;
    transition: transform 0.3s ease;
  }
  
  .menu-card:hover .menu-img {
    transform: scale(1.05);
  }
  
  .menu-card h5 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--evening-twilight);
    margin-bottom: 0.8rem;
    letter-spacing: 0.2px;
  }
  
  .menu-card .description {
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 400;
    color: #666;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    min-height: 80px;
  }
  
  .price-tag {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 1.8rem;
    color: var(--evening-amber);
    letter-spacing: 0.5px;
  }
  
  .btn-add-to-cart {
    background: linear-gradient(135deg, var(--afternoon-green), #81C784);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.8rem 2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(165, 214, 167, 0.3);
  }
  
  .btn-add-to-cart:hover {
    background: linear-gradient(135deg, var(--evening-amber), #FFA726);
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
  }
  
  .btn-outline {
    border: 2px solid var(--afternoon-green);
    color: var(--afternoon-green);
    border-radius: 50px;
    padding: 0.7rem 2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    transition: all 0.3s ease;
    background: transparent;
  }
  
  .btn-outline:hover {
    background: var(--afternoon-green);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-cart {
    background: linear-gradient(135deg, var(--evening-amber), #FF9800);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.7rem 2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    box-shadow: 0 5px 20px rgba(255, 183, 77, 0.3);
  }
  
  .user-greeting {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--evening-twilight);
    font-size: 1.1rem;
  }
  
  .footer {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(15px);
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    padding: 4rem 0 3rem;
    margin-top: 4rem;
    border-radius: 30px 30px 0 0;
    font-family: 'Montserrat', Helvetica, sans-serif;
  }
  
  .no-results {
    text-align: center;
    padding: 4rem;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    border: 2px dashed var(--afternoon-green);
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .content-wrapper {
    animation: fadeIn 1s ease;
  }
  
  /* Hide carousel controls and indicators */
  .background-carousel .carousel-control-prev,
  .background-carousel .carousel-control-next,
  .background-carousel .carousel-indicators {
    display: none;
  }

  /* Typography improvements */
  h1, h2, h3, h4, h5, h6 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
  }

  p, span, div, input, button {
    font-family: 'Montserrat', Helvetica, sans-serif;
  }

  .section-divider {
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--afternoon-green), transparent);
    margin: 3rem 0;
    opacity: 0.5;
  }

  /* Responsive design */
  @media (max-width: 992px) {
    .hero-section {
      flex-direction: column;
      text-align: center;
      padding: 2rem;
    }
    
    .hero-logo-container {
      padding: 1rem;
      margin-bottom: 2rem;
    }
    
    .hero-content {
      border-left: none;
      border-top: 2px solid rgba(165, 214, 167, 0.3);
      padding-top: 2rem;
    }
    
    .hero-logo {
      font-size: 3.5rem;
    }
    
    .hero-content-title {
      font-size: 2.5rem;
    }
    
    .hero-logo::before {
      top: -2rem;
      font-size: 3rem;
    }
  }

  @media (max-width: 768px) {
    .hero-logo {
      font-size: 3rem;
    }
    
    .hero-content-title {
      font-size: 2rem;
    }
    
    .hero-subtitle {
      font-size: 1.5rem;
    }
    
    .atmosphere-list li {
      font-size: 1.3rem;
    }
    
    .atmosphere-quote {
      font-size: 1.3rem;
    }
    
    .navbar-brand {
      font-size: 1.6rem;
      padding-left: 2rem;
    }
    
    .navbar-brand::before {
      font-size: 1.4rem;
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
    <div class="carousel-item">
      <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="Coffee and calm space">
    </div>
    <div class="carousel-item">
      <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="Cozy café interior">
    </div>
  </div>
</div>

<div class="content-wrapper">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="index.php">Forresto</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <div class="navbar-nav ms-auto align-items-center gap-3">
          <?php if(isset($_SESSION['user'])): ?>
            <span class="user-greeting me-3">
              <i class="fas fa-user-circle me-2"></i>
              Hello, <?=htmlspecialchars($_SESSION['user']['username'])?>
            </span>
            <a href="login.php" class="btn btn-outline">
              <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
            <?php if($_SESSION['user']['role'] === 'admin'): ?>
              <a href="admin_dashboard.php" class="btn btn-outline">
                <i class="fas fa-cog me-2"></i>Admin
              </a>
            <?php endif; ?>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline">
              <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
            <a href="register.php" class="btn btn-add-to-cart">
              <i class="fas fa-user-plus me-2"></i>Join Us
            </a>
          <?php endif; ?>
          <a href="cart.php" class="btn btn-cart">
            <i class="fas fa-shopping-basket me-2"></i>Cart
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <!-- New Hero Section with Logo Left, Content Right -->
    <div class="hero-section">
      <div class="row w-100 align-items-center">
        <!-- Left Column: Logo and Subtitle -->
       <div class="col-lg-5">
  <div class="hero-logo-container">
    <!-- Replace this div with your logo image -->
    <img src="Uploads/logo1.png" alt="Forresto Logo" class="custom-logo">
    <div class="hero-subtitle">A Sanctuary in the City</div>
    <div class="hero-logo-divider"></div>
    <div class="hero-logo-quote">
      Where calm lives, waiting for us to step inside and breathe
    </div>
  </div>
</div>
        
        <!-- Right Column: Content from your image -->
        <div class="col-lg-7">
          <div class="hero-content">
            <h1 class="hero-content-title">Welcome to Forresto</h1>
            
            <p class="hero-content-description">
              The atmosphere shifts beautifully throughout the day:
            </p>
            
            <ul class="atmosphere-list">
              <li>Bright Mornings</li>
              <li>Relaxed Afternoons</li>
              <li>Glowing Evenings</li>
            </ul>
            
            <div class="atmosphere-quote">
              "What makes Forresto truly special is this atmosphere."
            </div>
            
            <p class="hero-content-description">
              It's more than a place to eat or drink, it's a sanctuary. A rare haven in the heart of the city 
              where people can take a breath, reset, and rediscover balance.
            </p>
            
            <form class="hero-search-box" method="GET" action="index.php">
              <input name="q" class="form-control" 
                     placeholder="Discover our menu..."
                     value="<?= htmlspecialchars($q) ?>">
              <button class="btn btn-add-to-cart" type="submit">
                <i class="fas fa-search me-2"></i>Search Menu
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="section-divider"></div>

    <?php
    $catSql = "SELECT DISTINCT CATEGORY FROM MENU ORDER BY CATEGORY";
    $catStmt = sqlsrv_query($conn, $catSql);
    
    $hasResults = false;
    
    while ($catRow = sqlsrv_fetch_array($catStmt, SQLSRV_FETCH_ASSOC)):
      $cat = $catRow['CATEGORY'];
      
      if ($q !== '') {
          $like = "%$q%";
          $sql = "SELECT * FROM MENU 
                  WHERE CATEGORY = '$cat' 
                  AND (PRODUCTNAME LIKE '$like' OR DESCRIPTION LIKE '$like')";
      } else {
          $sql = "SELECT * FROM MENU WHERE CATEGORY = '$cat'";
      }
      
      $stmt = sqlsrv_query($conn, $sql);
      $categoryItems = array();
      
      while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
          $categoryItems[] = $row;
      }
      
      if (!empty($categoryItems)) {
          $hasResults = true;
          echo "<div class='content-overlay mb-5'>";
          echo "<h2 class='category-title'>" . htmlspecialchars(ucfirst($cat)) . "</h2>";
          echo "<div class='row g-5'>";
          
          foreach ($categoryItems as $row) {
              $id = $row['PRODUCTID'];
              $name = htmlspecialchars($row['PRODUCTNAME']);
              $price = number_format($row['PRICE'], 2);
              $img = htmlspecialchars($row['IMAGEPATH'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800');
              $desc = htmlspecialchars($row['DESCRIPTION'] ?: 'A moment of calm in every bite.');
              
              echo "<div class='col-lg-4 col-md-6'>
                      <div class='menu-card'>
                        <img src='{$img}' class='menu-img' alt='{$name}'>
                        <h5>{$name}</h5>
                        <p class='description'>{$desc}</p>
                        <div class='d-flex align-items-center justify-content-between mt-4'>
                          <span class='price-tag'>₱{$price}</span>
                          <form method='POST' action='add_to_cart.php'>
                            <input type='hidden' name='id' value='{$id}'>
                            <input type='hidden' name='return' value='index.php'>
                            <button type='submit' class='btn btn-add-to-cart'>
                              <i class='fas fa-plus me-2'></i>Add to Cart
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>";
          }
          
          echo "</div></div>";
      }
    endwhile;
    
    if (!$hasResults && $q !== '') {
      echo "<div class='no-results my-5 content-overlay'>
              <i class='fas fa-search fa-3x mb-4' style='color: var(--afternoon-green);'></i>
              <h3 class='mb-3' style='font-family: \"Playfair Display\", serif;'>No Results Found</h3>
              <p class='mb-4'>Try searching for something else or <a href='index.php' style='color: var(--evening-amber); font-weight: 600;'>browse all items</a>.</p>
            </div>";
    }
    ?>

    <footer class="footer">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8 text-lg-start text-center mb-5 mb-lg-0">
            <h4 class="mb-4" style="color: var(--evening-twilight); font-family: 'Playfair Display', serif;">Forresto</h4>
            <p class="mb-0" style="color: #666; font-size: 1.1rem; line-height: 1.8;">
              A sanctuary in the city where people can take a breath, reset, and rediscover balance. 
              At Forresto, the calm we're always chasing isn't somewhere far away, it lives here, 
              waiting for us to step inside and breathe.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end text-center">
            <p class="mb-3">
              <i class="fas fa-map-marker-alt me-2" style="color: var(--afternoon-green);"></i>
              <span style="font-weight: 600;">JP Laurel St. Nasugbu, Batangas</span>
            </p>
            <p class="mb-3">
              <i class="fas fa-clock me-2" style="color: var(--evening-amber);"></i>
              <span style="font-weight: 600;">Open Daily: 7AM - 10PM</span>
            </p>
            <p class="mb-0">
              <i class="fas fa-phone me-2" style="color: var(--morning-light);"></i>
              <span style="font-weight: 600;">(043) 123-4567</span>
            </p>
          </div>
        </div>
        <div class="section-divider my-5"></div>
        <div class="text-center">
          <p class="mb-0" style="color: #888; font-size: 0.95rem;">
            <small>&copy; <?= date('Y') ?> Forresto. A place to feel lighter, calmer, and more at home.</small>
          </p>
        </div>
      </div>
    </footer>
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
    
    // Add fade animation to content
    const contentElements = document.querySelectorAll('.content-overlay, .hero-section');
    contentElements.forEach((element, index) => {
      setTimeout(() => {
        element.style.animation = 'fadeIn 0.8s ease forwards';
        element.style.opacity = '0';
      }, index * 200);
    });
    
    // Add hover effect to menu cards
    const menuCards = document.querySelectorAll('.menu-card');
    menuCards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-12px) scale(1.02)';
      });
      
      card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
      });
    });
  });
</script>
</body>
</html>