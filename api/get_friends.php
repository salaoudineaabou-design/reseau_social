<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$pdo = getDB();

// Amis acceptés
$stmt = $pdo->prepare("
    SELECT u.id, u.prenom, u.nom, u.avatar, u.bio
    FROM friendships f
    JOIN users u ON u.id = IF(f.user_id = ?, f.friend_id, f.user_id)
    WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
");
$stmt->execute([$user['id'], $user['id'], $user['id']]);
$friends = $stmt->fetchAll();

// Invitations reçues en attente
$stmt = $pdo->prepare("
    SELECT f.id AS friendship_id, u.id, u.prenom, u.nom, u.avatar
    FROM friendships f
    JOIN users u ON u.id = f.user_id
    WHERE f.friend_id = ? AND f.status = 'pending'
");
$stmt->execute([$user['id']]);
$pendingReceived = $stmt->fetchAll();

jsonResponse(['success' => true, 'friends' => $friends, 'pending_received' => $pendingReceived]);
