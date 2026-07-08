<?php
/**
 * api/messages.php
 * GET  ?conversation_id=X&user_id=X&depuis=TIMESTAMP → Messages depuis
 *      un instant donné (utilisé pour le polling toutes les 3s).
 * POST { user_id, destinataire_id, contenu } → Envoie un message.
 *      Crée automatiquement la conversation si elle n'existe pas encore.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    $conv_id = (int)($_GET['conversation_id'] ?? 0);
    $user_id = (int)($_GET['user_id'] ?? 0);
    $depuis  = $_GET['depuis'] ?? '1970-01-01 00:00:00';

    if ($conv_id <= 0 || $user_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
        exit;
    }

    $check = $pdo->prepare('SELECT COUNT(*) FROM conversations WHERE id=? AND (user1_id=? OR user2_id=?)');
    $check->execute([$conv_id, $user_id, $user_id]);
    if ($check->fetchColumn() == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé à cette conversation']);
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT m.id, m.contenu, m.image, m.created_at, m.lu, m.expediteur_id,
               u.nom AS exp_nom, u.prenom AS exp_prenom, u.avatar AS exp_avatar
        FROM messages m
        JOIN users u ON u.id = m.expediteur_id
        WHERE m.conversation_id = ? AND m.created_at > ?
        ORDER BY m.created_at ASC
    ');
    $stmt->execute([$conv_id, $depuis]);
    $messages = $stmt->fetchAll();

    // Marquer comme lus les messages reçus par l'utilisateur connecté
    $pdo->prepare('UPDATE messages SET lu = 1 WHERE conversation_id = ? AND expediteur_id != ?')
        ->execute([$conv_id, $user_id]);

    echo json_encode(['success' => true, 'data' => $messages]);
    exit;
}

if ($methode === 'POST') {
    // Deux cas possibles :
    // - multipart/form-data (envoi d'une image, avec ou sans texte)
    // - application/json (message texte seul)
    $est_multipart = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');

    if ($est_multipart) {
        $expediteur_id = (int)($_POST['user_id'] ?? 0);
        $dest_id       = (int)($_POST['destinataire_id'] ?? 0);
        $contenu       = nettoyer_texte($_POST['contenu'] ?? '');

        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND email_verifie = 1 AND is_banned = 0');
        $stmt->execute([$expediteur_id]);
        if (!$stmt->fetch()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Non authentifié']);
            exit;
        }
    } else {
        $connecte      = verifier_session($pdo);
        $expediteur_id = (int)$connecte['id'];
        $body          = lire_json_body();
        $dest_id       = (int)($body['destinataire_id'] ?? 0);
        $contenu       = nettoyer_texte($body['contenu'] ?? '');
    }

    if ($dest_id <= 0 || $dest_id === $expediteur_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Destinataire invalide']);
        exit;
    }

    $chemin_image = null;
    if ($est_multipart && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $mime_reel = $finfo->file($_FILES['image']['tmp_name']);
        $mimes_ok  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

        if (!isset($mimes_ok[$mime_reel])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format image non autorisé']);
            exit;
        }
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Image trop lourde (max 5 Mo)']);
            exit;
        }

        $ext         = $mimes_ok[$mime_reel];
        $nom_fichier = uniqid('msg_', true) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/' . $nom_fichier);
        $chemin_image = 'uploads/' . $nom_fichier;
    }

    if ($contenu === '' && $chemin_image === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message vide']);
        exit;
    }

    // Normaliser les IDs (le plus petit toujours en user1_id) pour éviter les doublons
    $u1 = min($expediteur_id, $dest_id);
    $u2 = max($expediteur_id, $dest_id);

    $conv = $pdo->prepare('SELECT id FROM conversations WHERE user1_id=? AND user2_id=?');
    $conv->execute([$u1, $u2]);
    $conversation = $conv->fetch();

    if (!$conversation) {
        $pdo->prepare('INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)')->execute([$u1, $u2]);
        $conv_id = $pdo->lastInsertId();
    } else {
        $conv_id = $conversation['id'];
    }

    $pdo->prepare('INSERT INTO messages (conversation_id, expediteur_id, contenu, image) VALUES (?, ?, ?, ?)')
        ->execute([$conv_id, $expediteur_id, $contenu, $chemin_image]);

    http_response_code(201);
    echo json_encode(['success' => true, 'conv_id' => (int)$conv_id, 'id' => (int)$pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
