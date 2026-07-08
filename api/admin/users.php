<?php
/**
 * api/admin/users.php
 * GET  ?user_id=X&q=recherche → Liste des utilisateurs (avec recherche optionnelle)
 * POST { user_id, action, cible_id } → bannir / debannir / promouvoir_modo /
 *      revoquer_role / supprimer
 * (user_id = l'admin/modérateur connecté qui effectue l'action)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

/** Vérifie que l'appelant est admin ou modérateur, retourne son rôle. */
function verifier_admin(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ? AND role != 'user' AND is_banned = 0");
    $stmt->execute([$user_id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }
    return $admin;
}

$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    $appelant_id = (int)($_GET['user_id'] ?? 0);
    verifier_admin($pdo, $appelant_id);

    $recherche = '%' . ($_GET['q'] ?? '') . '%';
    $stmt = $pdo->prepare(
        'SELECT id, nom, prenom, email, role, is_banned, created_at
         FROM users
         WHERE (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)
         ORDER BY created_at DESC'
    );
    $stmt->execute([$recherche, $recherche, $recherche]);

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($methode === 'POST') {
    $body        = lire_json_body();
    $appelant_id = (int)($body['user_id'] ?? 0);
    $appelant    = verifier_admin($pdo, $appelant_id);

    $action = $body['action'] ?? '';
    $cible  = (int)($body['cible_id'] ?? 0);

    if ($cible <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Utilisateur cible invalide']);
        exit;
    }

    switch ($action) {
        case 'bannir':
            $pdo->prepare('UPDATE users SET is_banned = 1 WHERE id = ?')->execute([$cible]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur banni']);
            break;

        case 'debannir':
            $pdo->prepare('UPDATE users SET is_banned = 0 WHERE id = ?')->execute([$cible]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur débanni']);
            break;

        case 'promouvoir_modo':
            // Seul un admin (pas un simple modérateur) peut promouvoir
            if ($appelant['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Seul un administrateur peut promouvoir']);
                exit;
            }
            $pdo->prepare("UPDATE users SET role = 'moderateur' WHERE id = ?")->execute([$cible]);
            echo json_encode(['success' => true, 'message' => 'Modérateur promu']);
            break;

        case 'promouvoir_admin':
            if ($appelant['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Seul un administrateur peut promouvoir']);
                exit;
            }
            $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$cible]);
            echo json_encode(['success' => true, 'message' => 'Administrateur promu']);
            break;

        case 'revoquer_role':
            if ($appelant['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Seul un administrateur peut révoquer un rôle']);
                exit;
            }
            $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$cible]);
            echo json_encode(['success' => true, 'message' => 'Rôle révoqué']);
            break;

        case 'supprimer':
            if ($appelant['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Seul un administrateur peut supprimer un compte']);
                exit;
            }
            // ON DELETE CASCADE supprime automatiquement articles, likes, commentaires, messages...
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$cible]);
            echo json_encode(['success' => true, 'message' => 'Compte supprimé']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
