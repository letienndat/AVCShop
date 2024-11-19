<?php
session_start();
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

if ($username_local === null || $role !== 1) {
    header("Location: " . "/AVCShop/src/home.php");
    exit;
}

// Xử lý form khi người dùng gửi
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $id = $_GET['product_id'];

    try {
        // Kết nối đến CSDL
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Bắt đầu transaction
        $conn->beginTransaction();

        // Lấy đường dẫn ảnh từ bảng thumbnails
        $stmt = $conn->prepare("SELECT path_image FROM thumbnails WHERE product_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $thumbnailPaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Lấy đường dẫn ảnh từ bảng images
        $stmt = $conn->prepare("SELECT path_image FROM images WHERE product_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $imagePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Gộp tất cả đường dẫn ảnh lại
        $allPaths = array_merge($thumbnailPaths, $imagePaths);

        // Xóa sản phẩm trong bảng products
        $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Kiểm tra và xóa file ảnh
        foreach ($allPaths as $path) {
            $path = str_replace("/AVCShop", "..", $path); // Chuyển đổi đường dẫn nếu cần
            if (file_exists($path)) {
                unlink($path); // Xóa file ảnh
            }
        }

        echo '<script>alert("Xóa sản phẩm thành công!")</script>';
        echo '<script>window.location.href = "/AVCShop/src/home.php"</script>';
    } catch (PDOException $e) {
        // Rollback nếu có lỗi
        $conn->rollBack();
        echo '<script>alert("Lỗi xảy ra: ' . $e->getMessage() . '")</script>';
        echo '<script>console.log("Lỗi: ' . $e->getMessage() . '")</script>';
    }
}
