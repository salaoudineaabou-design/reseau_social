<?php
/**
 * api/reset_password.php — POST
 * Change le mot de passe à l'aide d'un token valide et non expiré.
 * Reçoit : { token, nouveau_mdp }
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

$body        = lire_json_body();
$token       = $body['token'] ?? '';
$nouveau_mdp = (string)($body['nouveau_mdp'] ?? '');

if (strlen($nouveau_mdp) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mot de passe trop court (8 caractères minimum)']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, user_id FROM password_resets
     WHERE token = ? AND expire_at > NOW() AND utilise = 0'
);
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lien invalide ou expiré']);
    exit;
}

$hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
$pdo->prepare('UPDATE users SET mot_de_passe = ? WHERE id = ?')
    ->execute([$hash, $reset['user_id']]);

$pdo->prepare('UPDATE password_resets SET utilise = 1 WHERE id = ?')
    ->execute([$reset['id']]);

echo json_encode(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
