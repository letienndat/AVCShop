<?php

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Kiểm tra xem dữ liệu đã tồn tại trong cơ sở dữ liệu hay chưa
    $selectStmt = $conn->prepare("SELECT * FROM user_product_favorites WHERE username = :username AND product_id = :product_id");
    $selectStmt->bindParam(':username', $data['username']);
    $selectStmt->bindParam(':product_id', $data['product_id']);
    $selectStmt->execute();

    if ($selectStmt->rowCount() > 0) {
        // Nếu dữ liệu đã tồn tại, xóa nó đi
        $deleteStmt = $conn->prepare("DELETE FROM user_product_favorites WHERE username = :username AND product_id = :product_id");
        $deleteStmt->bindParam(':username', $data['username']);
        $deleteStmt->bindParam(':product_id', $data['product_id']);
        $deleteStmt->execute();

        $response = array('message' => 'Đã xóa sản phẩm khỏi danh sách yêu thích');
    } else {
        // Nếu dữ liệu chưa tồn tại, thêm dữ liệu mới vào
        $insertStmt = $conn->prepare("INSERT INTO user_product_favorites (username, product_id) VALUES (:username, :product_id)");
        $insertStmt->bindParam(':username', $data['username']);
        $insertStmt->bindParam(':product_id', $data['product_id']);
        $insertStmt->execute();

        $response = array('message' => 'Đã thêm sản phẩm vào danh sách yêu thích');
    }

    echo json_encode($response);
} catch (PDOException $e) {
    echo '<script>console.log("Lỗi: ' . $e->getMessage() . '")</script>';
}
