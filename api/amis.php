<?php
/**
 * api/amis.php
 * GET  ?user_id=X → Liste de tous les utilisateurs avec le statut de
 *                   relation vis-à-vis de l'utilisateur connecté.
 * POST { user_id, action, cible_id | relation_id } → envoyer / accepter /
 *                   refuser / supprimer une relation d'amitié.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    $user_connecte_id = (int)($_GET['user_id'] ?? 0);

    if ($user_connecte_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id requis']);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT id, nom, prenom, avatar, bio FROM users
         WHERE id != ? AND email_verifie = 1 AND is_banned = 0
         ORDER BY nom, prenom'
    );
    $stmt->execute([$user_connecte_id]);
    $users = $stmt->fetchAll();

    $stmtRel = $pdo->prepare(
        'SELECT id, statut, demandeur_id FROM amis
         WHERE (demandeur_id = ? AND receveur_id = ?) OR (demandeur_id = ? AND receveur_id = ?)'
    );

    foreach ($users as &$u) {
        $stmtRel->execute([$user_connecte_id, $u['id'], $u['id'], $user_connecte_id]);
        $relation = $stmtRel->fetch();

        if (!$relation) {
            $u['relation'] = 'aucune';
        } elseif ($relation['statut'] === 'accepte') {
            $u['relation']    = 'amis';
            $u['relation_id'] = $relation['id'];
        } elseif ($relation['statut'] === 'en_attente') {
            $u['relation'] = ((int)$relation['demandeur_id'] === $user_connecte_id)
                ? 'demande_envoyee'
                : 'demande_recue';
            $u['relation_id'] = $relation['id'];
        } else {
            $u['relation'] = 'refuse';
        }
    }
    unset($u);

    echo json_encode(['success' => true, 'data' => $users]);
    exit;
}

if ($methode === 'POST') {
    $connecte = verifier_session($pdo);
    $body     = lire_json_body();
    $action   = $body['action'] ?? '';
    $cible_id = (int)($body['cible_id'] ?? 0);
    $rel_id   = (int)($body['relation_id'] ?? 0);

    switch ($action) {
        case 'envoyer':
            if ($cible_id <= 0 || $cible_id === (int)$connecte['id']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cible invalide']);
                exit;
            }

            $check = $pdo->prepare(
                'SELECT COUNT(*) FROM amis
                 WHERE (demandeur_id=? AND receveur_id=?) OR (demandeur_id=? AND receveur_id=?)'
            );
            $check->execute([$connecte['id'], $cible_id, $cible_id, $connecte['id']]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Une relation existe déjà']);
                exit;
            }

            $pdo->prepare('INSERT INTO amis (demandeur_id, receveur_id) VALUES (?, ?)')
                ->execute([$connecte['id'], $cible_id]);
            echo json_encode(['success' => true, 'message' => 'Invitation envoyée']);
            break;

        case 'accepter':
            $pdo->prepare("UPDATE amis SET statut = 'accepte' WHERE id = ? AND receveur_id = ?")
                ->execute([$rel_id, $connecte['id']]);
            echo json_encode(['success' => true, 'message' => 'Invitation acceptée']);
            break;

        case 'refuser':
            $pdo->prepare("UPDATE amis SET statut = 'refuse' WHERE id = ? AND receveur_id = ?")
                ->execute([$rel_id, $connecte['id']]);
            echo json_encode(['success' => true, 'message' => 'Invitation refusée']);
            break;

        case 'supprimer':
            $pdo->prepare(
                'DELETE FROM amis
                 WHERE (demandeur_id=? AND receveur_id=?) OR (demandeur_id=? AND receveur_id=?)'
            )->execute([$connecte['id'], $cible_id, $cible_id, $connecte['id']]);
            echo json_encode(['success' => true, 'message' => 'Relation supprimée']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
