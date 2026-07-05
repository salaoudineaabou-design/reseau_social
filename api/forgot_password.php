<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$input = getJsonInput();
$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) jsonResponse(['success' => false, 'message' => 'Email invalide.'], 400);

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

// Réponse volontairement identique que l'email existe ou non (sécurité)
if ($user) {
    $token = generateToken();
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?');
    $stmt->execute([$token, $expiry, $user['id']]);

    $resetUrl = 'https://votre-domaine.example/vues/clients/reset-password.html?token=' . $token;
    $body = emailTemplate(
        'Réinitialisation de votre mot de passe',
        "Vous avez demandé la réinitialisation de votre mot de passe. Ce lien est valable 1 heure. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.",
        'Réinitialiser mon mot de passe',
        $resetUrl
    );
    sendHtmlMail($email, 'Réinitialisation de mot de passe', $body);
}

jsonResponse(['success' => true, 'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
