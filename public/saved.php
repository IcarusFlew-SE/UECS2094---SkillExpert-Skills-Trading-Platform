<?php
/**
 * Saved/Wishlist Page — bookmarked skills for the logged-in user.
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';
require_once __DIR__ . '/../includes/saved_functions.php';

$pageTitle     = 'Saved Skills - SkillExpert';
$currentUserId = (int) $_SESSION['user_id'];

$savedSkills = getSavedSkillsForUser($pdo, $currentUserId);
$flash       = getAndClearFlash();

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/main/assets/css/saved.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/saved.css'); ?>">

<section class="container saved-page">

    <div class="saved-page-header">
        <h1>Saved Skills</h1>
        <p>Your personal wishlist of talents to explore and swap when you're ready.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($savedSkills)): ?>
        <div class="empty-state">
            <p>Your wishlist is currently empty.</p>
            <p class="mt-1 text-muted">
                Browse skills across the platform and tap "Save for Later" to bookmark them here.
            </p>
            <p class="mt-3"><a href="/main/public/browse.php" class="btn btn-primary">Browse Skills</a></p>
        </div>
    <?php else: ?>
        <div class="saved-grid">
            <?php foreach ($savedSkills as $skill): ?>
                <article class="saved-card">
                    <div>
                        <div class="saved-card-top">
                            <span class="skill-cat-pill"><?php echo htmlspecialchars($skill['category']); ?></span>
                            <span class="favorite-mark">★</span>
                        </div>
                        <h3>
                            <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>">
                                <?php echo htmlspecialchars($skill['title']); ?>
                            </a>
                        </h3>
                        <p class="saved-card-meta">
                            Taught by <strong><?php echo htmlspecialchars($skill['teacherName']); ?></strong>
                        </p>
                    </div>

                    <div class="saved-card-actions">
                        <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>" class="btn btn-primary btn-sm">
                            View Details <span>→</span>
                        </a>
                        <form method="POST" action="/main/actions/skill_unsave.php" class="inline-form">
                            <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                            <input type="hidden" name="return_to" value="/main/public/saved.php">
                            <button type="submit" class="btn btn-decline btn-sm-narrow" data-confirm="Remove this skill from your saved list?">
                                Unsave
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
