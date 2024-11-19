<?php
error_reporting(E_ALL); // Báo cáo tất cả các loại lỗi
ini_set('display_errors', 1); // Hiển thị lỗi ra màn hình
ini_set('display_startup_errors', 1); // Hiển thị lỗi xảy ra trong quá trình khởi động PHP

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/model/product.php';
require_once $root . '/AVCShop/model/image.php';
require_once $root . '/AVCShop/local/const.php';

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new mysqli($servername, $username, $password, $dbname);

    $sourceDirectory = '../temp/images'; // Thư mục nguồn
    $destinationDirectory = '../public/images/products'; // Thư mục đích

    // Đảm bảo thư mục đích tồn tại
    if (!file_exists($destinationDirectory)) {
        mkdir($destinationDirectory, 0777, true);
    }

    // Đọc file JSON
    $jsonFilePath = '../data/products.json'; // Đường dẫn tới file JSON
    if (!file_exists($jsonFilePath)) {
        throw new Exception("File JSON không tồn tại: $jsonFilePath");
    }
    $jsonData = file_get_contents($jsonFilePath);
    $dataTest = json_decode($jsonData, true);

    if (!$dataTest) {
        throw new Exception("Không thể giải mã file JSON.");
    }

    foreach ($dataTest as $data) {
        // Thêm sản phẩm vào bảng `products`
        $product = new Product(
            $data["title"],
            $data["type"],
            $data["brand"],
            $data["manufacture"],
            $data["material"],
            $data["description"],
            strtoupper(uniqid()),
            random_int(90, 1000) * 1000
        );

        $stmtProduct = $conn->prepare("INSERT INTO products (id, title, price, type, brand, manufacture, material, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtProduct->bind_param('ssssssss', $product->id, $product->title, $product->price, $product->type, $product->brand, $product->manufacture, $product->material, $product->description);
        $stmtProduct->execute();

        if ($stmtProduct->affected_rows > 0) {
            // Xử lý thumbnail
            if (isset($data["thumbnail"]["path_image"])) {
                $thumbnailFile = $sourceDirectory . DIRECTORY_SEPARATOR . $product->type . DIRECTORY_SEPARATOR . $data["thumbnail"]["path_image"];
                if (is_file($thumbnailFile)) {
                    $thumbnailId = strtoupper(uniqid());
                    $newThumbnailName = 'thumbnail_' . $thumbnailId . '.jpg';
                    $newThumbnailPath = $destinationDirectory . DIRECTORY_SEPARATOR . $newThumbnailName;

                    if (copy($thumbnailFile, $newThumbnailPath)) {
                        $thumbnailDbPath = '/AVCShop/public/images/products/' . $newThumbnailName;
                        $thumbnailStmt = $conn->prepare("INSERT INTO thumbnails (id, product_id, title, path_image) VALUES (?, ?, ?, ?)");
                        $thumbnailTitle = "Thumbnail sản phẩm " . $product->id;
                        $thumbnailStmt->bind_param('ssss', $thumbnailId, $product->id, $thumbnailTitle, $thumbnailDbPath);
                        $thumbnailStmt->execute();
                    }
                }
            }

            // Xử lý danh sách images
            if (isset($data["images"]) && is_array($data["images"])) {
                foreach ($data["images"] as $imageData) {
                    if (isset($imageData["path_image"])) {
                        $imageFile = $sourceDirectory . DIRECTORY_SEPARATOR . $product->type . DIRECTORY_SEPARATOR . $imageData["path_image"];
                        if (is_file($imageFile)) {
                            $imageId = strtoupper(uniqid());
                            $newImageName = 'image_' . $imageId . '.jpg';
                            $newImagePath = $destinationDirectory . DIRECTORY_SEPARATOR . $newImageName;

                            if (copy($imageFile, $newImagePath)) {
                                $imageDbPath = '/AVCShop/public/images/products/' . $newImageName;
                                $imageStmt = $conn->prepare("INSERT INTO images (id, product_id, title, path_image) VALUES (?, ?, ?, ?)");
                                $imageTitle = "Hình ảnh sản phẩm " . $product->id;
                                $imageStmt->bind_param('ssss', $imageId, $product->id, $imageTitle, $imageDbPath);
                                $imageStmt->execute();
                            }
                        }
                    }
                }
            }
        }
    }

    echo "Dữ liệu đã được thêm vào cơ sở dữ liệu thành công!" . "<br>";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}

$conn = null;
?>
