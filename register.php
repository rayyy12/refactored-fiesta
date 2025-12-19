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

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $fullname = trim($_POST['fullname']);

    if ($username && $password && $role) {
        // Check if username exists
        $checkSql = "SELECT USERNAME FROM USERS WHERE USERNAME = ?";
        $checkParams = array($username);
        $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);

        if (sqlsrv_has_rows($checkStmt)) {
            $message = "Username '$username' already exists.";
        } else {
            // Insert new user
            $insertSql = "INSERT INTO USERS (USERNAME, PASSWORDHASH, ROLE, FULLNAME) VALUES (?, ?, ?, ?)";
            $insertParams = array($username, $password, $role, $fullname);
            $result = sqlsrv_query($conn, $insertSql, $insertParams);

            if ($result) {
                $message = "Account created successfully!";
                $_POST = array();
            } else {
                $message = "Error creating account.";
            }
        }
    } else {
        $message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register • Forresto</title>
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
            display: flex;
            align-items: center;
        }
        
        .container-main {
            max-width: 500px;
            margin: 0 auto;
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
            margin-bottom: 25px;
            text-align: center;
        }
        
        .alert-box {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 2px solid;
        }
        
        .alert-success {
            background: rgba(165, 214, 167, 0.2);
            border-color: var(--afternoon-green);
            color: var(--evening-twilight);
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #dc3545;
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
        
        .btn-register {
            background: linear-gradient(135deg, var(--afternoon-green), #81C784);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(165, 214, 167, 0.3);
        }
        
        .btn-back {
            border: 2px solid var(--evening-amber);
            border-radius: 50px;
            padding: 12px 30px;
            color: var(--evening-amber);
            font-weight: 600;
            margin-top: 15px;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: var(--evening-amber);
            color: white;
            text-decoration: none;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="card">
            <h1 class="page-title">
                <i class="fas fa-user-plus me-2"></i>
                Register
            </h1>
            
            <?php if($message): ?>
                <div class="alert-box <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>">
                    <i class="fas <?php echo strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user me-2"></i>
                        Full Name
                    </label>
                    <input type="text" name="fullname" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user-tag me-2"></i>
                        Username
                    </label>
                    <input type="text" name="username" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-key me-2"></i>
                        Password
                    </label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user-tie me-2"></i>
                        Role
                    </label>
                    <select name="role" class="form-select" required>
                        <option value="">Select Role</option>
                        <option value="staff" <?php echo ($_POST['role'] ?? '') == 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo ($_POST['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus me-2"></i>
                    Create Account
                </button>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        // Simple form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const required = this.querySelectorAll('[required]');
            let valid = true;
            
            required.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    valid = false;
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
        
        // Clear red border on input
        document.querySelectorAll('[required]').forEach(field => {
            field.addEventListener('input', function() {
                this.style.borderColor = '';
            });
        });
    </script>
</body>
</html>