<?php
/**
 * Handles user login.
 *
 * GET  → display the login form.
 *         If ?registered=1 is present, show a "registration successful" notice.
 * POST → look up user by email, verify password, start session on success.
 *
 * Session contract (from A.2 — do NOT change these key names):
 *   $_SESSION['user_id']  int    — the logged-in user's primary key
 *   $_SESSION['name']     string — the logged-in user's display name
 *
 * Dependencies:
 *   config/db.php            – provides $pdo
 *   includes/header.php      – starts session, opens HTML shell
 *   includes/footer.php      – closes HTML shell
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
$error = '';        // single error string (kept vague on purpose — see below)
$email = '';        // re-populate email field on failure so user doesn't retype it

// ── POST: process the submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic presence check before hitting the database.
    if ($email === '' || $password === '') {
        $error = "Please enter your email and password.";
    } else {

        // Fetch the user row by email using a prepared statement.
        // We SELECT everything so we have id, name, and passwordHash available.
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(); // returns associative array or false

        // password_verify() compares the submitted password against the bcrypt hash.
        // SECURITY: We show the same generic message whether the email doesn't exist
        // OR the password is wrong — this prevents an attacker from enumerating
        // which email addresses are registered in the system.
        if ($user && password_verify($password, $user['passwordHash'])) {

            // Credentials are valid — establish the session.
            // Regenerate the session ID first to prevent session fixation attacks.
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['name']    = $user['name'];

            // Send the user to the dashboard
            header("Location: /main/public/index.php");
            exit;

        } else {
            // Either the email wasn't found or the password didn't match.
            // Deliberately vague — don't reveal which one failed - security purposes
            $error = "Invalid email or password.";
        }
    }
}

// ── GET (or POST with errors): render the form
require_once __DIR__ . '/../includes/header.php';
?>

<section class="auth-section">
    <h1>Login</h1>

    <!-- Login form -->

    <?php if ($error !== ''): ?>
        <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="/main/auth/login.php" novalidate>

        <label for="login-email">Email</label>
        <input
            type="email"
            id="login-email"
            name="email"
            value="<?php echo htmlspecialchars($email); ?>"
            required
            autocomplete="email"
        >

        <label for="login-password">Password</label>
        <input
            type="password"
            id="login-password"
            name="password"
            required
            autocomplete="current-password"
        >
        <!-- Password is intentionally NOT re-populated on failure: security measure -->

        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="/main/auth/register.php">Register</a></p>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
