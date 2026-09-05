<?php
/**
 * My Skills dashboard — owner-only listing management with edit and delete.
 */
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$pageTitle = 'My Skills - SkillExpert';
$currentUserId = (int) $_SESSION['user_id'];
$editingId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editingSkill = null;

if ($editingId) {
    $stmt = $pdo->prepare('SELECT * FROM skills WHERE id = ? AND userId = ? LIMIT 1');
    $stmt->execute([$editingId, $currentUserId]);
    $editingSkill = $stmt->fetch();
    if (!$editingSkill) {
        setFlash('error', 'That skill was not found or you do not have permission to edit it.');
        header('Location: /main/public/my_skills.php');
        exit;
    }
}

$stmt = $pdo->prepare(
    "SELECT sk.*,
            COUNT(DISTINCT sr.id) AS requestCount,
            COUNT(DISTINCT CASE WHEN sr.status IN ('pending', 'accepted') THEN sr.id END) AS activeRequestCount,
            COUNT(DISTINCT ss.id) AS savedCount
     FROM skills sk
     LEFT JOIN swapRequests sr ON sr.skillId = sk.id
     LEFT JOIN savedSkills ss ON ss.skillId = sk.id
     WHERE sk.userId = ?
     GROUP BY sk.id
     ORDER BY sk.createdAt DESC"
);
$stmt->execute([$currentUserId]);
$mySkills = $stmt->fetchAll();
$flash = getAndClearFlash();
$categories = ['Programming', 'Design', 'Language', 'Music', 'Sports', 'Academic', 'Other'];

// Compute summary metrics for the dashboard banner
$totalSkills = count($mySkills);
$totalRequests = 0;
$totalActive = 0;
$totalSaved = 0;
foreach ($mySkills as $s) {
    $totalRequests += (int) ($s['requestCount'] ?? 0);
    $totalActive += (int) ($s['activeRequestCount'] ?? 0);
    $totalSaved += (int) ($s['savedCount'] ?? 0);
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/main/assets/css/my-skills.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/my-skills.css'); ?>">

<main class="my-skills-page">
    <div class="my-skills-container">

        <!-- Header / Hero Section -->
        <header class="my-skills-hero">
            <div class="my-skills-hero-content">
                <div class="hero-badge">
                    <span class="badge-dot"></span>
                    <span>Teacher Dashboard</span>
                </div>
                <h1 class="my-skills-title">My Skills</h1>
                <p class="my-skills-subtitle">
                    Manage your skill listings, monitor swap requests, and keep your offerings up to date.
                </p>
                <div class="my-skills-hero-actions">
                    <a href="/main/public/posting.php" class="btn btn-hero-cta">
                        + Post a New Skill
                    </a>
                    <a href="/main/public/swaps.php" class="btn btn-ghost-hero">
                        View Incoming Swaps &rarr;
                    </a>
                </div>
            </div>

            <!-- Stats Ribbon -->
            <?php if (!empty($mySkills)): ?>
                <div class="my-skills-stats">
                    <div class="stat-card">
                        <span class="stat-num"><?php echo $totalSkills; ?></span>
                        <span class="stat-label">Active Listings</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-num"><?php echo $totalRequests; ?></span>
                        <span class="stat-label">Swap Inquiries</span>
                    </div>
                    <div class="stat-card stat-highlight">
                        <span class="stat-num"><?php echo $totalActive; ?></span>
                        <span class="stat-label">Active Exchanges</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-num"><?php echo $totalSaved; ?></span>
                        <span class="stat-label">Wishlist Saves</span>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <!-- Flash messages -->
        <?php if ($flash): ?>
            <div class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['text']); ?>
            </div>
        <?php endif; ?>

        <!-- Edit Skill Form Section (shown when ?edit=id) -->
        <?php if ($editingSkill): ?>
            <section class="edit-skill-section" id="edit-skill">
                <div class="edit-card-header">
                    <div class="edit-header-badge">Editing Listing</div>
                    <h2>Update "<?php echo htmlspecialchars($editingSkill['title']); ?>"</h2>
                    <p>Make changes below and save to update your skill profile immediately.</p>
                </div>
                <form method="POST" action="/main/actions/skill_update.php" class="edit-skill-form" data-validate-skill-form>
                    <input type="hidden" name="skill_id" value="<?php echo (int) $editingSkill['id']; ?>">
                    
                    <div class="form-group">
                        <label for="title">Skill Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" maxlength="150" required value="<?php echo htmlspecialchars($editingSkill['title']); ?>" placeholder="e.g. Master React & Modern Web Development">
                    </div>

                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" class="form-control" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $editingSkill['category'] === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Skill Description <span class="required">*</span></label>
                        <textarea id="description" name="description" class="form-control" maxlength="1000" rows="5" required placeholder="Describe what you will teach, session formats, and experience level..."><?php echo htmlspecialchars($editingSkill['description']); ?></textarea>
                        <span class="form-hint">Up to 1000 characters. Be concise and engaging.</span>
                    </div>

                    <div class="edit-form-actions">
                        <button type="submit" class="btn btn-save">✓ Save Changes</button>
                        <a href="/main/public/my_skills.php" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <!-- Skill Cards Grid -->
        <section class="my-skills-content">
            <?php if (empty($mySkills)): ?>
                <div class="empty-state-container">
                    <div class="empty-state-icon">✦</div>
                    <h2>No Skills Posted Yet</h2>
                    <p>You haven't shared any skills with the community yet. Create your first listing to start exchanging knowledge with peers.</p>
                    <a href="/main/public/posting.php" class="btn btn-hero-cta empty-cta">
                        Post Your First Skill &rarr;
                    </a>
                </div>
            <?php else: ?>
                <div class="my-skills-grid">
                    <?php foreach ($mySkills as $skill): ?>
                        <article class="my-skill-card">
                            <div class="card-inner">
                                <div class="card-top-bar">
                                    <span class="category-pill category-<?php echo strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $skill['category'])); ?>">
                                        <?php echo htmlspecialchars($skill['category']); ?>
                                    </span>
                                    <span class="bookmark-stat" title="<?php echo (int) $skill['savedCount']; ?> users bookmarked this skill">
                                        <svg class="stat-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                        <?php echo (int) $skill['savedCount']; ?>
                                    </span>
                                </div>

                                <h3 class="card-title">
                                    <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>">
                                        <?php echo htmlspecialchars($skill['title']); ?>
                                    </a>
                                </h3>

                                <p class="card-desc">
                                    <?php echo htmlspecialchars(mb_strimwidth($skill['description'], 0, 140, '...')); ?>
                                </p>

                                <div class="card-metrics-strip">
                                    <div class="metric-chip" title="Total swap requests received">
                                        <span class="metric-icon">⇄</span>
                                        <span><?php echo (int) $skill['requestCount']; ?> requests</span>
                                    </div>
                                    <?php if ((int) $skill['activeRequestCount'] > 0): ?>
                                        <div class="metric-chip active-chip" title="Active ongoing swaps">
                                            <span class="pulse-dot"></span>
                                            <span><?php echo (int) $skill['activeRequestCount']; ?> active</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="metric-chip idle-chip">
                                            <span>0 active</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ((int) $skill['activeRequestCount'] > 0 || (int) $skill['savedCount'] > 0): ?>
                                    <div class="impact-notice">
                                        <span class="notice-icon">⚠</span>
                                        <span>Active swaps or saves linked. Deleting removes this listing, clears saves, and closes active requests.</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer-actions">
                                <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>" class="btn-card-action btn-view" title="Preview public skill page">
                                    View
                                </a>
                                <a href="/main/public/my_skills.php?edit=<?php echo (int) $skill['id']; ?>#edit-skill" class="btn-card-action btn-edit" title="Edit this skill">
                                    ✎ Edit
                                </a>
                                <form method="POST" action="/main/actions/skill_delete.php" class="inline-form">
                                    <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                                    <button type="submit" class="btn-card-action btn-delete" data-confirm="Delete this skill permanently? This removes it for other users too, including saved entries, comments, reviews, and related swap requests.">
                                        ✕ Delete
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<script src="/main/assets/js/skills-posting.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/skills-posting.js'); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
