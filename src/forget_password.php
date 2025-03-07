<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../inc/head.php'; ?>
    <link rel="stylesheet" href="../public/css/sign_up.css" />
    <title>Quên Mật Khẩu</title>
</head>

<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';
include '../service/redirect.php';
?>

<?php
@$username_ = $_POST['username'];

if (isset($username_)) {
    echo '<script>alert("Tính năng đang được triển khai!")</script>';
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
                <li><a href="/AVCShop/src/sign_in.php">Tài khoản<i class="fa fa-angle-right"></i></a></li>
                <li><a href="/AVCShop/src/forget_password.php">Quên mật khẩu</a></li>
            </ul>
        </div>

        <div class="container-sub-2">
            <div class="content">
                <h1 class="title-signup">Bạn Quên Mật Khẩu?</h1>
                <p>Nhập tên tài khoản đã đăng ký. Bấm nút <strong>Tiếp tục</strong> bạn sẽ được nhận lại mật khẩu</p>
                <form action="" method="POST">
                    <fieldset class="username">
                        <legend>Nhập tên tài khoản</legend>
                        <div class="form-group">
                            <label for="username" class="form-label col-sm-2">Tên Tài Khoản:</label>
                            <div class="col-sm-10">
                                <input type="text" id="username" class="form-control" name="username" placeholder="Tên Tài Khoản" required>
                            </div>
                        </div>
                    </fieldset>
                    <div class="button-submit">
                        <input type="submit" value="Tiếp tục">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    include '../inc/footer.php';
    ?>
</body>

<script src="/AVCShop/public/js/pin_header.js"></script>

</html>