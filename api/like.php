<?php
/**
 * api/like.php — POST
 * Gère le like/dislike d'un article avec logique de toggle :
 * - Nouveau vote → INSERT
 * - Même vote déjà présent → annule (DELETE)
 * - Vote différent déjà présent → change (UPDATE)
 * Reçoit : { user_id, article_id, type }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$connecte = verifier_session($pdo);

$body       = lire_json_body();
$article_id = (int)($body['article_id'] ?? 0);
$type       = $body['type'] ?? '';

if ($article_id <= 0 || !in_array($type, ['like', 'dislike'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

$check = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE id = ?');
$check->execute([$article_id]);
if ($check->fetchColumn() == 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, type FROM likes WHERE user_id = ? AND article_id = ?');
$stmt->execute([$connecte['id'], $article_id]);
$vote_existant = $stmt->fetch();

if ($vote_existant) {
    if ($vote_existant['type'] === $type) {
        $pdo->prepare('DELETE FROM likes WHERE id = ?')->execute([$vote_existant['id']]);
        $action = 'annule';
        $mon_vote = null;
    } else {
        $pdo->prepare('UPDATE likes SET type = ? WHERE id = ?')->execute([$type, $vote_existant['id']]);
        $action = 'modifie';
        $mon_vote = $type;
    }
} else {
    $pdo->prepare('INSERT INTO likes (user_id, article_id, type) VALUES (?, ?, ?)')
        ->execute([$connecte['id'], $article_id, $type]);
    $action = 'ajoute';
    $mon_vote = $type;
}

$stmt = $pdo->prepare(
    "SELECT SUM(type = 'like') AS nb_likes, SUM(type = 'dislike') AS nb_dislikes
     FROM likes WHERE article_id = ?"
);
$stmt->execute([$article_id]);
$totaux = $stmt->fetch();

echo json_encode([
    'success'     => true,
    'action'      => $action,
    'mon_vote'    => $mon_vote,
    'nb_likes'    => (int)($totaux['nb_likes'] ?? 0),
    'nb_dislikes' => (int)($totaux['nb_dislikes'] ?? 0),
]);
