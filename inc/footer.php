<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/local/const.php';
?>

<footer>
    <div class="footer-top"></div>
    <div class="footer-center">
        <div class="footer-center-sub">
            <div class="footer-content">
                <strong>
                    <?php
                        echo AppConstants::NAME_PROJECT;
                    ?>
                </strong>
                <ul class="ul-member">
                    <li class="li-name-member">
                        <?php
                            echo AppConstants::FULL_NAME_OWNER;
                        ?>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-center-sub">
            <div class="footer-content">
                <div class="module">
                    <h3 class="contact-title">HỖ TRỢ THANH TOÁN</h3>
                    <div class="icon-pay">
                        <i class="fa-brands fa-cc-visa"></i>
                        <i class="fa-brands fa-cc-mastercard"></i>
                        <i class="fa-brands fa-cc-paypal"></i>
                    </div>
                </div>
                <div class="module">
                    <h3 class="contact-title">FOLLOW US</h3>
                    <div class="icon-follow">
                        <i class="fa-brands fa-square-facebook"></i>
                        <i class="fa-brands fa-square-instagram"></i>
                        <i class="fa-brands fa-youtube"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-botton">
        <span>
            ©<?php
                echo AppConstants::NAME_PROJECT . " - " . AppConstants::FULL_NAME_OWNER;
            ?>
        </span>
    </div>
</footer>