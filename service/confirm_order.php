<?php
session_start();
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

try {
    if ($role === 1) {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        // Lấy orderId từ yêu cầu POST
        $orderId = isset($_POST['orderId']) ? $_POST['orderId'] : null;
    
        if ($orderId) {
            // Cập nhật trạng thái đơn hàng và thời gian xác nhận
            $query = "UPDATE user_order 
                      SET status = 1, time_confirm = NOW(), confirm_by = ?
                      WHERE id = ? AND status = 0"; // Chỉ cập nhật nếu trạng thái là 'chờ xác nhận'
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$username_local, $orderId]);
    
            // Kiểm tra nếu có bản ghi bị ảnh hưởng
            if ($stmt->rowCount() > 0) {
                echo "success";
            } else {
                echo "fail";
            }
        } else {
            echo "fail";
        }
    } else {
        echo "fail";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
