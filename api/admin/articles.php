<?php
/**
 * api/admin/articles.php
 * GET  ?user_id=X → Liste de tous les articles pour modération
 * POST { user_id, action:'supprimer', article_id } → Supprime un article
 * (admin ET modérateur autorisés)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

function verifier_moderation(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ? AND role != 'user' AND is_banned = 0");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    if (!$u) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }
    return $u;
}

$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    verifier_moderation($pdo, (int)($_GET['user_id'] ?? 0));

    $stmt = $pdo->query('
        SELECT a.id, a.contenu, a.image, a.created_at,
               u.id AS auteur_id, u.nom AS auteur_nom, u.prenom AS auteur_prenom,
               (SELECT COUNT(*) FROM commentaires WHERE article_id = a.id) AS nb_commentaires,
               (SELECT COUNT(*) FROM likes WHERE article_id = a.id AND type="like") AS nb_likes
        FROM articles a
        JOIN users u ON u.id = a.user_id
        ORDER BY a.created_at DESC
    ');
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($methode === 'POST') {
    $body = lire_json_body();
    verifier_moderation($pdo, (int)($body['user_id'] ?? 0));

    $action     = $body['action'] ?? '';
    $article_id = (int)($body['article_id'] ?? 0);

    if ($action === 'supprimer' && $article_id > 0) {
        $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([$article_id]);
        echo json_encode(['success' => true, 'message' => 'Article supprimé']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action inconnue ou paramètres invalides']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
