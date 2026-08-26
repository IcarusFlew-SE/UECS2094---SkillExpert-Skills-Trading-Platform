<?php
/**
 * Contact Page — static contact details + working contact form.
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

<link rel="stylesheet" href="/main/assets/css/contact.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/contact.css'); ?>">

<section class="container contact-page">

    <div class="contact-page-header">
        <h1>Get in Touch</h1>
        <p>Have questions about skill swapping, feedback, or need assistance? We're here to help.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </div>
    <?php endif; ?>

    <div class="contact-layout">

        <!-- Contact Details Card -->
        <div class="contact-info-card">
            <h2>Contact Information</h2>

            <div class="contact-method-list">
                <div class="contact-method-item">
                    <div class="contact-method-icon">✉</div>
                    <div class="contact-method-detail">
                        <strong>Email Us</strong>
                        <a href="mailto:hello@skillexpert.example">hello@skillexpert.example</a>
                    </div>
                </div>

                <div class="contact-method-item">
                    <div class="contact-method-icon">📞</div>
                    <div class="contact-method-detail">
                        <strong>Call Us</strong>
                        <a href="tel:+60123456789">+60 12-345 6789</a>
                    </div>
                </div>

                <div class="contact-method-item">
                    <div class="contact-method-icon">📍</div>
                    <div class="contact-method-detail">
                        <strong>Location</strong>
                        <span>Universiti Tunku Abdul Rahman, Kampar Campus, Perak, Malaysia</span>
                    </div>
                </div>
            </div>

            <div class="contact-social-section">
                <h3>Follow Our Community</h3>
                <div class="social-pill-list">
                    <a href="#" class="social-pill" target="_blank" rel="noopener">Instagram</a>
                    <a href="#" class="social-pill" target="_blank" rel="noopener">Twitter / X</a>
                    <a href="#" class="social-pill" target="_blank" rel="noopener">LinkedIn</a>
                    <a href="#" class="social-pill" target="_blank" rel="noopener">Discord</a>
                </div>
            </div>
        </div>

        <!-- Contact Form Card -->
        <div class="contact-form-card">
            <h2>Send a Direct Message</h2>
            <form method="POST" action="/main/actions/contact_submit.php" class="contact-form">
                <div>
                    <label for="contact-name">Your Full Name</label>
                    <input
                        type="text"
                        id="contact-name"
                        name="name"
                        value="<?php echo htmlspecialchars($prefillName); ?>"
                        maxlength="100"
                        placeholder="e.g. Alice Tan"
                        required
                        style="width: 100%; margin-top: 0.5rem;"
                    >
                </div>

                <div>
                    <label for="contact-email">Your Email Address</label>
                    <input
                        type="email"
                        id="contact-email"
                        name="email"
                        value="<?php echo htmlspecialchars($prefillEmail); ?>"
                        maxlength="150"
                        placeholder="you@example.com"
                        required
                        style="width: 100%; margin-top: 0.5rem;"
                    >
                </div>

                <div>
                    <label for="contact-subject">Subject (Optional)</label>
                    <input 
                        type="text" 
                        id="contact-subject" 
                        name="subject" 
                        maxlength="150"
                        placeholder="What is this regarding?"
                        style="width: 100%; margin-top: 0.5rem;"
                    >
                </div>

                <div>
                    <label for="contact-message">Your Message</label>
                    <textarea
                        id="contact-message"
                        name="message"
                        maxlength="2000"
                        placeholder="How can we help you today?"
                        required
                        style="width: 100%; margin-top: 0.5rem;"
                    ></textarea>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
                        Send Message <span>→</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
