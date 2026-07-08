-- =====================================================================
--  reseau_social.sql
--  Structure complète de la base de données du réseau social
--  A importer via phpMyAdmin (onglet SQL) en local (XAMPP) ou en ligne.
--  Encodage requis : utf8mb4_unicode_ci
-- =====================================================================

CREATE DATABASE IF NOT EXISTS reseau_social
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reseau_social;

-- Supprimer les tables existantes dans l'ordre inverse des dépendances
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS amis;
DROP TABLE IF EXISTS commentaires;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- 1. users — table centrale
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
  bio TEXT,
  role ENUM('user','moderateur','admin') DEFAULT 'user',
  email_verifie TINYINT(1) DEFAULT 0,
  token_email VARCHAR(255),
  is_banned TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. articles — publications du flux d'actualité
-- ---------------------------------------------------------------------
CREATE TABLE articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  contenu TEXT NOT NULL,
  image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. likes — likes et dislikes des articles
-- ---------------------------------------------------------------------
CREATE TABLE likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  article_id INT NOT NULL,
  type ENUM('like','dislike') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY vote_unique (user_id, article_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. commentaires — commentaires sur les articles
-- ---------------------------------------------------------------------
CREATE TABLE commentaires (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  article_id INT NOT NULL,
  contenu TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. amis — demandes et relations d'amitié
-- ---------------------------------------------------------------------
CREATE TABLE amis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  demandeur_id INT NOT NULL,
  receveur_id INT NOT NULL,
  statut ENUM('en_attente','accepte','refuse') DEFAULT 'en_attente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY amitie_unique (demandeur_id, receveur_id),
  FOREIGN KEY (demandeur_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receveur_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. conversations — canal entre deux utilisateurs
-- ---------------------------------------------------------------------
CREATE TABLE conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user1_id INT NOT NULL,
  user2_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY conv_unique (user1_id, user2_id),
  FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. messages — messages individuels du chat
-- ---------------------------------------------------------------------
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  expediteur_id INT NOT NULL,
  contenu TEXT,
  image VARCHAR(255),
  lu TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (expediteur_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. password_resets — tokens de réinitialisation de mot de passe
-- ---------------------------------------------------------------------
CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  expire_at TIMESTAMP NOT NULL,
  utilise TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. notifications — notifications système
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  destinataire_id INT NOT NULL,
  expediteur_id INT,
  type VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  lue TINYINT(1) DEFAULT 0,
  lien VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (destinataire_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  DONNÉES DE TEST
--  Mots de passe en clair pour la soutenance : test1234
--  Hash bcrypt correspondant (password_hash('test1234', PASSWORD_DEFAULT))
--  généré une fois pour tous les comptes de test ci-dessous.
-- =====================================================================

-- Hash bcrypt valide de 'test1234' (compatible password_verify() de PHP) :
-- $2b$12$9awB97XkPZjnnDRrolykGODE4uREh8QnXT2LuT7IFWbR4M65bBbSy
INSERT INTO users (nom, prenom, email, mot_de_passe, role, email_verifie, bio) VALUES
('Super', 'Admin', 'admin@test.com', '$2b$12$9awB97XkPZjnnDRrolykGODE4uREh8QnXT2LuT7IFWbR4M65bBbSy', 'admin', 1, 'Administrateur principal du réseau.'),
('Martin', 'Bob', 'modo@test.com', '$2b$12$9awB97XkPZjnnDRrolykGODE4uREh8QnXT2LuT7IFWbR4M65bBbSy', 'moderateur', 1, 'Modérateur de la plateforme.'),
('Dupont', 'Alice', 'alice@test.com', '$2b$12$9awB97XkPZjnnDRrolykGODE4uREh8QnXT2LuT7IFWbR4M65bBbSy', 'user', 1, 'Passionnée de photographie et de voyages.'),
('Kone', 'David', 'david@test.com', '$2b$12$9awB97XkPZjnnDRrolykGODE4uREh8QnXT2LuT7IFWbR4M65bBbSy', 'user', 1, 'Développeur web en formation.');

INSERT INTO articles (user_id, contenu) VALUES
(3, 'Bonjour tout le monde ! Voici mon premier article sur ce réseau social.'),
(4, 'Belle journée pour partager de nouvelles idées avec la communauté.');

INSERT INTO likes (user_id, article_id, type) VALUES
(4, 1, 'like'),
(3, 2, 'like');

INSERT INTO commentaires (user_id, article_id, contenu) VALUES
(4, 1, 'Super article Alice, bienvenue parmi nous !'),
(3, 2, 'Merci David, hâte de voir la suite.');
