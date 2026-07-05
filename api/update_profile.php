<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$prenom = sanitize($_POST['prenom'] ?? $user['prenom']);
$nom = sanitize($_POST['nom'] ?? $user['nom']);
$bio = sanitize($_POST['bio'] ?? $user['bio']);

$avatarPath = $user['avatar'];
if (!empty($_FILES['avatar']['name'])) {
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        jsonResponse(['success' => false, 'message' => 'Format d\'image non autorisé.'], 400);
    }
    $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_DIR_AVATARS . $filename);
    $avatarPath = 'assets/images/avatars/' . $filename;
}

$pdo = getDB();
$stmt = $pdo->prepare('UPDATE users SET prenom = ?, nom = ?, bio = ?, avatar = ? WHERE id = ?');
$stmt->execute([$prenom, $nom, $bio, $avatarPath, $user['id']]);

jsonResponse(['success' => true, 'message' => 'Profil mis à jour.', 'avatar' => $avatarPath]);
