<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$input = getJsonInput();

$postId = (int)($input['post_id'] ?? 0);
$type = $input['type'] ?? '';

if (!$postId || !in_array($type, ['like', 'dislike'])) {
    jsonResponse(['success' => false, 'message' => 'Requête invalide.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM post_reactions WHERE post_id = ? AND user_id = ?');
$stmt->execute([$postId, $user['id']]);
$existing = $stmt->fetch();

if ($existing && $existing['type'] === $type) {
    // Clic sur la même réaction => on la retire (toggle off)
    $stmt = $pdo->prepare('DELETE FROM post_reactions WHERE id = ?');
    $stmt->execute([$existing['id']]);
    $myReaction = null;
} elseif ($existing) {
    // Changement de like vers dislike ou inversement
    $stmt = $pdo->prepare('UPDATE post_reactions SET type = ? WHERE id = ?');
    $stmt->execute([$type, $existing['id']]);
    $myReaction = $type;
} else {
    $stmt = $pdo->prepare('INSERT INTO post_reactions (post_id, user_id, type) VALUES (?, ?, ?)');
    $stmt->execute([$postId, $user['id'], $type]);
    $myReaction = $type;
}

$stmt = $pdo->prepare("SELECT
    (SELECT COUNT(*) FROM post_reactions WHERE post_id = ? AND type = 'like') AS likes,
    (SELECT COUNT(*) FROM post_reactions WHERE post_id = ? AND type = 'dislike') AS dislikes
");
$stmt->execute([$postId, $postId]);
$counts = $stmt->fetch();

jsonResponse([
    'success' => true,
    'likes' => (int)$counts['likes'],
    'dislikes' => (int)$counts['dislikes'],
    'my_reaction' => $myReaction,
]);
