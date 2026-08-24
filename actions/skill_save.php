<?php
/**
 * Adds a skill to the current user's saved/wishlist list (Create).
 *
 * POST only. Requires login — saved items are personal data, and the
 * assignment specifically requires login before accessing features like
 * this ("Users must register and log in before accessing features that
 * store personal information, such as saved items, cart, wishlist...").
 *
 * Expected POST fields: skill_id int, required.
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';  // setFlash()/getAndClearFlash()
require_once __DIR__ . '/../includes/saved_functions.php';

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /main/public/browse.php");
    exit;
}

$skillId = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
$redirectTarget = "/main/public/details.php?id=" . ($skillId ?: '');

if (!$skillId) {
    setFlash('error', 'Missing skill.');
    header("Location: /main/public/browse.php");
    exit;
}

// Confirm the skill exists, and grab its owner so we can block self-saves.
$stmt = $pdo->prepare("SELECT id, userId FROM skills WHERE id = ? LIMIT 1");
$stmt->execute([$skillId]);
$skill = $stmt->fetch();

if (!$skill) {
    setFlash('error', 'That skill no longer exists.');
    header("Location: /main/public/browse.php");
    exit;
}

if ((int) $skill['userId'] === $currentUserId) {
    setFlash('error', "You can't save your own skill.");
    header("Location: $redirectTarget");
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO savedSkills (userId, skillId) VALUES (?, ?)");
    $stmt->execute([$currentUserId, $skillId]);
    setFlash('success', 'Saved — find it on your Saved page.');
} catch (PDOException $e) {
    // 23000 = unique constraint violation (already saved this one).
    if ($e->getCode() === '23000') {
        setFlash('error', "You've already saved this skill.");
    } else {
        throw $e;
    }
}

header("Location: $redirectTarget");
exit;
