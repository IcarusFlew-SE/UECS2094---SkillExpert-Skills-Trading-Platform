<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// ==========================================
// CHECK LOGIN
// ==========================================

if (!isset($_SESSION['user_id'])) {

    header('Location: /main/auth/login.php');

    exit;

}


// ==========================================
// ONLY ALLOW POST
// ==========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: /main/public/posting.php');

    exit;

}


// ==========================================
// GET FORM DATA
// ==========================================

$title =
    trim($_POST['title'] ?? '');

$category =
    trim($_POST['category'] ?? '');

$description =
    trim($_POST['description'] ?? '');

$userId =
    (int) $_SESSION['user_id'];


// ==========================================
// VALIDATE REQUIRED FIELDS
// ==========================================

if (
    $title === '' ||
    $category === '' ||
    $description === ''
) {

    die(
        'Please fill in all required fields.'
    );

}


// ==========================================
// INSERT SKILL
// ==========================================

$sql = "
    INSERT INTO skills
        (
            userId,
            title,
            category,
            description
        )
    VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
";


$stmt =
    $pdo->prepare($sql);


$stmt->execute([
    $userId,
    $title,
    $category,
    $description
]);


// ==========================================
// SUCCESS
// ==========================================

header(
    'Location: /main/public/posting.php?published=1'
);

exit;