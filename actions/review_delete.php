<?php
/**
 * Deletes one of the current user's own reviews (Delete of the review CRUD
 * set). Only the author of a review may remove it.
 *
 * POST only. Requires login.
 * Expected POST fields: review_id int, required.
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /main/public/swaps.php");
    exit;
}

$reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
$returnTo = $_POST['return_to'] ?? '/main/public/swaps.php';

// Only allow redirecting back within this app's own pages.
if (!is_string($returnTo) || strpos($returnTo, '/main/') !== 0) {
    $returnTo = '/main/public/swaps.php';
}

if (!$reviewId) {
    setFlash('error', 'Invalid review.');
    header("Location: $returnTo");
    exit;
}

// Ownership check baked directly into the DELETE — if the row isn't yours,
// rowCount() will be 0 and nothing happens.
$stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ? AND userId = ?");
$stmt->execute([$reviewId, $currentUserId]);

if ($stmt->rowCount() > 0) {
    setFlash('success', 'Review deleted.');
} else {
    setFlash('error', "That review couldn't be deleted (not found, or not yours).");
}

header("Location: $returnTo");
exit;
