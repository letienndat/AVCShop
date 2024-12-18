<?php
session_start();
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $status = isset($_GET['status']) ? intval($_GET['status']) : null;
    $searchId = isset($_GET['searchId']) ? $_GET['searchId'] : null;

    // Truy vấn kết hợp bảng user_order, products và thumbnails
    $query = "SELECT 
            user_order.id AS order_id,
            user_order.username,
            user_order.product_id,
            user_order.quantity,
            user_order.price,
            user_order.total_price,
            user_order.status,
            user_order.time_create,
            products.title AS product_name,
            thumbnails.path_image AS product_image,
            user_order.name_contact,
            user_order.phone_number,
            user_order.location,
            user_order.time_confirm,
            user_order.time_shipping,
            user_order.time_done,
            user_order.time_cancel,
            user_order.confirm_by,
            user_order.cancel_by
            FROM user_order
            INNER JOIN products ON user_order.product_id = products.id
            INNER JOIN thumbnails ON products.id = thumbnails.product_id";

    $params = [];

    // Nếu role là 1, không cần kiểm tra username
    if ($role !== 1) {
        $query .= " WHERE user_order.username = ?";
        $params[] = $username_local;
    }

    if ($status !== null) {
        $query .= " AND user_order.status = ?";
        $params[] = $status;
    }

    if ($searchId !== null) {
        $query .= " AND user_order.id = ?";
        $params[] = $searchId;
    }

    $query .= " ORDER BY user_order.time_create DESC";

    // Thực thi truy vấn
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($orders) === 0) {
        echo "<p class='notify-empty-list-order'>Không có đơn hàng phù hợp.</p>";
    } else {
        foreach ($orders as $order) {
            echo "
            <div class='order-item'>
                <div class='item-top'>
                    <a href='/AVCShop/src/detail.php?product_id={$order['product_id']}'>
                        <img src='{$order['product_image']}' alt='{$order['product_name']}'>
                    </a>
                    <div class='details'>
                        <a href='/AVCShop/src/detail.php?product_id={$order['product_id']}'><h3>{$order['product_name']}</h3></a>";
                        if ($role === 1) {
                            echo "<p><strong>Tạo bởi:</strong> {$order['username']}</p>";
                        }
                        echo "
                        <p><strong>ID sản phẩm:</strong> {$order['product_id']}</p>
                        <p><strong>ID đơn hàng:</strong> {$order['order_id']}</p>
                        <p><strong>Số lượng:</strong> {$order['quantity']}</p>
                        <p><strong>Giá:</strong> " . number_format($order['price'], 0, ',', '.') . " đ</p>
                        <p><strong>Tổng:</strong> " . number_format($order['total_price'], 0, ',', '.') . " đ</p>
                    </div>
                    <div class='actions'>
                        <button class='btn-detail' onclick='viewOrderDetail(event, \"{$order['order_id']}\")'>Xem chi tiết</button>";
                        if ($order['status'] === 0 && $role === 1) {
                            echo "<button class='btn-confirm' onclick='confirmOrder(\"{$order['order_id']}\")'>Xác nhận</button>";
                        }
                        if ($order['status'] === 1 && $role === 1) {
                            echo "<button class='btn-shipping' onclick='startShipping(\"{$order['order_id']}\")'>Giao hàng</button>";
                        }
                        if ($order['status'] === 2) {
                            echo "<button class='btn-received' onclick='confirmReceived(\"{$order['order_id']}\")'>Đã nhận hàng</button>";
                        }
                        if ($order['status'] !== 4 && $order['status'] !== 3) {
                            if ($role === 1) {
                                echo "<button class='btn-cancel' onclick='cancelOrder(event, \"{$order['order_id']}\")'>Huỷ đơn hàng</button>";
                            } else {
                                if ($order['status'] === 0) {
                                    echo "<button class='btn-cancel' onclick='cancelOrder(event, \"{$order['order_id']}\")'>Huỷ đơn hàng</button>";
                                } 
                            }
                        }
                    echo "
                    </div>
                </div>
                <div class='order-detail' id='order-detail-{$order['order_id']}' style='display: none;'>
                    <p><strong>Tên liên hệ:</strong> {$order['name_contact']}</p>
                    <p><strong>Số điện thoại:</strong> {$order['phone_number']}</p>
                    <p><strong>Địa chỉ:</strong> {$order['location']}</p>
                    <p><strong>Trạng thái:</strong> <span class='status-{$order['status']}'>" . getOrderStatus($order['status']) . "</span></p>";
                    if ($order['status'] === 4) {
                        echo "<p><strong>Huỷ bởi:</strong> {$order['cancel_by']}</p>";
                    }
                    if ($order['status'] === 1) {
                        echo "<p><strong>Xác nhận bởi:</strong> {$order['confirm_by']}</p>";
                    }
                    echo "
                    <p><strong>Thời gian tạo:</strong> {$order['time_create']}</p>";

            // Hiển thị thời gian phù hợp theo trạng thái
            if ($order['status'] == 1) {
                echo "<p><strong>Thời gian xác nhận:</strong> {$order['time_confirm']}</p>";
            } elseif ($order['status'] == 2) {
                echo "<p><strong>Thời gian xác nhận:</strong> {$order['time_confirm']}</p>";
                echo "<p><strong>Thời gian giao:</strong> {$order['time_shipping']}</p>";
            } elseif ($order['status'] == 3) {
                echo "<p><strong>Thời gian xác nhận:</strong> {$order['time_confirm']}</p>";
                echo "<p><strong>Thời gian giao:</strong> {$order['time_shipping']}</p>";
                echo "<p><strong>Thời gian nhận:</strong> {$order['time_done']}</p>";
            } elseif ($order['status'] == 4) {
                echo "<p><strong>Thời gian xác nhận:</strong> {$order['time_confirm']}</p>";
                echo "<p><strong>Thời gian giao:</strong> {$order['time_shipping']}</p>";
                echo "<p><strong>Thời gian huỷ:</strong> {$order['time_cancel']}</p>";
            }

            echo "</div>
            </div>
            ";
        }
    }
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Hàm để trả về trạng thái theo mã số
function getOrderStatus($status) {
    switch ($status) {
        case 0:
            return "Chờ xác nhận";
        case 1:
            return "Đã xác nhận";
        case 2:
            return "Đang giao";
        case 3:
            return "Đã nhận";
        case 4:
            return "Đã huỷ";
        default:
            return "Không xác định";
    }
}
?>