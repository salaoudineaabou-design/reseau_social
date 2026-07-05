<?php
require_once __DIR__ . '/functions.php';
requireAuth();
$pdo = getDB();

$postId = (int)($_GET['post_id'] ?? 0);
if (!$postId) jsonResponse(['success' => false, 'message' => 'post_id requis.'], 400);

$stmt = $pdo->prepare("
    SELECT c.*, u.prenom, u.nom, u.avatar
    FROM comments c JOIN users u ON u.id = c.user_id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$postId]);
$comments = $stmt->fetchAll();

jsonResponse(['success' => true, 'comments' => $comments]);
