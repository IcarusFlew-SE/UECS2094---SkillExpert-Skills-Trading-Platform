<?php
/**
 * Adds a comment to a skill's discussion thread (public/details.php).
 * Unlike reviews, any logged-in user can comment on any skill — no
 * completed swap required.
 *
 * POST only. Requires login.
 * Expected POST fields:
 *   skill_id      int, required
 *   comment_text  string, required (max 1000 chars)
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /main/public/browse.php");
    exit;
}

$skillId = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
$text    = trim($_POST['comment_text'] ?? '');
$redirectTarget = "/main/public/details.php?id=" . ($skillId ?: '') . "#comments";

if (!$skillId) {
    setFlash('error', 'Missing skill.');
    header("Location: /main/public/browse.php");
    exit;
}

if ($text === '') {
    setFlash('error', 'Comment cannot be empty.');
    header("Location: $redirectTarget");
    exit;
}

if (mb_strlen($text) > 1000) {
    $text = mb_substr($text, 0, 1000);
}

// Confirm the skill actually exists before attaching a comment to it.
$stmt = $pdo->prepare("SELECT id FROM skills WHERE id = ? LIMIT 1");
$stmt->execute([$skillId]);
if (!$stmt->fetch()) {
    setFlash('error', 'That skill no longer exists.');
    header("Location: /main/public/browse.php");
    exit;
}

$stmt = $pdo->prepare(
    "INSERT INTO comments (skillId, userId, commentText) VALUES (?, ?, ?)"
);
$stmt->execute([$skillId, $currentUserId, $text]);

setFlash('success', 'Comment posted.');
header("Location: $redirectTarget");
exit;
