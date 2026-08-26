<?php 
// Delete existing skill owned by user
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /main/public/my-skills.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$skillId = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);

if (!$skillId) {
    setFlash('error', 'Invalid skill selected.');
    header('Location: /main/public/my_skills.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM skills WHERE id = ? AND userId = ?');
$stmt->execute([$skillId, $currentUserId]);

setFlash($stmt->rowCount() > 0 ? 'success' : 'error', $stmt->rowCount() > 0 ? 'Skill deleted successfully.' : 'Skill not found, or you do not have permission to delete it.');
header('Location: /main/public/my_skills.php');
exit;
?>