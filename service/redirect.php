<?php
session_start();
if ($username_local !== null) {
    header("Location: " . "/AVCShop/src/home.php");
    exit;
}
