<?php
/**
 * functions.php - Fonctions utilitaires communes à toute l'API
 *
 * IMPORTANT : Conformément à l'énoncé, la session est gérée côté client
 * via sessionStorage (pas de cookies PHP classiques). Le client envoie
 * un "session_token" à chaque requête (header Authorization: Bearer <token>)
 * et le serveur valide ce token contre la colonne users.session_token.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function getBearerToken() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/Bearer\s(\S+)/', $auth, $m)) {
        return $m[1];
    }
    // Fallback: token en paramètre (utile pour <img src> ou tests simples)
    return $_GET['token'] ?? ($_POST['token'] ?? null);
}

/**
 * Vérifie le token de session et retourne l'utilisateur courant.
 * Termine la requête avec une erreur 401 si le token est invalide.
 */
function requireAuth() {
    $token = getBearerToken();
    if (!$token) {
        jsonResponse(['success' => false, 'message' => 'Authentification requise.'], 401);
    }
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE session_token = ? AND status = "actif"');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Session invalide ou expirée.'], 401);
    }
    return $user;
}

/** Vérifie que l'utilisateur courant est modérateur ou admin */
function requireStaff() {
    $user = requireAuth();
    if (!in_array($user['role'], ['moderateur', 'admin'])) {
        jsonResponse(['success' => false, 'message' => 'Accès refusé.'], 403);
    }
    return $user;
}

/** Vérifie que l'utilisateur courant est admin */
function requireAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Accès réservé à l\'administrateur.'], 403);
    }
    return $user;
}

function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function publicUser($user) {
    return [
        'id' => (int)$user['id'],
        'prenom' => $user['prenom'],
        'nom' => $user['nom'],
        'email' => $user['email'],
        'avatar' => $user['avatar'],
        'bio' => $user['bio'],
        'role' => $user['role'],
    ];
}

/**
 * Envoi d'email HTML. En environnement de développement sans serveur SMTP,
 * l'email est également journalisé dans /api/mail_log/ pour permettre
 * la vérification manuelle (utile pour la démo du TP).
 */
function sendHtmlMail($to, $subject, $htmlBody) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Réseau Social <no-reply@reseau-social.local>\r\n";

    $sent = @mail($to, $subject, $htmlBody, $headers);

    // Journalisation locale (toujours utile en dev/démo/correction du TP)
    $logDir = __DIR__ . '/mail_log';
    if (!is_dir($logDir)) mkdir($logDir, 0775, true);
    file_put_contents(
        $logDir . '/' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $to) . '.html',
        "<!-- To: $to | Subject: $subject -->\n" . $htmlBody
    );

    return $sent;
}

function emailTemplate($title, $bodyHtml, $buttonText = null, $buttonUrl = null) {
    $button = '';
    if ($buttonText && $buttonUrl) {
        $button = "<tr><td style='padding:24px 0;text-align:center;'>
            <a href='{$buttonUrl}' style='background:#1877f2;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:bold;font-family:Arial,sans-serif;'>{$buttonText}</a>
        </td></tr>";
    }
    return "
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#f0f2f5;padding:40px 0;font-family:Arial,sans-serif;'>
      <tr><td align='center'>
        <table width='480' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:8px;overflow:hidden;'>
          <tr><td style='background:#1877f2;padding:20px;text-align:center;'>
            <span style='color:#fff;font-size:22px;font-weight:bold;'>Réseau Social</span>
          </td></tr>
          <tr><td style='padding:30px;'>
            <h2 style='color:#1c1e21;font-family:Arial,sans-serif;'>{$title}</h2>
            <div style='color:#444;font-size:15px;line-height:1.5;font-family:Arial,sans-serif;'>{$bodyHtml}</div>
          </td></tr>
          {$button}
          <tr><td style='padding:16px;text-align:center;color:#90949c;font-size:12px;font-family:Arial,sans-serif;'>
            &copy; " . date('Y') . " Réseau Social - Projet TP Final
          </td></tr>
        </table>
      </td></tr>
    </table>";
}
