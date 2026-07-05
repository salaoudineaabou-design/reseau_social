Réseau Social Web - TP Final PHP / AJAX
1. Description du projet
Application web de type réseau social (inspirée de Facebook) développée en PHP natif (API REST)
et JavaScript vanilla (AJAX/Fetch) côté client, avec MySQL comme base de données.
Aucune bibliothèque backend externe n'est utilisée (PHP natif exclusivement, comme demandé).
Fonctionnalités implémentées :
Authentification complète (inscription avec email de confirmation HTML, connexion, mot de passe oublié / réinitialisation par email HTML)
Fil d'actualité : publications (texte + image), likes/dislikes persistants, commentaires en AJAX sans rechargement
Gestion des amis : liste des utilisateurs, envoi/réception/gestion des invitations, consultation de profils
Gestion du profil personnel : informations, photo de profil, mot de passe
Chat : sidebar des conversations, recherche d'amis, envoi de texte/images, actualisation par intervalle JS (3s)
Back-office : connexion distincte, rôles Administrateur / Modérateur, dashboard avec statistiques, gestion des articles/utilisateurs/staff
2. Architecture du projet
Code
3. Mode de fonctionnement
Le frontend communique avec l'API via Fetch/AJAX (JSON, ou FormData pour les uploads).
La session est gérée côté client via sessionStorage (équivalent JS des sessions PHP demandé
par l'énoncé) : après connexion, un session_token est stocké dans sessionStorage et envoyé
dans l'en-tête Authorization: Bearer <token> à chaque appel API. Le serveur valide ce token contre
la base de données (colonne users.session_token).
Aucune page ne recharge après le chargement initial : toutes les interactions (likes, commentaires,
amis, chat, back-office) sont gérées en AJAX avec mise à jour dynamique du DOM.
Le chat utilise un polling JavaScript toutes les 3 secondes (setInterval) pour simuler le
temps réel, comme autorisé par l'énoncé (alternative aux sockets Node.js).
4. Installation / Déploiement
Créer la base de données en important sql/schema.sql (contient aussi les comptes de test).
Configurer les identifiants MySQL dans api/config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS).
Déployer l'ensemble du dossier sur un serveur PHP (Apache/Nginx + PHP 8+, ex. XAMPP/WAMP en local).
S'assurer que les dossiers assets/images/avatars, assets/images/posts, assets/images/chat
sont accessibles en écriture par PHP (uploads).
Ouvrir index.html (ou l'URL du serveur) dans le navigateur.
Pour l'envoi d'emails réels (confirmation, réinitialisation), configurer un serveur SMTP/sendmail
sur l'hébergement ; en environnement de démonstration, les emails générés sont aussi journalisés
en HTML dans api/mail_log/ pour vérification manuelle.
5. Identifiants de test
Mot de passe pour tous les comptes ci-dessous : Password123!
Rôle
Email
Administrateur
admin@reseau.test
Modérateur
moderateur@reseau.test
Client
jean@reseau.test
Client
marie@reseau.test
Espace client : vues/clients/login.html
Espace back-office : vues/back-office/login.html
6. Membres du groupe et répartition des tâches
Répartition équitable des tâches du projet entre les quatre membres du groupe.
Membre
Rôle
Tâches réalisées
Salaou-Dine
Gestion des Amis & Module Chat
Liste des utilisateurs · Invitations d'amitié · Gestion des profils · Chat privé · Messages texte et images · Actualisation temps réel (polling 3s)
Marie-Ange
Authentification & Gestion du Profil
Inscription · Connexion · Mot de passe oublié · Emails HTML · Gestion de session (sessionStorage) · Profil utilisateur
Houéfa
Back-Office & Base de Données
Conception de la base de données · Dashboard · Gestion Admin/Modérateur · Statistiques · README · Documentation finale
Gildas
Réseau Social (Publications, Likes, Commentaires)
Publications · Flux d'actualité · Likes/Dislikes · Commentaires AJAX · Gestion des images
Ordre conseillé de réalisation
Houéfa crée la base de données.
Marie-Ange développe l'authentification.
Gildas développe les publications et commentaires.
Salaou-Dine développe les amis et le chat.
Houéfa finalise le back-office et l'intégration.
7. Lien du dépôt
https://github.com/salaoudineaabou-design/reseau_social
