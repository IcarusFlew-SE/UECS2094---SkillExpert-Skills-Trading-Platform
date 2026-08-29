<?php
/**
 * Handles the four state-changing actions a swap request can go through:
 *   accept   — receiver only,  pending  -> accepted
 *   decline  — receiver only,  pending  -> declined
 *   cancel   — requester only, pending  -> cancelled (withdraw own request)
 *   complete — either party,   accepted -> completed
 *
 * POST only. Requires login. Every branch re-checks BOTH the current
 * status and that the logged-in user is actually allowed to perform that
 * action on that row — never trust the button the client clicked.
 *
 * Expected POST fields:
 *   swap_id  int, required
 *   action   string, required — one of accept|decline|cancel|complete
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$currentUserId = (int) $_SESSION['user_id'];
$redirectTarget = '/main/public/swaps.php';

if (isset($_POST['redirect_to']) && is_string($_POST['redirect_to'])) {
    $postedRedirect = $_POST['redirect_to'];
    $allowedRedirects = [
        '/main/public/swaps.php',
        '/main/public/teaching_requests.php',
    ];
    if (in_array($postedRedirect, $allowedRedirects, true)) {
        $redirectTarget = $postedRedirect;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$redirectTarget}");
    exit;
}

$swapId = filter_input(INPUT_POST, 'swap_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$allowedActions = ['accept', 'decline', 'cancel', 'complete'];

if (!$swapId || !in_array($action, $allowedActions, true)) {
    setFlash('error', 'Invalid request.');
    header("Location: {$redirectTarget}");
    exit;
}

$swap = getSwapById($pdo, $swapId);

if (!$swap) {
    setFlash('error', 'That swap request no longer exists.');
    header("Location: {$redirectTarget}");
    exit;
}

$isRequester = ((int) $swap['requesterId'] === $currentUserId);
$isReceiver  = ((int) $swap['receiverId'] === $currentUserId);

// Not one of the two participants at all — nothing to do with this row.
if (!$isRequester && !$isReceiver) {
    setFlash('error', "You don't have permission to update that request.");
    header("Location: {$redirectTarget}");
    exit;
}

$newStatus  = null;
$errorMsg   = null;
$successMsg = null;

switch ($action) {
    case 'accept':
        if (!$isReceiver) {
            $errorMsg = 'Only the skill owner can accept a request.';
        } elseif ($swap['status'] !== 'pending') {
            $errorMsg = 'That request is no longer pending.';
        } else {
            $newStatus  = 'accepted';
            $successMsg = 'Swap request accepted.';
        }
        break;

    case 'decline':
        if (!$isReceiver) {
            $errorMsg = 'Only the skill owner can decline a request.';
        } elseif ($swap['status'] !== 'pending') {
            $errorMsg = 'That request is no longer pending.';
        } else {
            $newStatus  = 'declined';
            $successMsg = 'Swap request declined.';
        }
        break;

    case 'cancel':
        if (!$isRequester) {
            $errorMsg = 'Only the requester can cancel a request.';
        } elseif ($swap['status'] !== 'pending') {
            $errorMsg = 'Only pending requests can be cancelled.';
        } else {
            $newStatus  = 'cancelled';
            $successMsg = 'Request withdrawn.';
        }
        break;

    case 'complete':
        if ($swap['status'] !== 'accepted') {
            $errorMsg = 'Only an accepted swap can be marked complete.';
        } else {
            $newStatus  = 'completed';
            $successMsg = 'Swap marked as complete — you can leave a review now.';
        }
        break;
}

if ($newStatus === null) {
    setFlash('error', $errorMsg ?? 'Unable to update that request.');
    header("Location: {$redirectTarget}");
    exit;
}

if ($newStatus === 'completed') {
    $result = completeSwapWithCredits($pdo, $swap, $currentUserId);
    if (!$result['ok']) {
        setFlash('error', $result['message']);
        header("Location: {$redirectTarget}");
        exit;
    }
    $successMsg = $result['message'];
} else {
    $stmt = $pdo->prepare("UPDATE swapRequests SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $swapId]);
}

setFlash('success', $successMsg);
header("Location: {$redirectTarget}");
exit;
