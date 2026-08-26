<?php
/**
 * Handles user login.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in? No need to show the login form.
if (isset($_SESSION['user_id'])) {
    header("Location: /main/public/index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php'; // gives us $pdo

// ── Initialise state
$error = '';
$email = '';

// ── POST: process the submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['passwordHash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['name']    = $user['name'];
            header("Location: /main/public/index.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}

$pageTitle = 'Login - SkillExpert';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/main/assets/css/auth.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/auth.css'); ?>">

<div class="auth-wrapper">
    <section class="auth-card">
        <img src="/main/assets/img/logo-icon-dark.png" alt="SkillExpert" class="auth-logo-icon">
        <h1>Welcome Back</h1>
        <p class="auth-sub">Log in to manage your skills and swap requests</p>

        <?php if ($error !== ''): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="/main/auth/login.php" novalidate>
            <div>
                <label for="login-email" style="margin:2px;">Email Address</label>
                <input
                    type="email"
                    id="login-email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    placeholder="you@example.com"
                    required
                    autocomplete="email"
                >
            </div>

            <div>
                <label for="login-password" style="margin:2px;">Password</label>
                <input
                    type="password"
                    id="login-password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit">Log In <span>→</span></button>
        </form>

        <p class="auth-switch">Don't have an account? <a href="/main/auth/register.php">Create Account</a></p>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
