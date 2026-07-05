<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$input = getJsonInput();
$prenom = sanitize($input['prenom'] ?? '');
$nom = sanitize($input['nom'] ?? '');
$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $input['password'] ?? '';

if (!$prenom || !$nom || !$email || strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => 'Champs invalides. Le mot de passe doit contenir au moins 6 caractères.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Cet email est déjà utilisé.'], 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$verifyToken = generateToken();

$stmt = $pdo->prepare('INSERT INTO users (prenom, nom, email, password_hash, verify_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)');
$stmt->execute([$prenom, $nom, $email, $hash, $verifyToken]);

// Envoi de l'email de confirmation (HTML)
$verifyUrl = 'https://votre-domaine.example/vues/clients/verify.html?token=' . $verifyToken;
$body = emailTemplate(
    "Bienvenue {$prenom} !",
    "Merci de vous être inscrit(e) sur notre réseau social. Veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous.",
    "Confirmer mon email",
    $verifyUrl
);
sendHtmlMail($email, 'Confirmez votre inscription', $body);

jsonResponse(['success' => true, 'message' => 'Inscription réussie ! Un email de confirmation a été envoyé.']);
