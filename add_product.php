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

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);
    $destination = "Uploads/";

    // Basic validation
    if (empty($name) || empty($category) || $price <= 0 || !isset($_FILES["product_image"])) {
        $message = "Please fill in all required fields.";
    } else {
        $filename = time() . "_" . basename($_FILES["product_image"]["name"]);
        $targetfilepath = $destination . $filename;

        // Check file type
        $allowtypes = ['jpg','jpeg','png','gif','webp'];
        $filetype = strtolower(pathinfo($targetfilepath, PATHINFO_EXTENSION));

        if (!in_array($filetype, $allowtypes)) {
            $message = "Invalid file type. Use JPG, PNG, GIF, or WebP.";
        } elseif ($_FILES["product_image"]["size"] > 5000000) {
            $message = "File too large (max 5MB).";
        } else {
            // Create directory if needed
            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }

            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetfilepath)) {
                $sql = "INSERT INTO MENU (PRODUCTNAME, CATEGORY, PRICE, DESCRIPTION, IMAGEPATH)
                        VALUES (?, ?, ?, ?, ?)";
                $params = array($name, $category, $price, $description, $targetfilepath);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt) {
                    $message = "Product added successfully!";
                    $_POST = array(); // Clear form
                } else {
                    $message = "Error adding product.";
                }
            } else {
                $message = "Image upload failed.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Product • Forresto</title>
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
            max-width: 800px;
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
            margin-bottom: 25px;
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
        
        .form-control, .form-select, textarea {
            border: 2px solid var(--afternoon-green);
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus, textarea:focus {
            border-color: var(--evening-amber);
            box-shadow: 0 0 0 4px rgba(255, 183, 77, 0.15);
        }
        
        .image-upload {
            border: 2px dashed var(--afternoon-green);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: rgba(165, 214, 167, 0.05);
            cursor: pointer;
        }
        
        .image-upload:hover {
            background: rgba(165, 214, 167, 0.1);
        }
        
        .btn-save {
            background: linear-gradient(135deg, var(--afternoon-green), #81C784);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-save:hover {
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
        }
        
        .btn-back:hover {
            background: var(--evening-amber);
            color: white;
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
                <a href="productlist.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <div class="card">
            <h1 class="page-title">
                <i class="fas fa-plus-circle me-2"></i>
                Add New Product
            </h1>
            
            <?php if($message): ?>
                <div class="alert-box <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>">
                    <i class="fas <?php echo strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-utensils me-2"></i>
                            Product Name
                        </label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-tags me-2"></i>
                            Category
                        </label>
                        <input type="text" name="category" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag me-2"></i>
                            Price
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="price" class="form-control" 
                                   step="0.01" min="0.01" required
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left me-2"></i>
                            Description
                        </label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Optional product description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-image me-2"></i>
                        Product Image
                    </label>
                    <div class="image-upload" onclick="document.getElementById('productImage').click()">
                        <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color: var(--afternoon-green);"></i>
                        <p class="mb-1">Click to upload image</p>
                        <small class="text-muted">JPG, PNG, GIF, WebP • Max 5MB</small>
                        <input type="file" name="product_image" id="productImage" 
                               accept="image/*" required class="d-none"
                               onchange="showPreview(this)">
                    </div>
                    <div id="previewContainer" class="mt-3 text-center" style="display: none;">
                        <img id="imagePreview" class="img-thumbnail" style="max-height: 200px;">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save me-2"></i>
                    Add Product
                </button>
            </form>
        </div>
    </div>

    <script>
        function showPreview(input) {
            const preview = document.getElementById('imagePreview');
            const container = document.getElementById('previewContainer');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
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
    </script>
</body>
</html>