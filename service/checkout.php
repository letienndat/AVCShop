<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selectedProductIDs = json_decode($_POST['selectedProductIDs'], true);

    if (!empty($selectedProductIDs)) {
        $username_ = isset($_POST['username']) ? $_POST['username'] : "";
        $nameContact = isset($_POST['name_contact']) ? $_POST['name_contact'] : "";
        $phoneNumber = isset($_POST['phone_number']) ? $_POST['phone_number'] : "";
        $location = isset($_POST['location']) ? $_POST['location'] : "";

        $root = $_SERVER['DOCUMENT_ROOT'];
        require_once $root . '/AVCShop/database/info_connect_db.php';

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $successfulOrders = [];
            $failedOrders = [];
            $totalPrice = 0;

            foreach ($selectedProductIDs as $productId) {
                // Kiểm tra số lượng trong kho và trong giỏ hàng
                $stmt = $conn->prepare("
                    SELECT 
                        products.quantity AS stock_quantity, 
                        shop_cart.quantity AS cart_quantity, 
                        products.price AS product_price 
                    FROM products 
                    INNER JOIN shop_cart ON products.id = shop_cart.product_id 
                    WHERE shop_cart.product_id = ? 
                    AND shop_cart.username = ?
                ");
                $stmt->execute([$productId, $username_]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $stockQuantity = $result['stock_quantity'];
                    $cartQuantity = $result['cart_quantity'];
                    $productPrice = $result['product_price'];

                    if ($cartQuantity <= $stockQuantity) {
                        // Đơn hàng thành công
                        $successfulOrders[] = $productId;
                        $totalPrice += $cartQuantity * $productPrice;

                        // Trừ số lượng sản phẩm trong kho
                        $updateStockStmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                        $updateStockStmt->execute([$cartQuantity, $productId]);

                        // Xoá sản phẩm khỏi giỏ hàng
                        $deleteCartStmt = $conn->prepare("DELETE FROM shop_cart WHERE product_id = ? AND username = ?");
                        $deleteCartStmt->execute([$productId, $username_]);

                        // Tạo đơn hàng mới trong bảng user_order
                        $orderId = strtoupper(uniqid());
                        $timeCreate = date("Y-m-d H:i:s");
                        $insertOrderStmt = $conn->prepare("
                            INSERT INTO user_order (
                                id, username, product_id, name_contact, phone_number, location, 
                                price, quantity, total_price, status, time_create
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?
                            )
                        ");
                        $insertOrderStmt->execute([
                            $orderId, $username_, $productId, $nameContact, $phoneNumber, $location,
                            $productPrice, $cartQuantity, $cartQuantity * $productPrice, $timeCreate
                        ]);
                    } else {
                        // Đơn hàng thất bại do không đủ số lượng
                        $failedOrders[] = $productId;
                    }
                }
            }

            // Xây dựng thông báo kết quả
            $successCount = count($successfulOrders);
            $failCount = count($failedOrders);
            $message = "$successCount đơn hàng đã đặt thành công.";
            if ($failCount > 0) {
                $message .= " $failCount đơn hàng không thành công do không đủ số lượng trong kho.";
            }

            $response = [
                "message" => $message,
                "status" => ($failCount === 0) ? 2 : 1,
                "successfulOrders" => $successfulOrders,
                "failedOrders" => $failedOrders,
                "totalPrice" => $totalPrice,
            ];
        } catch (PDOException $e) {
            $response = ["message" => "Error! " . $e->getMessage(), "status" => 0];
        }
    } else {
        $response = ["message" => "Vui lòng chọn sản phẩm trước khi thực hiện thanh toán!", "status" => 1];
    }

    // Trả về phản hồi dạng JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
