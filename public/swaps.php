<?php
/**
 * "My Swaps" page — Requests management page.
 * Shows received & sent swap requests with accept/decline/cancel/complete/review actions.
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$pageTitle     = 'My Swaps - SkillExpert';
$currentUserId = (int) $_SESSION['user_id'];

$received = getReceivedRequests($pdo, $currentUserId);
$sent     = getSentRequests($pdo, $currentUserId);
$flash    = getAndClearFlash();

require_once __DIR__ . '/../includes/header.php';

/**
 * Renders the action buttons for one swap request row
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
                <button type="submit" class="btn btn-accept">✓ Accept Request</button>
            </form>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="decline">
                <button type="submit" class="btn btn-decline" data-confirm="Decline this swap request?">✕ Decline</button>
            </form>
        <?php endif; ?>

        <?php if ($viewerRole === 'sent' && $status === 'pending'): ?>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="btn btn-decline" data-confirm="Withdraw this swap request?">Withdraw Request</button>
            </form>
        <?php endif; ?>

        <?php if ($status === 'accepted'): ?>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="btn btn-accept" data-confirm="Mark this swap as complete?">★ Mark Complete</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
?>

<link rel="stylesheet" href="/main/assets/css/swaps.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/swaps.css'); ?>">

<section class="container swaps-page">

    <div class="swaps-page-header">
        <h1>My Skill Swaps</h1>
        <p>Manage your incoming learning requests and outgoing swap proposals.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </div>
    <?php endif; ?>

    <div class="swaps-tabs">
        <!-- CSS-only tab switcher -->
        <input type="radio" name="swap-tab" id="tab-received" class="swap-tab-radio" checked>
        <input type="radio" name="swap-tab" id="tab-sent" class="swap-tab-radio">

        <div class="swap-tab-labels">
            <label for="tab-received">📥 Received (<?php echo count($received); ?>)</label>
            <label for="tab-sent">📤 Sent (<?php echo count($sent); ?>)</label>
        </div>

        <!-- Panel: Received Requests -->
        <div class="swap-tab-panel" id="panel-received">
            <?php if (empty($received)): ?>
                <div class="empty-state">
                    <p>No incoming swap requests at the moment.</p>
                    <p class="mt-1"><small>Make sure your skills have clear descriptions to attract more learners!</small></p>
                </div>
            <?php else: ?>
                <?php foreach ($received as $swap): ?>
                    <article class="swap-card">
                        <div class="swap-card-top-bar">
                            <h3><?php echo htmlspecialchars($swap['skillTitle']); ?></h3>
                            <span class="status-badge status-<?php echo statusBadgeClass($swap['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($swap['status'])); ?>
                            </span>
                        </div>

                        <p class="swap-meta">
                            Requested by <strong><?php echo htmlspecialchars($swap['requesterName']); ?></strong>
                            on <?php echo htmlspecialchars(date('d M Y', strtotime($swap['createdAt']))); ?>
                        </p>

                        <?php if (!empty($swap['offeredSkillTitle'])): ?>
                            <div class="swap-offer-box">
                                <span>⇄ Offered in exchange:</span>
                                <strong><?php echo htmlspecialchars($swap['offeredSkillTitle']); ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($swap['message'])): ?>
                            <div class="swap-message-bubble">
                                &ldquo;<?php echo htmlspecialchars($swap['message']); ?>&rdquo;
                            </div>
                        <?php endif; ?>

                        <?php renderSwapActions($swap, 'received', $currentUserId); ?>

                        <?php if ($swap['status'] === 'completed'): ?>
                            <?php include __DIR__ . '/../includes/review_form_partial.php'; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Panel: Sent Requests -->
        <div class="swap-tab-panel" id="panel-sent">
            <?php if (empty($sent)): ?>
                <div class="empty-state">
                    <p>You haven't requested any skill swaps yet.</p>
                    <p class="mt-2"><a href="/main/public/browse.php" class="btn btn-primary">Browse Skills to Trade</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($sent as $swap): ?>
                    <article class="swap-card">
                        <div class="swap-card-top-bar">
                            <h3><?php echo htmlspecialchars($swap['skillTitle']); ?></h3>
                            <span class="status-badge status-<?php echo statusBadgeClass($swap['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($swap['status'])); ?>
                            </span>
                        </div>

                        <p class="swap-meta">
                            Requested from <strong><?php echo htmlspecialchars($swap['receiverName']); ?></strong>
                            on <?php echo htmlspecialchars(date('d M Y', strtotime($swap['createdAt']))); ?>
                        </p>

                        <?php if (!empty($swap['offeredSkillTitle'])): ?>
                            <div class="swap-offer-box">
                                <span>⇄ You offered:</span>
                                <strong><?php echo htmlspecialchars($swap['offeredSkillTitle']); ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($swap['message'])): ?>
                            <div class="swap-message-bubble">
                                &ldquo;<?php echo htmlspecialchars($swap['message']); ?>&rdquo;
                            </div>
                        <?php endif; ?>

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
