<?php

require 'db.php';

// Only act on POST; otherwise go back to the create page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$tags        = trim($_POST['tags'] ?? '');

// Title is required.
if ($title === '') {
    header('Location: create.php?error=empty');
    exit;
}

// Optional text fields: store NULL when left blank.
$description = $description === '' ? null : $description;
$category    = $category === '' ? null : $category;
$tags        = $tags === '' ? null : $tags;

// Handle the optional cover image upload.
$coverPath = null;

if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {

    // Validate that the uploaded file is a real image.
    $info = getimagesize($_FILES['cover']['tmp_name']);
    if ($info === false) {
        header('Location: create.php?error=image');
        exit;
    }

    // Derive extension from the real image type, not from user input.
    $ext = image_type_to_extension($info[2], false);
    if ($ext === false) {
        header('Location: create.php?error=image');
        exit;
    }

    // Make sure the destination directory exists.
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'covers';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = uniqid('', true) . '.' . $ext;
    $target   = $uploadDir . DIRECTORY_SEPARATOR . $filename;
//imomove yung mga file sa image file dito sa vscode ang file exp.
    if (!move_uploaded_file($_FILES['cover']['tmp_name'], $target)) {
        header('Location: create.php?error=image');
        exit;
    }

    // TO THE DATABASE
    $coverPath = 'images/covers/' . $filename;
}

$status = 'draft';

// Insert using a prepared statement; mysqli throws on error.
try {
    $stmt = $conn->prepare(
        'INSERT INTO deck (title, description, category, tags, cover_image, status) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssssss', $title, $description, $category, $tags, $coverPath, $status);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} catch (mysqli_sql_exception $e) {
    header('Location: create.php?error=db');
    exit;
}

header('Location: create.php?saved=1');
exit;
