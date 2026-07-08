# MonReseau Social — Réseau Social Web (PHP & AJAX)

Projet académique de réseau social développé en PHP natif (PDO), MySQL et
JavaScript (Fetch API), sans framework, sous forme de Single Page
Application (SPA) : une seule page HTML (`index.html`) dont le contenu
central est chargé et remplacé dynamiquement en AJAX.

## Sommaire
- [Stack technique](#stack-technique)
- [Structure du projet](#structure-du-projet)
- [Installation (XAMPP en local)](#installation-xampp-en-local)
- [Configuration des emails](#configuration-des-emails)
- [Comptes de test](#comptes-de-test)
- [Répartition du travail en équipe](#répartition-du-travail-en-équipe)

---

## Stack technique

| Couche       | Technologie                                   |
|--------------|------------------------------------------------|
| Frontend     | HTML5, CSS3, JavaScript vanilla (Fetch API)     |
| Backend      | PHP 8 natif, PDO (requêtes préparées)           |
| Base de données | MySQL / MariaDB (utf8mb4)                    |
| Emails       | API Mailtrap (avec repli sur `mail()` natif)    |
| Sécurité     | `password_hash()`/`password_verify()`, vérification MIME réelle des uploads, `strip_tags()` + `textContent`, requêtes préparées partout |

Aucune bibliothèque externe (pas de framework JS, pas de Composer) : tout
le code est lisible et conforme aux consignes de l'examen.

## Structure du projet

```
reseau_social/
├── index.html                  # Page unique de l'application (SPA)
├── .gitignore
├── assets/
│   ├── css/style.css           # Feuille de style globale
│   ├── js/                     # Logique frontend (voir répartition ci-dessous)
│   └── images/default-avatar.png
├── vues/
│   ├── clients/                # Fragments HTML injectés par le routeur JS
│   └── back-office/            # Fragments HTML du back-office
├── api/                        # Endpoints PHP (un fichier = une ressource)
│   ├── config.example.php      # Modèle de configuration (à copier)
│   ├── config.php              # Configuration active (ignorée par Git)
│   ├── helpers.php             # Fonctions communes (session, rôle, email)
│   └── admin/                  # Endpoints réservés au back-office
├── database/reseau_social.sql  # Script SQL complet (structure + données de test)
└── uploads/                    # Avatars et images d'articles uploadés
```

## Installation (XAMPP en local)

1. **Copier le dossier** `reseau_social/` dans `htdocs/` de XAMPP, de façon
   à ce que le projet soit accessible à `http://localhost/reseau_social/`.
2. **Démarrer Apache et MySQL** depuis le panneau de contrôle XAMPP.
3. **Créer la base de données** : ouvrir phpMyAdmin
   (`http://localhost/phpmyadmin`), onglet **SQL**, coller le contenu de
   `database/reseau_social.sql` puis exécuter. Cela crée la base
   `reseau_social`, toutes les tables, et insère 4 comptes de test + 2
   articles de démonstration.
4. **Vérifier la configuration** : le fichier `api/config.php` est déjà
   prêt pour un XAMPP standard (`DB_HOST=localhost`, `DB_USER=root`,
   `DB_PASS=''`). Si ta configuration MySQL diffère, ajuste ces valeurs.
5. **Ouvrir l'application** : `http://localhost/reseau_social/index.html`.

### Déploiement en ligne (hébergement mutualisé type InfinityFree)

1. Importer `database/reseau_social.sql` dans la base MySQL fournie par
   l'hébergeur via phpMyAdmin.
2. Copier `api/config.example.php` vers `api/config.php` et renseigner les
   vrais identifiants (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) fournis
   par l'hébergeur, ainsi que `APP_BASE_URL` avec l'URL réelle du site.
3. Uploader tous les fichiers du projet via FTP.
4. S'assurer que le dossier `uploads/` est accessible en écriture
   (permissions 755 ou 777 selon l'hébergeur).
5. Mettre `MODE_DEBUG` à `false` dans `config.php` avant la mise en
   production finale.

## Configuration des emails

La plupart des hébergeurs gratuits bloquent la fonction `mail()` de PHP.
Le projet utilise donc l'API HTTP de [Mailtrap](https://mailtrap.io)
(compte gratuit) comme solution recommandée :

1. Créer un compte gratuit sur Mailtrap et récupérer un token **Send API**.
2. Coller ce token dans `api/config.php`, constante `MAILTRAP_TOKEN`.
3. Si `MAILTRAP_TOKEN` est vide, le projet retombe automatiquement sur
   `mail()` (pratique en local avec un serveur SMTP de test configuré
   dans `php.ini`, par ex. via Mercury Mail de XAMPP).

Les emails concernés : confirmation d'inscription (`register.php`) et
réinitialisation de mot de passe (`forgot_password.php`).

## Comptes de test

Mot de passe identique pour tous les comptes : **`test1234`**

| Email              | Rôle        |
|--------------------|-------------|
| admin@test.com      | admin       |
| modo@test.com       | modérateur  |
| alice@test.com      | user        |
| david@test.com      | user        |

Le back-office est accessible via le lien « Accès back-office » en bas
de la page de connexion, ou directement via l'URL avec le paramètre de
navigation interne `admin-login`.

## Répartition du travail en équipe

Conformément au fichier de répartition du groupe, le projet a été divisé
en quatre lots de responsabilité. Voici le détail des fichiers produits
par chaque membre.

### Houéfa — Back-office & Base de données *(+ intégration finale)*
> Conception de la base de données, dashboard, gestion admin/modérateur,
> statistiques, README, documentation finale, et intégration finale du
> projet (dernière étape du planning d'équipe).

- `database/reseau_social.sql` — structure complète des 9 tables + données de test
- `api/config.example.php`, `api/config.php` — configuration de connexion PDO
- `api/helpers.php` — fonctions communes (session, rôles, envoi d'emails)
- `api/admin/login.php` — connexion réservée admin/modérateur
- `api/admin/stats.php` — statistiques du tableau de bord
- `api/admin/users.php` — gestion des comptes (ban, promotion, suppression)
- `api/admin/articles.php` — modération des publications
- `assets/js/admin.js` — logique complète du back-office
- `vues/back-office/login-admin.html`
- `README.md` — présent document
- **Intégration finale :** `index.html`, `assets/js/app.js` (routeur SPA), `assets/css/style.css`

### Marie-Ange — Authentification & Gestion du profil
> Inscription, connexion, mot de passe oublié, emails HTML, gestion de
> session, profil utilisateur.

- `api/register.php` — inscription + envoi de l'email de confirmation
- `api/login.php` — connexion et vérification des identifiants
- `api/verify_email.php` — activation du compte via lien email
- `api/forgot_password.php` — génération du lien de réinitialisation
- `api/reset_password.php` — changement de mot de passe via token
- `api/profil.php` — consultation et modification du profil, changement de mot de passe
- `api/upload_avatar.php` — upload sécurisé de la photo de profil
- `assets/js/auth.js` — logique d'inscription/connexion/mot de passe oublié
- `assets/js/profil.js` — affichage et édition du profil, upload d'avatar
- `vues/clients/login.html`, `register.html`, `forgot.html`, `reset-password.html`, `profil.html`

### Gildas — Réseau social (publications, likes, commentaires)
> Publications, flux d'actualité, likes/dislikes, commentaires AJAX,
> gestion des images.

- `api/articles.php` — flux d'actualité (lecture + publication avec image)
- `api/like.php` — système de like/dislike avec bascule (toggle)
- `api/commentaires.php` — lecture, ajout et suppression des commentaires
- `assets/js/feed.js` — chargement du flux, publication, likes, commentaires en AJAX
- `vues/clients/accueil.html`

### Salaou-Dine — Gestion des amis & module chat
> Liste des utilisateurs, invitations d'amitié, gestion des profils
> (relations), chat privé, messages texte et images, actualisation temps
> réel.

- `api/amis.php` — liste des utilisateurs + invitations (envoyer/accepter/refuser/supprimer)
- `api/conversations.php` — liste des conversations de l'utilisateur connecté
- `api/messages.php` — historique et envoi des messages (polling)
- `assets/js/amis.js` — logique de la page Amis
- `assets/js/chat.js` — logique du chat (sidebar, envoi, actualisation automatique toutes les 3s)
- `vues/clients/amis.html`, `chat.html`

### Fichiers transverses
- `assets/js/ui.js` — fonctions utilitaires communes à tout le frontend
  (affichage des messages, formatage des dates, mise à jour de la
  navbar) — maintenu conjointement par l'équipe au fil de l'intégration.
- `assets/images/default-avatar.png` — avatar par défaut

### Ordre de réalisation suivi
1. Houéfa crée la base de données.
2. Marie-Ange développe l'authentification.
3. Gildas développe les publications et commentaires.
4. Salaou-Dine développe les amis et le chat.
5. Houéfa finalise le back-office et l'intégration de l'ensemble du projet.
