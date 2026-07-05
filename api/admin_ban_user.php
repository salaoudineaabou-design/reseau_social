<?php
require_once __DIR__ . '/functions.php';
$staff = requireStaff();
$input = getJsonInput();
$id = (int)($input['id'] ?? 0);
$action = $input['action'] ?? 'ban'; // ban | unban
if (!$id) jsonResponse(['success' => false, 'message' => 'id requis.'], 400);

$pdo = getDB();
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$id]);
$target = $stmt->fetch();
if (!$target) jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
if ($staff['role'] === 'moderateur' && $target['role'] !== 'user') {
    jsonResponse(['success' => false, 'message' => 'Action réservée à l\'administrateur.'], 403);
}

$newStatus = $action === 'ban' ? 'banni' : 'actif';
$stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
$stmt->execute([$newStatus, $id]);

jsonResponse(['success' => true, 'message' => 'Statut mis à jour.']);
