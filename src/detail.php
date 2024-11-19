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

    <?php
    $type = $_GET['type'];

    $root = $_SERVER['DOCUMENT_ROOT'];
    require_once $root . '/AVCShop/database/info_connect_db.php';
    require_once $root . '/AVCShop/local/data.php';

    try {
        // Kết nối đến cơ sở dữ liệu MySQL
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Truy vấn để lấy thumbnail và tất cả ảnh chi tiết của sản phẩm
        $stmt = $conn->prepare("SELECT p.title, p.id, p.material, p.brand, p.manufacture, p.price, p.description, t.path_image AS thumbnail_path, i.path_image AS image_path
                                FROM products p
                                LEFT JOIN thumbnails t ON p.id = t.product_id
                                LEFT JOIN images i ON p.id = i.product_id
                                WHERE p.id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Khởi tạo mảng $images chứa tất cả các ảnh
        $images = array();

        // Thêm ảnh thumbnail vào mảng $images
        if (!empty($product['thumbnail_path'])) {
        $images[] = $product['thumbnail_path'];
        }

        // Thêm các ảnh chi tiết vào mảng $images (nếu có)
        $stmtImages = $conn->prepare("SELECT path_image FROM images WHERE product_id = :product_id");
        $stmtImages->bindParam(':product_id', $product_id, PDO::PARAM_STR);
        $stmtImages->execute();
        $additionalImages = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

        // Lặp qua các ảnh chi tiết và thêm vào mảng $images
        foreach ($additionalImages as $image) {
        $images[] = $image['path_image'];
        }
    } catch (PDOException $e) {
        echo '<script>console.log("Lỗi: ' . $e->getMessage() . '")</script>';
    }
    ?>

    <title>
        <?php
            // Kết nối đến cơ sở dữ liệu MySQL
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Truy vấn lấy giày từ bảng "products"
            $stmt = $conn->query("SELECT title FROM products" . " WHERE id = '" . $product_id . "'");
            $stmt->execute();

            $title = $stmt->fetch(PDO::FETCH_ASSOC);

            echo $title['title'];
        ?>
    </title>
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
                    <div class="product-images">
                        <!-- Danh sách ảnh nhỏ nằm bên trái -->
                        <div class="thumbnail-images-container">
                            <!-- Nút mũi tên lên -->
                            <button class="arrow-btn arrow-btn-up" onclick="changeSelectedImage('up')">&#8593;</button>

                            <div class="thumbnail-images" id="thumbnailContainer">
                                <!-- Các ảnh thu nhỏ được hiển thị sẽ được thay đổi dần qua JS -->
                            </div>

                            <!-- Nút mũi tên xuống -->
                            <button class="arrow-btn arrow-btn-down" onclick="changeSelectedImage('down')">&#8595;</button>
                        </div>

                        <!-- Ảnh lớn được chọn -->
                        <div id="mainImageContainer" class="container-main-image">
                            <img id="mainImage" src="" alt="Main Image" class="main-image">
                        </div>
                        <!-- Phần overlay và ảnh full-screen -->
                        <div id="fullscreenOverlay" class="fullscreen-overlay">
                            <div class="fullscreen-container">
                                <img id="fullscreenImage" class="fullscreen-image" src="" alt="Fullscreen Image">
                            </div>
                        </div>
                    </div>
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
                            <div class="brand-product">
                                <span><?php echo "Thương hiệu: " . $product['brand'] ?></span>
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
    // Mảng ảnh từ PHP
    let images = <?php echo json_encode($images); ?>;
    let visibleImagesIndices = [0, 1, 2, 3].slice(0, Math.min(4, images.length));  // Chỉ số của những ảnh đang hiển thị (tối đa là 4)
    let selectedIndex = 0;  // Chỉ số của ảnh đang được chọn

    // Cập nhật ảnh thu nhỏ và ảnh chính
    function updateThumbnailImages() {
        let thumbnailContainer = document.getElementById('thumbnailContainer');
        let allThumbnails = document.querySelectorAll('.small-image');
        
        // Đảm bảo số lượng ảnh thu nhỏ đúng
        visibleImagesIndices.forEach((index, idx) => {
            let imgElement = allThumbnails[idx];  // Lấy thẻ img tương ứng
            imgElement.src = images[index];  // Cập nhật src của ảnh thu nhỏ

            imgElement.classList.toggle('selected-thumbnail', index === selectedIndex);  // Cập nhật lớp 'selected-thumbnail'
        });

        // Cập nhật ảnh chính
        document.getElementById('mainImage').src = images[selectedIndex];
    }

    // Chọn ảnh chính từ ảnh thu nhỏ
    function changeMainImage(imagePath, imgElement) {
        document.getElementById('mainImage').src = imagePath;
        let allThumbnails = document.querySelectorAll('.small-image');
        allThumbnails.forEach(img => img.classList.remove('selected-thumbnail'));
        imgElement.classList.add('selected-thumbnail');
    }

    // Xử lý bấm nút lên/xuống
    function changeSelectedImage(direction) {
        let firstVisibleImage = visibleImagesIndices[0];
        let lastVisibleImage = visibleImagesIndices[visibleImagesIndices.length - 1];

        if (direction === 'up') {
            // Bấm lên
            if (selectedIndex > firstVisibleImage) {
                selectedIndex--;  // Di chuyển ảnh chọn lên
            } else {
                // Nếu ảnh chọn là ảnh đầu tiên trong danh sách, dịch chuyển danh sách lên
                if (firstVisibleImage > 0) {
                    visibleImagesIndices = visibleImagesIndices.map(index => index - 1);  // Dịch chuyển tất cả các ảnh
                    selectedIndex--;  // Cập nhật lại ảnh chọn
                } else {
                    // Nếu ảnh chọn là ảnh đầu tiên trong danh sách, chuyển về ảnh cuối cùng
                    selectedIndex = images.length - 1;

                    // Cập nhật lại các ảnh thu nhỏ (vì đã chuyển ảnh chọn về cuối cùng)
                    visibleImagesIndices = Array.from({ length: Math.min(4, images.length) }, (_, i) => images.length - Math.min(4, images.length) + i);
                }
            }
        } else if (direction === 'down') {
            // Bấm xuống
            if (selectedIndex < lastVisibleImage) {
                selectedIndex++;  // Di chuyển ảnh chọn xuống
            } else {
                // Nếu ảnh chọn là ảnh cuối cùng trong danh sách, dịch chuyển danh sách xuống
                if (lastVisibleImage < images.length - 1) {
                    visibleImagesIndices = visibleImagesIndices.map(index => index + 1);  // Dịch chuyển tất cả các ảnh
                    selectedIndex++;  // Cập nhật lại ảnh chọn
                } else {
                    visibleImagesIndices = [0, 1, 2, 3].slice(0, Math.min(4, images.length));
                    selectedIndex = 0;
                }
            }
        }

        // Cập nhật lại ảnh thu nhỏ và ảnh chính
        updateThumbnailImages();
    }

    // Gọi updateThumbnailImages khi trang được tải
    document.addEventListener("DOMContentLoaded", function() {
        // Tạo các ảnh thu nhỏ ban đầu
        let thumbnailContainer = document.getElementById('thumbnailContainer');
        visibleImagesIndices.forEach((index) => {
            let imgElement = document.createElement('img');
            imgElement.src = images[index];
            imgElement.alt = 'Thumbnail ' + (index + 1);
            imgElement.className = 'small-image';
            imgElement.onclick = function() {
                changeMainImage(images[index], imgElement);
            };
            thumbnailContainer.appendChild(imgElement);
        });

        // Cập nhật ảnh thu nhỏ và ảnh chính sau khi tạo
        updateThumbnailImages();
    });

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

    document.addEventListener("DOMContentLoaded", function () {
        const productImage = document.getElementById("mainImage"); // Ảnh trong trang detail
        const fullscreenOverlay = document.getElementById("fullscreenOverlay"); // Overlay
        const fullscreenImage = document.getElementById("fullscreenImage"); // Ảnh full-screen

        // Bấm vào ảnh để hiển thị full-screen
        productImage.addEventListener("click", function () {
            // Đặt src của ảnh full-screen giống với ảnh gốc
            fullscreenImage.src = productImage.src;
            // Tính toán chiều cao màn hình hiện tại
            const windowHeight = window.innerHeight;

            // Gán chiều cao ảnh sao cho không vượt quá chiều cao màn hình
            fullscreenImage.style.maxHeight = windowHeight + 'px';

            fullscreenOverlay.style.display = 'flex'; // Hiển thị overlay
        });

        // Bấm vào overlay (không chứa ảnh) để đóng full-screen
        fullscreenOverlay.addEventListener("click", function (event) {
            // Kiểm tra nếu phần tử click không phải ảnh
            if (event.target !== fullscreenImage) {
                fullscreenOverlay.style.display = "none"; // Ẩn overlay
            }
        });
    });
</script>

<script src="/AVCShop/public/js/padding-top-body.js"></script>

</html>