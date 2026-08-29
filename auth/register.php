<?php
/**
 * Handles new user registration.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in? Send them to the dashboard instead.
if (isset($_SESSION['user_id'])) {
    header("Location: /main/public/index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php'; // gives us $pdo

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '') {
        $errors[] = "Name is required.";
    }
    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }
    if ($password === '') {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $creditsBalance = 5;

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, passwordHash, creditsBalance)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $passwordHash, $creditsBalance]);
            $newUserId = (int) $pdo->lastInsertId();

            require_once __DIR__ . '/../includes/swap_functions.php';
            recordCreditTransaction(
                $pdo,
                $newUserId,
                $creditsBalance,
                'welcome_bonus',
                'Welcome bonus — start learning on SkillExpert'
            );

            session_regenerate_id(true);
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['name']    = $name;

            header("Location: /main/public/index.php");
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = "This email has already been registered.";
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Register - SkillExpert';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/main/assets/css/auth.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/auth.css'); ?>">

<div class="auth-wrapper">
    <section class="auth-card">
        <img src="/main/assets/img/logo-icon-dark.png" alt="SkillExpert" class="auth-logo-icon">
        <h1>Create an Account</h1>
        <p class="auth-sub">Join SkillExpert and start exchanging talents today</p>

        <?php if (!empty($errors)): ?>
            <ul class="error-list">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="/main/auth/register.php" novalidate>
            <div>
                <label for="reg-name" style="margin:2px;">Full Name</label>
                <input
                    type="text"
                    id="reg-name"
                    name="name"
                    value="<?php echo htmlspecialchars($name); ?>"
                    placeholder="e.g. Alice Tan"
                    required
                    autocomplete="name"
                >
            </div>

            <div>
                <label for="reg-email" style="margin:2px;">Email Address</label>
                <input
                    type="email"
                    id="reg-email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    placeholder="you@example.com"
                    required
                    autocomplete="email"
                >
            </div>

            <div>
                <label for="reg-password" style="margin:2px;">Password <small>(min 6 chars)</small></label>
                <input
                    type="password"
                    id="reg-password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit">Create Free Account <span>→</span></button>
        </form>

        <p class="auth-switch">Already have an account? <a href="/main/auth/login.php">Log In</a></p>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
