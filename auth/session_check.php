<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: /main/auth/login.php");
    exit; // exit - prevents the rest of the page executing after a redirect header, db runs and data leakage
}
