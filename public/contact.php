<?php
/**
 * Contact Page — static contact details + a real working contact form.
 * No login required; works for visitors and logged-in users alike.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/swap_functions.php'; // getAndClearFlash()

$pageTitle = 'Contact Us - SkillExpert';
$flash     = getAndClearFlash();

// Pre-fill name/email for a logged-in visitor so they don't have to retype them.
$prefillName  = $_SESSION['name'] ?? '';
$prefillEmail = '';
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([(int) $_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row) {
        $prefillEmail = $row['email'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container contact-page">
    <h1>Contact Us</h1>
    <p>Questions, feedback, or something not working right? Reach out — we'd love to hear from you.</p>

    <?php if ($flash): ?>
        <p class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </p>
    <?php endif; ?>

    <div class="contact-layout">
        <div class="contact-info">
            <h2>Get in touch</h2>

            <p class="contact-line">
                <strong>Email:</strong>
                <a href="mailto:hello@skillexpert.example">hello@skillexpert.example</a>
            </p>
            <p class="contact-line">
                <strong>Phone:</strong>
                <a href="tel:+60123456789">+60 12-345 6789</a>
            </p>
            <p class="contact-line">
                <strong>Address:</strong>
                Universiti Tunku Abdul Rahman, Kampar Campus, Perak, Malaysia
            </p>

            <h3>Follow us</h3>
            <ul class="social-links">
                <li><a href="#" target="_blank" rel="noopener">Instagram</a></li>
                <li><a href="#" target="_blank" rel="noopener">Twitter / X</a></li>
                <li><a href="#" target="_blank" rel="noopener">Facebook</a></li>
            </ul>
        </div>

        <div class="contact-form-card">
            <h2>Send a message</h2>
            <form method="POST" action="/main/actions/contact_submit.php" class="contact-form">
                <label for="contact-name">Name</label>
                <input
                    type="text"
                    id="contact-name"
                    name="name"
                    value="<?php echo htmlspecialchars($prefillName); ?>"
                    maxlength="100"
                    required
                >

                <label for="contact-email">Email</label>
                <input
                    type="email"
                    id="contact-email"
                    name="email"
                    value="<?php echo htmlspecialchars($prefillEmail); ?>"
                    maxlength="150"
                    required
                >

                <label for="contact-subject">Subject (optional)</label>
                <input type="text" id="contact-subject" name="subject" maxlength="150">

                <label for="contact-message">Message</label>
                <textarea
                    id="contact-message"
                    name="message"
                    maxlength="2000"
                    rows="5"
                    required
                ></textarea>

                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
