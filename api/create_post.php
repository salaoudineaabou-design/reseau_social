<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

// multipart/form-data pour supporter l'upload d'image
$content = sanitize($_POST['content'] ?? '');
if (!$content) {
    jsonResponse(['success' => false, 'message' => 'Le contenu ne peut pas être vide.'], 400);
}

$imagePath = null;
if (!empty($_FILES['image']['name'])) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        jsonResponse(['success' => false, 'message' => 'Format d\'image non autorisé.'], 400);
    }
    $filename = uniqid('post_') . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR_POSTS . $filename);
    $imagePath = 'assets/images/posts/' . $filename;
}

$pdo = getDB();
$stmt = $pdo->prepare('INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)');
$stmt->execute([$user['id'], $content, $imagePath]);

jsonResponse(['success' => true, 'message' => 'Article publié.', 'post_id' => (int)$pdo->lastInsertId()]);
