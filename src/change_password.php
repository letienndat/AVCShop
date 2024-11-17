<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/profile.css" />
    <title>Đổi Mật Khẩu</title>
</head>

<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';
if ($username_local === null) {
    header("Location: " . "/AVCShop/src/home.php");
    exit;
}
?>

<?php

$username_ = $username_local;
$old_password = $_POST['old-password'];
$new_password = $_POST['new-password'];

if (isset($old_password) && isset($new_password)) {
    try {
        // Kết nối đến cơ sở dữ liệu MySQL
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Lấy mật khẩu đã mã hóa từ cơ sở dữ liệu
        $stmt = $conn->prepare("SELECT password FROM account WHERE username = :username");
        $stmt->bindParam(':username', $username_);
        $stmt->execute();

        // Kiểm tra xem tài khoản có tồn tại không
        if ($stmt->rowCount() > 0) {
            // Lấy mật khẩu đã mã hóa từ cơ sở dữ liệu
            $storedPasswordHash = $stmt->fetchColumn();

            // Kiểm tra mật khẩu cũ với mật khẩu đã mã hóa
            if (password_verify($old_password, $storedPasswordHash)) {
                // Mã hóa mật khẩu mới trước khi lưu
                $newPasswordHash = password_hash($new_password, PASSWORD_DEFAULT);

                // Cập nhật mật khẩu mới vào cơ sở dữ liệu
                $updateQuery = "UPDATE account SET password = :new_password WHERE username = :username";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bindParam(':new_password', $newPasswordHash);
                $stmt->bindParam(':username', $username_);
                $stmt->execute();

                // Kiểm tra việc cập nhật thành công
                if ($stmt->rowCount() > 0) {
                    echo '<script>alert("Thay đổi mật khẩu thành công!")</script>';
                    echo '<script>window.location.href="/AVCShop/src/home.php"</script>';
                } else {
                    echo '<script>alert("Thay đổi mật khẩu thất bại, hãy thử lại!")</script>';
                    echo '<script>window.location.href="/AVCShop/src/change_password.php"</script>';
                }
            } else {
                // Mật khẩu cũ không đúng
                echo '<script>alert("Mật khẩu cũ không đúng, hãy thử lại!")</script>';
                echo '<script>window.location.href="/AVCShop/src/change_password.php"</script>';
            }
        } else {
            // Không tìm thấy tài khoản
            echo '<script>alert("Tài khoản không tồn tại!")</script>';
            echo '<script>window.location.href="/AVCShop/src/change_password.php"</script>';
        }
    } catch (PDOException $e) {
        echo '<script>console.log("Lỗi: ' . $e->getMessage() . '")</script>';
    }
}
?>

<body>
    <?php
    include '../inc/header.php';
    ?>

    <div class="container-signup">
        <div class="container-sub-1">
            <ul class="breadcrumb">
                <li><a href="/AVCShop/src/home.php">Trang chủ<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/profile.php">Tài khoản<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/change_password.php">Đổi mật khẩu</a></li>
            </ul>
        </div>

        <div class="container-sub-2">
            <div class="content">
                <h1 class="title-signup">Đổi Mật Khẩu</h1>
                <p><strong>Lưu ý:</strong> Các mục dấu <strong>màu đỏ</strong> không được bỏ trống & phải điền đầy đủ, chính xác</p>
                <form id="change-password" action="/AVCShop/src/change_password.php" method="POST">
                    <fieldset class="username">
                        <legend>Tên tài khoản</legend>
                        <div class="form-group">
                            <label for="username" class="form-label col-sm-2">Tên Tài Khoản<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="text" id="username" class="form-control" value="<?php echo $username_ ?>" disabled autocomplete="one-time-code">
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="password">
                        <legend>Đổi mật khẩu</legend>
                        <div class="form-group">
                            <label for="old-password" class="form-label col-sm-2">Mật Khẩu Cũ<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="password" id="old-password" class="form-control" name="old-password" placeholder="Mật Khẩu Cũ" autocomplete="one-time-code">
                            </div>
                        </div>
                        <div class="form-group pad">
                            <label for="new-password" class="form-label col-sm-2">Mật Khẩu Mới<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="password" id="new-password" class="form-control" name="new-password" placeholder="Mật Khẩu Mới" autocomplete="one-time-code">
                                <span class="note-input">Yêu cầu từ 8 ký tự trở lên (chứa a-z, A-Z, 0-9, !@#$%^&*()-_+=)</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="new-password-confirm" class="form-label col-sm-2">Nhập Lại Mật Khẩu Mới<sup>*</sup>:</label>
                            <div class="col-sm-10">
                                <input type="password" id="new-password-confirm" class="form-control" placeholder="Nhập Lại Mật Khẩu Mới" autocomplete="one-time-code">
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
                isUsername('#username'),
                isRequired('#old-password'),
                isPassword('#new-password'),
                confirmPassword('#new-password', '#new-password-confirm')
            ]
        })

        if (!res) {
            event.preventDefault()
        }
    }
</script>

<script src="/AVCShop/public/js/padding-top-body.js"></script>

</html>