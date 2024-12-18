<?php
session_start();
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['order_id'];

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Kiểm tra quyền (Role)
        if ($role === 1) {
            // Role = 1 (Admin) xác nhận vô điều kiện
            $stmt = $conn->prepare("UPDATE user_order SET status = 3, time_done = NOW() WHERE id = ? AND status = 2");
            $stmt->execute([$orderId]);
        } else {
            // Role khác Admin: kiểm tra $username_local
            $stmt = $conn->prepare("UPDATE user_order 
                                    SET status = 3, time_done = NOW() 
                                    WHERE id = ? AND username = ? AND status = 2");
            $stmt->execute([$orderId, $username_local]);
        }

        // Xử lý kết quả
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 2, 'message' => 'Trạng thái đơn hàng đã được chuyển thành "Đã nhận hàng".']);
        } else {
            echo json_encode(['status' => 1, 'message' => 'Không thể cập nhật trạng thái. Vui lòng kiểm tra điều kiện.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 0, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}
?>
