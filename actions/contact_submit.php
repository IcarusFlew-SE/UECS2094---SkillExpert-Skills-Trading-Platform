<?php
/**
 * Handles Contact page form submissions (Create).
 *
 * POST only. No login required — a contact form needs to work for
 * visitors too. If the sender happens to be logged in, we still record
 * their userId (nullable column) purely so the message can be traced back
 * to an account later; it's not required for the insert to succeed.
 *
 * Expected POST fields:
 *   name     string, required
 *   email    string, required, valid email format
 *   subject  string, optional
 *   message  string, required (max 2000 chars)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php'; // setFlash()/getAndClearFlash()

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /main/public/contact.php");
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '') {
    $errors[] = 'Name is required.';
}
if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
}
if ($message === '') {
    $errors[] = 'Message is required.';
}

if (strlen($message) > 2000) {
    $message = substr($message, 0, 2000);
}
if (strlen($subject) > 150) {
    $subject = substr($subject, 0, 150);
}

if (!empty($errors)) {
    setFlash('error', implode(' ', $errors));
    header("Location: /main/public/contact.php");
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

$stmt = $pdo->prepare(
    "INSERT INTO contactMessages (userId, name, email, subject, message)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$userId, $name, $email, $subject !== '' ? $subject : null, $message]);

setFlash('success', "Thanks, {$name} — your message has been sent. We'll get back to you soon.");
header("Location: /main/public/contact.php");
exit;
