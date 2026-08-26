<?php
// Update existing skill
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /main/public/my_skills.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$skillId = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$allowedCategories = ['Programming', 'Design', 'Language', 'Music', 'Sports', 'Academic', 'Other'];

if (!$skillId || $title === '' || $category === '' || $description === '') {
    setFlash('error', 'Please fill in all required skill fields.');
    header('Location: /main/public/my_skills.php' . ($skillId ? '? edit=' . $skillId : ''));
    exit;
}

if (mb_strlen($title) > 150 || mb_strlen($description) > 1000 || !in_array($category, $allowedCategories, true)) {
    setFlash('error', 'Please keep your skill within the allowed length and category choices.');
    header('Location: /main/public/my_skills.php?edit=' . $skillId);
    exit;
}

$stmt = $pdo->prepare('UPDATE skills SET title = ?, category = ?, description = ? WHERE id = ? AND userId = ?');
$stmt->execute([$title, $category, $description, $skillId, $currentUserId]);

setFlash($stmt->rowCount() > 0 ? 'success' : 'error', $stmt->rowCount() > 0 ? 'Skill updated successfully.' : 'No changes were saved, or you do not have permission to edit that skill.');
header('Location: /main/public/my_skills.php');
exit;