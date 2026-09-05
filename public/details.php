<?php
/**
 * Item Details Page — skill listing in full, swap request form,
 * verified reviews, comments discussion, and save/bookmark toggle.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';
require_once __DIR__ . '/../includes/saved_functions.php';

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
$isSaved       = false;

if ($skill) {
    $isOwner = $isLoggedIn && ((int) $skill['userId'] === $currentUserId);

    if ($isLoggedIn && !$isOwner) {
        // Skills the current user could offer in exchange, for the dropdown.
        $stmt = $pdo->prepare("SELECT id, title FROM skills WHERE userId = ? ORDER BY title");
        $stmt->execute([$currentUserId]);
        $myOwnSkills = $stmt->fetchAll();

        $isSaved = isSkillSavedByUser($pdo, $currentUserId, (int) $skill['id']);
    }

    $reviews       = getReviewsForSkill($pdo, (int) $skill['id']);
    $ratingSummary = getSkillRatingSummary($pdo, (int) $skill['id']);
    $comments      = getCommentsForSkill($pdo, (int) $skill['id']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/main/assets/css/details.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/details.css'); ?>">

<div class="details-page">

    <a href="/main/public/browse.php" class="back-link">
        ← Back to Browse Skills
    </a>

    <?php if ($flash): ?>
        <div class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </div>
    <?php endif; ?>

    <?php if (!$skill): ?>
        <div class="empty-state">
            <h2>Skill Not Found</h2>
            <p class="mt-1">The skill listing you requested might have been removed or does not exist.</p>
            <p class="mt-3"><a href="/main/public/browse.php" class="btn btn-primary">Browse Available Skills</a></p>
        </div>
    <?php else: ?>

        <!-- ===================== SKILL INFO HERO CARD ===================== -->
        <article class="skill-detail-card" id="skill-<?php echo (int) $skill['id']; ?>">
            <div class="skill-detail-header">
                <div>
                    <span class="skill-category-tag"><?php echo htmlspecialchars($skill['category']); ?></span>
                    <h1><?php echo htmlspecialchars($skill['title']); ?></h1>
                </div>

                <!-- Save / Bookmark Button OR Owner Action Buttons -->
                <?php if ($isLoggedIn && !$isOwner): ?>
                    <div class="save-action-bar">
                        <?php if ($isSaved): ?>
                            <form method="POST" action="/main/actions/skill_unsave.php" class="inline-form">
                                <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                                <input type="hidden" name="return_to" value="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>">
                                <button type="submit" class="btn btn-decline" title="Remove from your saved list">
                                    ★ Saved in Wishlist
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="/main/actions/skill_save.php" class="inline-form">
                                <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                                <button type="submit" class="btn btn-ghost" title="Save this skill for later">
                                    ☆ Save for Later
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php elseif ($isOwner): ?>
                    <div class="save-action-bar">
                        <a href="/main/public/my_skills.php?edit=<?php echo (int) $skill['id']; ?>#edit-skill" class="btn btn-primary btn-sm">Edit Skill</a>
                        <form method="POST" action="/main/actions/skill_delete.php" class="inline-form">
                            <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                            <button type="submit" class="btn btn-decline btn-sm-narrow" data-confirm="Delete this skill permanently? This removes it for other users too, including saved entries, comments, reviews, and related swap requests.">Delete Skill</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="skill-teacher-bar">
                <div class="teacher-avatar-lg">
                    <?php echo htmlspecialchars(mb_substr($skill['teacherName'], 0, 1)); ?>
                </div>
                <div class="teacher-info-wrap">
                    <span class="teacher-label">Taught by</span>
                    <span class="teacher-name-lg"><?php echo htmlspecialchars($skill['teacherName']); ?></span>
                </div>
                <?php if (!empty($ratingSummary['reviewCount'])): ?>
                    <div class="skill-rating-summary push-left-auto">
                        <span class="stars" aria-hidden="true"><?php echo renderStars((int) round($ratingSummary['avgRating'])); ?></span>
                        <span><?php echo htmlspecialchars((string) $ratingSummary['avgRating']); ?> / 5 (<?php echo (int) $ratingSummary['reviewCount']; ?>)</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="skill-description-box">
                <p><?php echo nl2br(htmlspecialchars($skill['description'])); ?></p>
            </div>
        </article>

        <!-- ===================== Request a Swap Section ===================== -->
        <section class="detail-section-card">
            <h2>⇄ Propose a Skill Swap</h2>

            <?php if ($isOwner): ?>
                <div class="empty-state compact">
                    <p>This is your own skill listing. You can manage incoming requests from your <a href="/main/public/swaps.php">My Swaps</a> dashboard, or manage and edit your listings in <a href="/main/public/my_skills.php">My Skills</a>.</p>
                </div>
            <?php elseif (!$isLoggedIn): ?>
                <div class="empty-state compact">
                    <p>Want to learn this skill? <a href="/main/auth/login.php">Log in</a> or <a href="/main/auth/register.php">create an account</a> to propose a skill exchange with <?php echo htmlspecialchars($skill['teacherName']); ?>.</p>
                </div>
            <?php else: ?>
                <?php
                $userCredits = getUserCreditsBalance($pdo, $currentUserId);
                ?>
                <form method="POST" action="/main/actions/swap_request_create.php" class="swap-request-form">
                    <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">

                    <div>
                        <label for="offered-skill">Offer one of your skills in exchange (optional)</label>
                        <?php if (!empty($myOwnSkills)): ?>
                            <select name="offered_skill_id" id="offered-skill" class="form-control">
                                <option value="">— No specific skill offered (open exchange) —</option>
                                <?php foreach ($myOwnSkills as $mySkill): ?>
                                    <option value="<?php echo (int) $mySkill['id']; ?>">
                                        <?php echo htmlspecialchars($mySkill['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <p class="info-note-small mt-1">
                                You haven't posted any skills yet, but you can still send an exchange request!
                            </p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="swap-message">Message to Teacher</label>
                        <textarea 
                            name="message" 
                            id="swap-message" 
                            maxlength="500" 
                            placeholder="Introduce yourself and explain what you'd like to learn or how you can collaborate..." 
                            class="form-control"
                        ></textarea>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">
                            Send Swap Request <span>→</span>
                        </button>
                        <p class="info-note-small mt-1">
                            Without offering your own skill, completing this swap costs <strong>1 credit</strong>
                            (you have <strong><?php echo (int) $userCredits; ?></strong>).
                            Offer one of your skills above for a direct barter with no credit cost.
                        </p>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <!-- ===================== Reviews Section ===================== -->
        <section class="detail-section-card" id="reviews">
            <h2>★ Verified Reviews</h2>

            <?php if (empty($reviews)): ?>
                <div class="empty-state compact">
                    <p>No reviews yet. Reviews are unlocked once members complete an exchange for this skill.</p>
                </div>
            <?php else: ?>
                <ul class="review-list">
                    <?php foreach ($reviews as $review): ?>
                        <li class="review-item">
                            <div class="review-header">
                                <div class="review-reviewer">
                                    <span class="stars" aria-hidden="true"><?php echo renderStars((int) $review['rating']); ?></span>
                                    <span><?php echo htmlspecialchars($review['reviewerName']); ?></span>
                                </div>
                                <span class="review-date"><?php echo htmlspecialchars(date('d M Y', strtotime($review['createdAt']))); ?></span>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php endif; ?>
                            <?php if ($isLoggedIn && (int) $review['userId'] === $currentUserId): ?>
                                <div class="mt-1 text-right">
                                    <form method="POST" action="/main/actions/review_delete.php" class="inline-form">
                                        <input type="hidden" name="review_id" value="<?php echo (int) $review['id']; ?>">
                                        <input type="hidden" name="return_to" value="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>">
                                        <button type="submit" class="btn-link-danger" data-confirm="Delete your review?">Delete Review</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- ===================== Comments Discussion ===================== -->
        <section class="detail-section-card" id="comments">
            <h2>💬 Discussion & Questions (<?php echo count($comments); ?>)</h2>

            <?php if ($isLoggedIn): ?>
                <form method="POST" action="/main/actions/comment_submit.php" class="comment-form">
                    <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                    <label for="comment-text" class="sr-only-label">Add a question or comment</label>
                    <textarea 
                        name="comment_text" 
                        id="comment-text" 
                        maxlength="1000" 
                        placeholder="Ask a question about this skill or schedule..." 
                        required
                    ></textarea>
                    <div>
                        <button type="submit" class="btn btn-primary">Post Comment</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state small mb-4">
                    <p><a href="/main/auth/login.php">Log in</a> to ask questions or join the discussion.</p>
                </div>
            <?php endif; ?>

            <?php if (empty($comments)): ?>
                <div class="empty-state compact">
                    <p>No questions posted yet. Be the first to ask about this skill!</p>
                </div>
            <?php else: ?>
                <ul class="comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <li class="comment-item">
                            <div class="comment-header">
                                <div class="comment-author-wrap">
                                    <div class="comment-avatar">
                                        <?php echo htmlspecialchars(mb_substr($comment['authorName'], 0, 1)); ?>
                                    </div>
                                    <strong class="comment-author"><?php echo htmlspecialchars($comment['authorName']); ?></strong>
                                </div>
                                <span class="comment-date"><?php echo htmlspecialchars(date('d M Y, g:ia', strtotime($comment['createdAt']))); ?></span>
                            </div>
                            <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['commentText'])); ?></p>
                            <?php if ($isLoggedIn && (int) $comment['userId'] === $currentUserId): ?>
                                <div class="comment-actions">
                                    <form method="POST" action="/main/actions/comment_delete.php" class="inline-form">
                                        <input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
                                        <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                                        <button type="submit" class="btn-link-danger" data-confirm="Delete this comment?">Delete</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
