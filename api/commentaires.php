<?php
/**
 * api/commentaires.php
 * GET  ?article_id=X → Liste des commentaires d'un article
 * POST { user_id, article_id, contenu } → Ajoute un commentaire
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    $article_id = (int)($_GET['article_id'] ?? 0);

    if ($article_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'article_id invalide']);
        exit;
    }

    $check = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE id = ?');
    $check->execute([$article_id]);
    if ($check->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT c.id, c.contenu, c.created_at,
               u.id AS auteur_id, u.nom AS auteur_nom, u.prenom AS auteur_prenom, u.avatar AS auteur_avatar
        FROM commentaires c
        JOIN users u ON u.id = c.user_id
        WHERE c.article_id = ?
        ORDER BY c.created_at ASC
    ');
    $stmt->execute([$article_id]);
    $commentaires = $stmt->fetchAll();

    echo json_encode(['success' => true, 'total' => count($commentaires), 'data' => $commentaires]);
    exit;
}

if ($methode === 'POST') {
    $connecte = verifier_session($pdo);

    $body       = lire_json_body();
    $article_id = (int)($body['article_id'] ?? 0);
    $contenu    = nettoyer_texte($body['contenu'] ?? '');

    if ($article_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'article_id invalide']);
        exit;
    }

    if ($contenu === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas être vide']);
        exit;
    }

    $check = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE id = ?');
    $check->execute([$article_id]);
    if ($check->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO commentaires (user_id, article_id, contenu) VALUES (?, ?, ?)');
    $stmt->execute([$connecte['id'], $article_id, $contenu]);
    $nouveau_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare('
        SELECT c.id, c.contenu, c.created_at,
               u.id AS auteur_id, u.nom AS auteur_nom, u.prenom AS auteur_prenom, u.avatar AS auteur_avatar
        FROM commentaires c
        JOIN users u ON u.id = c.user_id
        WHERE c.id = ?
    ');
    $stmt->execute([$nouveau_id]);
    $commentaire_complet = $stmt->fetch();

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Commentaire ajouté', 'commentaire' => $commentaire_complet]);
    exit;
}

if ($methode === 'DELETE') {
    $connecte = verifier_session($pdo);
    $body     = lire_json_body();
    $com_id   = (int)($body['commentaire_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT user_id FROM commentaires WHERE id = ?');
    $stmt->execute([$com_id]);
    $com = $stmt->fetch();

    if (!$com) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Commentaire introuvable']);
        exit;
    }

    $est_auteur     = ((int)$com['user_id'] === (int)$connecte['id']);
    $est_moderation = in_array($connecte['role'], ['admin', 'moderateur'], true);

    if (!$est_auteur && !$est_moderation) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }

    $pdo->prepare('DELETE FROM commentaires WHERE id = ?')->execute([$com_id]);
    echo json_encode(['success' => true, 'message' => 'Commentaire supprimé']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
