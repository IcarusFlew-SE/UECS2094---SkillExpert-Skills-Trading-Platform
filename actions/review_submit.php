<?php
/**
 * Handles "Leave a review" submissions from public/swaps.php (shown once a
 * swap's status is 'completed').
 *
 * POST only. Requires login. A review can only be left by one of the two
 * participants of a COMPLETED swap, and only once per swap per user — the
 * database's unique_review(swapId, userId) constraint is the real
 * guarantee, the application check here just gives a nicer error message
 * instead of a raw PDOException.
 *
 * Expected POST fields:
 *   swap_id  int, required
 *   rating   int 1-5, required
 *   comment  string, optional (max 1000 chars)
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /main/public/swaps.php");
    exit;
}

$swapId  = filter_input(INPUT_POST, 'swap_id', FILTER_VALIDATE_INT);
$rating  = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment = trim($_POST['comment'] ?? '');

if (strlen($comment) > 1000) {
    $comment = substr($comment, 0, 1000);
}

if (!$swapId || !$rating || $rating < 1 || $rating > 5) {
    setFlash('error', 'Please choose a rating between 1 and 5 stars.');
    header("Location: /main/public/swaps.php");
    exit;
}

$swap = getSwapById($pdo, $swapId);

if (!$swap) {
    setFlash('error', 'That swap no longer exists.');
    header("Location: /main/public/swaps.php");
    exit;
}

$isParticipant = ((int) $swap['requesterId'] === $currentUserId
                || (int) $swap['receiverId'] === $currentUserId);

if (!$isParticipant) {
    setFlash('error', "You weren't part of that swap.");
    header("Location: /main/public/swaps.php");
    exit;
}

if ($swap['status'] !== 'completed') {
    setFlash('error', 'You can only review a swap after it has been completed.');
    header("Location: /main/public/swaps.php");
    exit;
}

if (userHasReviewed($pdo, $swapId, $currentUserId)) {
    setFlash('error', "You've already reviewed this swap.");
    header("Location: /main/public/swaps.php");
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO reviews (swapId, userId, rating, comment) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$swapId, $currentUserId, $rating, $comment !== '' ? $comment : null]);
    setFlash('success', 'Thanks — your review has been posted.');
} catch (PDOException $e) {
    // 23000 = unique/constraint violation (e.g. a duplicate double-submit).
    if ($e->getCode() === '23000') {
        setFlash('error', "You've already reviewed this swap.");
    } else {
        throw $e;
    }
}

header("Location: /main/public/swaps.php");
exit;
