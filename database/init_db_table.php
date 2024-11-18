<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new mysqli($servername, $username, $password);

    // Kiểm tra kết nối
    if ($conn->connect_error) {
        die("Kết nối không thành công: " . $conn->connect_error);
    }

    // Tạo cơ sở dữ liệu
    $createDBQuery = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($createDBQuery) === TRUE) {
        echo "Cơ sở dữ liệu đã được tạo hoặc đã tồn tại.<br>";
    } else {
        echo '<span style="color: red">Lỗi khi tạo cơ sở dữ liệu: ' . $conn->error . '</span><br>';
    }

    // // Chọn cơ sở dữ liệu mới tạo
    $conn->select_db($dbname);

    // Đặt chế độ kiểm soát lỗi
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    echo "Tạo database AVCShop thành công!" . "<br>";

    // Tạo bảng products
    $createTableQuery = "CREATE TABLE IF NOT EXISTS products (
        id VARCHAR(20) PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        price INT(11) NOT NULL,
        type VARCHAR(255) NOT NULL,
        brand VARCHAR(255) NOT NULL,
        manufacture VARCHAR(255) NOT NULL,
        material VARCHAR(255) NOT NULL,
        description TEXT NOT NULL
    )";
    if ($conn->query($createTableQuery) === TRUE) {
        echo "Tạo bảng Products thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo bảng Products không thành công!' . '</span><br>';
    }

    // Tạo bảng thumbnails
    $createThumbnailsTableQuery = "CREATE TABLE IF NOT EXISTS thumbnails (
        id VARCHAR(20) PRIMARY KEY,
        product_id VARCHAR(20) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        path_image VARCHAR(255) NOT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    if ($conn->query($createThumbnailsTableQuery) === TRUE) {
        echo "Tạo bảng Thumbnails thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo bảng Thumbnails không thành công!' . '</span><br>';
    }

    // Tạo bảng images
    $createImagesTableQuery = "CREATE TABLE IF NOT EXISTS images (
        id VARCHAR(20) PRIMARY KEY,
        product_id VARCHAR(20) NOT NULL,
        title VARCHAR(255) NOT NULL,
        path_image VARCHAR(255) NOT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    if ($conn->query($createImagesTableQuery) === TRUE) {
        echo "Tạo bảng Images thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo bảng Images không thành công!' . '</span><br>';
    }

    // Tạo bảng Account
    $createTableQuery = "CREATE TABLE IF NOT EXISTS account (
        username VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin PRIMARY KEY,
        password VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
        role int(11) NOT NULL
    )";
    if ($conn->query($createTableQuery) === TRUE) {
        echo "Tạo bảng Account thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo bảng Account không thành công!' . '</span><br>';
    }

    // Tạo bảng User
    $createTableQuery = "CREATE TABLE IF NOT EXISTS user (
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        username VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin PRIMARY KEY,
        FOREIGN KEY (username) REFERENCES account(username) ON DELETE CASCADE ON UPDATE CASCADE
    )";
    if ($conn->query($createTableQuery) === TRUE) {
        echo "Tạo bảng User thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo bảng User không thành công!' . '</span><br>';
    }

    // Tạo bảng User_Product_Favorites
    $createTableQuery = "CREATE TABLE IF NOT EXISTS user_product_favorites (
        username VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin,
        product_id VARCHAR(255),
        PRIMARY KEY (username, product_id),
        FOREIGN KEY (username) REFERENCES user(username) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
    )";
    if ($conn->query($createTableQuery) === TRUE) {
        echo "Tạo bảng User_Product_Favorites thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo bảng User_Product_Favorites không thành công!' . '</span><br>';
    }

    // Tạo bảng Shop_Cart
    $createTableQuery = "CREATE TABLE IF NOT EXISTS shop_cart (
        username VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin,
        product_id VARCHAR(255),
        quantity INT(11) NOT NULL,
        time DATETIME NOT NULL,
        PRIMARY KEY (username, product_id),
        FOREIGN KEY (username) REFERENCES user(username) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
    )";
    if ($conn->query($createTableQuery) === TRUE) {
        echo "Tạo bảng Shop_Cart thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo bảng Shop_Cart không thành công!' . '</span><br>';
    }

    // Chuẩn bị truy vấn INSERT
    $username_ = "admin";
    $password_ = "admin";
    $hashedPassword = password_hash($password_, PASSWORD_DEFAULT);
    $role = 1;
    $stmt = $conn->prepare("INSERT INTO account (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username_, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo "Tạo tài khoản admin thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo tài khoản admin không thành công!' . '</span><br>';
    }

    // Chuẩn bị truy vấn INSERT
    $first_name = "Nguyễn";
    $last_name = "Tuấn Vũ";
    $username_ = "admin";
    $stmt = $conn->prepare("INSERT INTO user (first_name, last_name, username) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $first_name, $last_name, $username_);

    if ($stmt->execute()) {
        echo "Tạo thông tin cho admin thành công!" . "<br>";
    } else {
        echo '<span style="color: red">Tạo thông tin cho admin không thành công!' . '</span><br>';
    }

} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}

$conn = null;
