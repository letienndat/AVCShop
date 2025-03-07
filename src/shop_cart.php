<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/shop_cart.css">
    <title>Giỏ Hàng</title>
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
                <li><a href="/AVCShop/src/shop_cart.php">Giỏ hàng</a></li>
            </ul>
        </div>
        <div class="container-sub-2">
            <h1 class="title-page">Giỏ hàng</h1>

            <?php
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare("SELECT 
                           products.*, 
                           shop_cart.quantity, 
                           thumbnails.path_image AS thumbnail
                        FROM products
                        INNER JOIN shop_cart ON products.id = shop_cart.product_id
                        LEFT JOIN thumbnails ON products.id = thumbnails.product_id
                        WHERE shop_cart.username = :username
                        ORDER BY shop_cart.time DESC");
            $stmt->bindParam(':username', $username_local);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php
            if (sizeof($result) <= 0) {
                echo '<div class="no-products">
                    <span class="notify-products">Không tồn tại sản phẩm nào trong giỏ</span>
                    </div>';
            } else {
            ?>
                <?php
                echo '<form action="/AVCShop/src/payment.php" id="form" method="POST">';
                ?>
                <div class="col-sm-12">
                    <div class="table">
                        <table>
                            <thead>
                                <tr>
                                    <td class="select-product"></td>
                                    <td class="text-center col-image">Hình ảnh</td>
                                    <td>Tên sản phẩm</td>
                                    <td class="text-center">Số lượng</td>
                                    <td class="text-right">Đơn giá</td>
                                    <td class="text-right">Tổng cộng</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($result as $row) {
                                ?>
                                    <?php
                                    echo '<tr class="row-number" id="row-' . $row['id'] . '">';
                                    ?>
                                    <td class="text-center select-product">
                                        <?php
                                        echo '<input type="checkbox" name="option[]" id="input-checkbox-' . $row['id'] . '" value="' . $row['id'] . '" onclick="click_checkbox(event, \'' . $row["id"] . '\', ' . $row['price'] . ')">';
                                        ?>
                                    </td>
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
                                    <td class="text-center td-quantity">
                                        <div class="quantity">
                                            <?php
                                            echo '<span class="input-group-addon product_quantity_down" onclick="up_down_quantity(\'-\', \'' . $row["id"] . '\', ' . $row['price'] . ', \'' . $username_local . '\')">-</span>';
                                            echo '<input type="text" id="quantity-product-' . $row['id'] . '" class="form-control" value="' . $row['quantity'] . '" disabled>';
                                            echo '<span class="input-group-addon product_quantity_up" onclick="up_down_quantity(\'+\', \'' . $row["id"] . '\', ' . $row['price'] . ', \'' . $username_local . '\')">+</span>';
                                            ?>
                                        </div>
                                    </td>
                                    <td class="text-right" style="color: #666666">
                                        <?php echo number_format($row['price'], 0, ",", ".") . ' đ' ?>
                                    </td>
                                    <?php
                                    echo '<td id="sum-price-product-' . $row["id"] . '" class="text-right" style="color: #666666">';
                                    ?>
                                    <?php echo number_format($row['price'] * $row['quantity'], 0, ",", ".") . ' đ' ?>
                                    </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-sm-12">
                        <div class="col-sm-4 col-offset-8 table">
                            <table>
                                <tbody>
                                    <tr>
                                        <th class="text-right table-5">Thành tiền:&nbsp;</th>
                                        <td class="text-right table-7" id="price-first" style="color: #666666">0 đ</td>
                                    </tr>
                                    <tr>
                                        <th class="text-right table-5">Tổng cộng:&nbsp;</th>
                                        <td class="text-right table-7" id="price-second" style="color: #666666">0 đ</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="buttons">
                        <div class="pull-right">
                            <?php echo '<button type="submit" class="btn">Thanh toán</button>' ?>
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

<script>
    var sum_all = 0

    const click_checkbox = (event, id, price) => {

        if (event.target.checked) {
            sum_all += price * document.querySelector(`#quantity-product-${id}`).value
        } else {
            sum_all -= price * document.querySelector(`#quantity-product-${id}`).value
        }

        document.querySelector('#price-first').textContent = numberFormat(sum_all) + ' đ'
        document.querySelector('#price-second').textContent = numberFormat(sum_all) + ' đ'
    }

    const up_down_quantity = (operator, id, price, username_local) => {
        var option = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        }

        var input_quantity = document.querySelector(`#quantity-product-${id}`)
        if (operator === '-') {
            if (input_quantity.value === '1') {
                const confirm_remove = confirm('Bạn có chắc chắn xóa đơn này khỏi giỏ hàng không?')
                if (confirm_remove.valueOf()) {
                    operator = 'del'
                } else {
                    return
                }
            } else {
                --input_quantity.value
            }
        } else if (operator === '+') {
            ++input_quantity.value
        }

        option['body'] = JSON.stringify({
            username: username_local,
            product_id: id,
            operator
        })
        fetch('/AVCShop/service/update_quantity_shop_cart.php', option)
            .then(response => response.json())
            .then(data => {
                var check = document.querySelector(`#input-checkbox-${id}`).checked
                if (data.status === 1) {
                    document.querySelector(`#sum-price-product-${id}`).textContent = numberFormat(input_quantity.value * price) + ' đ'

                    if (check) {
                        if (operator === '-') {
                            sum_all -= price
                        } else if (operator === '+') {
                            sum_all += price
                        }
                    }
                } else if (data.status == 2) {
                    alert(data.message)
                    if (operator === '-') {
                        ++input_quantity.value
                    } else if (operator === '+') {
                        --input_quantity.value
                    }
                } else if (data.status == 0) {
                    if (document.querySelectorAll('.row-number').length === 1) {
                        window.location.href = '/AVCShop/src/shop_cart.php'
                    } else {
                        if (check) {
                            sum_all -= price
                        }

                        document.querySelector(`#row-${id}`).remove()
                        const total_orders = document.querySelectorAll('input[type="checkbox"]').length
                        document.querySelector('#total_order').innerHTML = total_orders > 99 ? '99+' : total_orders
                    }
                }

                document.querySelector('#price-first').textContent = numberFormat(sum_all) + ' đ'
                document.querySelector('#price-second').textContent = numberFormat(sum_all) + ' đ'
            })
    }

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