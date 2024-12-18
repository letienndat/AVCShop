<?php

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lấy số lượng hiện có của sản phẩm từ bảng products
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = :product_id");
    $stmt->bindParam(':product_id', $data['product_id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(array('status' => 1, 'message' => 'Sản phẩm không tồn tại'));
        exit;
    }

    // Kiểm tra xem có phải là thao tác xóa (del)
    if ($data['operator'] === 'del') {
        $stmt = $conn->prepare("DELETE FROM shop_cart WHERE username = :username AND product_id = :product_id");
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->execute();

        $response = array("status" => 0);
    } else {
        // Nếu là thao tác sửa số lượng (+ hoặc -)
        $quantity = ($data['operator'] === '-') ? -1 : 1;

        // Lấy số lượng hiện tại của sản phẩm trong giỏ hàng
        $stmt = $conn->prepare("SELECT quantity FROM shop_cart WHERE username = :username AND product_id = :product_id");
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->execute();
        $cartProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cartProduct) {
            $currentCartQuantity = $cartProduct['quantity'];

            // Kiểm tra số lượng sau khi thay đổi có vượt quá số lượng trong kho không
            $newQuantity = $currentCartQuantity + $quantity;

            if ($newQuantity > $product['quantity']) {
                echo json_encode(array('status' => 2, 'message' => 'Số lượng sản phẩm trong kho không đủ'));
                exit;
            }

            // Cập nhật lại số lượng trong giỏ hàng
            $stmt = $conn->prepare("UPDATE shop_cart SET quantity = quantity + :quantity WHERE username = :username AND product_id = :product_id");
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->execute();

            $response = array("status" => 1);
        } else {
            $response = array("status" => 0, "message" => "Sản phẩm không tồn tại trong giỏ hàng");
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (PDOException $e) {
    echo '<script>console.log("Lỗi: ' . $e->getMessage() . '")</script>';
}
