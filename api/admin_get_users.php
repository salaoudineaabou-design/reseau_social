<?php
require_once __DIR__ . '/functions.php';
requireStaff();
$pdo = getDB();
$stmt = $pdo->query("SELECT id, prenom, nom, email, avatar, role, status, is_verified, created_at FROM users ORDER BY created_at DESC");
jsonResponse(['success' => true, 'users' => $stmt->fetchAll()]);
