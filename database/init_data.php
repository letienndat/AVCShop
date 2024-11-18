<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/model/product.php';
require_once $root . '/AVCShop/model/image.php';

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Tạo bảng thumbnails nếu chưa tồn tại
    $createThumbnailsTableQuery = "CREATE TABLE IF NOT EXISTS thumbnails (
        id VARCHAR(36) PRIMARY KEY,
        product_id VARCHAR(36) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        path_image VARCHAR(255) NOT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->query($createThumbnailsTableQuery);

    $products = array();
    $images = array();

    $sourceDirectory = '../temp/images'; // Thư mục nguồn
    $destinationDirectory = '../public/images/products'; // Thư mục đích

    // Đảm bảo thư mục đích tồn tại
    if (!file_exists($destinationDirectory)) {
        mkdir($destinationDirectory, 0777, true);
    }

    // Lấy danh sách các thư mục con trong thư mục nguồn
    $subdirectories = scandir($sourceDirectory);

    foreach ($subdirectories as $subdirectory) {
        // Bỏ qua các mục `.` và `..`
        if ($subdirectory === '.' || $subdirectory === '..') {
            continue;
        }

        $type = $subdirectory;

        // Đường dẫn đầy đủ của thư mục con
        $currentSubdirectory = $sourceDirectory . DIRECTORY_SEPARATOR . $subdirectory;

        // Kiểm tra nếu là thư mục
        if (is_dir($currentSubdirectory)) {
            // Lấy danh sách các tệp ảnh trong thư mục con
            $files = scandir($currentSubdirectory);

            foreach ($files as $file) {
                // Bỏ qua các mục `.` và `..`
                if ($file === '.' || $file === '..') {
                    continue;
                }

                // Tạo sản phẩm mới
                $productId = strtoupper(uniqid()); // Tạo ID duy nhất cho sản phẩm
                $product = new Product($productId, "Converse Chuck Taylor All Star Festival Smoothie", random_int(800, 2000) * 1000, $type, "Converse", "Việt Nam", "Textile", "Thiết kế cổ cao cá tính giúp bảo vệ an toàn vùng mắt cá chân");
                array_push($products, $product);
    
                // Chuẩn bị truy vấn INSERT cho bảng `products`
                $stmtProduct = $conn->prepare("INSERT INTO products (id, title, price, type, brand, manufacture, material, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtProduct->bind_param('ssssssss', $product->id, $product->title, $product->price, $product->type, $product->brand, $product->manufacture, $product->material, $product->description);
                $stmtProduct->execute();
    
                // Kiểm tra xem sản phẩm đã được thêm thành công chưa và lấy `product_id`
                if ($stmtProduct->affected_rows > 0) {
    
                    // Tiến hành thêm vào bảng thumbnails nếu sản phẩm tồn tại
                    $thumbnailSaved = false; // Kiểm tra đã lưu thumbnail hay chưa

                    // Đường dẫn đầy đủ của tệp hiện tại
                    $currentFile = $currentSubdirectory . DIRECTORY_SEPARATOR . $file;

                    // Kiểm tra nếu là file ảnh
                    if (is_file($currentFile) && preg_match('/\.(jpg|jpeg|png|gif|bmp)$/i', $file)) {
                        $thumbnailId = strtoupper(uniqid());
                        $newFileNameThumbnail = 'thumbnail_' . $thumbnailId . '.jpg'; // Tên ảnh mới cho thumbnail

                        // Đường dẫn tệp mới trong thư mục đích
                        $newFileThumbnail = $destinationDirectory . DIRECTORY_SEPARATOR . $newFileNameThumbnail;

                        // Sao chép tệp
                        if (copy($currentFile, $newFileThumbnail)) {
                            $path_image_thumbnail = '/AVCShop/public/images/products/' . $newFileNameThumbnail;

                            // Lưu thumbnail vào bảng `thumbnails` (chỉ lưu lần đầu)
                            if (!$thumbnailSaved) {
                                // Lưu thumbnail
                                $thumbnailTitle = "Thumbnail sản phẩm" . $productId;
                                $thumbnailStmt = $conn->prepare("INSERT INTO thumbnails (id, product_id, title, path_image) VALUES (?, ?, ?, ?)");
                                $thumbnailStmt->bind_param('ssss', $thumbnailId, $productId, $thumbnailTitle, $path_image_thumbnail);
                                $thumbnailStmt->execute();
                                $thumbnailSaved = true;
                            }

                            // Tạo thêm 3 bản sao ảnh trong bảng `images` với ID là UUID
                            for ($i = 1; $i <= 10; $i++) {
                                $imageId = strtoupper(uniqid());
                                $newFileNameImage = 'image_' . $imageId . '.jpg'; // Tên ảnh mới cho image

                                // Đường dẫn tệp mới trong thư mục đích
                                $newFileImage = $destinationDirectory . DIRECTORY_SEPARATOR . $newFileNameImage;

                                // Sao chép tệp
                                if (copy($currentFile, $newFileImage)) {
                                    $path_image = '/AVCShop/public/images/products/' . $newFileNameImage;

                                    // Lưu image vào array images
                                    $imageTitle = "Hình ảnh sản phẩm " . $productId;
                                    $image = new Image($imageId, $productId, $imageTitle, $path_image); // ID của ảnh sao chép = UUID
                                    $images[] = $image;
                                }
                            }
                        } else {
                            echo "Lỗi khi sao chép tệp: $currentFile -> $newFile" . "<br>";
                        }
                    } 
                } else {
                    echo "Không thể thêm sản phẩm vào bảng `products`." . "<br>";
                }
            }
        }
    }

    echo "Hoàn tất sao chép và đổi tên ảnh!" . "<br>";

    // Chuẩn bị truy vấn INSERT cho bảng `images`
    $stmtImage = $conn->prepare("INSERT INTO images (id, product_id, title, path_image) VALUES (?, ?, ?, ?)");

    foreach ($images as $image) {
        $stmtImage->bind_param('ssss', $image->id, $image->product_id, $image->title, $image->path_image);
        $stmtImage->execute();
    }

    echo "Dữ liệu đã được thêm vào cơ sở dữ liệu thành công!" . "<br>";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}

$conn = null;
?>