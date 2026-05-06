<?php
session_start();

if (isset($_SESSION['pending_checkout'])) {
    unset($_SESSION['pending_checkout']);
}

header('Location: cart.php');
exit;
?>