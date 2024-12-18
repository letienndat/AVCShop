<?php
session_start();
error_reporting(E_ALL); // Báo cáo tất cả các loại lỗi
ini_set('display_errors', 1); // Hiển thị lỗi ra màn hình
ini_set('display_startup_errors', 1); // Hiển thị lỗi xảy ra trong quá trình khởi động PHP

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

if ($username_local === null) {
    header("Location: " . "/AVCShop/src/home.php");
    exit;
}

$list_products = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selectedProductIDs = isset($_POST['option']) ? $_POST['option'] : array();

    if (sizeof($selectedProductIDs) > 0) {
        // Kết nối cơ sở dữ liệu
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Chuyển danh sách ID thành chuỗi placeholder
        $placeholders = rtrim(str_repeat('?,', count($selectedProductIDs)), ',');

        $sql = "SELECT 
                    products.*, 
                    shop_cart.quantity AS cart_quantity, 
                    thumbnails.path_image AS thumbnail
                FROM products
                INNER JOIN shop_cart ON products.id = shop_cart.product_id
                LEFT JOIN thumbnails ON products.id = thumbnails.product_id
                WHERE shop_cart.product_id IN ($placeholders)
                ORDER BY shop_cart.time DESC";

        $stmt = $conn->prepare($sql);

        // Bind từng giá trị trong mảng ID
        foreach ($selectedProductIDs as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_STR);
        }

        // Thực thi câu truy vấn
        $stmt->execute();
        $list_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalAmount = 0;
        $priceShip = 0;

        // Kiểm tra và cập nhật số lượng sản phẩm trong giỏ hàng nếu cần thiết
        foreach ($list_products as $product) {
            // Lấy số lượng sản phẩm trong kho và trong giỏ hàng
            $stock_quantity = $product['quantity'];
            $cart_quantity = $product['cart_quantity'];
            $product_price = $product['price'];

            // Nếu số lượng trong giỏ lớn hơn số lượng trong kho, điều chỉnh lại số lượng trong giỏ
            if ($cart_quantity > $stock_quantity) {
                $new_cart_quantity = $stock_quantity;

                // Cập nhật số lượng giỏ hàng với số lượng tối đa trong kho
                $updateStmt = $conn->prepare("UPDATE shop_cart SET quantity = :quantity WHERE product_id = :product_id AND username = :username");
                $updateStmt->bindParam(':quantity', $new_cart_quantity);
                $updateStmt->bindParam(':product_id', $product['id']);
                $updateStmt->bindParam(':username', $_SESSION['username']);  // Hoặc username từ dữ liệu request
                $updateStmt->execute();

                // Cập nhật lại số lượng giỏ hàng sau khi điều chỉnh
                $cart_quantity = $new_cart_quantity;
            }

            // Tính tổng tiền cho sản phẩm này (số lượng * giá sản phẩm)
            $totalAmount += $cart_quantity * $product_price;
        }
    }
} else if ($_SERVER["REQUEST_METHOD"] === "GET") {
    header("Location: " . "/AVCShop/src/home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/payment.css">
    <link rel="stylesheet" href="../public/css/shop_cart.css">
    <link rel="stylesheet" href="../public/css/add_product.css">
    <title>Thanh toán</title>
</head>

<body>
    <?php
    include '../inc/header.php';
    ?>

    <div class="container-content">
        <div class="container-sub-1">
            <ul class="breadcrumb">
                <li><a href="/AVCShop/src/home.php">Trang chủ<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/shop_cart.php">Giỏ hàng<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/payment.php">Thanh toán</a></li>
            </ul>
        </div>
        <div class="container-sub-2">
            <h1 class="title-page">Thanh toán</h1>

            <?php
            if (sizeof($list_products) <= 0) {
                echo '<div class="no-products">
                    <span class="notify-products">Danh sách sản phẩm cần thanh toán trống</span>
                    </div>';
            } else {
            ?>
                <?php
                echo '<form action="" id="form" method="POST" onsubmit="submit_form(event)"';
                ?>
                <div class="col-sm-12">
                    <fieldset class="info-receiver">
                        <legend>Thông tin người nhận</legend>
                        <div class="form-group">
                            <label for="name_contact" class="form-label col-sm-2">Tên liên hệ<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="name_contact" class="text-left form-control" name="name_contact" placeholder="Tên liên hệ" require autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone_number" class="form-label col-sm-2">Số điện thoại<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="phone_number" class="text-left form-control" name="phone_number" placeholder="Số điện thoại" require autocomplete="one-time-code" oninput="input_phone_number(event)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="location" class="form-label col-sm-2">Địa chỉ<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="location" class="text-left form-control" name="location" placeholder="Địa chỉ" require autocomplete="one-time-code">
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="list-products">
                        <legend>Danh sách sản phẩm</legend>
                        <div class="table">
                            <table>
                                <thead>
                                    <tr>
                                        <td class="text-center col-image">Hình ảnh</td>
                                        <td>Tên sản phẩm</td>
                                        <td class="text-center">Số lượng</td>
                                        <td class="text-right">Đơn giá</td>
                                        <td class="text-right">Thành tiền</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($list_products as $row) {
                                    ?>
                                        <?php
                                        echo '<tr class="row-number" id="row-' . $row['id'] . '">';
                                        ?>
                                        <td class="text-center col-image">
                                            <?php
                                            echo '<a href="/AVCShop/src/detail.php?product_id=' . $row['id'] . '">' .
                                                '<img class="img-thumbnail" src="' . $row['thumbnail'] . '" alt="' . $row['title'] . '">' .
                                                '</a>';
                                            ?>
                                        </td>
                                        <td class="td-title">
                                            <?php
                                            echo '<a href="/AVCShop/src/detail.php?product_id=' . $row['id'] . '">' . $row['title'] . '</a>';
                                            ?>
                                        </td>
                                        
                                        <td class="text-center" style="color: #666666">
                                            <?php echo $row['cart_quantity'] ?>
                                        </td>
                                        <td class="text-right" style="color: #666666">
                                            <?php echo number_format($row['price'], 0, ",", ".") . ' đ' ?>
                                        </td>
                                        <?php
                                        echo '<td id="sum-price-product-' . $row["id"] . '" class="text-right" style="color: #666666">';
                                        ?>
                                        <?php echo number_format($row['price'] * $row['cart_quantity'], 0, ",", ".") . ' đ' ?>
                                        </td>
                                        </tr>
                                    <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>Phương thức thanh toán</legend>
                        <div class="payment-method">
                            <div class="item-method">
                                <input type="radio" name="select-payment-method" id="select-payment-method" checked>
                                <label for="select-payment-method">Thanh toán khi nhận hàng</label>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="payment-detail">
                        <legend>Chi tiết thanh toán</legend>
                        <div class="col-sm-12">
                            <div class="col-sm-4 col-offset-8 table">
                                <table>
                                    <tbody>
                                        <tr>
                                            <th class="text-right table-7">Tổng tiền hàng:&nbsp;</th>
                                            <td class="text-right table-7" style="color: #666666">
                                                <?php echo number_format($totalAmount, 0, ',', '.') . " đ"; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-right table-7">Tổng tiền phí vận chuyển:&nbsp;</th>
                                            <td class="text-right table-7" style="color: #666666">
                                                <?php echo number_format($priceShip, 0, ',', '.') . " đ"; ?>
                                            </td>
                                        </tr>
                                            <th class="text-right table-7">Tổng thanh toán:&nbsp;</th>
                                            <td class="text-right table-7" style="color: #666666">
                                                <?php echo number_format($totalAmount + $priceShip, 0, ',', '.') . " đ"; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </fieldset>
                    <div class="buttons">
                        <div class="pull-right">
                            <?php echo '<button type="submit" onclick="validate(event)" class="btn">Đặt hàng</button>' ?>
                        </div>
                    </div>
                </div>
                </form>
            <?php
            }
            ?>
        </div>
    </div>

    <?php
    include '../inc/footer.php';
    ?>
</body>

<script src="../public/js/validate.js"></script>
<script>
    const validate = () => {
        const res = Validate({
            rules: [
                isRequired('#name_contact'),
                isRequired('#phone_number'),
                isRequired('#location'),
            ]
        })

        if (!res) {
            event.preventDefault()
        }
    }

    const input_phone_number = (event) => {
        let inputValue = event.target.value;

        // Loại bỏ tất cả ký tự không phải là số
        inputValue = inputValue.replace(/[^0-9]/g, '');

        // Cập nhật lại giá trị trong input
        event.target.value = inputValue;
    }

    const submit_form = (event) => {
        event.preventDefault();

        const selectedProductIDs = <?php echo json_encode($selectedProductIDs); ?>;

        const formData = new FormData();
        formData.append('username', '<?php echo $username_local; ?>');
        formData.append('name_contact', document.querySelector('#name_contact').value);
        formData.append('phone_number', document.querySelector('#phone_number').value);
        formData.append('location', document.querySelector('#location').value);
        formData.append('selectedProductIDs', JSON.stringify(selectedProductIDs));

        fetch("/AVCShop/service/checkout.php", {
                method: "POST", 
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message)
                if (data.status === 2) {
                    window.location.href = '/AVCShop/src/list_order.php'
                }
            })
            .catch(error => console.error(error));
    };

    function numberFormat(number, decimals = 0, decimalSeparator = ",", thousandSeparator = ".") {
        let numString = number.toFixed(decimals).toString();
        let parts = numString.split(".");
        let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        let decimalPart = parts[1] ? (decimalSeparator + parts[1]) : "";
        return integerPart + decimalPart;
    }
</script>

<script src="/AVCShop/public/js/pin_header.js"></script>

</html>