<?php
/**
 * Handles new user registration.
 *
 * GET  → display the registration form
 * POST → validate input, hash the password, insert a new users row,
 *         then redirect to login on success.
 *
 * Dependencies:
 *   config/db.php   – provides $pdo (PDO connection)
 *   includes/header.php / footer.php – shared page shell
 */

// ── Bootstrap
// Start session before header.php (which also guards session_start internally).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in? Send them to the dashboard instead.
if (isset($_SESSION['user_id'])) {
    header("Location: /main/public/index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php'; // gives us $pdo

// ── Initialise state
$errors = [];          // validation error messages shown on the form
$name   = '';          // re-populate fields so the user doesn't retype everything
$email  = '';

// ── POST: process the submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Trim whitespace so "  " doesn't sneak through as a valid value.
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // ── Server-side validation
    // All three fields are required.
    if ($name === '') {
        $errors[] = "Name is required.";
    }
    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Catches obvious typos like "user@" before we even hit the database.
        $errors[] = "Enter a valid email address.";
    }
    if ($password === '') {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        // Minimum length check — keeps the password column from storing garbage.
        $errors[] = "Password must be at least 6 characters.";
    }

    // ── Insert into database (only if no validation errors)
    if (empty($errors)) {

        // Hash the password with bcrypt (PASSWORD_DEFAULT).
        // The resulting string starts with $2y$10$ — never store plaintext.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // New registrations start with 5 credits.
        $creditsBalance = 5;

        try {
            // Prepared statement – prevents SQL injection via placeholders
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, passwordHash, creditsBalance)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $passwordHash, $creditsBalance]);

            // Auto-login the user after registration
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['name']    = $name;

            // Send the user to the dashboard
            header("Location: /main/public/index.php");
            exit;

        } catch (PDOException $e) {
            // MySQL error 23000 = UNIQUE constraint violation (duplicate email).
            // Catch it here and show a friendly message instead of a crash page.
            if ($e->getCode() === '23000') {
                $errors[] = "This email has already been registered";
            } else {
                // Unexpected database error — re-throw so it surfaces during dev.
                throw $e;
            }
        }
    }
}

// ── GET (or POST with errors): render the form via URL or submitted data
require_once __DIR__ . '/../includes/header.php';
?>

<section class="auth-section">
    <h1>Create an Account</h1>

    <?php if (!empty($errors)): ?>
        <!-- Validation error list — shown when any check above fails -->
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="/main/auth/register.php" novalidate>

        <label for="reg-name">Name</label>
        <input
            type="text"
            id="reg-name"
            name="name"
            value="<?php echo htmlspecialchars($name); ?>"
            required
            autocomplete="name"
        >

        <label for="reg-email">Email</label>
        <input
            type="email"
            id="reg-email"
            name="email"
            value="<?php echo htmlspecialchars($email); ?>"
            required
            autocomplete="email"
        >

        <label for="reg-password">Password <small>(*minimum 6 characters*)</small></label>
        <input
            type="password"
            id="reg-password"
            name="password"
            required
            autocomplete="new-password"
        >
        <!-- Password is intentionally NOT re-populated on failure: security measure -->

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="/main/auth/login.php">Login</a></p>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
