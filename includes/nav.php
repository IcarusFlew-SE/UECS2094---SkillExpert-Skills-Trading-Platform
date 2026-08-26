<?php
// $isLoggedIn is set by header.php before this file is included.
// Guard defensively in case nav.php is ever included standalone.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($isLoggedIn)) {
    $isLoggedIn = isset($_SESSION['user_id']);
}
$userName = $_SESSION['name'] ?? 'User';
?>
<nav class="main-nav">
    <div class="nav-container">
        <a href="/main/public/index.php" class="nav-brand">
            <img src="/main/assets/img/logo-icon-dark.png" alt="SkillExpert" class="nav-logo-icon">
            SkillExpert
        </a>

        <input type="checkbox" id="nav-toggle" class="nav-toggle-checkbox">
        <label for="nav-toggle" class="nav-toggle-label" aria-label="Toggle navigation">
            <span class="hamburger"></span>
        </label>

        <ul class="nav-menu">
            <?php if ($isLoggedIn): ?>
                <!-- Logged-in state navigation -->
                <li class="nav-item"><a href="/main/public/browse.php" class="nav-link">Browse</a></li>
                <li class="nav-item"><a href="/main/public/saved.php" class="nav-link">Saved</a></li>
                <li class="nav-item"><a href="/main/public/posting.php" class="nav-link">Post a Skill</a></li>
                <li class="nav-item"><a href="/main/public/teaching_requests.php" class="nav-link">My Teaching Requests</a></li>
                <li class="nav-item"><a href="/main/public/swaps.php" class="nav-link">My Swaps</a></li>
                <li class="nav-item nav-user-item">
                    <span class="nav-user-name"><?php echo htmlspecialchars($userName); ?></span>
                </li>
                <li class="nav-item"><a href="/main/auth/logout.php" class="nav-link btn-logout">Logout</a></li>
            <?php else: ?>
                <!-- Logged-out state navigation -->
                <li class="nav-item"><a href="/main/public/index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="/main/public/browse.php" class="nav-link">Browse</a></li>
                <li class="nav-item"><a href="/main/public/contact.php" class="nav-link">Contact</a></li>
                <li class="nav-item"><a href="/main/auth/login.php" class="nav-link">Login</a></li>
                <li class="nav-item"><a href="/main/auth/register.php" class="nav-link btn-register">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>