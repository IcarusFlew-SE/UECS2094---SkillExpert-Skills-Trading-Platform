<?php
// Start session only if one isn't already running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session contract (project-wide — do NOT change these key names):
//   $_SESSION['user_id']  -> int   — logged-in user's id
//   $_SESSION['name']     -> string — logged-in user's display name
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'SkillExpert'); ?></title>
    <link rel="icon" type="image/png" href="/main/assets/img/logo-icon-dark.png">
    <link rel="stylesheet" href="/main/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
    <link rel="stylesheet" href="/main/assets/css/layout.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/layout.css'); ?>">
    <link rel="stylesheet" href="/main/assets/css/components.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/components.css'); ?>">
</head>
<body>

<?php require_once __DIR__ . '/nav.php'; ?>

<main>
