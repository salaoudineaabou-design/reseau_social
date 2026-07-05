<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT u.id, u.prenom, u.nom, u.avatar,
        (SELECT content FROM messages m2
         WHERE (m2.sender_id = u.id AND m2.receiver_id = ?) OR (m2.sender_id = ? AND m2.receiver_id = u.id)
         ORDER BY m2.created_at DESC LIMIT 1) AS last_message,
        (SELECT created_at FROM messages m3
         WHERE (m3.sender_id = u.id AND m3.receiver_id = ?) OR (m3.sender_id = ? AND m3.receiver_id = u.id)
         ORDER BY m3.created_at DESC LIMIT 1) AS last_message_time,
        (SELECT COUNT(*) FROM messages m4 WHERE m4.sender_id = u.id AND m4.receiver_id = ? AND m4.is_read = 0) AS unread_count
    FROM users u
    WHERE u.id IN (
        SELECT sender_id FROM messages WHERE receiver_id = ?
        UNION
        SELECT receiver_id FROM messages WHERE sender_id = ?
    )
    ORDER BY last_message_time DESC
");
$uid = $user['id'];
$stmt->execute([$uid, $uid, $uid, $uid, $uid, $uid, $uid]);
$conversations = $stmt->fetchAll();

jsonResponse(['success' => true, 'conversations' => $conversations]);
