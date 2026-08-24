<?php
/**
 * Saved/Wishlist Page — everything the logged-in user has bookmarked.
 * Requires login (auth/session_check.php redirects otherwise), per the
 * assignment's rule that saved items need an account.
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';  // getAndClearFlash()
require_once __DIR__ . '/../includes/saved_functions.php';

$pageTitle     = 'Saved Skills - SkillExpert';
$currentUserId = (int) $_SESSION['user_id'];

$savedSkills = getSavedSkillsForUser($pdo, $currentUserId);
$flash       = getAndClearFlash();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container saved-page">
    <h1>Saved Skills</h1>
    <p>Skills you've bookmarked to look at or request later.</p>

    <?php if ($flash): ?>
        <p class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </p>
    <?php endif; ?>

    <?php if (empty($savedSkills)): ?>
        <p class="empty-state">
            Nothing saved yet. <a href="/main/public/browse.php">Browse skills</a> and tap "Save" on
            anything you'd like to come back to.
        </p>
    <?php else: ?>
        <div class="saved-list">
            <?php foreach ($savedSkills as $skill): ?>
                <article class="saved-card">
                    <div class="saved-card-main">
                        <p class="skill-category-tag"><?php echo htmlspecialchars($skill['category']); ?></p>
                        <h3><a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>">
                            <?php echo htmlspecialchars($skill['title']); ?>
                        </a></h3>
                        <p class="swap-meta">Taught by <strong><?php echo htmlspecialchars($skill['teacherName']); ?></strong></p>
                    </div>
                    <div class="swap-actions">
                        <a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>" class="btn btn-primary">View Details</a>
                        <form method="POST" action="/main/actions/skill_unsave.php" class="inline-form">
                            <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                            <input type="hidden" name="return_to" value="/main/public/saved.php">
                            <button type="submit" class="btn btn-decline" data-confirm="Remove this skill from your saved list?">Unsave</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
