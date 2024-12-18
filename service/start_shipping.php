<?php
session_start();
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($role === 1) {
        $data = json_decode(file_get_contents('php://input'), true);
        $orderId = $data['order_id'];
    
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
            // Cập nhật trạng thái thành "Đang giao hàng"
            $stmt = $conn->prepare("UPDATE user_order SET status = 2, time_shipping = NOW() WHERE id = ? AND status = 1");
            $stmt->execute([$orderId]);
    
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 2, 'message' => 'Trạng thái đã cập nhật thành đang giao hàng']);
            } else {
                echo json_encode(['status' => 1, 'message' => 'Không thể cập nhật trạng thái.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 0, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 0, 'message' => 'Bạn không có quyền truy cập!']);
    }
}
?>
