<?php
/**
 * Partial: review form / review status for ONE completed swap.
 *
 * Included from public/swaps.php inside a `foreach ($swaps as $swap)` loop,
 * so it relies on the parent scope's variables — PHP's `include` shares the
 * including file's variable scope, so this is intentional, not an oversight:
 *   $swap           — the current swap row (array, from getReceivedRequests/getSentRequests)
 *   $currentUserId  — logged-in user's id
 *   $pdo            — PDO connection (from config/db.php, required earlier)
 *
 * Only meant to run for swaps with status === 'completed' — the caller
 * checks that before including this file.
 */

$alreadyReviewed = userHasReviewed($pdo, (int) $swap['id'], $currentUserId);
$isRequesterView = isset($swap['requesterId']) && (int) $swap['requesterId'] === $currentUserId;
$reviewPartnerName = $isRequesterView
    ? ($swap['receiverName'] ?? 'your teacher')
    : ($swap['requesterName'] ?? 'your learner');
$returnTo = $_SERVER['REQUEST_URI'] ?? '/main/public/swaps.php';

if (!str_starts_with($returnTo, '/main/public/swaps.php') && !str_starts_with($returnTo, '/main/public/teaching_requests.php')) {
    $returnTo = '/main/public/swaps.php';
}
?>
<div class="review-partial" id="review-swap-<?php echo (int) $swap['id']; ?>">
    <div class="review-callout-header">
        <div>
            <p class="review-eyebrow">Completed swap</p>
            <h4>Share how the lesson went</h4>
        </div>
        <a href="/main/public/details.php?id=<?php echo (int) $swap['skillId']; ?>#reviews" class="review-details-link">View skill reviews</a>
    </div>

    <?php if ($alreadyReviewed): ?>
        <p class="review-done">✓ You've already reviewed this swap. Your feedback now appears on the skill's details page.</p>
    <?php else: ?>
        <p class="review-helper-text">
            Leave a verified peer review for <strong><?php echo htmlspecialchars($reviewPartnerName); ?></strong>. Reviews unlock only after a completed exchange, so future students can trust the feedback.
        </p>
        <form method="POST" action="/main/actions/review_submit.php" class="review-form">
            <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo); ?>">

            <fieldset class="star-rating" role="radiogroup" aria-label="Rating">
                <legend>Rate this swap</legend>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <?php $inputId = 'star-' . $swap['id'] . '-' . $i; ?>
                    <input type="radio" name="rating" id="<?php echo $inputId; ?>" value="<?php echo $i; ?>" required>
                    <label for="<?php echo $inputId; ?>" title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">★</label>
                <?php endfor; ?>
            </fieldset>

            <label for="comment-<?php echo $swap['id']; ?>" class="sr-only-label">Comment (optional)</label>
            <textarea
                name="comment"
                id="comment-<?php echo $swap['id']; ?>"
                maxlength="1000"
                rows="3"
                placeholder="What did you learn, teach, or appreciate about this swap? (optional)"
            ></textarea>

            <div class="review-submit-row">
                <button type="submit" class="btn btn-accept">Submit Verified Review</button>
                <span class="review-microcopy">Helps keep SwapExpert community-led, not marketplace-led.</span>
            </div>
        </form>
    <?php endif; ?>
</div>
