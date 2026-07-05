-- ============================================================
-- Schéma de base de données - Réseau Social Web (PHP/AJAX)
-- ============================================================

CREATE DATABASE IF NOT EXISTS reseau_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reseau_social;

-- ---------------------------------------------------------
-- Table des utilisateurs (clients + admin/modérateurs)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    bio VARCHAR(500) DEFAULT '',
    role ENUM('user','moderateur','admin') NOT NULL DEFAULT 'user',
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verify_token VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    session_token VARCHAR(255) DEFAULT NULL,
    status ENUM('actif','banni') NOT NULL DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Articles / Publications
-- ---------------------------------------------------------
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Likes / Dislikes
-- ---------------------------------------------------------
CREATE TABLE post_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('like','dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Commentaires
-- ---------------------------------------------------------
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Amitiés
-- ---------------------------------------------------------
CREATE TABLE friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,       -- demandeur
    friend_id INT NOT NULL,     -- destinataire
    status ENUM('pending','accepted','refused') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_friendship (user_id, friend_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Messages (Chat)
-- ---------------------------------------------------------
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Comptes de test
-- Mot de passe pour tous les comptes de test : Password123!
-- Hash généré avec password_hash() PHP (bcrypt)
-- ---------------------------------------------------------
INSERT INTO users (prenom, nom, email, password_hash, role, is_verified, status) VALUES
('Admin', 'Principal', 'admin@reseau.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 'actif'),
('Modo', 'Test', 'moderateur@reseau.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'moderateur', 1, 'actif'),
('Jean', 'Dupont', 'jean@reseau.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1, 'actif'),
('Marie', 'Curie', 'marie@reseau.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1, 'actif');
