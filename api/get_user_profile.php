<?php
require_once __DIR__ . '/functions.php';
requireAuth();
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) jsonResponse(['success' => false, 'message' => 'id requis.'], 400);

$stmt = $pdo->prepare('SELECT id, prenom, nom, avatar, bio, created_at FROM users WHERE id = ?');
$stmt->execute([$id]);
$profile = $stmt->fetch();

if (!$profile) jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);

$stmt = $pdo->prepare("
    SELECT p.*, u.prenom, u.nom, u.avatar
    FROM posts p JOIN users u ON u.id = p.user_id
    WHERE p.user_id = ? ORDER BY p.created_at DESC
");
$stmt->execute([$id]);
$posts = $stmt->fetchAll();

jsonResponse(['success' => true, 'profile' => $profile, 'posts' => $posts]);
