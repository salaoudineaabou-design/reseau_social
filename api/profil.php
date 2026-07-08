<?php
/**
 * api/profil.php
 * GET  ?user_id=X                                    → Consulter un profil public
 * POST { user_id, nom, prenom, bio }                 → Modifier son propre profil
 * POST { user_id, action:'changer_mdp', ancien_mdp, nouveau_mdp } → Changer le mot de passe
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    $user_id = (int)($_GET['user_id'] ?? 0);

    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id requis']);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT id, nom, prenom, email, avatar, bio, role, created_at
         FROM users WHERE id = ? AND is_banned = 0'
    );
    $stmt->execute([$user_id]);
    $profil = $stmt->fetch();

    if (!$profil) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
        exit;
    }

    $nb_articles = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE user_id = ?');
    $nb_articles->execute([$user_id]);

    $nb_amis = $pdo->prepare(
        "SELECT COUNT(*) FROM amis WHERE statut = 'accepte' AND (demandeur_id = ? OR receveur_id = ?)"
    );
    $nb_amis->execute([$user_id, $user_id]);

    $profil['nb_articles'] = (int)$nb_articles->fetchColumn();
    $profil['nb_amis']     = (int)$nb_amis->fetchColumn();

    echo json_encode(['success' => true, 'data' => $profil]);
    exit;
}

if ($methode === 'POST') {
    $connecte = verifier_session($pdo);
    $body     = lire_json_body();

    // Sous-action : changement de mot de passe
    if (($body['action'] ?? '') === 'changer_mdp') {
        $ancien  = (string)($body['ancien_mdp'] ?? '');
        $nouveau = (string)($body['nouveau_mdp'] ?? '');

        if (strlen($nouveau) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nouveau mot de passe trop court (8 caractères min)']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT mot_de_passe FROM users WHERE id = ?');
        $stmt->execute([$connecte['id']]);
        $hash_actuel = $stmt->fetchColumn();

        if (!password_verify($ancien, $hash_actuel)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ancien mot de passe incorrect']);
            exit;
        }

        $pdo->prepare('UPDATE users SET mot_de_passe = ? WHERE id = ?')
            ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $connecte['id']]);

        echo json_encode(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
        exit;
    }

    // Cas général : modification des informations du profil
    $nom    = nettoyer_texte($body['nom'] ?? '');
    $prenom = nettoyer_texte($body['prenom'] ?? '');
    $bio    = nettoyer_texte($body['bio'] ?? '');

    if ($nom === '' || $prenom === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nom et prénom requis']);
        exit;
    }

    $pdo->prepare('UPDATE users SET nom = ?, prenom = ?, bio = ? WHERE id = ?')
        ->execute([$nom, $prenom, $bio, $connecte['id']]);

    $stmt = $pdo->prepare('SELECT id, nom, prenom, email, avatar, bio, role FROM users WHERE id = ?');
    $stmt->execute([$connecte['id']]);

    echo json_encode(['success' => true, 'message' => 'Profil mis à jour', 'user' => $stmt->fetch()]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
