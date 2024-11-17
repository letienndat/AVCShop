<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/model/product.php';

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new mysqli($servername, $username, $password, $dbname);

    $products = array();

    // for ($i=1; $i <= 32; $i++) { 
    //     $id = strtoupper(uniqid());
    //     $path_temp = '../temp/images/converse (' . $i . ').jpg';
    //     $path_image = '../public/images/' . $id . '.jpg';
    //     $type = null;
    //     if ($i <= 7) {
    //         $type = 'classic';
    //     } else if ($i <= 12) {
    //         $type = 'chuck_1970s';
    //     } else if ($i <= 18) {
    //         $type = 'chuck_2';
    //     } else if ($i <= 27) {
    //         $type = 'seasonal';
    //     } else {
    //         $type = 'sneaker';
    //     }
    //     copy($path_temp, $path_image);
    //     $path_image = '/AVCShop/public/images/' . $id . '.jpg';
    //     array_push($products, new Product($id, $path_image, "Converse Chuck Taylor All Star Festival Smoothie", random_int(800, 2000) * 1000, $type, "Converse", "Việt Nam", "Textile", "Thiết kế cổ cao cá tính giúp bảo vệ an toàn vùng mắt cá chân"));
    // }

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

                // Đường dẫn đầy đủ của tệp hiện tại
                $currentFile = $currentSubdirectory . DIRECTORY_SEPARATOR . $file;

                // Kiểm tra nếu là file ảnh
                if (is_file($currentFile) && preg_match('/\.(jpg|jpeg|png|gif|bmp)$/i', $file)) {
                    $id = strtoupper(uniqid());
                    // Tạo tên mới
                    $newFileName = $id . '.jpg';

                    // Đường dẫn tệp mới trong thư mục đích
                    $newFile = $destinationDirectory . DIRECTORY_SEPARATOR . $newFileName;

                    // Debug thông tin trước khi copy
                    echo "Đang xử lý tệp: $currentFile" . "<br>";
                    echo "Tệp sẽ được sao chép đến: $newFile" . "<br>";

                    // Sao chép tệp và kiểm tra lỗi
                    if (copy($currentFile, $newFile)) {
                        $path_image = '/AVCShop/public/images/products/' . $newFileName;
                        array_push($products, new Product($id, $path_image, "Converse Chuck Taylor All Star Festival Smoothie", random_int(800, 2000) * 1000, $type, "Converse", "Việt Nam", "Textile", "Thiết kế cổ cao cá tính giúp bảo vệ an toàn vùng mắt cá chân"));
                        echo "Sao chép thành công!" . "<br>";
                    } else {
                        // Nếu thất bại, hiển thị lỗi chi tiết
                        echo "Lỗi khi sao chép tệp: $currentFile -> $newFile" . "<br>";
                        if (!is_writable($newSubdirectory)) {
                            echo "Thư mục đích không ghi được: $newSubdirectory" . "<br>";
                        }
                        if (!is_readable($currentFile)) {
                            echo "Tệp nguồn không đọc được: $currentFile" . "<br>";
                        }
                    }
                }
            }
        }
    }

    echo "Hoàn tất sao chép và đổi tên ảnh!" . "<br>";

    // Chuẩn bị truy vấn INSERT
    $stmt = $conn->prepare("INSERT INTO products (id, path_image, title, price, type, brain, manufacture, material, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Thực hiện INSERT cho từng đôi giày
    foreach ($products as $product) {
        $stmt->bind_param('sssssssss', $product->id, $product->path_image, $product->title, $product->price, $product->type, $product->brain, $product->manufacture, $product->material, $product->description);
        $stmt->execute();
    }

    echo "Dữ liệu đã được thêm vào cơ sở dữ liệu thành công!" . "<br>";
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}

$conn = null;
