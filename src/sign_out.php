<?php
@session_start();
@session_destroy();
echo '<script>window.location.href="/AVCShop/src/home.php"</script>';
?>