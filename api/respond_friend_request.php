<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$input = getJsonInput();

$friendshipId = (int)($input['friendship_id'] ?? 0);
$action = $input['action'] ?? ''; // accept | refuse

if (!$friendshipId || !in_array($action, ['accept', 'refuse'])) {
    jsonResponse(['success' => false, 'message' => 'Requête invalide.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM friendships WHERE id = ? AND friend_id = ?');
$stmt->execute([$friendshipId, $user['id']]);
$friendship = $stmt->fetch();

if (!$friendship) {
    jsonResponse(['success' => false, 'message' => 'Invitation introuvable.'], 404);
}

$newStatus = $action === 'accept' ? 'accepted' : 'refused';
$stmt = $pdo->prepare('UPDATE friendships SET status = ? WHERE id = ?');
$stmt->execute([$newStatus, $friendshipId]);

jsonResponse(['success' => true, 'message' => $action === 'accept' ? 'Invitation acceptée.' : 'Invitation refusée.']);
