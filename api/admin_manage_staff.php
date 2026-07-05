<?php
/**
 * Gestion des modérateurs/administrateurs - réservé à l'admin.
 * actions: promote (user->moderateur), promote_admin (->admin), demote (->user), delete
 */
require_once __DIR__ . '/functions.php';
requireAdmin();
$input = getJsonInput();

$id = (int)($input['id'] ?? 0);
$action = $input['action'] ?? '';
if (!$id || !$action) jsonResponse(['success' => false, 'message' => 'Requête invalide.'], 400);

$pdo = getDB();
switch ($action) {
    case 'promote_moderateur':
        $stmt = $pdo->prepare("UPDATE users SET role = 'moderateur' WHERE id = ?");
        $stmt->execute([$id]);
        break;
    case 'promote_admin':
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->execute([$id]);
        break;
    case 'demote':
        $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
        $stmt->execute([$id]);
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Action inconnue.'], 400);
}

jsonResponse(['success' => true, 'message' => 'Rôle mis à jour.']);
