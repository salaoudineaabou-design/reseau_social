<?php
/**
 * api/conversations.php — GET
 * Liste les conversations de l'utilisateur connecté avec le dernier
 * message et le nombre de messages non lus (pour la sidebar du chat).
 * Reçoit : ?user_id=X
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$user_id = (int)($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$sql = '
    SELECT
        c.id AS conv_id,
        CASE WHEN c.user1_id = :uid1 THEN c.user2_id ELSE c.user1_id END AS autre_id,
        u.nom AS autre_nom, u.prenom AS autre_prenom, u.avatar AS autre_avatar,
        (SELECT contenu FROM messages WHERE conversation_id = c.id
            ORDER BY created_at DESC LIMIT 1) AS dernier_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id
            ORDER BY created_at DESC LIMIT 1) AS dernier_message_date,
        (SELECT COUNT(*) FROM messages
            WHERE conversation_id = c.id AND expediteur_id != :uid2 AND lu = 0) AS non_lus
    FROM conversations c
    JOIN users u ON u.id = CASE WHEN c.user1_id = :uid3 THEN c.user2_id ELSE c.user1_id END
    WHERE c.user1_id = :uid4 OR c.user2_id = :uid5
    ORDER BY dernier_message_date DESC
';

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':uid1' => $user_id, ':uid2' => $user_id, ':uid3' => $user_id,
    ':uid4' => $user_id, ':uid5' => $user_id,
]);

echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
