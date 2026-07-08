<!-- <?php
/**
 * api/verify_email.php — GET
 * Appelé directement par le navigateur via le lien reçu par email
 * (pas par Fetch). Valide le token, active le compte, puis redirige.
 */

require_once __DIR__ . '/config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: ' . rtrim(APP_BASE_URL, '/') . '/index.html?erreur=token_manquant');
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE token_email = ? AND email_verifie = 0');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . rtrim(APP_BASE_URL, '/') . '/index.html?erreur=token_invalide');
    exit;
}

$stmt = $pdo->prepare('UPDATE users SET email_verifie = 1, token_email = NULL WHERE id = ?');
$stmt->execute([$user['id']]);

header('Location: ' . rtrim(APP_BASE_URL, '/') . '/index.html?succes=compte_active');
exit; -->