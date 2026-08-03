<?php
// $isLoggedIn is set by header.php before this file is included.
// Guard defensively in case nav.php is ever included standalone.
if (!isset($isLoggedIn)) {
    $isLoggedIn = isset($_SESSION['user_id']);
}
?>
<nav>
    <ul>
        <li><a href="/main/public/index.php">Home</a></li>

        <?php if ($isLoggedIn): ?>
            <!-- Logged-in user links -->
            <li><a href="/main/public/swaps.php">My Swaps</a></li>
            <li><a href="/main/auth/logout.php">Logout</a></li>
        <?php else: ?>
            <!-- Guest user links -->
            <li><a href="/main/auth/login.php">Login</a></li>
            <li><a href="/main/auth/register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>