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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM USERS WHERE USERNAME = '$username' AND PASSWORDHASH = '$password'";
    $result = sqlsrv_query($conn, $sql);

    if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {

        $_SESSION['user'] = [
            'id' => $row['USERID'],
            'username' => $row['USERNAME'],
            'role' => $row['ROLE']
        ];
        header("Location: index.php");
    } else {
        $error = "Invalid username or password.";
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login • Forresto</title>
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

        /* Login Container */
        .login-container {
            min-height: calc(100vh - 76px);
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .login-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .login-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            min-height: 600px;
        }
        
        /* Left Side: Brand Section */
        .brand-section {
            padding: 3rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-section::before {
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
        
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 4.5rem;
            color: var(--evening-twilight);
            margin-bottom: 0.5rem;
            line-height: 1;
            position: relative;
        }
        

        
        .login-subtitle {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--evening-amber);
            text-align: center;
            font-style: italic;
            margin-bottom: 2rem;
        }
        
        .login-divider {
            width: 150px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--afternoon-green), transparent);
            margin: 2rem auto;
        }
        
        .login-description {
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-size: 1.1rem;
            color: #555;
            line-height: 1.8;
            text-align: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }
        
        .atmosphere-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }
        
        .atmosphere-list li {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--evening-twilight);
            margin-bottom: 1rem;
            padding-left: 2rem;
            position: relative;
        }
        
        .atmosphere-list li::before {
            content: '✨';
            position: absolute;
            left: 0;
            top: 0;
        }
        
        /* Right Side: Form Section */
        .form-section {
            padding: 3rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 80% 20%, rgba(255, 213, 79, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 20% 80%, rgba(179, 229, 252, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .form-content {
            position: relative;
            z-index: 1;
        }
        
        .form-title {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 2.5rem;
            color: var(--evening-twilight);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .form-subtitle {
            font-family: 'Montserrat', Helvetica, sans-serif;
            color: #666;
            text-align: center;
            margin-bottom: 2.5rem;
            font-size: 1.1rem;
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
        
        .form-control::placeholder {
            color: rgba(85, 85, 85, 0.6);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--afternoon-green);
            cursor: pointer;
            z-index: 10;
            padding: 0.5rem;
        }
        
        .toggle-password:hover {
            color: var(--evening-amber);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--afternoon-green), #81C784);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
            box-shadow: 0 5px 20px rgba(165, 214, 167, 0.3);
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, var(--evening-amber), #FFA726);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
        }
        
        .error-alert {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), transparent);
            border: 2px solid rgba(220, 53, 69, 0.3);
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            color: #dc3545;
            font-weight: 600;
            font-family: 'Montserrat', Helvetica, sans-serif;
            backdrop-filter: blur(5px);
        }
        
        .register-link {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(165, 214, 167, 0.3);
        }
        
        .register-link p {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .register-link a {
            color: var(--evening-amber);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Montserrat', Helvetica, sans-serif;
            display: inline-flex;
            align-items: center;
        }
        
        .register-link a:hover {
            color: var(--evening-twilight);
            text-decoration: underline;
        }
        
        .btn-back {
            border: 2px solid var(--afternoon-green);
            color: var(--afternoon-green);
            border-radius: 50px;
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
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Hide carousel controls */
        .background-carousel .carousel-control-prev,
        .background-carousel .carousel-control-next,
        .background-carousel .carousel-indicators {
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .login-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
                min-height: auto;
            }
            
            .brand-section, .form-section {
                padding: 2rem;
            }
            
            .login-logo {
                font-size: 3.5rem;
            }
            
            .login-logo::before {
                top: -2rem;
                font-size: 2.5rem;
            }
            
            .form-title {
                font-size: 2.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .login-wrapper {
                padding: 1rem;
            }
            
            .login-container {
                padding: 1rem 0;
            }
            
            .brand-section, .form-section {
                padding: 1.5rem;
            }
            
            .login-logo {
                font-size: 3rem;
            }
            
            .form-title {
                font-size: 2rem;
            }
            
            .login-subtitle {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-logo {
                font-size: 2.5rem;
            }
            
            .login-logo::before {
                top: -1.5rem;
                font-size: 2rem;
            }
            
            .form-title {
                font-size: 1.8rem;
            }
            
            .login-subtitle {
                font-size: 1.3rem;
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
            <a class="navbar-brand" href="index.php">Forresto</a>
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Café
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="login-container">
        <div class="login-wrapper">
            <div class="login-grid">
                <!-- Left Side: Brand Section -->
                <div class="brand-section">
                    <div class="logo-container">
                        <div class="login-logo">Forresto</div>
                        <div class="login-subtitle">A Sanctuary in the City</div>
                    </div>
                    
                    <div class="login-divider"></div>
                    
                    <p class="login-description">
                        The atmosphere shifts beautifully throughout the day:
                    </p>
                    
                    <ul class="atmosphere-list">
                        <li>Bright Mornings</li>
                        <li>Relaxed Afternoons</li>
                        <li>Glowing Evenings</li>
                    </ul>
                    
                    <p class="login-description">
                        It's more than a place to eat or drink, it's a sanctuary. 
                        A rare haven in the heart of the city where people can take a breath, 
                        reset, and rediscover balance.
                    </p>
                </div>
                
                <!-- Right Side: Form Section -->
                <div class="form-section">
                    <div class="form-content">
                        <h2 class="form-title">Welcome Back</h2>
                        <p class="form-subtitle">
                            Sign in to continue your journey to calm
                        </p>
                        
                        <?php if(isset($error)): ?>
                            <div class="error-alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" id="loginForm">
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>
                                    Username
                                </label>
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       class="form-control" 
                                       required 
                                       placeholder="Enter your username"
                                       autocomplete="username">
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-key me-2"></i>
                                    Password
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" 
                                           name="password" 
                                           id="password" 
                                           class="form-control" 
                                           required 
                                           placeholder="Enter your password"
                                           autocomplete="current-password">
                                    <button type="button" class="toggle-password" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login to Your Account
                            </button>
                        </form>
                        
                        <div class="register-link">
                            <p>New to Forresto?</p>
                            <a href="register.php">
                                <i class="fas fa-user-plus me-2"></i>
                                Create an Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>
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
            
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // Form validation
            const loginForm = document.getElementById('loginForm');
            loginForm.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();
                
                if (username.length === 0 || password.length === 0) {
                    e.preventDefault();
                    
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-alert';
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Please fill in all fields.';
                    
                    const existingError = loginForm.querySelector('.error-alert');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    loginForm.insertBefore(errorDiv, loginForm.firstChild);
                    
                    setTimeout(() => {
                        if (errorDiv.parentNode) {
                            errorDiv.remove();
                        }
                    }, 3000);
                    
                    return;
                }
                
                const submitBtn = loginForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
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
            
            // Auto-focus on username
            document.getElementById('username').focus();
            
            // Add fade-in animations
            const brandSection = document.querySelector('.brand-section');
            const formSection = document.querySelector('.form-section');
            
            brandSection.style.animation = 'fadeIn 0.8s ease forwards';
            formSection.style.animation = 'fadeIn 0.8s ease 0.2s forwards';
            
            brandSection.style.opacity = '0';
            formSection.style.opacity = '0';
        });
    </script>
</body>
</html>