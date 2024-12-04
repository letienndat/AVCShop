<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/home.css">
    <title>
        <?php
        @$type = $_GET['type'];
        @$search = $_GET['search'];
        @$sort = $_GET['sort'];
        echo getTitlePage($type, $search);
        ?>
    </title>

</head>

<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';
?>

<?php

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getTitlePage($type, $search)
{
    switch ($type) {
        case 'ao':
            return "Áo";
        case 'quan':
            return "Quần";
        case 'dam-vay':
            return "Đầm/Váy";
        case 'ao-khoac':
            return "Áo khoác";
        case 'do-lot':
            return "Đồ lót";
        default:
            return isset($search) ? (trim($search) === "" ? "Search" : ("Search - " . $search)) : "Thời trang nam nữ";
    }
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
                <?php
                if (isset($type)) {
                ?>
                    <li><a href=<?php echo "/AVCShop/src/home.php?type=" . $type ?>>
                            <?php
                            echo getTitlePage($type, $search);
                            ?>
                        </a>
                    </li>
                <?php
                } else if (isset($search)) {
                ?>
                    <li><a href=<?php echo "/AVCShop/src/home.php?search=" . $search ?>>Search</a></li>
                <?php
                } else {
                ?>
                    <li><a href="/AVCShop/src/home.php">Tất cả sản phẩm</a></li>
                <?php
                }
                ?>
            </ul>
        </div>
        <div class="container-sub-2">
            <?php
            if (!isset($search)) {
            ?>
                <div class="div-banner">
                    <img class="banner" src=<?php echo "/AVCShop/public/images/banners/" . (isset($type) ? $type . ".jpg" : "home.jpg") ?> alt="Banner">
                </div>
            <?php
            } else {
                echo "<h1 class='title-page'>Search" . (trim($search) !== "" ? " - " . $search : "") . "</h1>";
            }
            ?>

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

                    // Tham số phân trang
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;  // Mặc định là trang 1
                    $perPage = 30;  // Số sản phẩm mỗi trang
                    $offset = ($page - 1) * $perPage;

                    // Truy vấn lấy danh sách giày từ bảng "products" và kết hợp với bảng "thumbnails"
                    if (!isset($search)) {
                        if (isset($sort)) {
                            $stmt = $conn->query("SELECT p.*, t.path_image AS thumbnail_path FROM products p
                                                LEFT JOIN thumbnails t ON p.id = t.product_id
                                                " . (isset($type) ? (" WHERE p.type = '" . $type . "'") : "") . " 
                                                ORDER BY p.price " . $sort . " LIMIT $perPage OFFSET $offset");
                        } else {
                            $stmt = $conn->query("SELECT p.*, t.path_image AS thumbnail_path FROM products p
                                                LEFT JOIN thumbnails t ON p.id = t.product_id
                                                " . (isset($type) ? (" WHERE p.type = '" . $type . "'") : "") . " 
                                                LIMIT $perPage OFFSET $offset");
                        }
                    } else if (isset($search)) {
                        if (isset($sort)) {
                            $stmt = $conn->prepare("SELECT p.*, t.path_image AS thumbnail_path FROM products p
                                                    LEFT JOIN thumbnails t ON p.id = t.product_id
                                                    WHERE p.title LIKE :keyword OR p.id LIKE :id
                                                    ORDER BY p.price " . $sort . " LIMIT $perPage OFFSET $offset");
                        } else {
                            $stmt = $conn->prepare("SELECT p.*, t.path_image AS thumbnail_path FROM products p
                                                    LEFT JOIN thumbnails t ON p.id = t.product_id
                                                    WHERE p.title LIKE :keyword OR p.id LIKE :id 
                                                    LIMIT $perPage OFFSET $offset");
                        }
                        $search = trim($search);
                        $stmt->bindValue(':keyword', "%$search%", PDO::PARAM_STR);
                        $stmt->bindValue(':id', "%$search%", PDO::PARAM_STR);
                        $stmt->execute();
                    }

                    // Lấy kết quả tìm kiếm
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Lấy tổng số sản phẩm để tính số trang
                    if (!isset($search)) {
                        $totalStmt = $conn->query("SELECT COUNT(*) FROM products" . (isset($type) ? (" WHERE type = '" . $type . "'") : ""));
                    } else {
                        $totalStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE title LIKE :keyword OR id LIKE :id" . (isset($type) ? (" AND type = '" . $type . "'") : ""));
                        $search = trim($search);
                        $totalStmt->bindValue(':keyword', "%$search%", PDO::PARAM_STR);
                        $totalStmt->bindValue(':id', "%$search%", PDO::PARAM_STR);
                        $totalStmt->execute();
                    }
                    $totalRows = $totalStmt->fetchColumn();
                    $totalPages = ceil($totalRows / $perPage);
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
                                    if (isset($search)) {
                                        echo "/AVCShop/src/detail.php?product_id=" . $product['id'];
                                    } else if (isset($type)) {
                                        echo "/AVCShop/src/detail.php?product_id=" . $product['id'] . '&type=' . $type;
                                    } else {
                                        echo "/AVCShop/src/detail.php?product_id=" . $product['id'] . '&type=all';
                                    }
                                    ?>">
                                    <img class="image-product" src=<?php echo $product['thumbnail_path'] ?> alt="<?php echo $product['title'] ?>">
                                </a>
                            </div>
                            <div class="bottom-block">
                                <h4>
                                    <a href="
                                    <?php
                                    if (isset($search)) {
                                        echo "/AVCShop/src/detail.php?product_id=" . $product['id'];
                                    } else if (isset($type)) {
                                        echo "/AVCShop/src/detail.php?product_id=" . $product['id'] . '&type=' . $type;
                                    } else {
                                        echo "/AVCShop/src/detail.php?product_id=" . $product['id'] . '&type=all';
                                    }
                                    ?>">
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
        <!-- Hiển thị phân trang -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <!-- Liên kết đến trang đầu và trang trước -->
                <a href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $type ? '&type=' . urlencode($type) : '' ?><?= $sort ? '&sort=' . urlencode($sort) : '' ?>">|<</a>
                <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $type ? '&type=' . urlencode($type) : '' ?><?= $sort ? '&sort=' . urlencode($sort) : '' ?>"><</a>
            <?php endif; ?>

            <?php
            // Hiển thị các nút số trang
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            if ($startPage > 1) {
                echo '<span>...</span>';
            }

            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $page) {
                    echo "<strong>$i</strong>";
                } else {
                    echo "<a href='?page=$i" . ($search ? '&search=' . urlencode($search) : '') . ($type ? '&type=' . urlencode($type) : '') . ($sort ? '&sort=' . urlencode($sort) : '') . "'>$i</a>";
                }
            }

            if ($endPage < $totalPages) {
                echo '<span>...</span>';
            }
            ?>

            <?php if ($page < $totalPages): ?>
                <!-- Liên kết đến trang tiếp theo và trang cuối -->
                <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $type ? '&type=' . urlencode($type) : '' ?><?= $sort ? '&sort=' . urlencode($sort) : '' ?>">></a>
                <a href="?page=<?= $totalPages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $type ? '&type=' . urlencode($type) : '' ?><?= $sort ? '&sort=' . urlencode($sort) : '' ?>">>|</a>
            <?php endif; ?>
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
        const start = window.scrollY; // Vị trí hiện tại
        const maxDuration = 500; // Giới hạn thời gian tối đa (0.5 giây)
        const distance = start;
        const duration = Math.min(distance / 2, maxDuration); // Thời gian tỷ lệ với khoảng cách

        const startTime = performance.now();

        const animateScroll = (currentTime) => {
            const elapsedTime = currentTime - startTime;
            const progress = Math.min(elapsedTime / duration, 1);
            const scrollAmount = start * (1 - progress);

            window.scrollTo(0, scrollAmount);

            if (progress < 1) {
                requestAnimationFrame(animateScroll);
            }
        };

        requestAnimationFrame(animateScroll);
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