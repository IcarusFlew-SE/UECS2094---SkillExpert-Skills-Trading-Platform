<?php
/**
 * "My Swaps" page — the Requests page from the assignment spec.
 * Shows the swap requests the current user has RECEIVED (on their own
 * skills) and SENT (on other people's skills), with the relevant
 * accept/decline/cancel/complete/review actions for each one.
 *
 * Requires login (auth/session_check.php redirects to /main/auth/login.php
 * otherwise).
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$pageTitle     = 'My Swaps - SkillExpert';
$currentUserId = (int) $_SESSION['user_id'];

$received = getReceivedRequests($pdo, $currentUserId);
$sent     = getSentRequests($pdo, $currentUserId);
$flash    = getAndClearFlash();

require_once __DIR__ . '/../includes/header.php'; // opens <html>/<body>/<main> — must run before any HTML below

/**
 * Renders the action buttons for one swap request row, from the point of
 * view of $viewerRole ('received' or 'sent'). Kept as a local function so
 * the markup below doesn't repeat the same if/else four times.
 */
function renderSwapActions(array $swap, string $viewerRole, int $currentUserId): void
{
    $status = $swap['status'];
    ?>
    <div class="swap-actions">
        <?php if ($viewerRole === 'received' && $status === 'pending'): ?>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="accept">
                <button type="submit" class="btn btn-accept">Accept</button>
            </form>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="decline">
                <button type="submit" class="btn btn-decline" data-confirm="Decline this swap request?">Decline</button>
            </form>
        <?php endif; ?>

        <?php if ($viewerRole === 'sent' && $status === 'pending'): ?>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="btn btn-decline" data-confirm="Withdraw this swap request?">Withdraw</button>
            </form>
        <?php endif; ?>

        <?php if ($status === 'accepted'): ?>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="btn btn-accept" data-confirm="Mark this swap as complete?">Mark Complete</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
?>

<section class="container swaps-page">
    <h1>My Swaps</h1>
    <p>Manage your active and completed skill exchanges here.</p>

    <?php if ($flash): ?>
        <p class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </p>
    <?php endif; ?>

    <div class="swaps-tabs">
        <!-- CSS-only tab switcher (radio buttons), same pattern as the
             CSS-only nav hamburger in includes/nav.php — no JS libraries. -->
        <input type="radio" name="swap-tab" id="tab-received" class="swap-tab-radio" checked>
        <input type="radio" name="swap-tab" id="tab-sent" class="swap-tab-radio">

        <div class="swap-tab-labels">
            <label for="tab-received">Received (<?php echo count($received); ?>)</label>
            <label for="tab-sent">Sent (<?php echo count($sent); ?>)</label>
        </div>

        <div class="swap-tab-panel" id="panel-received">
            <?php if (empty($received)): ?>
                <p class="empty-state">No one has requested a swap on your skills yet.</p>
            <?php else: ?>
                <?php foreach ($received as $swap): ?>
                    <article class="swap-card">
                        <div class="swap-card-main">
                            <h3><?php echo htmlspecialchars($swap['skillTitle']); ?></h3>
                            <p class="swap-meta">
                                Requested by <strong><?php echo htmlspecialchars($swap['requesterName']); ?></strong>
                                <span class="status-badge status-<?php echo statusBadgeClass($swap['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($swap['status'])); ?>
                                </span>
                            </p>
                            <?php if (!empty($swap['offeredSkillTitle'])): ?>
                                <p class="swap-offer">In exchange for: <em><?php echo htmlspecialchars($swap['offeredSkillTitle']); ?></em></p>
                            <?php endif; ?>
                            <?php if (!empty($swap['message'])): ?>
                                <p class="swap-message">&ldquo;<?php echo htmlspecialchars($swap['message']); ?>&rdquo;</p>
                            <?php endif; ?>
                        </div>
                        <?php renderSwapActions($swap, 'received', $currentUserId); ?>
                        <?php if ($swap['status'] === 'completed'): ?>
                            <?php include __DIR__ . '/../includes/review_form_partial.php'; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="swap-tab-panel" id="panel-sent">
            <?php if (empty($sent)): ?>
                <p class="empty-state">You haven't requested any swaps yet. <a href="/main/public/browse.php">Browse skills</a> to get started.</p>
            <?php else: ?>
                <?php foreach ($sent as $swap): ?>
                    <article class="swap-card">
                        <div class="swap-card-main">
                            <h3><?php echo htmlspecialchars($swap['skillTitle']); ?></h3>
                            <p class="swap-meta">
                                Requested from <strong><?php echo htmlspecialchars($swap['receiverName']); ?></strong>
                                <span class="status-badge status-<?php echo statusBadgeClass($swap['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($swap['status'])); ?>
                                </span>
                            </p>
                            <?php if (!empty($swap['offeredSkillTitle'])): ?>
                                <p class="swap-offer">You offered: <em><?php echo htmlspecialchars($swap['offeredSkillTitle']); ?></em></p>
                            <?php endif; ?>
                            <?php if (!empty($swap['message'])): ?>
                                <p class="swap-message">&ldquo;<?php echo htmlspecialchars($swap['message']); ?>&rdquo;</p>
                            <?php endif; ?>
                        </div>
                        <?php renderSwapActions($swap, 'sent', $currentUserId); ?>
                        <?php if ($swap['status'] === 'completed'): ?>
                            <?php include __DIR__ . '/../includes/review_form_partial.php'; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
