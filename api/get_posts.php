<?php
require_once __DIR__ . '/functions.php';
$currentUser = requireAuth();
$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT p.*, u.prenom, u.nom, u.avatar,
        (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id AND type = 'like') AS likes,
        (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id AND type = 'dislike') AS dislikes,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
        (SELECT type FROM post_reactions WHERE post_id = p.id AND user_id = ?) AS my_reaction
    FROM posts p
    JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
    LIMIT 100
");
$stmt->execute([$currentUser['id']]);
$posts = $stmt->fetchAll();

foreach ($posts as &$p) {
    $p['id'] = (int)$p['id'];
    $p['likes'] = (int)$p['likes'];
    $p['dislikes'] = (int)$p['dislikes'];
    $p['comments_count'] = (int)$p['comments_count'];
}

jsonResponse(['success' => true, 'posts' => $posts]);
