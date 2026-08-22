<?php
/**
 * Item Details Page — one skill listing in full, plus (Barry's part):
 * a "Request a Swap" form, the reviews left for this skill, and its
 * comment thread.
 *
 * NOTE TO TEAMMATE (skills domain): the block marked
 * "SKILL INFO — placeholder" below is intentionally minimal — title,
 * category, description, teacher name and an image slot. Feel free to
 * replace/extend it (gallery, tags, richer layout, etc.) — just keep the
 * $skill array shape (id, title, category, description, imagePath, userId,
 * teacherName) and the #skill-id anchor around it, since the swap/review/
 * comment sections below rely on $skill['id'] being resolved before they run.
 *
 * GET param: id (skill id)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$isLoggedIn    = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

$skillId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$skill = false;
if ($skillId) {
    $stmt = $pdo->prepare(
        "SELECT sk.*, u.name AS teacherName
         FROM skills sk
         JOIN users u ON sk.userId = u.id
         WHERE sk.id = ?
         LIMIT 1"
    );
    $stmt->execute([$skillId]);
    $skill = $stmt->fetch();
}

$pageTitle = $skill ? htmlspecialchars($skill['title']) . ' - SkillExpert' : 'Skill Not Found - SkillExpert';
$flash     = getAndClearFlash();

// Data needed further down the page — only fetched if the skill exists.
$isOwner       = false;
$myOwnSkills   = [];
$reviews       = [];
$ratingSummary = ['avgRating' => null, 'reviewCount' => 0];
$comments      = [];

if ($skill) {
    $isOwner = $isLoggedIn && ((int) $skill['userId'] === $currentUserId);

    if ($isLoggedIn && !$isOwner) {
        // Skills the current user could offer in exchange, for the dropdown.
        $stmt = $pdo->prepare("SELECT id, title FROM skills WHERE userId = ? ORDER BY title");
        $stmt->execute([$currentUserId]);
        $myOwnSkills = $stmt->fetchAll();
    }

    $reviews       = getReviewsForSkill($pdo, (int) $skill['id']);
    $ratingSummary = getSkillRatingSummary($pdo, (int) $skill['id']);
    $comments      = getCommentsForSkill($pdo, (int) $skill['id']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container details-page">

    <?php if ($flash): ?>
        <p class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </p>
    <?php endif; ?>

    <?php if (!$skill): ?>
        <p class="empty-state">Skill not found. <a href="/main/public/browse.php">Back to Browse</a></p>

    <?php else: ?>

        <!-- ===================== SKILL INFO — placeholder ===================== -->
        <article class="skill-detail-card" id="skill-<?php echo (int) $skill['id']; ?>">
            <p class="skill-category-tag"><?php echo htmlspecialchars($skill['category']); ?></p>
            <h1><?php echo htmlspecialchars($skill['title']); ?></h1>
            <p class="skill-teacher">Taught by <strong><?php echo htmlspecialchars($skill['teacherName']); ?></strong></p>
            <p class="skill-description"><?php echo nl2br(htmlspecialchars($skill['description'])); ?></p>
            <?php if (!empty($ratingSummary['reviewCount'])): ?>
                <p class="skill-rating-summary">
                    <span class="stars" aria-hidden="true"><?php echo renderStars((int) round($ratingSummary['avgRating'])); ?></span>
                    <?php echo htmlspecialchars((string) $ratingSummary['avgRating']); ?> / 5
                    (<?php echo (int) $ratingSummary['reviewCount']; ?> review<?php echo $ratingSummary['reviewCount'] === 1 ? '' : 's'; ?>)
                </p>
            <?php endif; ?>
        </article>
        <!-- =================== END SKILL INFO — placeholder =================== -->

        <!-- ===================== Request a Swap (Barry) ===================== -->
        <section class="request-swap-section">
            <?php if ($isOwner): ?>
                <p class="info-note">This is your own listing — manage requests for it from <a href="/main/public/swaps.php">My Swaps</a>.</p>
            <?php elseif (!$isLoggedIn): ?>
                <p class="info-note"><a href="/main/auth/login.php">Log in</a> to request a swap for this skill.</p>
            <?php else: ?>
                <h2>Request a Swap</h2>
                <form method="POST" action="/main/actions/swap_request_create.php" class="swap-request-form">
                    <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">

                    <?php if (!empty($myOwnSkills)): ?>
                        <label for="offered-skill">Offer one of your skills in exchange (optional)</label>
                        <select name="offered_skill_id" id="offered-skill">
                            <option value="">— No specific skill offered —</option>
                            <?php foreach ($myOwnSkills as $mySkill): ?>
                                <option value="<?php echo (int) $mySkill['id']; ?>">
                                    <?php echo htmlspecialchars($mySkill['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p class="info-note-small">You haven't posted any skills yet, but you can still send a request.</p>
                    <?php endif; ?>

                    <label for="swap-message">Message (optional)</label>
                    <textarea name="message" id="swap-message" maxlength="500" rows="3"
                              placeholder="Introduce yourself and say why you'd like this swap..."></textarea>

                    <button type="submit" class="btn btn-primary">Send Swap Request</button>
                </form>
            <?php endif; ?>
        </section>
        <!-- =================== END Request a Swap (Barry) =================== -->

        <!-- ===================== Reviews (Barry) ===================== -->
        <section class="reviews-section" id="reviews">
            <h2>Reviews</h2>
            <?php if (empty($reviews)): ?>
                <p class="empty-state">No reviews yet — reviews appear here once a swap for this skill is completed.</p>
            <?php else: ?>
                <ul class="review-list">
                    <?php foreach ($reviews as $review): ?>
                        <li class="review-item">
                            <p class="review-header">
                                <span class="stars" aria-hidden="true"><?php echo renderStars((int) $review['rating']); ?></span>
                                <strong><?php echo htmlspecialchars($review['reviewerName']); ?></strong>
                                <span class="review-date"><?php echo htmlspecialchars(date('d M Y', strtotime($review['createdAt']))); ?></span>
                            </p>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php endif; ?>
                            <?php if ($isLoggedIn && (int) $review['userId'] === $currentUserId): ?>
                                <form method="POST" action="/main/actions/review_delete.php" class="inline-form">
                                    <input type="hidden" name="review_id" value="<?php echo (int) $review['id']; ?>">
                                    <input type="hidden" name="return_to" value="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>">
                                    <button type="submit" class="btn-link-danger" data-confirm="Delete your review?">Delete</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <!-- =================== END Reviews (Barry) =================== -->

        <!-- ===================== Comments (Barry) ===================== -->
        <section class="comments-section" id="comments">
            <h2>Comments (<?php echo count($comments); ?>)</h2>

            <?php if ($isLoggedIn): ?>
                <form method="POST" action="/main/actions/comment_submit.php" class="comment-form">
                    <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                    <label for="comment-text" class="sr-only-label">Add a comment</label>
                    <textarea name="comment_text" id="comment-text" maxlength="1000" rows="2"
                              placeholder="Ask a question or leave a comment..." required></textarea>
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </form>
            <?php else: ?>
                <p class="info-note"><a href="/main/auth/login.php">Log in</a> to join the discussion.</p>
            <?php endif; ?>

            <?php if (empty($comments)): ?>
                <p class="empty-state">No comments yet. Be the first to ask something.</p>
            <?php else: ?>
                <ul class="comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <li class="comment-item">
                            <p class="comment-header">
                                <strong><?php echo htmlspecialchars($comment['authorName']); ?></strong>
                                <span class="comment-date"><?php echo htmlspecialchars(date('d M Y, g:ia', strtotime($comment['createdAt']))); ?></span>
                            </p>
                            <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['commentText'])); ?></p>
                            <?php if ($isLoggedIn && (int) $comment['userId'] === $currentUserId): ?>
                                <form method="POST" action="/main/actions/comment_delete.php" class="inline-form">
                                    <input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
                                    <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                                    <button type="submit" class="btn-link-danger" data-confirm="Delete this comment?">Delete</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <!-- =================== END Comments (Barry) =================== -->

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
