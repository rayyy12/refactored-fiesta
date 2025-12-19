<?php
session_start();
$serverName="DESKTOP-I9LLCAD\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Product Management • Forresto</title>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700;800&display=swap" rel="stylesheet">
<!-- Font Awesome -->
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
    --admin-purple: #8a2be2;
    --success-green: #28a745;
    --danger-red: #dc3545;
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
  }
  
  .admin-navbar {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(15px);
    border-bottom: 1px solid rgba(165, 214, 167, 0.3);
    padding: 1rem 0;
    transition: all 0.3s ease;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 500;
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

  .management-container {
    max-width: 1400px;
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
  
  .admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  }
  
  .stat-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    border-color: var(--evening-amber);
  }
  
  .stat-value {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--evening-amber);
    margin-bottom: 0.5rem;
    line-height: 1;
  }
  
  .stat-label {
    color: var(--evening-twilight);
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  
  .btn-add-product {
    background: linear-gradient(135deg, var(--afternoon-green), #81C784);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 1rem 2.5rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(165, 214, 167, 0.3);
  }
  
  .btn-add-product:hover {
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
    padding: 1rem 2.5rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(255, 183, 77, 0.3);
  }
  
  .btn-back:hover {
    background: linear-gradient(135deg, #FF9800, var(--evening-amber));
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
    color: white;
  }
  
  .products-table-container {
    background: white;
    border-radius: 24px;
    margin: 2rem auto;
    padding: 2rem;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
  }
  
  .products-table-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 80%, rgba(179, 229, 252, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 213, 79, 0.05) 0%, transparent 50%);
    pointer-events: none;
  }
  
  /* Table Styles */
  .table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--text-dark);
    --bs-table-border-color: rgba(0, 0, 0, 0.08);
    margin-bottom: 0;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.08);
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
    font-size: 0.9rem;
  }
  
  .table td {
    padding: 1.5rem;
    border-color: rgba(0, 0, 0, 0.05);
    vertical-align: middle;
    font-family: 'Montserrat', sans-serif;
  }
  
  .table tbody tr {
    transition: all 0.3s ease;
  }
  
  .table tbody tr:nth-child(even) {
    background: rgba(165, 214, 167, 0.05);
  }
  
  .table tbody tr:hover {
    background: rgba(255, 213, 79, 0.1);
    transform: translateX(5px);
  }
  
  .product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid var(--afternoon-green);
    transition: all 0.3s ease;
  }
  
  .product-image:hover {
    transform: scale(1.1) rotate(2deg);
    border-color: var(--evening-amber);
    box-shadow: 0 5px 15px rgba(255, 183, 77, 0.3);
  }
  
  .price-tag {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--evening-amber) !important;
    font-size: 1.2rem;
  }
  
  .category-badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-family: 'Montserrat', sans-serif;
  }
  
  .category-coffee { 
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.1), rgba(160, 82, 45, 0.05));
    color: #8B4513 !important; 
    border: 2px solid #8B4513; 
  }
  
  .category-pastry { 
    background: linear-gradient(135deg, rgba(255, 213, 79, 0.1), rgba(255, 193, 7, 0.05));
    color: #FFB74D !important; 
    border: 2px solid #FFB74D; 
  }
  
  .category-dessert { 
    background: linear-gradient(135deg, rgba(218, 112, 214, 0.1), rgba(186, 104, 200, 0.05));
    color: #BA68C8 !important; 
    border: 2px solid #BA68C8; 
  }
  
  .category-tea { 
    background: linear-gradient(135deg, rgba(165, 214, 167, 0.1), rgba(129, 199, 132, 0.05));
    color: #66BB6A !important; 
    border: 2px solid #66BB6A; 
  }
  
  .category-other { 
    background: linear-gradient(135deg, rgba(179, 229, 252, 0.1), rgba(144, 202, 249, 0.05));
    color: #4A8FE7 !important; 
    border: 2px solid #4A8FE7; 
  }
  
  .action-buttons {
    display: flex;
    gap: 0.5rem;
  }
  
  .btn-edit {
    background: linear-gradient(135deg, var(--afternoon-sky), #64B5F6);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    font-family: 'Montserrat', sans-serif;
    box-shadow: 0 4px 15px rgba(100, 181, 246, 0.2);
  }
  
  .btn-edit:hover {
    background: linear-gradient(135deg, #4A8FE7, var(--afternoon-sky));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 143, 231, 0.3);
    color: white;
  }
  
  .btn-delete {
    background: linear-gradient(135deg, var(--danger-red), #FF5252);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    font-family: 'Montserrat', sans-serif;
    box-shadow: 0 4px 15px rgba(244, 67, 54, 0.2);
  }
  
  .btn-delete:hover {
    background: linear-gradient(135deg, #FF1744, var(--danger-red));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(244, 67, 54, 0.3);
    color: white;
  }
  
  .empty-state {
    text-align: center;
    padding: 4rem;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    border: 2px dashed var(--afternoon-green);
  }

  .empty-state i {
    font-size: 4rem;
    color: var(--afternoon-green);
    margin-bottom: 1.5rem;
  }

  .search-container {
    position: relative;
    max-width: 400px;
    margin-bottom: 1.5rem;
  }
  
  .search-input {
    background: white;
    border: 2px solid var(--afternoon-green);
    border-radius: 50px;
    color: var(--text-dark);
    padding: 1rem 1.5rem 1rem 3rem;
    width: 100%;
    font-size: 1rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 400;
    transition: all 0.3s ease;
  }
  
  .search-input:focus {
    background: white;
    border-color: var(--evening-amber);
    box-shadow: 0 0 0 4px rgba(255, 183, 77, 0.15);
    outline: none;
  }
  
  .search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--afternoon-green);
  }
  
  .user-greeting {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--evening-twilight);
    font-size: 1.1rem;
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

  .section-divider {
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--afternoon-green), transparent);
    margin: 3rem 0;
    opacity: 0.5;
  }
  
  @media (max-width: 992px) {
    .page-header {
      padding: 2rem;
    }
    
    .page-title {
      font-size: 2.5rem;
    }
    
    .admin-stats {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 768px) {
    .page-title {
      font-size: 2rem;
    }
    
    .admin-brand {
      font-size: 1.6rem;
      padding-left: 2rem;
    }
    
    .admin-brand::before {
      font-size: 1.4rem;
    }
    
    .table thead {
      display: none;
    }
    
    .table tbody tr {
      display: block;
      margin-bottom: 1.5rem;
      background: white !important;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      border: 1px solid rgba(0, 0, 0, 0.08);
    }
    
    .table tbody td {
      display: block;
      text-align: right;
      border: none;
      padding: 0.75rem;
      position: relative;
      color: var(--text-dark) !important;
      background: transparent !important;
    }
    
    .table tbody td::before {
      content: attr(data-label);
      position: absolute;
      left: 1.5rem;
      top: 50%;
      transform: translateY(-50%);
      font-weight: 700;
      color: var(--evening-twilight) !important;
      font-family: 'Montserrat', sans-serif;
    }
    
    .action-buttons {
      justify-content: flex-end;
      margin-top: 1rem;
    }
    
    .product-image {
      margin: 0 auto;
      display: block;
    }
    
    .admin-stats {
      grid-template-columns: 1fr;
    }
  }
</style>
</head>

<body>
<nav class="navbar navbar-expand-lg admin-navbar">
  <div class="container">
    <a class="navbar-brand admin-brand" href="admin_dashboard.php">Forresto Admin</a>
    
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
        <a href="index.php" class="btn btn-back">
          <i class="fas fa-arrow-left me-2"></i>Back to Café
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="management-container">
  <div class="page-header">
    <h1 class="page-title">
      <i class="fas fa-utensils me-2"></i>
      Product Management
    </h1>
    <p class="page-subtitle">Curate your Forresto menu. Manage products, update pricing, and ensure every offering is a masterpiece.</p>
    
    <?php
    // Get product statistics
    $statsSql = "SELECT 
        COUNT(*) as total_products,
        COUNT(DISTINCT CATEGORY) as categories,
        SUM(PRICE) as total_value,
        AVG(PRICE) as avg_price
        FROM STRBARAKSMENU";
    $statsResult = sqlsrv_query($conn, $statsSql);
    $stats = sqlsrv_fetch_array($statsResult, SQLSRV_FETCH_ASSOC);
    ?>
    
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="productSearch" class="search-input" placeholder="Search products...">
      </div>
      
      <div class="d-flex gap-2">
        <a href="add_product.php" class="btn btn-add-product">
          <i class="fas fa-plus-circle me-2"></i>Add New Product
        </a>
        <a href="admin_dashboard.php" class="btn btn-back">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
      </div>
    </div>
  </div>

  <div class="section-divider"></div>

  <div class="products-table-container">
    <?php
    $sql = "SELECT * FROM STRBARAKSMENU ORDER BY PRODUCTNAME";
    $stmt = sqlsrv_query($conn, $sql);
    $hasProducts = sqlsrv_has_rows($stmt);
    
    if (!$hasProducts) {
      echo '<div class="empty-state">
              <i class="fas fa-utensils"></i>
              <h3 class="mb-3" style="font-family: \'Playfair Display\', serif; color: var(--evening-twilight);">No Products Found</h3>
              <p class="mb-4">Your menu is empty. Start by adding your first masterpiece!</p>
              <a href="add_product.php" class="btn btn-add-product">
                <i class="fas fa-plus-circle me-2"></i>Add Your First Product
              </a>
            </div>';
    } else {
      echo '<div class="table-responsive">
              <table class="table table-hover" id="productsTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th width="180">Actions</th>
                  </tr>
                </thead>
                <tbody>';
      
      while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $categoryClass = 'category-other';
        switch(strtolower($row['CATEGORY'])) {
          case 'coffee': $categoryClass = 'category-coffee'; break;
          case 'pastry': $categoryClass = 'category-pastry'; break;
          case 'dessert': $categoryClass = 'category-dessert'; break;
          case 'tea': $categoryClass = 'category-tea'; break;
        }
        
        echo '<tr>
                <td data-label="ID"><strong>' . $row['PRODUCTID'] . '</strong></td>
                <td data-label="Product">
                  <strong>' . htmlspecialchars($row['PRODUCTNAME']) . '</strong>
                </td>
                <td data-label="Category">
                  <span class="category-badge ' . $categoryClass . '">
                    ' . htmlspecialchars($row['CATEGORY']) . '
                  </span>
                </td>
                <td data-label="Description">
                  <small style="color: #666;">
                    ' . htmlspecialchars($row['DESCRIPTION'] ?? 'No description') . '
                  </small>
                </td>
                <td data-label="Price">
                  <span class="price-tag">₱' . number_format($row['PRICE'], 2) . '</span>
                </td>
                <td data-label="Image">
                  <img src="' . htmlspecialchars($row['IMAGEPATH'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800') . '" 
                       class="product-image" 
                       alt="' . htmlspecialchars($row['PRODUCTNAME']) . '">
                </td>
                <td data-label="Actions">
                  <div class="action-buttons">
                    <a href="edit_product.php?id=' . $row['PRODUCTID'] . '" class="btn btn-edit">
                      <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <a href="delete_product.php?id=' . $row['PRODUCTID'] . '" 
                       class="btn btn-delete" 
                       onclick="return confirmDelete(\'' . htmlspecialchars(addslashes($row['PRODUCTNAME'])) . '\')">
                      <i class="fas fa-trash me-1"></i>Delete
                    </a>
                  </div>
                </td>
              </tr>';
      }
      
      echo '</tbody>
            </table>
          </div>';
    }
    ?>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Product search functionality
    const searchInput = document.getElementById('productSearch');
    const productsTable = document.getElementById('productsTable');
    
    if (searchInput && productsTable) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = productsTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
      });
    }
    
    // Enhanced delete confirmation
    window.confirmDelete = function(productName) {
      return confirm(`Are you sure you want to delete "${productName}"?\n\nThis action cannot be undone and will remove the product from your menu permanently.`);
    };
    
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
      row.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(5px)';
      });
      
      row.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
      });
    });
    
    // Add animation to product images
    const productImages = document.querySelectorAll('.product-image');
    productImages.forEach(img => {
      img.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1) rotate(2deg)';
      });
      
      img.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1) rotate(0deg)';
      });
    });
    
    // Add keyboard shortcut for adding product (Ctrl + N)
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const addBtn = document.querySelector('.btn-add-product');
        if (addBtn) addBtn.click();
      }
      
      // Focus search with Ctrl + /
      if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault();
        if (searchInput) searchInput.focus();
      }
    });
    
    // Show search hint
    if (searchInput) {
      searchInput.addEventListener('focus', function() {
        this.setAttribute('title', 'Press Ctrl + / to focus here');
      });
    }
    
    // Add fade animation to content
    const contentElements = document.querySelectorAll('.page-header, .products-table-container');
    contentElements.forEach((element, index) => {
      setTimeout(() => {
        element.style.animation = 'fadeIn 0.8s ease forwards';
        element.style.opacity = '0';
      }, index * 200);
    });
    
    // Add hover effect to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px) scale(1.02)';
      });
      
      card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
      });
    });
  });
</script>
</body>
</html>