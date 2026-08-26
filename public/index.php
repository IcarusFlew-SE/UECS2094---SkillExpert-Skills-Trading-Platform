<?php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'SkillExpert - Trade Skills, Not Money';

$stmt = $pdo->query(
    'SELECT skills.*, users.name AS teacher_name
    FROM skills 
    JOIN users ON skills.userId = users.id
    ORDER BY skills.createdAt DESC LIMIT 6'
);
$featuredSkills = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/main/assets/css/home.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/home.css'); ?>">

<div class="homepage">

    <!-- ==========================================
         HERO SECTION (Matching Post a Skill aesthetic)
         ========================================== -->
    <section class="home-hero">
        <div class="home-hero-glow"></div>
        <div class="home-hero-container">
            <div class="home-hero-content">
                <div class="hero-eyebrow">
                    ✦ Peer-to-Peer Skills Exchange
                </div>
                <h1>
                    Trade your <span>talent</span>.<br>
                    Learn anything.
                </h1>
                <p class="home-hero-sub">
                    Why pay for lessons when you can exchange what you already know? Swap hours, master new crafts, and grow with real people.
                </p>
                <div class="home-hero-actions">
                    <a href="/main/public/browse.php" class="btn-hero-primary">
                        Browse Skills <span>→</span>
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="/main/auth/register.php" class="btn-hero-secondary">
                            Join Community Free
                        </a>
                    <?php else: ?>
                        <a href="/main/public/posting.php" class="btn-hero-secondary">
                            + Post a Skill
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Interactive Live Exchange Visual -->
            <div class="home-hero-visual">
                <div class="hero-interactive-card">
                    <div class="hero-card-header">
                        <span class="hero-card-badge">Skill Exchange</span>
                        <span class="hero-live-indicator">Active Swapping</span>
                    </div>

                    <div class="hero-trade-flow">
                        <div class="trade-item">
                            <div class="trade-avatar">🎸</div>
                            <div class="trade-info">
                                <div class="trade-name">Alice Tan</div>
                                <div class="trade-skill">Teaching: Guitar Lessons</div>
                            </div>
                        </div>

                        <div class="trade-arrow">⇅</div>

                        <div class="trade-item">
                            <div class="trade-avatar">🇪🇸</div>
                            <div class="trade-info">
                                <div class="trade-name">Ben Osman</div>
                                <div class="trade-skill">Teaching: Conversational Spanish</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================
         MAIN CONTENT CONTAINER
         ========================================== -->
    <div class="container">

        <!-- Featured Skills Section -->
        <section class="featured-skills-section" style="margin-top: 1rem;">
            <div class="section-head-wrap">
                <div>
                    <h2>Featured Skills</h2>
                    <p>Discover recent talents shared by members across the platform</p>
                </div>
                <a href="/main/public/browse.php" class="see-all-link">
                    Explore All Skills <span>→</span>
                </a>
            </div>

            <?php if (empty($featuredSkills)): ?>
                <div class="empty-state">
                    <p>No skills have been posted yet.</p>
                    <p style="margin-top: 0.5rem;"><a href="/main/public/posting.php">Be the first to post a skill and kick off the community!</a></p>
                </div>
            <?php else: ?>
                <div class="skills-grid">
                    <?php foreach ($featuredSkills as $skill): ?>
                        <article class="skill-card">
                            <div>
                                <div class="skill-card-top">
                                    <span class="skill-cat-pill"><?php echo htmlspecialchars($skill['category']); ?></span>
                                </div>
                                <h3 class="skill-card-title">
                                    <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>">
                                        <?php echo htmlspecialchars($skill['title']); ?>
                                    </a>
                                </h3>
                                <p class="skill-card-desc">
                                    <?php echo htmlspecialchars($skill['description']); ?>
                                </p>
                            </div>
                            <div class="skill-card-footer">
                                <div class="skill-teacher-meta">
                                    <div class="teacher-avatar-sm">
                                        <?php echo htmlspecialchars(mb_substr($skill['teacher_name'], 0, 1)); ?>
                                    </div>
                                    <span class="teacher-name-sm"><?php echo htmlspecialchars($skill['teacher_name']); ?></span>
                                </div>
                                <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>" class="skill-card-action">
                                    View <span>→</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- How It Works 3-Step Section -->
        <section class="home-how-it-works">
            <div style="text-align: center; max-width: 600px; margin: 0 auto;">
                <span class="skill-cat-pill">Simple 3 Steps</span>
                <h2 style="font-size: 2.2rem; font-weight: 800; margin-top: 0.75rem; letter-spacing: -0.03em;">How Skill Trading Works</h2>
                <p style="color: var(--text-secondary); margin-top: 0.5rem;">No transaction fees, no payments. Just mutual learning.</p>
            </div>

            <div class="how-steps-grid">
                <div class="step-card">
                    <div class="step-icon-wrap">1</div>
                    <h3>Post Your Skill</h3>
                    <p>Tell the community what you know — whether it's Python coding, playing piano, baking, or fitness.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon-wrap">2</div>
                    <h3>Request an Exchange</h3>
                    <p>Find someone with a skill you want to acquire and send a swap request proposing an exchange.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon-wrap">3</div>
                    <h3>Learn & Review</h3>
                    <p>Connect, schedule your sessions, complete the exchange, and review your experience.</p>
                </div>
            </div>
        </section>

        <!-- Bottom CTA Banner -->
        <section class="home-cta-banner">
            <h2>Ready to start trading skills?</h2>
            <p>Join our community today and turn your talents into endless learning opportunities.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="/main/auth/register.php" class="btn-hero-primary">Create Your Free Account</a>
                    <a href="/main/public/browse.php" class="btn-hero-secondary">Explore Skills</a>
                <?php else: ?>
                    <a href="/main/public/posting.php" class="btn-hero-primary">Post a Skill Now</a>
                    <a href="/main/public/browse.php" class="btn-hero-secondary">Browse Listings</a>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
