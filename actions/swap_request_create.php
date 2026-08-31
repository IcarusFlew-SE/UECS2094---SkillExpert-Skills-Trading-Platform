<?php
/**
 * Handles "Request a Swap" submissions from public/details.php.
 *
 * POST only. Requires login (a signed-out visitor is bounced to login by
 * auth/session_check.php before any of this runs).
 *
 * Expected POST fields:
 *   skill_id          int, required  — the skill being requested
 *   offered_skill_id  int, optional  — one of the requester's own skills
 *   message           string, optional (max 500 chars)
 *
 * On success: redirect back to the skill's details page with a flash
 * message. On failure: same redirect, with an error flash instead — we
 * don't re-render a form here, Post/Redirect/Get keeps this handler simple.
 */

require_once __DIR__ . '/../auth/session_check.php'; // ensures $_SESSION['user_id'] exists
require_once __DIR__ . '/../config/db.php';           // gives us $pdo
require_once __DIR__ . '/../includes/swap_functions.php';

$currentUserId = (int) $_SESSION['user_id'];

// Only accept POST — a GET request to this URL has nothing to do.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /main/public/browse.php");
    exit;
}

$skillId         = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
$offeredSkillId  = filter_input(INPUT_POST, 'offered_skill_id', FILTER_VALIDATE_INT); // false/null if blank or invalid
$message         = trim($_POST['message'] ?? '');

// Cap message length so a runaway textarea can't bloat the table.
if (mb_strlen($message) > 500) {
    $message = mb_substr($message, 0, 500);
}

// Where do we send the user back to? Always the skill they were looking at.
$redirectTarget = "/main/public/details.php?id=" . ($skillId ?: '');

if (!$skillId) {
    setFlash('error', 'Missing or invalid skill.');
    header("Location: /main/public/browse.php");
    exit;
}

// Look up the skill so we know who the receiver is, and confirm it exists.
$stmt = $pdo->prepare("SELECT id, userId FROM skills WHERE id = ? LIMIT 1");
$stmt->execute([$skillId]);
$skill = $stmt->fetch();

if (!$skill) {
    setFlash('error', 'That skill no longer exists.');
    header("Location: /main/public/browse.php");
    exit;
}

$receiverId = (int) $skill['userId'];

// Can't request a swap on your own listing.
if ($receiverId === $currentUserId) {
    setFlash('error', "You can't request a swap on your own skill.");
    header("Location: $redirectTarget");
    exit;
}

// If an offered skill was chosen, make sure it actually belongs to the
// requester — otherwise someone could offer away a skill they don't own.
if ($offeredSkillId) {
    $stmt = $pdo->prepare("SELECT id FROM skills WHERE id = ? AND userId = ? LIMIT 1");
    $stmt->execute([$offeredSkillId, $currentUserId]);
    if (!$stmt->fetch()) {
        $offeredSkillId = null; // silently ignore an invalid/foreign skill id
    }
} else {
    $offeredSkillId = null; // normalise false -> null for the DB column
}

// Avoid piling up duplicate pending requests for the same skill from the
// same requester (friendlier than relying on the user to notice).
$stmt = $pdo->prepare(
    "SELECT id FROM swapRequests
     WHERE skillId = ? AND requesterId = ? AND status = 'pending'
     LIMIT 1"
);
$stmt->execute([$skillId, $currentUserId]);
if ($stmt->fetch()) {
    setFlash('error', 'You already have a pending request for this skill.');
    header("Location: $redirectTarget");
    exit;
}

$stmt = $pdo->prepare(
    "INSERT INTO swapRequests (skillId, requesterId, receiverId, offeredSkillId, message, status)
     VALUES (?, ?, ?, ?, ?, 'pending')"
);
$stmt->execute([$skillId, $currentUserId, $receiverId, $offeredSkillId, $message !== '' ? $message : null]);

setFlash('success', 'Swap request sent! You can track it from My Swaps.');
header("Location: $redirectTarget");
exit;
