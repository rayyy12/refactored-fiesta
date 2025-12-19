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

$id = $_GET['id'];
$message = "";

$sql = "SELECT * FROM STRBARAKSMENU WHERE PRODUCTID = '$id'";
$stmt = sqlsrv_query($conn, $sql);
$product = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$product) { die("Product not found."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    $newImage = $product['IMAGEPATH'];
    if (!empty($_FILES['product_image']['name'])) {

        $destination = "Uploads/";
        $filename = basename($_FILES["product_image"]["name"]);
        $targetfilepath = $destination . time() . "_" . $filename;

        $allowtypes = ['jpg', 'jpeg', 'png', 'gif'];
        $filetype = pathinfo($targetfilepath, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowtypes)) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetfilepath)) {

                $newImage = $targetfilepath;

            } else {
                $message = "Image upload failed.";
            }
        }
    }

    $sql2 = "UPDATE STRBARAKSMENU
             SET PRODUCTNAME='$name',
                 CATEGORY='$category',
                 PRICE='$price',
                 DESCRIPTION='$description',
                 IMAGEPATH='$newImage'
             WHERE PRODUCTID='$id'";

    $stmt2 = sqlsrv_query($conn, $sql2);

    if ($stmt2) {
        $message = "Product updated successfully!";
    } else {
        $message = "Database update error:<br>" . print_r(sqlsrv_errors(), true);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Product</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body style='background:#0b1e3f; color:#f2e4b7;'>

<div class='container p-4'>
<h2>Edit Product</h2>

<?php if($message): ?>
    <div class='alert alert-warning'><?= $message ?></div>
<?php endif; ?>

<form method='POST' enctype='multipart/form-data'>

    <label>Product Name</label>
    <input class='form-control' type='text' name='name' value="<?= $product['PRODUCTNAME'] ?>" required>

    <label>Category</label>
    <input class='form-control' type='text' name='category' value="<?= $product['CATEGORY'] ?>" required>

    <label>Price</label>
    <input class='form-control' type='number' step='0.01' name='price' value="<?= $product['PRICE'] ?>" required>

    <label>Description</label>
    <textarea class='form-control' name='description'><?= $product['DESCRIPTION'] ?></textarea>

    <label>Current Image</label><br>
    <img src="<?= $product['IMAGEPATH'] ?>" width="200" class="mb-3">

    <label>Replace Image</label>
    <input class='form-control' type='file' name='product_image' accept='image/*'>

    <button class='btn btn-lumiere mt-3'>Save Changes</button>

</form>

<a href='admin_dashboard.php' class='btn btn-outline-light mt-3'>Back</a>

</div>
</body>
</html>
