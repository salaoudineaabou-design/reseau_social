<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$pdo = getDB();

$otherId = (int)($_GET['user_id'] ?? 0);
$sinceId = (int)($_GET['since_id'] ?? 0); // pour ne récupérer que les nouveaux messages
if (!$otherId) jsonResponse(['success' => false, 'message' => 'user_id requis.'], 400);

$sql = "SELECT * FROM messages WHERE
        ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))";
$params = [$user['id'], $otherId, $otherId, $user['id']];

if ($sinceId > 0) {
    $sql .= " AND id > ?";
    $params[] = $sinceId;
}
$sql .= " ORDER BY created_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Marquer comme lus les messages reçus
$stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
$stmt->execute([$otherId, $user['id']]);

jsonResponse(['success' => true, 'messages' => $messages]);
