<?php
session_start();

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';
require_once $root . '/AVCShop/local/const.php';

if ($username_local === null || $role !== 1) {
    header("Location: " . "/AVCShop/src/home.php");
    exit;
}

// Xử lý form khi người dùng gửi
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Biến lưu trạng thái phản hồi
    $response = ['success' => false, 'message' => '', 'product_id' => ''];

    try {
        // Kết nối đến cơ sở dữ liệu
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Tạo sản phẩm mới
        $id = strtoupper(uniqid()); // Tạo ID sản phẩm mới
        $stmt = $conn->prepare("INSERT INTO products (id, title, price, quantity, type, brand, manufacture, material, description) VALUES (:id, :title, :price, :quantity, :type, :brand, :manufacture, :material, :description)");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':title', $_POST['title']);
        $stmt->bindParam(':price', $_POST['price']);
        $stmt->bindParam(':quantity', $_POST['quantity']);
        $stmt->bindParam(':type', $_POST['type']);
        $stmt->bindParam(':brand', $_POST['brand']);
        $stmt->bindParam(':manufacture', $_POST['manufacture']);
        $stmt->bindParam(':material', $_POST['material']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->execute();

        $uploadDir = '../public/images/products';

        // Xử lý ảnh thumbnail
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Tạo ID thumbnail mới
            $idThumbnail = strtoupper(uniqid());

            // Đường dẫn lưu trữ
            $imageFileName = 'thumbnail_' . $idThumbnail . '.jpg'; // Đặt tên file theo idThumbnail
            $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $imageFileName;

            // Di chuyển file vào thư mục đích
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destinationPath)) {
                $thumbnailTitle = "Thumbnail sản phẩm " . $id;
                $path_image_thumbnail = '/AVCShop/public/images/products/' . $imageFileName;

                // Lưu vào bảng thumbnails
                $stmt = $conn->prepare("INSERT INTO thumbnails (id, product_id, title, path_image) VALUES (:id, :product_id, :title, :path_image)");
                $stmt->bindParam(':id', $idThumbnail);
                $stmt->bindParam(':product_id', $id);
                $stmt->bindParam(':title', $thumbnailTitle);
                $stmt->bindParam(':path_image', $path_image_thumbnail);
                $stmt->execute();
            } else {
                $response['message'] = 'Không thể tải lên ảnh thumbnail.';
                echo json_encode($response);
                exit;
            }
        }

        // Xử lý ảnh chi tiết (new_images)
        if (isset($_FILES['new_images']) && count($_FILES['new_images']['name']) > 0) {
            for ($i = 0; $i < count($_FILES['new_images']['name']); $i++) {
                $imageTmpName = $_FILES['new_images']['tmp_name'][$i];
                $imageName = $_FILES['new_images']['name'][$i];
                $imageError = $_FILES['new_images']['error'][$i];

                if ($imageError === UPLOAD_ERR_OK) {
                    // Tạo ID cho ảnh chi tiết
                    $idImage = strtoupper(uniqid());

                    // Đổi tên file ảnh
                    $newImageName = 'image_' . $idImage . '.jpg';
                    $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $newImageName;

                    // Di chuyển ảnh vào thư mục đích
                    if (move_uploaded_file($imageTmpName, $destinationPath)) {
                        $imagelTitle = "Ảnh sản phẩm " . $id;
                        $path_image_images = '/AVCShop/public/images/products/' . $newImageName;

                        // Lưu vào bảng images
                        $stmt = $conn->prepare("INSERT INTO images (id, product_id, title, path_image) VALUES (:id, :product_id, :title, :path_image)");
                        $stmt->bindParam(':id', $idImage);
                        $stmt->bindParam(':product_id', $id);
                        $stmt->bindParam(':title', $imagelTitle);
                        $stmt->bindParam(':path_image', $path_image_images);
                        $stmt->execute();
                    } else {
                        $response['message'] = 'Không thể tải lên ảnh chi tiết.';
                        echo json_encode($response);
                        exit;
                    }
                }
            }
        }

        $response['success'] = true;
        $response['message'] = 'Thêm sản phẩm mới thành công!';
        $response['product_id'] = $id;
    } catch (PDOException $e) {
        $response['message'] = 'Lỗi: ' . $e->getMessage();
    }

    // Kết nối CSDL xong
    $conn = null;

    // Trả về phản hồi
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/add_product.css" />
    <title>Thêm Sản Phẩm</title>
</head>

<body>
    <?php
    include '../inc/header.php';
    ?>

    <div class="container-signup">
        <div class="container-sub-1">
            <ul class="breadcrumb">
                <li><a href="/AVCShop/src/home.php">Trang chủ<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/profile.php">Quản trị viên<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/add_product.php">Thêm sản phẩm</a></li>
            </ul>
        </div>

        <div class="container-sub-2">
            <div class="content">
                <h1 class="title-add-product">Thêm sản phẩm</h1>
                <p><strong>Lưu ý:</strong> Các mục dấu <strong>màu đỏ</strong> không được bỏ trống & phải điền đầy đủ, chính xác</p>
                <form id="add-product" onsubmit="sanitizePrice()" action="/AVCShop/src/add_product.php" method="POST" enctype="multipart/form-data">
                    <fieldset class="info-product">
                        <legend>Thông tin sản phẩm</legend>
                        <!-- Ảnh đại diện -->
                        <div class="form-group-image">
                            <label for="image" class="form-label col-sm-2">Ảnh<sup>*</sup>:</label>
                            <div class="col-sm-10 form-image">
                                <img id="image-preview" src="#" alt="Preview" style="display: none; max-width: 150px; max-height: 150px;">
                                <input type="file" id="image" name="image" accept=".png, .jpg, .jpeg, .webp" required autocomplete="one-time-code" onchange="change_image(event)">
                            </div>
                        </div>
                        <!-- Danh sách ảnh chi tiết -->
                        <div class="form-group-image">
                            <label for="image" class="form-label col-sm-2">Ảnh chi tiết<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <div class="image-list-scrollable" id="image-list">
                                </div>
                                <label for="new-images">Thêm hình ảnh mới:</label>
                                <input type="file" id="new-images" multiple accept=".png, .jpg, .jpeg, .webp" required onchange="previewImages(event)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="title" class="form-label col-sm-2">Tên<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="title" class="form-control" name="title" placeholder="Tên" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="price" class="form-label col-sm-2">Giá<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" value="0" id="price" class="form-control" name="price" placeholder="Giá" autocomplete="one-time-code" oninput="input_price(event)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="quantity" class="form-label col-sm-2">Số lượng<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" value="0" id="quantity" class="form-control" name="quantity" placeholder="Số lượng" autocomplete="one-time-code" oninput="input_quantity(event)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="type" class="form-label col-sm-2">Thể loại<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <select id="type" class="form-control" name="type">
                                    <option value="ao">Áo</option>
                                    <option value="quan">Quần</option>
                                    <option value="dam-vay">Đầm/Váy</option>
                                    <option value="ao-khoac">Áo khoác</option>
                                    <option value="do-lot">Đồ lót</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="brand" class="form-label col-sm-2">Thương hiệu<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="brand" class="form-control" name="brand" placeholder="Thương hiệu" value="<?php echo AppConstants::BRAND_NAME; ?>" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="manufacture" class="form-label col-sm-2">Sản xuất<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="manufacture" class="form-control" name="manufacture" placeholder="Sản xuất" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="material" class="form-label col-sm-2">Chất liệu<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="material" class="form-control" name="material" placeholder="Chất liệu" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group-area">
                            <label for="description" class="form-label col-sm-2">Mô tả<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control-area" name="description" id="description" cols="30" rows="10" placeholder="Mô tả"></textarea>
                            </div>
                        </div>
                    </fieldset>
                    <div class="button-submit">
                        <input type="submit" onclick="validate(event)" value="Thêm">
                    </div>
                </form>
            </div>
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
                isRequired('#title'),
                isRequired('#price'),
                isRequired('#quantity'),
                isRequired('#brand'),
                isRequired('#manufacture'),
                isRequired('#material'),
                isRequired('#description')
            ]
        })

        if (!res) {
            event.preventDefault()
        }
    }

    const input_quantity = (event) => {
        let inputValue = event.target.value;

        // Loại bỏ tất cả ký tự không phải là số
        inputValue = inputValue.replace(/[^0-9]/g, '');

        // Cập nhật lại giá trị trong input
        event.target.value = inputValue;
    }

    const input_price = (event) => {
        let inputValue = event.target.value;

        // Loại bỏ tất cả ký tự không phải là số và dấu phân cách thập phân
        inputValue = inputValue.replace(/[^0-9,]/g, '');

        // Tách phần nguyên và phần thập phân (nếu có)
        let [integerPart, decimalPart] = inputValue.split(',');

        // Thêm dấu phân cách phần nghìn (dấu ".") vào phần nguyên
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

        // Cập nhật lại giá trị trong input (phần thập phân vẫn giữ nguyên nếu có)
        event.target.value = decimalPart ? `${integerPart},${decimalPart}` : integerPart;
    }

    // Trước khi gửi form, loại bỏ dấu phân cách nghìn và gửi giá trị số thuần túy
    const sanitizePrice = () => {
        const priceInput = document.getElementById('price');
        let priceValue = priceInput.value;

        // Loại bỏ dấu phân cách phần nghìn (dấu ".") và chỉ giữ lại số và dấu phân cách thập phân
        priceValue = priceValue.replace(/\./g, '').replace(',', '.'); // Thay dấu ',' thành dấu '.' nếu có

        // Gán lại giá trị đã làm sạch
        priceInput.value = priceValue;
    };

    const imagePreview = document.getElementById('image-preview');

    const change_image = (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                imagePreview.src = event.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.src = '#';
            imagePreview.style.display = 'none';
        }
    }
    
    let new_images = [];     // Mảng ảnh mới

    function previewImages(event) {
        const files = event.target.files;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();

            reader.onload = function (e) {
                // Tạo phần tử ảnh mới để hiển thị
                const imageItem = document.createElement('div');
                imageItem.classList.add('image-item');

                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('image-thumbnail');
                img.removeAttribute('data-id'); // Ảnh mới không có ID

                // Tạo ID duy nhất cho ảnh mới
                const imageId = `new_image_${new_images.length}`;

                // Thêm nút xóa
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.classList.add('remove-image-btn');
                removeButton.textContent = '×';
                removeButton.onclick = function () {
                    removeImage(removeButton, imageId); // Gửi imageId vào hàm removeImage
                };

                // Thêm ảnh vào DOM
                imageItem.appendChild(img);
                imageItem.appendChild(removeButton);
                document.getElementById('image-list').appendChild(imageItem);

                // Thêm ảnh vào mảng new_images
                new_images.push({ id: imageId, file: file, src: e.target.result });
            };

            reader.readAsDataURL(file); // Đọc ảnh
        }
    }

    function removeImage(button, imageId) {
        const imageItem = button.closest('.image-item');
        const imgElement = imageItem.querySelector('img');

        new_images = new_images.filter(image => image.id !== imageId); // Xoá ảnh khỏi new_images

        // Xoá ảnh khỏi DOM
        imageItem.remove();
    }

    document.getElementById('add-product').addEventListener('submit', function (event) {
        event.preventDefault(); // Ngừng submit form mặc định để xử lý thêm

        const formData = new FormData(this); // Lấy tất cả dữ liệu form vào FormData

        // Gửi ảnh mới lên server
        new_images.forEach(image => {
            formData.append('new_images[]', image.file); // Gửi mỗi ảnh dưới dạng 'new_images[]'
        });

        // Thực hiện gửi form dữ liệu tới server
        fetch('/AVCShop/src/add_product.php', {
            method: 'POST',
            body: formData
        })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json(); // Chuyển phản hồi sang JSON
        })
        .then((data) => {
            if (data.success) {
                alert(data.message); // Hiển thị thông báo thành công
                // Điều hướng về trang xem chi tiết sản phẩm

                // Lấy prouct_id từ response
                var productId = data['product_id']
                window.location.href = "/AVCShop/src/detail.php?product_id=" + productId;
            } else {
                alert('Lỗi: ' + data.message); // Hiển thị thông báo lỗi
            }
        })
        .catch((error) => {
            console.error('Fetch error:', error); // Log lỗi mạng
            alert('Đã xảy ra lỗi trong khi cập nhật sản phẩm.');
        });
    });

    // Ngăn chặn sự kiện cuộn chuột trên input type="number"
    document.getElementById('price').addEventListener('wheel', function(event) {
        event.preventDefault(); // Ngừng hành vi cuộn
    });
</script>

<script src="/AVCShop/public/js/pin_header.js"></script>

</html>