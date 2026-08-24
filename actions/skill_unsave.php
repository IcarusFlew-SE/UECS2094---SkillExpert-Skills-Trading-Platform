<?php
/**
 * Removes a skill from the current user's saved list (Delete).
 *
 * POST only. Requires login. Ownership is baked directly into the DELETE
 * (userId = the logged-in user) rather than checked separately — if the
 * row isn't theirs, rowCount() is 0 and nothing happens.
 *
 * Expected POST fields:
 *   skill_id   int, required
 *   return_to  string, optional — where to redirect back to (defaults to
 *              the Saved page; details.php passes its own URL so unsaving
 *              from there doesn't bounce you away from what you're looking at)
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /main/public/saved.php");
    exit;
}

$skillId  = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
$returnTo = $_POST['return_to'] ?? '/main/public/saved.php';

// Only allow redirecting back within this app's own pages.
if (!is_string($returnTo) || strpos($returnTo, '/main/') !== 0) {
    $returnTo = '/main/public/saved.php';
}

if (!$skillId) {
    setFlash('error', 'Invalid skill.');
    header("Location: $returnTo");
    exit;
}

$stmt = $pdo->prepare("DELETE FROM savedSkills WHERE userId = ? AND skillId = ?");
$stmt->execute([$currentUserId, $skillId]);

if ($stmt->rowCount() > 0) {
    setFlash('success', 'Removed from your saved list.');
} else {
    setFlash('error', "That wasn't in your saved list.");
}

header("Location: $returnTo");
exit;
