<?php
/**
 * api/register.php — POST
 * Crée un nouveau compte utilisateur et envoie un email de confirmation.
 * Reçoit : { nom, prenom, email, mot_de_passe }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$body    = lire_json_body();
$nom     = nettoyer_texte($body['nom'] ?? '');
$prenom  = nettoyer_texte($body['prenom'] ?? '');
$email   = trim($body['email'] ?? '');
$mdp     = (string)($body['mot_de_passe'] ?? '');

// 1. Validation des champs obligatoires
if ($nom === '' || $prenom === '' || $email === '' || $mdp === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires']);
    exit;
}

// 2. Validation du format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format email invalide']);
    exit;
}

// 3. Validation de la robustesse du mot de passe
if (strlen($mdp) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']);
    exit;
}

// 4. Vérifier l'unicité de l'email
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
    exit;
}

// 5. Hacher le mot de passe et générer le token d'activation
$hash  = password_hash($mdp, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));

// 6. Insertion en base
$stmt = $pdo->prepare(
    'INSERT INTO users (nom, prenom, email, mot_de_passe, token_email, email_verifie)
     VALUES (?, ?, ?, ?, ?, 1)'
);
$stmt->execute([$nom, $prenom, $email, $hash, $token]);

// // 7. Envoi de l'email HTML de confirmation (échec silencieux, ne bloque pas l'inscription)
// $lien = rtrim(APP_BASE_URL, '/') . '/api/verify_email.php?token=' . $token;
// $corps = gabarit_email(
//     'Bienvenue sur MonReseau, ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ' !',
//     '<p>Merci de ton inscription. Clique sur le bouton ci-dessous pour confirmer ton adresse email et activer ton compte.</p>
//      <p style="color:#64748B;font-size:0.85rem;">Ce lien expire dans 24 heures.</p>',
//     $lien,
//     'Confirmer mon email'
// );
// envoyer_email($email, 'Confirmez votre inscription sur MonReseau', $corps);

// http_response_code(201);
// echo json_encode([
//     'success' => true,
//     'message' => 'Compte créé avec succès. Vérifie tes emails pour activer ton compte.',
// ]);
