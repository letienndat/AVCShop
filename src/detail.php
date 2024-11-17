<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/detail.css" />

    <?php
    $product_id = $_GET['product_id'];

    if (!isset($product_id)) {
        echo '<script>window.location.href="/AVCShop/src/home.php"</script>';
    }
    ?>

    <title>
        <?php
        $type = $_GET['type'];

        $root = $_SERVER['DOCUMENT_ROOT'];
        require_once $root . '/AVCShop/database/info_connect_db.php';
        require_once $root . '/AVCShop/local/data.php';

        try {
            // Kết nối đến cơ sở dữ liệu MySQL
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Truy vấn lấy giày từ bảng "products"
            $stmt = $conn->query("SELECT * FROM products" . " WHERE id = '" . $product_id . "'");
            $stmt->execute();

            // Lấy kết quả tìm kiếm
            $product = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo $product[0]['title'];
        } catch (PDOException $e) {
            echo '<script>console.log("Lỗi: ' . $e->getMessage() . '")</script>';
        }
        ?>
    </title>

    <?php
    if (sizeof($product) === 0) {
        echo '<script>window.location.href="/AVCShop/src/home.php"</script>';
    } else {
        $product = $product[0];
    }
    ?>
</head>

<body>
    <?php
    include '../inc/header.php';
    ?>

    <div class="container-content">
        <div class="container-sub-1">
            <ul class="breadcrumb">
                <li><a href="/AVCShop/src/home.php">Trang chủ<i class="fa fa-angle-right"></i></a></li>

                <?php
                if (isset($type)) {
                    $title = "Tất cả";
                    switch ($type) {
                        case 'ao':
                            $title = "Áo";
                            break;
                        case 'quan':
                            $title = "Quần";
                            break;
                        case 'dam-vay':
                            $title = "Đầm/Váy";
                            break;
                        case 'ao-khoac':
                            $title = "Áo khoác";
                            break;
                        case 'do-lot':
                            $title = "Đồ lót";
                            break;
                    }

                ?>
                    <li><a href="<?php echo "/AVCShop/src/home.php" . ($type === 'all' ? "" : "?type=" . $type) ?>"><?php echo $title ?><i class="fa fa-angle-right"></i></a></li>
                <?php
                }
                ?>

                <li><a href=""><?php echo $product['title'] ?></a></li>
            </ul>
        </div>
        <div class="container-sub-2">
            <div class="col-sm-12">
                <div class="content-product-left col-sm-5">
                    <div class="image-single-box">
                        <img src="<?php echo $product['path_image'] ?>" alt="Ảnh sản phẩm">
                    </div>
                </div>
                <div class="content-product-right col-sm-7">
                    <div class="title-product">
                        <h1 class="title-real"><?php echo $product['title'] ?></h1>
                        <div class="title-id"><?php echo " - " . $product['id'] ?></div>
                    </div>
                    <div class="desc-product">
                        <div class="col-sm-4">
                            <div class="id-product">
                                <span><?php echo "ID: " . $product['id'] ?></span>
                            </div>
                            <div class="metarial-product">
                                <span><?php echo "Chất liệu: " . $product['material'] ?></span>
                            </div>
                            <div class="brain-product">
                                <span><?php echo "Thương hiệu: " . $product['brain'] ?></span>
                            </div>
                            <div class="manufacture-product">
                                <span><?php echo "Sản xuất: " . $product['manufacture'] ?></span>
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <span class="price">
                                <span class="title-price">Giá: </span>
                                <span class="price-real"><?php echo number_format($product['price'], 0, ",", ".") . " đ" ?></span>
                            </span>
                            <span class="notify">
                                MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC KHI ĐẶT HÀNG ONLINE
                            </span>
                        </div>
                    </div>
                    <div class="desc-content-product">
                        <?php echo $product['description'] ?>
                    </div>
                    <div class="box-option">
                        <div class="quantity-box">
                            <label for="quantity-product">Số lượng</label>
                            <div class="quantity-content">
                                <span class="input-group-addon product_quantity_down" onclick="up_down_quantity('-')">-</span>
                                <input type="text" id="quantity-product" class="form-control" name="quantity" value="1" oninput="change_input_quantity(event)" onblur="blur_input(event)">
                                <span class="input-group-addon product_quantity_up" onclick="up_down_quantity('+')">+</span>
                            </div>
                        </div>
                        <div class="favorite">
                            <button class="btn" id="button-favorite" <?php echo 'onclick=click_favorite(' . ($username_local !== null ? ('true,"' . $username_local . '","' . $product["id"] . '"') : 'false') . ')' ?>>
                                <?php
                                // Kiểm tra xem username đã thích sản phẩm hay chưa
                                $selectStmt = $conn->prepare("SELECT * FROM user_product_favorites WHERE username = :username AND product_id = :product_id");
                                $selectStmt->bindParam(':username', $username_local);
                                $selectStmt->bindParam(':product_id', $product['id']);
                                $selectStmt->execute();

                                if ($selectStmt->rowCount() > 0) {
                                    // Nếu đã thích sản phẩm, thêm "fa-solid" vào class của thẻ i
                                    echo '<i class="fa-solid fa-heart"></i>';
                                } else {
                                    // Nếu chưa thích sản phẩm, thêm "fa-regular" vào class của thẻ i
                                    echo '<i class="fa-regular fa-heart"></i>';
                                }
                                ?>
                            </button>
                        </div>
                    </div>
                    <div class="box-option">
                        <button class="button-cart" <?php echo 'onclick=add_shop_cart(' . ($username_local !== null ? ('true,"' . $username_local . '","' . $product["id"] . '"') : 'false') . ')' ?>>ĐẶT HÀNG</button>
                        <?php
                        if ($role === 1) {
                        ?>
                            <div class="box-option-admin">
                                <button class="button-admin" <?php echo 'onclick=edit_product(\'' . $product['id'] . '\')' ?>>CHỈNH SỬA</button>
                                <button class="button-admin" <?php echo 'onclick=delete_product(\'' . $product['id'] . '\')' ?>>XÓA</button>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include '../inc/footer.php';
    ?>
</body>

<script>
    const click_favorite = (status, username_, product_id) => {
        if (status) {
            var i_favorite = document.querySelector('#button-favorite > .fa-heart')
            i_favorite.classList.contains('fa-regular') ? i_favorite.classList.replace('fa-regular', 'fa-solid') : i_favorite.classList.replace('fa-solid', 'fa-regular')

            var option = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username_,
                    product_id
                }),
            }

            fetch('/AVCShop/service/add_favorite.php', option)
                .then(response => response.json()) // Chuyển dữ liệu phản hồi thành JSON
                .then(data => {
                    // Xử lý dữ liệu phản hồi từ PHP
                    alert(data.message); // Hiển thị phản hồi trong alert
                })
                .catch(err => console.error(err))
        } else {
            alert("Rất tiếc, bạn phải đăng nhập trước!");
        }
    }

    const add_shop_cart = (status, username_, product_id) => {
        if (status) {
            var quantity = document.querySelector('#quantity-product').value

            if (Number.isInteger(parseInt(quantity)) && parseInt(quantity) > 0) {
                var option = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: username_,
                        product_id,
                        quantity: parseInt(quantity),
                        time: new Date()
                    }),
                }

                fetch('/AVCShop/service/add_shop_cart.php', option)
                    .then(response => response.json()) // Chuyển dữ liệu phản hồi thành JSON
                    .then(data => {
                        // Xử lý dữ liệu phản hồi từ PHP
                        alert(data.message); // Hiển thị phản hồi trong alert
                        window.location.href = '/AVCShop/src/shop_cart.php'
                    })
                    .catch(err => console.error(err))
            } else {
                alert("Xin lỗi, số lượng sản phẩm không hợp lệ!");
            }
        } else {
            alert("Rất tiếc, bạn phải đăng nhập trước!");
        }
    }

    const change_input_quantity = (event) => {
        if (event.data < '0' || event.data > '9') {
            event.target.value = event.target.value.replace(/\D/, '')
        }
    }

    const blur_input = (event) => {
        if (event.target.value === '') {
            // event.target.value = '1'
        } else {
            event.target.value = parseInt(event.target.value).toString()
        }
    }

    const up_down_quantity = (operator) => {
        input_quantity = document.querySelector('#quantity-product')
        if (operator === '-') {
            input_quantity.value = (input_quantity.value <= 0 ? input_quantity.value : parseInt(input_quantity.value) - 1).toString()
        } else if (operator === '+') {
            ++input_quantity.value
        }
    }

    const edit_product = (id) => {
        window.location.href = `/AVCShop/src/edit_product.php?product_id=${id}`
    }

    const delete_product = (id) => {
        window.location.href = `/AVCShop/service/delete_product.php?product_id=${id}`
    }
</script>

<script src="/AVCShop/public/js/padding-top-body.js"></script>

</html>