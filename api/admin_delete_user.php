<?php
require_once __DIR__ . '/functions.php';
$staff = requireStaff();
$input = getJsonInput();
$id = (int)($input['id'] ?? 0);
if (!$id) jsonResponse(['success' => false, 'message' => 'id requis.'], 400);

$pdo = getDB();
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$id]);
$target = $stmt->fetch();
if (!$target) jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);

// Un modérateur ne peut pas supprimer un admin ou un autre modérateur
if ($staff['role'] === 'moderateur' && $target['role'] !== 'user') {
    jsonResponse(['success' => false, 'message' => 'Action réservée à l\'administrateur.'], 403);
}

$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);

jsonResponse(['success' => true, 'message' => 'Utilisateur supprimé.']);
