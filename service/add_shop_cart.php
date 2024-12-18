<?php

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $date = new DateTime($data['time']);
    $formattedDate = $date->format('Y-m-d H:i:s');

    // Lấy số lượng hiện có của sản phẩm từ bảng products
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = :product_id");
    $stmt->bindParam(':product_id', $data['product_id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(array('status' => 1, 'message' => 'Sản phẩm không tồn tại'));
        exit;
    }

    // Kiểm tra số lượng trong kho
    // Lấy số lượng sản phẩm hiện có trong giỏ hàng của người dùng
    $stmt = $conn->prepare("SELECT quantity FROM shop_cart WHERE username = :username AND product_id = :product_id");
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':product_id', $data['product_id']);
    $stmt->execute();
    $cartProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    $currentCartQuantity = $cartProduct ? $cartProduct['quantity'] : 0;

    // Kiểm tra tổng số lượng sản phẩm (số lượng trong giỏ + số lượng yêu cầu) có vượt quá số lượng trong kho không
    if (($currentCartQuantity + $data['quantity']) > $product['quantity']) {
        echo json_encode(array('status' => 2, 'message' => 'Số lượng sản phẩm không đủ trong kho'));
        exit;
    }

    // Kiểm tra xem sản phẩm đã tồn tại trong giỏ hàng của người dùng chưa
    if ($cartProduct) {
        // Sản phẩm đã tồn tại trong giỏ hàng, cập nhật số lượng
        $stmt = $conn->prepare("UPDATE shop_cart SET quantity = quantity + :quantity, time = :time WHERE username = :username AND product_id = :product_id");
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':time', $formattedDate);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->execute();
    } else {
        // Sản phẩm chưa tồn tại trong giỏ hàng, thêm mới vào
        $stmt = $conn->prepare("INSERT INTO shop_cart (username, product_id, quantity, time) VALUES (:username, :product_id, :quantity, :time)");
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':time', $formattedDate);
        $stmt->execute();
    }

    echo json_encode(array('status' => 3, 'message' => 'Thêm sản phẩm vào giỏ hàng thành công'));
} catch (PDOException $e) {
    echo json_encode(array('status' => 0, 'message' => $e->getMessage()));
}
