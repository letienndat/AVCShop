<?php
session_start();
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

// Xử lý hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['order_id'];

    // Kiểm tra nếu role là user (role === 0)
    if ($role === 0) {
        // Kiểm tra xem đơn hàng có phải do user này tạo hay không
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Lấy thông tin đơn hàng và kiểm tra chủ sở hữu
            $stmt = $conn->prepare("SELECT username, product_id, quantity FROM user_order WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Kiểm tra xem người dùng có phải là chủ sở hữu đơn hàng không
                if ($order['username'] === $username_local) {
                    // Nếu là chủ sở hữu thì cho phép huỷ đơn hàng
                    // Cập nhật status thành 4 (hủy đơn) và thời gian hủy
                    $stmt = $conn->prepare("UPDATE user_order SET status = 4, time_cancel = NOW(), cancel_by = ? WHERE id = ?");
                    $stmt->execute([$username_local, $orderId]);

                    // Trả lại số lượng sản phẩm vào kho
                    $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                    $stmt->execute([$order['quantity'], $order['product_id']]);

                    // Trả kết quả thành công
                    echo json_encode(['status' => 2, 'message' => 'Đơn hàng đã được hủy']);
                } else {
                    // Nếu không phải chủ sở hữu, không cho phép huỷ đơn hàng
                    echo json_encode(['status' => 3, 'message' => 'Bạn không có quyền huỷ đơn hàng này']);
                }
            } else {
                // Trường hợp không tìm thấy đơn hàng
                echo json_encode(['status' => 1, 'message' => 'Đơn hàng không tồn tại']);
            }
        } catch (PDOException $e) {
            // Trả lỗi nếu có lỗi trong quá trình xử lý
            echo json_encode(['status' => 0, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    } 
    // Nếu role là admin (role === 1), không cần kiểm tra username
    else if ($role === 1) {
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Lấy thông tin đơn hàng để cập nhật
            $stmt = $conn->prepare("SELECT product_id, quantity FROM user_order WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Cập nhật status thành 4 (hủy đơn) và thời gian hủy
                $stmt = $conn->prepare("UPDATE user_order SET status = 4, time_cancel = NOW(), cancel_by = ? WHERE id = ?");
                $stmt->execute([$username_local, $orderId]);

                // Trả lại số lượng sản phẩm vào kho
                $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $stmt->execute([$order['quantity'], $order['product_id']]);

                // Trả kết quả thành công
                echo json_encode(['status' => 2, 'message' => 'Đơn hàng đã được hủy']);
            } else {
                // Trường hợp không tìm thấy đơn hàng
                echo json_encode(['status' => 1, 'message' => 'Đơn hàng không tồn tại']);
            }
        } catch (PDOException $e) {
            // Trả lỗi nếu có lỗi trong quá trình xử lý
            echo json_encode(['status' => 0, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }
}
?>
