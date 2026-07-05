<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$receiverId = (int)($_POST['receiver_id'] ?? 0);
$content = sanitize($_POST['content'] ?? '');

if (!$receiverId) jsonResponse(['success' => false, 'message' => 'Destinataire requis.'], 400);

$imagePath = null;
if (!empty($_FILES['image']['name'])) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        jsonResponse(['success' => false, 'message' => 'Format d\'image non autorisé.'], 400);
    }
    $filename = uniqid('chat_') . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR_CHAT . $filename);
    $imagePath = 'assets/images/chat/' . $filename;
}

if (!$content && !$imagePath) {
    jsonResponse(['success' => false, 'message' => 'Message vide.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content, image) VALUES (?, ?, ?, ?)');
$stmt->execute([$user['id'], $receiverId, $content ?: null, $imagePath]);

jsonResponse([
    'success' => true,
    'message' => [
        'id' => (int)$pdo->lastInsertId(),
        'sender_id' => (int)$user['id'],
        'receiver_id' => $receiverId,
        'content' => $content,
        'image' => $imagePath,
        'created_at' => date('Y-m-d H:i:s'),
    ],
]);
