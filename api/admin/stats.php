<?php
/**
 * api/admin/stats.php — GET
 * Statistiques globales pour le tableau de bord du back-office.
 * Reçoit : ?user_id=X (l'admin/modérateur connecté)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$user_id = (int)($_GET['user_id'] ?? 0);
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND role != 'user' AND is_banned = 0");
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$stats = [];
$stats['total_users']    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$stats['total_articles'] = (int)$pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
$stats['total_likes']    = (int)$pdo->query("SELECT COUNT(*) FROM likes WHERE type = 'like'")->fetchColumn();
$stats['total_messages'] = (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$stats['users_bannis']   = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_banned = 1')->fetchColumn();

$stmt = $pdo->query(
    'SELECT DATE(created_at) AS jour, COUNT(*) AS nb
     FROM users
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY jour ASC'
);
$stats['inscriptions_semaine'] = $stmt->fetchAll();

$stmt = $pdo->query(
    'SELECT a.id, LEFT(a.contenu, 60) AS apercu, COUNT(c.id) AS nb_commentaires
     FROM articles a
     LEFT JOIN commentaires c ON c.article_id = a.id
     GROUP BY a.id
     ORDER BY nb_commentaires DESC
     LIMIT 5'
);
$stats['articles_populaires'] = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $stats]);
