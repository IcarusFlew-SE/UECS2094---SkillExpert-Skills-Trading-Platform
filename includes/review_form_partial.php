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
?>
<div class="review-partial">
    <?php if ($alreadyReviewed): ?>
        <p class="review-done">✓ You've already reviewed this swap. See it on the skill's details page.</p>
    <?php else: ?>
        <form method="POST" action="/main/actions/review_submit.php" class="review-form">
            <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">

            <fieldset class="star-rating" role="radiogroup" aria-label="Rating">
                <legend>Rate this swap</legend>
                <?php
                // Rendered 5 -> 1 in the markup (source order) but visually
                // reversed with CSS so that clicking a star also highlights
                // every star before it — a pure-CSS star-rating widget,
                // no JS required for the visual, JS only adds a submit guard.
                for ($i = 5; $i >= 1; $i--):
                    $inputId = 'star-' . $swap['id'] . '-' . $i;
                    ?>
                    <input type="radio" name="rating" id="<?php echo $inputId; ?>" value="<?php echo $i; ?>" required>
                    <label for="<?php echo $inputId; ?>" title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">★</label>
                <?php endfor; ?>
            </fieldset>

            <label for="comment-<?php echo $swap['id']; ?>" class="sr-only-label">Comment (optional)</label>
            <textarea
                name="comment"
                id="comment-<?php echo $swap['id']; ?>"
                maxlength="1000"
                rows="2"
                placeholder="How did the swap go? (optional)"
            ></textarea>

            <button type="submit" class="btn btn-accept">Submit Review</button>
        </form>
    <?php endif; ?>
</div>
