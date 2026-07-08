<?php
/**
 * api/articles.php
 * GET  → Récupère les 20 derniers articles du flux (avec auteur + compteurs)
 * POST → Publie un nouvel article (FormData, image optionnelle)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    $sql = '
        SELECT
            a.id,
            a.contenu,
            a.image,
            a.created_at,
            u.id AS auteur_id,
            u.nom AS auteur_nom,
            u.prenom AS auteur_prenom,
            u.avatar AS auteur_avatar,
            (SELECT COUNT(*) FROM likes WHERE article_id = a.id AND type = "like") AS nb_likes,
            (SELECT COUNT(*) FROM likes WHERE article_id = a.id AND type = "dislike") AS nb_dislikes,
            (SELECT COUNT(*) FROM commentaires WHERE article_id = a.id) AS nb_commentaires
        FROM articles a
        JOIN users u ON u.id = a.user_id
        ORDER BY a.created_at DESC
        LIMIT 20
    ';
    $articles = $pdo->query($sql)->fetchAll();

    // Ajouter le statut du vote de l'utilisateur connecté (si fourni) sur chaque article
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($user_id > 0) {
        $stmtVote = $pdo->prepare('SELECT type FROM likes WHERE user_id = ? AND article_id = ?');
        foreach ($articles as &$art) {
            $stmtVote->execute([$user_id, $art['id']]);
            $vote = $stmtVote->fetchColumn();
            $art['mon_vote'] = $vote !== false ? $vote : null;
        }
        unset($art);
    }

    echo json_encode(['success' => true, 'data' => $articles]);
    exit;
}

if ($methode === 'POST') {
    // L'upload d'image utilise FormData : user_id et contenu arrivent via $_POST
    $user_id = (int)($_POST['user_id'] ?? 0);
    $contenu = nettoyer_texte($_POST['contenu'] ?? '');

    if ($user_id <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }

    // Vérifier que l'utilisateur existe, est actif et non banni
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND email_verifie = 1 AND is_banned = 0');
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session invalide']);
        exit;
    }

    if ($contenu === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le contenu de l\'article est requis']);
        exit;
    }

    $chemin_image = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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

        $ext          = $mimes_ok[$mime_reel];
        $nom_fichier  = uniqid('article_', true) . '.' . $ext;
        $destination  = __DIR__ . '/../uploads/' . $nom_fichier;
        move_uploaded_file($_FILES['image']['tmp_name'], $destination);
        $chemin_image = 'uploads/' . $nom_fichier;
    }

    $stmt = $pdo->prepare('INSERT INTO articles (user_id, contenu, image) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $contenu, $chemin_image]);
    $nouvel_id = $pdo->lastInsertId();

    // Récupérer l'article complet pour l'affichage immédiat côté frontend
    $stmt = $pdo->prepare('
        SELECT a.id, a.contenu, a.image, a.created_at,
               u.id AS auteur_id, u.nom AS auteur_nom, u.prenom AS auteur_prenom, u.avatar AS auteur_avatar,
               0 AS nb_likes, 0 AS nb_dislikes, 0 AS nb_commentaires
        FROM articles a JOIN users u ON u.id = a.user_id
        WHERE a.id = ?
    ');
    $stmt->execute([$nouvel_id]);
    $article = $stmt->fetch();

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Article publié', 'article' => $article]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
