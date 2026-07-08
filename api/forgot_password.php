<?php
/**
 * api/forgot_password.php — POST
 * Génère un token de réinitialisation temporaire (1h) et envoie
 * un email HTML avec le lien. Reçoit : { email }
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

$body  = lire_json_body();
$email = trim($body['email'] ?? '');

// Message générique renvoyé dans tous les cas : ne jamais confirmer
// si un email existe ou non en base (sécurité anti-énumération).
$message_generique = 'Si cet email existe, un lien de réinitialisation a été envoyé.';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => true, 'message' => $message_generique]);
    exit;
}

$stmt = $pdo->prepare('SELECT id, prenom FROM users WHERE email = ? AND email_verifie = 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => true, 'message' => $message_generique]);
    exit;
}

$token     = bin2hex(random_bytes(32));
$expire_at = date('Y-m-d H:i:s', time() + 3600); // +1 heure

// Nettoyer les anciens tokens de cet utilisateur
$pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);

$pdo->prepare(
    'INSERT INTO password_resets (user_id, token, expire_at) VALUES (?, ?, ?)'
)->execute([$user['id'], $token, $expire_at]);

$lien  = rtrim(APP_BASE_URL, '/') . '/index.html?action=reset&token=' . $token;
$corps = gabarit_email(
    'Réinitialisation de mot de passe',
    '<p>Bonjour ' . htmlspecialchars($user['prenom'], ENT_QUOTES, 'UTF-8') . ',</p>
     <p>Tu as demandé la réinitialisation de ton mot de passe. Clique sur le bouton ci-dessous pour choisir un nouveau mot de passe.</p>
     <p style="color:#64748B;font-size:0.85rem;">Ce lien expire dans 1 heure. Si tu n\'es pas à l\'origine de cette demande, ignore cet email.</p>',
    $lien,
    'Réinitialiser mon mot de passe'
);
envoyer_email($email, 'Réinitialisation de votre mot de passe — MonReseau', $corps);

echo json_encode(['success' => true, 'message' => $message_generique]);
