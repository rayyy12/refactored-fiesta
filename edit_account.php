<?php
session_start();
$serverName = "DESKTOP-I9LLCAD\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) die(print_r(sqlsrv_errors(), true));

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$error = null;

$sql = "SELECT * FROM USERS WHERE USERID = $id";
$stmt = sqlsrv_query($conn, $sql);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$user) die("User not found.");

if (isset($_POST['save'])) {
    $username = $_POST['username'];
    $role = $_POST['role'];

    $update = "UPDATE USERS SET USERNAME='$username', ROLE='$role' WHERE USERID=$id";
    $result = sqlsrv_query($conn, $update);

    if ($result) {
        header("Location: accounts.php");
        exit;
    } else {
        $error = "Error updating user.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Account • Forresto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --afternoon-green: #A5D6A7;
            --evening-amber: #FFB74D;
            --evening-twilight: #5D4037;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #FFF8E1 0%, #B3E5FC 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .navbar {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--evening-twilight);
        }
        
        .container-main {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            color: var(--evening-twilight);
            margin-bottom: 10px;
        }
        
        .user-badge {
            display: inline-block;
            background: rgba(165, 214, 167, 0.2);
            border: 2px solid var(--afternoon-green);
            border-radius: 50px;
            padding: 8px 20px;
            margin-bottom: 25px;
            font-weight: 600;
            color: var(--afternoon-green);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--evening-twilight);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid var(--afternoon-green);
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--evening-amber);
            box-shadow: 0 0 0 4px rgba(255, 183, 77, 0.15);
        }
        
        .btn-save {
            background: linear-gradient(135deg, var(--afternoon-green), #81C784);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(165, 214, 167, 0.3);
        }
        
        .btn-cancel {
            border: 2px solid var(--evening-amber);
            border-radius: 50px;
            padding: 12px 30px;
            color: var(--evening-amber);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: var(--evening-amber);
            color: white;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 2px solid #dc3545;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            color: #dc3545;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Forresto Admin</a>
            <div class="d-flex align-items-center gap-3">
                <span style="color: var(--evening-twilight); font-weight: 600;">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
                </span>
                <a href="accounts.php" class="btn btn-cancel">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <div class="card">
            <h1 class="page-title">
                <i class="fas fa-user-edit me-2"></i>
                Edit Account
            </h1>
            
            <div class="user-badge">
                <i class="fas fa-user-tag me-2"></i>
                Editing: <?= htmlspecialchars($user['USERNAME']) ?>
            </div>
            
            <?php if($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-user me-2"></i>
                        Username
                    </label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['USERNAME']) ?>" 
                           class="form-control" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-user-tag me-2"></i>
                        Role
                    </label>
                    <select name="role" class="form-select" required>
                        <option value="staff" <?= $user['ROLE']=='staff'?'selected':'' ?>>
                            <i class="fas fa-user-tie me-2"></i>Staff
                        </option>
                        <option value="admin" <?= $user['ROLE']=='admin'?'selected':'' ?>>
                            <i class="fas fa-crown me-2"></i>Admin
                        </option>
                    </select>
                </div>

                <div class="d-flex gap-3 mt-4">
                    <button type="submit" name="save" class="btn btn-save">
                        <i class="fas fa-save me-2"></i>
                        Save Changes
                    </button>
                    <a href="accounts.php" class="btn btn-cancel">
                        <i class="fas fa-times me-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>