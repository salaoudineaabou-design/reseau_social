<?php
/**
 * api/helpers.php
 * -----------------------------------------------------------------
 * Fonctions réutilisables incluses par config.php dans TOUS les
 * endpoints de l'API. Ne jamais dupliquer cette logique ailleurs.
 * -----------------------------------------------------------------
 */

/**
 * Lit et décode le corps JSON envoyé par une requête Fetch.
 * Retourne toujours un tableau (vide si le body est absent/invalide).
 */
function lire_json_body(): array
{
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
}

/**
 * Vérifie que l'utilisateur qui appelle l'endpoint est bien connecté.
 * L'ID est lu dans le body JSON (POST) ou dans les paramètres GET
 * selon la méthode utilisée, ce qui permet d'appeler cette fonction
 * de la même façon dans les deux cas.
 *
 * Arrête le script avec un 401 si la session n'est pas valide.
 * Retourne le tableau associatif de l'utilisateur sinon.
 */
function verifier_session(PDO $pdo): array
{
    $methode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $user_id = 0;

    if ($methode === 'GET') {
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    } else {
        $body = lire_json_body();
        $user_id = isset($body['user_id']) ? (int)$body['user_id'] : 0;
    }

    if ($user_id <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT id, nom, prenom, email, avatar, role FROM users
         WHERE id = ? AND email_verifie = 1 AND is_banned = 0'
    );
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session invalide']);
        exit;
    }

    return $user;
}

/**
 * Vérifie que l'utilisateur connecté possède un des rôles autorisés.
 * Arrête le script avec un 403 sinon.
 */
function verifier_role(array $user, array $roles_autorises): void
{
    if (!in_array($user['role'], $roles_autorises, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }
}

/**
 * Nettoie un texte saisi par l'utilisateur avant stockage en BDD.
 * Double protection : strip_tags() ici + textContent côté JS à l'affichage.
 */
function nettoyer_texte(?string $texte): string
{
    return trim(strip_tags((string)$texte));
}

/**
 * Envoie un email au format HTML.
 * - Si MAILTRAP_TOKEN est renseigné dans config.php, utilise l'API HTTP
 *   de Mailtrap (fonctionne même sur les hébergeurs qui bloquent mail()).
 * - Sinon, retombe sur la fonction native mail() de PHP (pratique en
 *   local avec un serveur SMTP de test configuré dans php.ini).
 *
 * Ne bloque jamais le flux appelant : les erreurs d'envoi sont
 * silencieuses côté utilisateur (l'inscription ne doit pas échouer
 * juste parce que l'email n'est pas parti).
 */
function envoyer_email(string $destinataire, string $sujet, string $corps_html): bool
{
    if (defined('MAILTRAP_TOKEN') && MAILTRAP_TOKEN !== '') {
        $payload = json_encode([
            'from'    => ['email' => MAIL_FROM, 'name' => MAIL_FROM_NAME],
            'to'      => [['email' => $destinataire]],
            'subject' => $sujet,
            'html'    => $corps_html,
        ]);

        $ch = curl_init('https://send.api.mailtrap.io/api/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . MAILTRAP_TOKEN,
            'Content-Type: application/json',
        ]);
        $reponse = curl_exec($ch);
        $erreur  = curl_errno($ch);
        curl_close($ch);

        return $erreur === 0 && $reponse !== false;
    }

    // Repli sur mail() natif (nécessite un serveur SMTP configuré dans php.ini)
    $entetes  = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
    $entetes .= "Content-Type: text/html; charset=utf-8\r\n";

    return @mail($destinataire, $sujet, $corps_html, $entetes);
}

/**
 * Génère l'enveloppe HTML commune à tous les emails du projet
 * (même charte graphique que le frontend).
 */
function gabarit_email(string $titre, string $message_html, string $lien = '', string $texte_bouton = ''): string
{
    $bouton = '';
    if ($lien !== '' && $texte_bouton !== '') {
        $bouton = '<a href="' . htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') . '"
            style="display:inline-block;margin-top:20px;background:#2563EB;color:#ffffff;
            padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">'
            . htmlspecialchars($texte_bouton, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    return '
    <!DOCTYPE html>
    <html>
    <body style="font-family: Segoe UI, Arial, sans-serif; background:#F1F5F9; padding:24px;">
      <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:12px;
                  padding:32px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <h2 style="color:#2563EB;margin-top:0;">' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '</h2>
        <div style="color:#1E293B;line-height:1.6;">' . $message_html . '</div>
        ' . $bouton . '
        <p style="color:#64748B;font-size:0.8rem;margin-top:28px;">MonReseau Social — Projet académique PHP / AJAX</p>
      </div>
    </body>
    </html>';
}
