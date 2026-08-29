<?php
/**
 * My Teaching Requests — skill-owner lifecycle dashboard.
 * Dedicated page for incoming swap requests on the current user's posted skills.
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$pageTitle     = 'My Teaching Requests - SkillExpert';
$currentUserId = (int) $_SESSION['user_id'];
$received      = getReceivedRequests($pdo, $currentUserId);
$flash         = getAndClearFlash();

require_once __DIR__ . '/../includes/header.php';

function renderTeachingRequestActions(array $swap): void
{
    $status = $swap['status'];
    ?>
    <div class="swap-actions">
        <?php if ($status === 'pending'): ?>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="accept">
                <input type="hidden" name="redirect_to" value="/main/public/teaching_requests.php">
                <button type="submit" class="btn btn-accept">✓ Accept Request</button>
            </form>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="redirect_to" value="/main/public/teaching_requests.php">
                <button type="submit" class="btn btn-decline" data-confirm="Decline this teaching request?">✕ Decline</button>
            </form>
        <?php elseif ($status === 'accepted'): ?>
            <form method="POST" action="/main/actions/swap_request_action.php" class="inline-form">
                <input type="hidden" name="swap_id" value="<?php echo (int) $swap['id']; ?>">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="redirect_to" value="/main/public/teaching_requests.php">
                <button type="submit" class="btn btn-accept" data-confirm="Mark this teaching request as complete? This awards the session as finished.">★ Complete Session</button>
            </form>
        <?php else: ?>
            <p class="swap-action-note">No further action needed for this <?php echo htmlspecialchars($status); ?> request.</p>
        <?php endif; ?>
    </div>
    <?php
}
?>

<link rel="stylesheet" href="/main/assets/css/swaps.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/swaps.css'); ?>">

<section class="container swaps-page teaching-requests-page">
    <div class="swaps-page-header teaching-requests-header">
        <p class="eyebrow">Skill-owner dashboard</p>
        <h1>My Teaching Requests</h1>
        <p>Review learners who want your posted skills, accept the sessions you can teach, decline the ones you cannot, and complete finished swaps so the time-credit exchange feels real.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </div>
    <?php endif; ?>

    <div class="swap-lifecycle-guide" aria-label="Teaching request lifecycle">
        <div><strong>1. Pending</strong><span>Learner asks for your skill.</span></div>
        <div><strong>2. Accepted</strong><span>You agree and arrange the session.</span></div>
        <div><strong>3. Completed</strong><span>Session is done and ready for reviews.</span></div>
    </div>

    <?php if (empty($received)): ?>
        <div class="empty-state">
            <p>No incoming teaching requests yet.</p>
            <p class="mt-2"><a href="/main/public/posting.php" class="btn btn-primary">Post or Improve a Skill</a></p>
        </div>
    <?php else: ?>
        <div class="teaching-request-list">
            <?php foreach ($received as $swap): ?>
                <article class="swap-card teaching-request-card">
                    <div class="swap-card-top-bar">
                        <div>
                            <span class="swap-card-label">They want to learn</span>
                            <h3><?php echo htmlspecialchars($swap['skillTitle']); ?></h3>
                        </div>
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
                            <span>⇄ They can teach you:</span>
                            <strong><?php echo htmlspecialchars($swap['offeredSkillTitle']); ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="swap-offer-box muted-offer">
                            <span>Time-credit swap:</span>
                            <strong>No specific return skill selected yet</strong>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($swap['message'])): ?>
                        <div class="swap-message-bubble">
                            &ldquo;<?php echo htmlspecialchars($swap['message']); ?>&rdquo;
                        </div>
                    <?php endif; ?>

                    <?php renderTeachingRequestActions($swap); ?>

                    <?php if ($swap['status'] === 'completed'): ?>
                        <?php include __DIR__ . '/../includes/review_form_partial.php'; ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
