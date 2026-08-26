<?php
/**
 * Delete an existing skill owned by the logged-in user.
 *
 * The database schema is intentionally wired with cascading foreign keys:
 * deleting a skill removes other users' saved entries, comments, and swap
 * requests for that skill. Reviews attached to those swaps are removed through
 * the swapRequests -> reviews cascade. If this skill was offered inside someone
 * else's swap request, that offeredSkillId is set to NULL instead of deleting
 * the other request.
 */
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /main/public/my_skills.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$skillId = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);

if (!$skillId) {
    setFlash('error', 'Invalid skill selected.');
    header('Location: /main/public/my_skills.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, title FROM skills WHERE id = ? AND userId = ? LIMIT 1');
$stmt->execute([$skillId, $currentUserId]);
$skill = $stmt->fetch();

if (!$skill) {
    setFlash('error', 'Skill not found, or you do not have permission to delete it.');
    header('Location: /main/public/my_skills.php');
    exit;
}

$impactStmt = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM savedSkills WHERE skillId = ?) AS savedCount,
        (SELECT COUNT(*) FROM comments WHERE skillId = ?) AS commentCount,
        (SELECT COUNT(*) FROM swapRequests WHERE skillId = ?) AS requestCount,
        (SELECT COUNT(*) FROM swapRequests WHERE skillId = ? AND status IN ('pending', 'accepted')) AS activeRequestCount,
        (SELECT COUNT(*)
         FROM reviews rv
         JOIN swapRequests sr ON rv.swapId = sr.id
         WHERE sr.skillId = ?) AS reviewCount"
);
$impactStmt->execute([$skillId, $skillId, $skillId, $skillId, $skillId]);
$impact = $impactStmt->fetch() ?: [
    'savedCount' => 0,
    'commentCount' => 0,
    'requestCount' => 0,
    'activeRequestCount' => 0,
    'reviewCount' => 0,
];

try {
    $pdo->beginTransaction();

    $deleteStmt = $pdo->prepare('DELETE FROM skills WHERE id = ? AND userId = ?');
    $deleteStmt->execute([$skillId, $currentUserId]);

    if ($deleteStmt->rowCount() !== 1) {
        $pdo->rollBack();
        setFlash('error', 'Skill not found, or you do not have permission to delete it.');
        header('Location: /main/public/my_skills.php');
        exit;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('error', 'Unable to delete that skill right now. Please try again.');
    header('Location: /main/public/my_skills.php');
    exit;
}

$impactParts = [];
if ((int) $impact['activeRequestCount'] > 0) {
    $impactParts[] = (int) $impact['activeRequestCount'] . ' active request(s) closed';
}
if ((int) $impact['requestCount'] > 0) {
    $impactParts[] = (int) $impact['requestCount'] . ' total swap request(s) removed';
}
if ((int) $impact['savedCount'] > 0) {
    $impactParts[] = (int) $impact['savedCount'] . ' saved-list item(s) removed';
}
if ((int) $impact['commentCount'] > 0) {
    $impactParts[] = (int) $impact['commentCount'] . ' comment(s) removed';
}
if ((int) $impact['reviewCount'] > 0) {
    $impactParts[] = (int) $impact['reviewCount'] . ' review(s) removed';
}

$message = 'Skill "' . $skill['title'] . '" was deleted.';
if (!empty($impactParts)) {
    $message .= ' Impact: ' . implode(', ', $impactParts) . '.';
}

setFlash('success', $message);
header('Location: /main/public/my_skills.php');
exit;