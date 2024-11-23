<?php
session_start();

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

if ($username_local === null || $role !== 1) {
    header("Location: " . "/AVCShop/src/home.php");
    exit;
}

// Xử lý form khi người dùng gửi
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Biến lưu trạng thái
    $response = ['success' => false, 'message' => ''];

    $id = $_GET['product_id'];

    // Kiểm tra xem người dùng đã gửi hình ảnh lên hay chưa
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            // Kết nối đến cơ sở dữ liệu
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Câu truy vấn lấy id của thumbnail
            $query = "
                        SELECT t.id 
                        FROM thumbnails t
                        JOIN products p ON p.id = t.product_id
                        WHERE p.id = :product_id
                    ";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':product_id', $id, PDO::PARAM_STR);
            $stmt->execute();

            // Lấy kết quả
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                // Đường dẫn thư mục lưu trữ hình ảnh
                $uploadDir = '/public/images/products/';

                $imageFileName = "thumbnail_" . $row['id'] . '.jpg';

                // Đường dẫn tệp tạm thời của hình ảnh
                $tempImageFile = $_FILES['image']['tmp_name'];

                $destinationPath = '..' . $uploadDir . $imageFileName;

                // Kiểm tra nếu tên file đã tồn tại trong thư mục đích
                if (file_exists($destinationPath)) {
                    // Xóa file cũ trước khi di chuyển file mới
                    unlink($destinationPath);
                }

                // Di chuyển hình ảnh vào thư mục lưu trữ
                move_uploaded_file($tempImageFile, $destinationPath);
            } else {
                $response['success'] = false;
                $response['message'] = 'Không thể cập nhật ảnh đại diện bởi vì không tìm thấy nó!';

                echo json_encode($response);
                exit;
            }
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['message'] = 'Lỗi: ' . $e->getMessage();

            echo json_encode($response);
            exit;
        }
    }

    // Kiểm tra nếu có ảnh mới trong new_images
    if (isset($_FILES['new_images']) && count($_FILES['new_images']['name']) > 0) {

        $destinationDirectory = '../public/images/products'; // Thư mục đích

        // Lặp qua tất cả các ảnh trong mảng new_images
        for ($i = 0; $i < count($_FILES['new_images']['name']); $i++) {
            $imageTmpName = $_FILES['new_images']['tmp_name'][$i];  // Đường dẫn tạm thời của ảnh
            $imageName = $_FILES['new_images']['name'][$i];  // Tên gốc của ảnh
            $imageError = $_FILES['new_images']['error'][$i];  // Lỗi (nếu có) của ảnh
            $imageSize = $_FILES['new_images']['size'][$i];  // Kích thước của ảnh

            // Kiểm tra lỗi upload ảnh
            if ($imageError === UPLOAD_ERR_OK) {
                // Kiểm tra loại file (chỉ cho phép ảnh)
                $imageExtension = pathinfo($imageName, PATHINFO_EXTENSION);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array(strtolower($imageExtension), $allowedExtensions)) {
                    // Đổi đuôi ảnh thành .jpg
                    $imageId = strtoupper(uniqid());
                    $newImageName = 'image_' . $imageId . '.jpg'; // Tên mới với đuôi .jpg

                    // Đường dẫn tệp mới trong thư mục đích
                    $newFileImage = $destinationDirectory . DIRECTORY_SEPARATOR . $newImageName;

                    // Di chuyển ảnh từ thư mục tạm thời đến thư mục lưu trữ
                    if (move_uploaded_file($imageTmpName, $newFileImage)) {
                        $imagePath = '/AVCShop/public/images/products/' . $newImageName; // Đường dẫn lưu ảnh

                        // Chèn ảnh vào bảng images (sử dụng product_id từ URL)
                        $query = "INSERT INTO images (id, product_id, title, path_image) VALUES ('$imageId', '$id', 'Hình ảnh sản phẩm $id', '$imagePath')";
                        if ($conn->query($query)) {
                            // echo "Ảnh chi tiết đã được tải lên thành công!";
                        } else {
                            $response['success'] = false;
                            $response['message'] = "Lỗi khi lưu ảnh vào cơ sở dữ liệu: " . $conn->error;

                            echo json_encode($response);
                            exit;
                        }
                    } else {
                        $response['success'] = false;
                        $response['message'] = "Lỗi khi di chuyển ảnh.";

                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = "Ảnh không hợp lệ. Chỉ chấp nhận ảnh có định dạng JPG, JPEG, PNG, WEBP.";

                    echo json_encode($response);
                    exit;
                }
            } else {
                $response['success'] = false;
                $response['message'] = "Lỗi khi upload ảnh: " . $imageError;

                echo json_encode($response);
                exit;
            }
        }
    }

    // Xử lý ảnh đã xóa
    if (isset($_POST['removed_images']) && is_array($_POST['removed_images'])) {
        // Kiểm tra và chuyển đổi chuỗi thành mảng nếu dữ liệu gửi về là chuỗi
        $removedImages = explode(',', $_POST['removed_images'][0]);
    
        // Lặp qua các ID ảnh đã xóa và xử lý
        foreach ($removedImages as $imageId) {
            // Truy vấn để lấy đường dẫn ảnh (path_image)
            $querySelect = "SELECT path_image FROM images WHERE id = :imageId AND product_id = :productId";
            $stmtSelect = $conn->prepare($querySelect);
            $stmtSelect->execute(['imageId' => $imageId, 'productId' => $id]);
            
            // Kiểm tra xem có kết quả trả về không
            if ($stmtSelect->rowCount() > 0) {
                // Lấy đường dẫn ảnh
                $row = $stmtSelect->fetch(PDO::FETCH_ASSOC);
                $imagePath = $_SERVER['DOCUMENT_ROOT'] . $row['path_image']; // Đảm bảo đường dẫn tuyệt đối
    
                // Tiến hành xóa ảnh khỏi cơ sở dữ liệu trước
                $queryDelete = "DELETE FROM images WHERE id = :imageId AND product_id = :productId";
                $stmtDelete = $conn->prepare($queryDelete);
                if ($stmtDelete->execute(['imageId' => $imageId, 'productId' => $id])) {
                    // echo "Ảnh đã được xóa thành công khỏi cơ sở dữ liệu.";
                    
                    // Sau khi xóa thành công trong cơ sở dữ liệu, tiếp tục xóa file ảnh khỏi thư mục
                    if (file_exists($imagePath)) {
                        if (unlink($imagePath)) {
                            // echo "File ảnh đã được xóa khỏi thư mục.";
                        } else {
                            $response['success'] = false;
                            $response['message'] = "Lỗi khi xóa file ảnh.";

                            echo json_encode($response);
                            exit;
                        }
                    } else {
                        $response['success'] = false;
                        $response['message'] = "Ảnh không tồn tại trong thư mục.";

                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = "Lỗi khi xóa ảnh khỏi cơ sở dữ liệu.";

                    echo json_encode($response);
                    exit;
                }
            } else {
                $response['success'] = false;
                $response['message'] = json_encode($removedImages) . " - " . "Ảnh không tồn tại trong cơ sở dữ liệu.";

                echo json_encode($response);
                exit;
            }
        }
    }     

    $title = $_POST['title'];
    $price = $_POST['price'];
    $type = $_POST['type'];
    $brand = $_POST['brand'];
    $manufacture = $_POST['manufacture'];
    $material = $_POST['material'];
    $description = $_POST['description'];

    // Tiến hành lưu thông tin sản phẩm vào cơ sở dữ liệu
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Tiến hành cập nhật thông tin sản phẩm vào CSDL
        $stmt = $conn->prepare("UPDATE products SET title = :title, price = :price, type = :type, brand = :brand, manufacture = :manufacture, material = :material, description = :description WHERE id = :id");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':brand', $brand);
        $stmt->bindParam(':manufacture', $manufacture);
        $stmt->bindParam(':material', $material);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    
        $response['success'] = true;
        $response['message'] = "Cập nhật thông tin sản phẩm thành công!";
    } catch (PDOException $e) {
        $response['success'] = true;
        $response['message'] = "Cập nhật thông tin sản phẩm thất bại!";
    }

    $conn = null;

    echo json_encode($response);
    exit;
} else if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $product_id = $_GET['product_id'];

    if (!isset($product_id)) {
        header("Location: " . "/AVCShop/src/home.php");
        exit;
    } else {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Sử dụng truy vấn SQL để lấy thông tin sản phẩm, thumbnail và danh sách images
        $stmt = $conn->prepare("
            SELECT 
                products.*, 
                thumbnails.path_image AS thumbnail_path, 
                GROUP_CONCAT(images.id) AS image_ids, 
                GROUP_CONCAT(images.path_image) AS image_list
            FROM products
            LEFT JOIN thumbnails ON products.id = thumbnails.product_id
            LEFT JOIN images ON products.id = images.product_id
            WHERE products.id = :id
            GROUP BY products.id, thumbnails.path_image
        ");
        $stmt->bindParam(':id', $product_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $path_image = $result['thumbnail_path']; // Ảnh thumbnail
            $title = $result['title'];
            $price = $result['price'];
            $type = $result['type'];
            $brand = $result['brand'];
            $manufacture = $result['manufacture'];
            $material = $result['material'];
            $description = $result['description'];
            $image_ids = $result['image_ids'] ? explode(',', $result['image_ids']) : []; // Danh sách id của images
            $image_list = $result['image_list'] ? explode(',', $result['image_list']) : []; // Danh sách path_image của images
        } else {
            // ID không tồn tại trong bảng products
            header("Location: /AVCShop/src/home.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/add_product.css" />
    <title>Chỉnh Sửa Sản Phẩm</title>
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
                <li><a href="<?php echo '/AVCShop/src/edit_product.php?product_id=' . $product_id ?>">Chỉnh sửa sản phẩm</a></li>
            </ul>
        </div>

        <div class="container-sub-2">
            <div class="content">
                <h1 class="title-add-product">Chỉnh sửa sản phẩm</h1>
                <p><strong>Lưu ý:</strong> Các mục dấu <strong>màu đỏ</strong> không được bỏ trống & phải điền đầy đủ, chính xác</p>
                <form id="edit-product" onsubmit="sanitizePrice()" action="<?php echo '/AVCShop/src/edit_product.php?product_id=' . $product_id ?>" method="POST" enctype="multipart/form-data">
                    <fieldset class="info-product">
                        <legend>Thông tin sản phẩm</legend>
                        <div class="form-group">
                            <label for="id" class="form-label col-sm-2">ID<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="id" class="form-control" value="<?php echo $product_id ?>" name="id" placeholder="ID" disabled autocomplete="one-time-code">
                            </div>
                        </div>
                        <!-- Ảnh đại diện -->
                        <div class="form-group-image">
                            <label for="image" class="form-label col-sm-2">Ảnh đại diện<sup>*</sup>:</label>
                            <div class="col-sm-10 form-image">
                                <img id="image-preview" src="<?php echo $path_image ?>" alt="Preview" style="display: block; max-width: 150px; max-height: 150px;">
                                <input type="file" id="image" name="image" accept=".png, .jpg, .jpeg, .webp" autocomplete="one-time-code" onchange="change_image(event)">
                            </div>
                        </div>
                        <!-- Danh sách ảnh chi tiết -->
                        <div class="form-group-image">
                            <label for="image" class="form-label col-sm-2">Ảnh chi tiết<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <div class="image-list-scrollable" id="image-list">
                                    <?php if (!empty($image_list)) : ?>
                                        <?php foreach ($image_list as $index => $image_path) : ?>
                                            <div class="image-item">
                                                <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                                    alt="Image <?php echo $index + 1; ?>" 
                                                    class="image-thumbnail" 
                                                    data-id="<?php echo $image_ids[$index]; ?>"> <!-- Gắn id vào thẻ img -->
                                                <button type="button" class="remove-image-btn" onclick="removeImage(this)">×</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <p>Không có hình ảnh nào.</p>
                                    <?php endif; ?>
                                </div>
                                <label for="new-images">Thêm hình ảnh mới:</label>
                                <input type="file" id="new-images" multiple accept=".png, .jpg, .jpeg, .webp" onchange="previewImages(event)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="title" class="form-label col-sm-2">Tên<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="title" class="form-control" value="<?php echo $title ?>" name="title" placeholder="Tên" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="price" class="form-label col-sm-2">Giá<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="price" class="form-control" value="<?php echo number_format($price, 0, ",", ".") ?>" name="price" placeholder="Giá" autocomplete="one-time-code" oninput="input_price(event)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="type" class="form-label col-sm-2">Thể loại<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <select id="type" class="form-control" name="type">
                                    <option <?php echo ($type === 'ao' ? 'selected' : '') ?> value="ao">Áo</option>
                                    <option <?php echo ($type === 'quan' ? 'selected' : '') ?> value="quan">Quần</option>
                                    <option <?php echo ($type === 'dam-vay' ? 'selected' : '') ?> value="dam-vay">Đầm/Váy</option>
                                    <option <?php echo ($type === 'ao-khoac' ? 'selected' : '') ?> value="ao-khoac">Áo khoác</option>
                                    <option <?php echo ($type === 'do-lot' ? 'selected' : '') ?> value="do-lot">Đồ lót</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="brand" class="form-label col-sm-2">Thương hiệu<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="brand" class="form-control" name="brand" value="<?php echo $brand ?>" placeholder="Thương hiệu" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="manufacture" class="form-label col-sm-2">Sản xuất<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="manufacture" class="form-control" name="manufacture" value="<?php echo $manufacture ?>" placeholder="Sản xuất" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="material" class="form-label col-sm-2">Chất liệu<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="material" class="form-control" name="material" value="<?php echo $material ?>" placeholder="Chất liệu" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group-area">
                            <label for="description" class="form-label col-sm-2">Mô tả<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control-area" name="description" id="description" cols="30" rows="10" placeholder="Mô tả"><?php echo str_replace("<br>", "\n", $description) ?></textarea>
                            </div>
                        </div>
                    </fieldset>
                    <div class="button-submit">
                        <input type="submit" onclick="validate(event)" value="Lưu thay đổi">
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

    let removed_images = []; // Mảng ảnh bị xóa
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

        // Kiểm tra xem ảnh có ID không
        const imageIdAttr = imgElement.getAttribute('data-id');

        if (imageIdAttr) {
            // Nếu ảnh có ID, đó là ảnh đã có trong cơ sở dữ liệu, thêm vào removed_images[]
            removed_images.push(imageIdAttr);
        } else {
            // Nếu ảnh không có ID, đó là ảnh mới, xoá khỏi new_images[]
            new_images = new_images.filter(image => image.id !== imageId); // Xoá ảnh khỏi new_images
        }

        // Xoá ảnh khỏi DOM
        imageItem.remove();
    }

    document.getElementById('edit-product').addEventListener('submit', function (event) {
        event.preventDefault(); // Ngừng submit form mặc định để xử lý thêm

        const formData = new FormData(this); // Lấy tất cả dữ liệu form vào FormData

        // Gửi ảnh mới lên server
        new_images.forEach(image => {
            formData.append('new_images[]', image.file); // Gửi mỗi ảnh dưới dạng 'new_images[]'
        });

        // Gửi ảnh đã xóa
        if (removed_images.length > 0) { 
            formData.append('removed_images[]', removed_images.join(',')); // Thêm mảng ảnh đã xóa vào form
        }

        // Thực hiện gửi form dữ liệu tới server
        fetch('/AVCShop/src/edit_product.php?product_id=' + '<?php echo $product_id ?>', {
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
                // Lấy URL hiện tại
                var urlParams = new URLSearchParams(window.location.search);

                // Lấy giá trị của tham số 'product_id'
                var productId = urlParams.get('product_id');
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