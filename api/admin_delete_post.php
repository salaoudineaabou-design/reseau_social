<?php
require_once __DIR__ . '/functions.php';
requireStaff();
$input = getJsonInput();
$id = (int)($input['id'] ?? 0);
if (!$id) jsonResponse(['success' => false, 'message' => 'id requis.'], 400);

$pdo = getDB();
$stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
$stmt->execute([$id]);

jsonResponse(['success' => true, 'message' => 'Article supprimé.']);
