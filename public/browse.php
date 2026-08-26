<?php
/**
 * Browse Skills Catalog
 * Displays all skills with category filters, instant client-side search,
 * and teacher information.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Fetch all skills with teacher names
$stmt = $pdo->query(
    "SELECT sk.*, u.name AS teacherName,
     (SELECT COUNT(*) FROM swapRequests sr WHERE sr.skillId = sk.id AND sr.status = 'completed') AS completedSwaps
     FROM skills sk
     JOIN users u ON sk.userId = u.id
     ORDER BY sk.createdAt DESC"
);
$skills = $stmt->fetchAll();

// Get unique categories for filter pills
$categories = ['All', 'Programming', 'Design', 'Language', 'Music', 'Sports', 'Academic', 'Other'];

$pageTitle = 'Browse Skills - SkillExpert';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/main/assets/css/browse.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/browse.css'); ?>">

<div class="browse-page">

    <!-- ==========================================
         BROWSE HERO & SEARCH BAR
         ========================================== -->
    <section class="browse-hero">
        <div class="browse-hero-content">
            <h1>Explore <span>Skills</span> to Trade</h1>
            <p>Discover talents shared by the community. Find a skill you want to learn and propose an exchange today.</p>

            <div class="browse-search-box">
                <span class="browse-search-icon">🔍</span>
                <input 
                    type="text" 
                    id="browse-search" 
                    class="browse-search-input" 
                    placeholder="Search by skill name, topic, or teacher..." 
                    autocomplete="off"
                >
            </div>
        </div>
    </section>

    <!-- ==========================================
         MAIN CATALOG CONTAINER
         ========================================== -->
    <div class="container">

        <!-- Category Filter Pills -->
        <div class="category-filter-bar" id="category-filters">
            <?php foreach ($categories as $idx => $cat): ?>
                <button 
                    type="button" 
                    class="filter-pill <?php echo $idx === 0 ? 'active' : ''; ?>" 
                    data-filter="<?php echo htmlspecialchars($cat); ?>"
                >
                    <?php echo htmlspecialchars($cat); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="browse-count-label">
            Showing <strong id="visible-count"><?php echo count($skills); ?></strong> skill<?php echo count($skills) === 1 ? '' : 's'; ?> available
        </div>

        <?php if (empty($skills)): ?>
            <div class="empty-state">
                <p>No skills have been listed on SkillExpert yet.</p>
                <p style="margin-top: 0.5rem;"><a href="/main/public/posting.php">Post the first skill and start exchanging!</a></p>
            </div>
        <?php else: ?>
            <div class="browse-grid" id="skills-grid">
                <?php foreach ($skills as $skill): ?>
                    <article 
                        class="browse-card" 
                        data-category="<?php echo htmlspecialchars($skill['category']); ?>"
                        data-title="<?php echo htmlspecialchars($skill['title']); ?>"
                        data-desc="<?php echo htmlspecialchars($skill['description']); ?>"
                        data-teacher="<?php echo htmlspecialchars($skill['teacherName']); ?>"
                    >
                        <div>
                            <div class="browse-card-header">
                                <span class="skill-cat-pill"><?php echo htmlspecialchars($skill['category']); ?></span>
                                <?php if (!empty($skill['completedSwaps']) && (int)$skill['completedSwaps'] > 0): ?>
                                    <span class="status-badge status-completed">✓ <?php echo (int)$skill['completedSwaps']; ?> Swapped</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="browse-card-title">
                                <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>">
                                    <?php echo htmlspecialchars($skill['title']); ?>
                                </a>
                            </h3>

                            <p class="browse-card-desc">
                                <?php echo htmlspecialchars($skill['description']); ?>
                            </p>
                        </div>

                        <div class="browse-card-footer">
                            <div class="browse-teacher-info">
                                <div class="browse-teacher-avatar">
                                    <?php echo htmlspecialchars(mb_substr($skill['teacherName'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="browse-teacher-name"><?php echo htmlspecialchars($skill['teacherName']); ?></div>
                                    <div class="browse-teacher-sub">Teacher</div>
                                </div>
                            </div>

                            <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>" class="browse-view-btn">
                                Details <span>→</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="browse-no-results" class="empty-state" style="display: none; margin-top: 2rem;">
                <p>No skills matched your search filter.</p>
                <p style="margin-top: 0.5rem;"><small>Try searching with another keyword or selecting "All" categories.</small></p>
            </div>
        <?php endif; ?>

    </div>

</div>

<script src="/main/assets/js/browse.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/browse.js'); ?>"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
