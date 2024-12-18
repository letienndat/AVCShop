<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/shop_cart.css">
    <link rel="stylesheet" href="../public/css/list_order.css">
    <title>Danh sách đơn mua</title>
</head>

<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';
if ($username_local === null) {
    echo '<script>
    alert("Xin lỗi, bạn chưa đăng nhập!")
    window.location.href="/AVCShop/src/sign_in.php"
    </script>';
}
?>

<body>
    <?php
    include '../inc/header.php';
    ?>

    <div class="container-content">
        <div class="container-sub-1">
            <ul class="breadcrumb">
                <li><a href="/AVCShop/src/home.php">Trang chủ<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/list_order.php">Danh sách đơn mua</a></li>
            </ul>
        </div>
        <div class="tabbar">
            <button class="tab-button active" data-status="0">Chờ xác nhận</button>
            <button class="tab-button" data-status="1">Đã xác nhận</button>
            <button class="tab-button" data-status="2">Đang giao</button>
            <button class="tab-button" data-status="3">Đã nhận</button>
            <button class="tab-button" data-status="4">Đã hủy</button>
        </div>
        <div class="search-container">
            <input type="text" id="search-id" placeholder="Nhập ID đơn hàng">
            <button id="search-btn">Tìm kiếm</button>
            <button id="reload-btn">Tải lại</button>
        </div>
        <div id="order-list" class="order-list">
            <!-- Danh sách đơn hàng sẽ được tải ở đây -->
        </div>
    </div>

    <?php
    include '../inc/footer.php';
    ?>
</body>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabButtons = document.querySelectorAll('.tab-button');
        const orderList = document.getElementById('order-list');
        const searchInput = document.getElementById('search-id');
        const searchBtn = document.getElementById('search-btn');
        const reloadBtn = document.getElementById('reload-btn');

        let currentStatus = 0; // Mặc định là "Chờ xác nhận"

        function loadOrders(status = null, searchId = null) {
            let url = '/AVCShop/service/fetch_orders.php';
            if (status !== null) {
                url += `?status=${status}`;
            }
            if (searchId) {
                url += `&searchId=${searchId}`;
            }

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    orderList.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error fetching orders:', error);
                    orderList.innerHTML = '<p>Đã xảy ra lỗi khi tải danh sách đơn hàng.</p>';
                });
        }

        // Khi nhấn vào tab
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                currentStatus = button.getAttribute('data-status');
                loadOrders(currentStatus);
            });
        });

        // Khi nhấn nút tìm kiếm
        searchBtn.addEventListener('click', () => {
            const searchId = searchInput.value.trim();
            if (searchId) {
                loadOrders(currentStatus, searchId);
            } else {
                alert('Vui lòng nhập ID đơn hàng để tìm kiếm.');
            }
        });

        // Khi nhấn nút tải lại
        reloadBtn.addEventListener('click', () => {
            loadOrders(currentStatus);
        });

        // Tải danh sách mặc định khi vào trang
        loadOrders(currentStatus);
    });

    function viewOrderDetail(event, orderId) {
        var detailDiv = document.getElementById('order-detail-' + orderId);

        // Kiểm tra xem phần chi tiết có đang ẩn không
        if (detailDiv.style.display === 'none' || detailDiv.style.display === '') {
            detailDiv.style.display = 'block'; // Hiển thị chi tiết
            event.target.textContent = "Ẩn chi tiết"
        } else {
            detailDiv.style.display = 'none'; // Ẩn chi tiết
            event.target.textContent = "Xem chi tiết"
        }
    }

    function confirmOrder(orderId) {
        if (confirm('Bạn có chắc chắn muốn xác nhận đơn hàng này?')) {
            const formData = new FormData();
            formData.append("orderId", orderId);

            fetch('/AVCShop/service/confirm_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) 
            .then(data => {
                if (data === "success") {
                    alert('Đơn hàng đã được xác nhận');
                    const tabButtons = document.querySelectorAll('.tab-button');
                    tabButtons[1].click()
                } else {
                    alert('Có lỗi xảy ra khi xác nhận đơn hàng');
                }
            })
            .catch(error => {
                console.error('Lỗi xác nhận đơn hàng:', error);
                alert('Có lỗi xảy ra khi xác nhận đơn hàng');
            });
        }
    }

    function startShipping(orderId) {
        fetch('/AVCShop/service/start_shipping.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_id: orderId }),
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.status === 2) {
                alert('Trạng thái đơn hàng đã được chuyển sang "Đang giao hàng".');
                const tabButtons = document.querySelectorAll('.tab-button');
                tabButtons[2].click()
            } else {
                alert(data.message);
            }
        })
        .catch((error) => {
            console.error('Error:', error);
        });
    }

    function confirmReceived(orderId) {
        fetch('/AVCShop/service/received.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_id: orderId }),
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.status === 2) {
                alert('Trạng thái đơn hàng đã chuyển thành "Đã nhận hàng".');
                const tabButtons = document.querySelectorAll('.tab-button');
                tabButtons[3].click()
            } else {
                alert(data.message);
            }
        })
        .catch((error) => {
            console.error('Error:', error);
        });
    }

    function cancelOrder(event, orderId) {
        if (confirm("Bạn có chắc chắn muốn hủy đơn hàng này?")) {
            // Gửi yêu cầu AJAX để hủy đơn hàng
            fetch('/AVCShop/service/cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 2) {
                    alert(data.message);
                    const tabButtons = document.querySelectorAll('.tab-button');
                    tabButtons[4].click()
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert("Có lỗi xảy ra.");
            });
        }
    }


</script>

<script src="/AVCShop/public/js/pin_header.js"></script>

</html>