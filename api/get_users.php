<?php
require_once __DIR__ . '/functions.php';
$currentUser = requireAuth();
$pdo = getDB();

$search = sanitize($_GET['search'] ?? '');
$sql = "SELECT id, prenom, nom, avatar, bio FROM users WHERE id != ? AND status = 'actif'";
$params = [$currentUser['id']];
if ($search) {
    $sql .= " AND (prenom LIKE ? OR nom LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY prenom ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Ajoute le statut d'amitié entre l'utilisateur courant et chaque résultat
$stmt2 = $pdo->prepare("SELECT * FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
foreach ($users as &$u) {
    $stmt2->execute([$currentUser['id'], $u['id'], $u['id'], $currentUser['id']]);
    $f = $stmt2->fetch();
    if (!$f) {
        $u['friendship_status'] = 'none';
    } elseif ($f['status'] === 'accepted') {
        $u['friendship_status'] = 'friends';
    } elseif ($f['user_id'] == $currentUser['id']) {
        $u['friendship_status'] = 'pending_sent';
    } else {
        $u['friendship_status'] = 'pending_received';
        $u['friendship_id'] = (int)$f['id'];
    }
    $u['id'] = (int)$u['id'];
}

jsonResponse(['success' => true, 'users' => $users]);
