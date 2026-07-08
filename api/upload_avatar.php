<?php
/**
 * api/upload_avatar.php — POST (FormData)
 * Change la photo de profil. Vérifie le type MIME réel du fichier
 * (signature magique), pas seulement l'extension, pour empêcher
 * qu'un fichier malveillant renommé en .jpg ne soit accepté.
 * Reçoit : FormData { user_id, avatar (fichier) }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND email_verifie = 1 AND is_banned = 0');
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session invalide']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu']);
    exit;
}

// Vérification du type MIME RÉEL via la signature magique du fichier
$finfo     = new finfo(FILEINFO_MIME_TYPE);
$mime_reel = $finfo->file($_FILES['avatar']['tmp_name']);
$mimes_ok  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

if (!isset($mimes_ok[$mime_reel])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format image non autorisé']);
    exit;
}

if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image trop lourde (max 2 Mo)']);
    exit;
}

$ext         = $mimes_ok[$mime_reel];
$nom_fichier = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
$destination = __DIR__ . '/../uploads/' . $nom_fichier;

move_uploaded_file($_FILES['avatar']['tmp_name'], $destination);
$chemin = 'uploads/' . $nom_fichier;

$pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$chemin, $user_id]);

echo json_encode(['success' => true, 'avatar' => $chemin]);
