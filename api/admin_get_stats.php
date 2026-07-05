<?php
require_once __DIR__ . '/functions.php';
requireStaff();
$pdo = getDB();

$stats = [
    'total_users' => (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch()['c'],
    'total_posts' => (int)$pdo->query("SELECT COUNT(*) c FROM posts")->fetch()['c'],
    'total_comments' => (int)$pdo->query("SELECT COUNT(*) c FROM comments")->fetch()['c'],
    'total_messages' => (int)$pdo->query("SELECT COUNT(*) c FROM messages")->fetch()['c'],
    'total_moderateurs' => (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE role='moderateur'")->fetch()['c'],
    'total_admins' => (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch()['c'],
    'users_bannis' => (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE status='banni'")->fetch()['c'],
    'inscriptions_7j' => (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetch()['c'],
];

// Répartition des inscriptions sur les 7 derniers jours (pour graphique)
$stmt = $pdo->query("
    SELECT DATE(created_at) as jour, COUNT(*) as total
    FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY
    GROUP BY DATE(created_at) ORDER BY jour ASC
");
$stats['inscriptions_par_jour'] = $stmt->fetchAll();

jsonResponse(['success' => true, 'stats' => $stats]);
