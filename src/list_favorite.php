<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/list_favorite.css">
    <title>Danh Sách Yêu Thích</title>
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
@$sort = $_GET['sort'];
?>

<body>
    <?php
    include '../inc/header.php';
    ?>

    <div class="container-content">
        <div class="container-sub-1">
            <ul class="breadcrumb">
                <li><a href="/AVCShop/src/home.php">Trang chủ<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/list_favorite.php">Danh sách yêu thích</a></li>
            </ul>
        </div>
        <div class="container-sub-2">
            <h1 class="title-page">Danh sách yêu thích</h1>

            <div class="col-sm-12">
                <div class="form-group">
                    <select class="select-sort" name="sort" id="sort" onchange="select_option_sort()">
                        <option <?php if (isset($sort)) {
                                    echo $sort === 'default' ? 'selected' : '';
                                }  ?> value="default">Sắp xếp: Mặc định</option>
                        <option <?php if (isset($sort)) {
                                    echo $sort === 'asc' ? 'selected' : '';
                                }  ?> value="asc">Sắp xếp: Giá (Thấp -> Cao)</option>
                        <option <?php if (isset($sort)) {
                                    echo $sort === 'desc' ? 'selected' : '';
                                }  ?> value="desc">Sắp xếp: Giá (Cao -> Thấp)</option>
                    </select>
                </div>

                <?php

                try {
                    // Kết nối đến cơ sở dữ liệu MySQL
                    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Chuẩn bị câu truy vấn SQL
                    $sqlQuery = "SELECT 
                                    products.id, 
                                    products.title, 
                                    products.price, 
                                    thumbnails.path_image AS thumbnail
                                FROM products
                                JOIN user_product_favorites ON products.id = user_product_favorites.product_id
                                LEFT JOIN thumbnails ON products.id = thumbnails.product_id
                                WHERE user_product_favorites.username = :username"
                        . (isset($sort) ? (' ORDER BY products.price ' . ($sort === 'asc' ? 'ASC' : 'DESC')) : '');



                    // Chuẩn bị statement
                    $stmt = $conn->prepare($sqlQuery);
                    $stmt->bindParam(':username', $username_local);

                    // Thực hiện truy vấn
                    $stmt->execute();

                    // Lấy kết quả tìm kiếm
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                    <div class=<?php echo (sizeof($products) > 0 ? "products" : "no-products") ?>>
                        <?php
                        if (sizeof($products) === 0) {
                            echo "<span class=" . "notify-products" . ">Không tồn tại sản phẩm nào</span>";
                        }

                        foreach ($products as $product) {
                        ?>
                            <div class="product" title="<?php echo $product['title'] ?>">
                                <div class="top-block">
                                    <a href="
                                        <?php
                                        echo "/AVCShop/src/detail.php?product_id=" . $product['id'];
                                        ?>
                                    ">
                                        <img class="image-product" src=<?php echo $product['thumbnail'] ?> alt="<?php echo $product['title'] ?>">
                                        <?php 
                                            if ($product['quantity'] <= 0) {
                                                ?>
                                                <div class="out-of-stock">Hết hàng</div>
                                                <?php
                                            }
                                        ?>
                                    </a>
                                </div>
                                <div class="bottom-block">
                                    <h4>
                                        <a href="
                                        <?php
                                        echo "/AVCShop/src/detail.php?product_id=" . $product['id'];
                                        ?>
                                    ">
                                            <?php echo mb_strtoupper($product['title'], 'UTF-8') ?></a>
                                    </h4>
                                    <div class="id-product">
                                        <?php echo "# " . $product['id'] ?>
                                    </div>
                                    <div class="price-product">
                                        <span class="title-price">Giá: </span>
                                        <span class="price-real"> <?php echo number_format($product['price'], 0, ",", ".") . " đ" ?> </span>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } catch (PDOException $e) {
                        echo '<script>console.log("Lỗi: ' . $e->getMessage() . '")</script>';
                    }
                    ?>
                    </div>
            </div>
        </div>
    </div>

    <?php
    include '../inc/footer.php';
    ?>

    <div title="Cuộn lên" class="hide up-to-top" onclick="click_up_to_top()">
        <i class="fa fa-long-arrow-up"></i>
    </div>
</body>

<script>
    const select_option_sort = () => {
        const element_sort = document.querySelector('#sort')
        const params = new URLSearchParams(window.location.search)

        if (params) {
            if (params.has('sort')) {
                if (element_sort.value === 'default') {
                    params.delete('sort')
                } else {
                    params.set('sort', element_sort.value)
                }
            } else {
                if (element_sort.value !== 'default') {
                    params.append('sort', element_sort.value)
                }
            }
            window.location.href = window.location.href.split('?')[0] + (params.toString() === '' ? '' : '?') + params.toString()
        }
    }

    const click_up_to_top = async () => {
        var y = window.pageYOffset;

        for (let i = y; i >= 0; i -= 30) {
            if (i < 90) {
                window.scrollTo(window.pageXOffset, 0);
                break
            }
            window.scrollTo(window.pageXOffset, i);
            await new Promise(resolve => setTimeout(resolve, 5));
        }
    };

    window.addEventListener('scroll', () => {
        up_to_top = document.querySelector('.up-to-top')
        if (window.pageYOffset >= 500) {
            up_to_top.classList.add('show')
        } else {
            up_to_top.classList.remove('show')
        }
    })
</script>

<script src="/AVCShop/public/js/pin_header.js"></script>

</html>