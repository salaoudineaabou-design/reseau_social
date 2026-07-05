<?php
require_once __DIR__ . '/functions.php';
requireStaff();
$pdo = getDB();
$stmt = $pdo->query("
    SELECT p.*, u.prenom, u.nom,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
    FROM posts p JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
");
jsonResponse(['success' => true, 'posts' => $stmt->fetchAll()]);
