<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$input = getJsonInput();

$postId = (int)($input['post_id'] ?? 0);
$content = sanitize($input['content'] ?? '');

if (!$postId || !$content) {
    jsonResponse(['success' => false, 'message' => 'Requête invalide.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
$stmt->execute([$postId, $user['id'], $content]);

jsonResponse([
    'success' => true,
    'comment' => [
        'id' => (int)$pdo->lastInsertId(),
        'content' => $content,
        'prenom' => $user['prenom'],
        'nom' => $user['nom'],
        'avatar' => $user['avatar'],
        'created_at' => date('Y-m-d H:i:s'),
    ],
]);
