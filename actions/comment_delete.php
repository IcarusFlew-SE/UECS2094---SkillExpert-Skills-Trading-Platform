<?php
/**
 * Deletes one of the current user's own comments (Delete of the comment
 * CRUD set). Only the author of a comment may remove it.
 *
 * POST only. Requires login.
 * Expected POST fields: comment_id int, required; skill_id int, required
 * (just used to build the redirect back to the right details page).
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /main/public/browse.php");
    exit;
}

$commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
$skillId   = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
$redirectTarget = "/main/public/details.php?id=" . ($skillId ?: '') . "#comments";

if (!$commentId) {
    setFlash('error', 'Invalid comment.');
    header("Location: $redirectTarget");
    exit;
}

$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND userId = ?");
$stmt->execute([$commentId, $currentUserId]);

if ($stmt->rowCount() > 0) {
    setFlash('success', 'Comment deleted.');
} else {
    setFlash('error', "That comment couldn't be deleted (not found, or not yours).");
}

header("Location: $redirectTarget");
exit;
