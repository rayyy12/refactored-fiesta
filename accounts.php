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

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Account Management • Forresto</title>
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
  
  /* Main Container */
  .management-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
  }
  
  /* Page Header */
  .page-header {
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

  .page-header::before {
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
  
  .page-title {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 2.8rem;
    color: var(--evening-twilight);
    margin-bottom: 0.5rem;
    line-height: 1.2;
  }
  
  .page-subtitle {
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-size: 1.2rem;
    color: #666;
    line-height: 1.6;
    max-width: 800px;
    margin-bottom: 2rem;
  }
  
  /* Search Box */
  .search-container {
    position: relative;
    max-width: 500px;
    margin-bottom: 2rem;
  }
  
  .search-input {
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid var(--afternoon-green);
    border-radius: 50px;
    color: var(--text-dark);
    padding: 1rem 1.5rem 1rem 3.5rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-size: 1rem;
    transition: all 0.3s ease;
    width: 100%;
  }
  
  .search-input:focus {
    background: white;
    border-color: var(--evening-amber);
    box-shadow: 0 0 0 3px rgba(255, 183, 77, 0.2);
  }
  
  .search-icon {
    position: absolute;
    left: 1.2rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--afternoon-green);
  }
  
  /* Buttons */
  .btn-add-user {
    background: linear-gradient(135deg, var(--afternoon-green), #81C784);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 0.85rem 2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(165, 214, 167, 0.3);
    display: inline-flex;
    align-items: center;
  }
  
  .btn-add-user:hover {
    background: linear-gradient(135deg, var(--evening-amber), #FFA726);
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
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
  
  .btn-admin {
    background: linear-gradient(135deg, var(--admin-purple), #7B1FA2);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 0.85rem 2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(156, 39, 176, 0.3);
    display: inline-flex;
    align-items: center;
  }
  
  .btn-admin:hover {
    background: linear-gradient(135deg, #AB47BC, var(--admin-purple));
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(156, 39, 176, 0.4);
    text-decoration: none;
    color: white;
  }
  
  /* Users Table Container */
  .users-table-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(25px);
    border-radius: 24px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
    margin-bottom: 3rem;
  }

  .users-table-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 80%, rgba(179, 229, 252, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 213, 79, 0.1) 0%, transparent 50%);
    pointer-events: none;
  }
  
  .table {
    margin-bottom: 0;
    color: var(--text-dark);
  }
  
  .table thead th {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--evening-twilight);
    border-bottom: 3px solid var(--afternoon-green);
    padding: 1.2rem 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.95rem;
    background: rgba(255, 255, 255, 0.9);
  }
  
  .table tbody td {
    padding: 1.2rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  }
  
  .table tbody tr {
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.5);
  }
  
  .table tbody tr:hover {
    background: rgba(165, 214, 167, 0.1);
    transform: translateX(5px);
  }
  
  /* Role Badges */
  .role-badge {
    display: inline-block;
    padding: 0.4rem 1.2rem;
    border-radius: 20px;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .role-admin { 
    background: linear-gradient(135deg, var(--admin-purple), #7B1FA2);
    color: white;
  }
  
  .role-staff { 
    background: linear-gradient(135deg, var(--evening-amber), #FF9800);
    color: white;
  }
  
  .role-user { 
    background: linear-gradient(135deg, var(--afternoon-green), #4CAF50);
    color: white;
  }
  
  /* Action Buttons */
  .action-buttons {
    display: flex;
    gap: 0.75rem;
  }
  
  .btn-edit {
    background: linear-gradient(135deg, var(--afternoon-green), #81C784);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.6rem 1.2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
  }
  
  .btn-edit:hover {
    background: linear-gradient(135deg, #81C784, var(--afternoon-green));
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(165, 214, 167, 0.3);
    text-decoration: none;
    color: white;
  }
  
  .btn-delete {
    background: linear-gradient(135deg, var(--danger-red), #D32F2F);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.6rem 1.2rem;
    font-family: 'Montserrat', Helvetica, sans-serif;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
  }
  
  .btn-delete:hover {
    background: linear-gradient(135deg, #D32F2F, var(--danger-red));
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
    text-decoration: none;
    color: white;
  }
  
  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
  }
  
  .empty-state i {
    font-size: 4rem;
    color: var(--afternoon-green);
    margin-bottom: 1.5rem;
    opacity: 0.7;
  }
  
  .empty-state h3 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--evening-twilight);
    margin-bottom: 1rem;
  }
  
  /* User Stats */
  .user-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
  }
  
  .stat-value {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 2.5rem;
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
    .page-header, .users-table-container {
      padding: 2rem;
    }
    
    .page-title {
      font-size: 2.2rem;
    }
    
    .action-buttons {
      flex-direction: column;
    }
  }
  
  @media (max-width: 768px) {
    .management-container {
      padding: 1rem;
    }
    
    .page-header, .users-table-container {
      padding: 1.5rem;
    }
    
    .page-title {
      font-size: 1.8rem;
    }
    
    .table thead {
      display: none;
    }
    
    .table tbody tr {
      display: block;
      margin-bottom: 1rem;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 16px;
      padding: 1rem;
      border: 1px solid rgba(0, 0, 0, 0.08);
    }
    
    .table tbody td {
      display: block;
      text-align: right;
      border: none;
      padding: 0.75rem;
      position: relative;
    }
    
    .table tbody td::before {
      content: attr(data-label);
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--evening-twilight);
    }
    
    .action-buttons {
      flex-direction: row;
      justify-content: flex-end;
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
    <a class="navbar-brand" href="admin_dashboard.php">Forresto Admin</a>
    <div class="d-flex align-items-center gap-3">
      <span class="admin-badge">
        <i class="fas fa-user-tie me-1"></i>
        <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
      </span>
      <a href="index.php" class="btn btn-back">
        <i class="fas fa-arrow-left me-2"></i>
        Back to Café
      </a>
      <a href="logout.php" class="btn-admin">
        <i class="fas fa-sign-out-alt me-2"></i>Logout
      </a>
    </div>
  </div>
</nav>

<div class="management-container">
  <!-- Page Header -->
  <div class="page-header">
    <h1 class="page-title">
      <i class="fas fa-users-cog me-2"></i>
      Account Management
    </h1>
    <p class="page-subtitle">Manage staff accounts, assign roles, and maintain your sanctuary's team.</p>
    
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="userSearch" class="search-input" placeholder="Search users by name or role...">
      </div>
      
      <div class="d-flex gap-2">
        <a href="admin_dashboard.php" class="btn-back">
          <i class="fas fa-chart-line me-2"></i>Dashboard
        </a>
      </div>
    </div>
    
    <!-- User Stats -->
    <?php
    $statsSql = "SELECT ROLE, COUNT(*) as count FROM USERS GROUP BY ROLE";
    $statsStmt = sqlsrv_query($conn, $statsSql);
    

      

    ?>
    

  <!-- Users Table -->
  <div class="users-table-container">
    <?php
    $sql = "SELECT * FROM USERS ORDER BY ROLE, USERNAME";
    $stmt = sqlsrv_query($conn, $sql);
    $hasUsers = sqlsrv_has_rows($stmt);
    
    if (!$hasUsers) {
      echo '<div class="empty-state">
              <i class="fas fa-users"></i>
              <h3>No Users Found</h3>
              <p>Start by adding your first team member!</p>
              <a href="register.php" class="btn-add-user mt-3">
                <i class="fas fa-user-plus me-2"></i>Add First User
              </a>
            </div>';
    } else {
      echo '<div class="table-responsive">
              <table class="table table-hover" id="usersTable">
                <thead>
                  <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th width="180">Actions</th>
                  </tr>
                </thead>
                <tbody>';
      
      while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $roleClass = 'role-user';
        switch(strtolower($row['ROLE'])) {
          case 'admin': $roleClass = 'role-admin'; break;
          case 'staff': $roleClass = 'role-staff'; break;
        }
        
        $createdDate = '';
        if (isset($row['CREATEDATE']) && $row['CREATEDATE'] instanceof DateTime) {
          $createdDate = $row['CREATEDATE']->format('M d, Y');
        }
        
        echo '<tr>
                <td data-label="User ID"><strong>' . $row['USERID'] . '</strong></td>
                <td data-label="Username">
                  <strong>' . htmlspecialchars($row['USERNAME']) . '</strong>
                  ' . ($createdDate ? '<br><small style="color: #666;">Joined: ' . $createdDate . '</small>' : '') . '
                </td>
                <td data-label="Full Name">
                  ' . htmlspecialchars($row['FULLNAME'] ?? 'Not set') . '
                </td>
                <td data-label="Email">
                  ' . htmlspecialchars($row['EMAIL'] ?? 'Not set') . '
                </td>
                <td data-label="Role">
                  <span class="role-badge ' . $roleClass . '">
                    <i class="fas ' . ($row['ROLE'] === 'admin' ? 'fa-crown' : ($row['ROLE'] === 'staff' ? 'fa-user-tie' : 'fa-user')) . ' me-1"></i>
                    ' . htmlspecialchars($row['ROLE']) . '
                  </span>
                </td>
                <td data-label="Actions">
                  <div class="action-buttons">
                    <a href="edit_account.php?id=' . $row['USERID'] . '" class="btn-edit">
                      <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <a href="delete_account.php?id=' . $row['USERID'] . '" 
                       class="btn-delete" 
                       onclick="return confirmDelete(\'' . htmlspecialchars(addslashes($row['USERNAME'])) . '\', \'' . htmlspecialchars($row['ROLE']) . '\')">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize the carousel with autoplay
    const carousel = new bootstrap.Carousel(document.getElementById('backgroundCarousel'), {
      interval: 5000,
      wrap: true,
      pause: false
    });
    
    // Search functionality
    const searchInput = document.getElementById('userSearch');
    const usersTable = document.getElementById('usersTable');
    
    if (searchInput && usersTable) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = usersTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
      });
    }
    
    // Delete confirmation
    window.confirmDelete = function(username, role) {
      if (role.toLowerCase() === 'admin') {
        return confirm(`⚠️ WARNING: Deleting administrator "${username}"!\n\nThis user has full system access. Are you sure you want to proceed?\n\nThis action cannot be undone.`);
      }
      return confirm(`Are you sure you want to delete user "${username}"?\n\nThis action will permanently remove the user account.`);
    };
    
    // Add animations
    const sections = document.querySelectorAll('.page-header, .users-table-container');
    sections.forEach((section, index) => {
      section.style.animation = `fadeIn 0.8s ease ${index * 0.2}s forwards`;
      section.style.opacity = '0';
    });
    
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
  });
</script>
</body>
</html>