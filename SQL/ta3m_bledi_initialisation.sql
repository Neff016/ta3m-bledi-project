-- Taam Bledi : initialisation complete de ta3m_bledi
-- DDL uniquement : aucune donnee personnelle, aucun compte fictif.
-- Ce fichier ne contient volontairement aucun DROP, TRUNCATE ou INSERT.

CREATE DATABASE IF NOT EXISTS ta3m_bledi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ta3m_bledi;

CREATE TABLE IF NOT EXISTS utilisateur (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telephone VARCHAR(8) NULL,
    motDePasse VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'AGRICULTEUR',
    statut VARCHAR(10) NOT NULL DEFAULT 'ACTIF',
    photo VARCHAR(100) NULL,
    dateInscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resetToken VARCHAR(64) NULL,
    resetExpiration DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS badge (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    critere VARCHAR(100) NOT NULL,
    points INT NOT NULL,
    icone VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS utilisateur_badge (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    idUtilisateur INT NOT NULL,
    idBadge INT NOT NULL,
    dateAttribution DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motif VARCHAR(255) NOT NULL,
    CONSTRAINT uq_utilisateur_badge UNIQUE (idUtilisateur, idBadge),
    CONSTRAINT fk_ub_utilisateur FOREIGN KEY (idUtilisateur)
        REFERENCES utilisateur(id) ON DELETE CASCADE,
    CONSTRAINT fk_ub_badge FOREIGN KEY (idBadge)
        REFERENCES badge(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS terrain (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    localisation VARCHAR(50) NOT NULL,
    superficie DECIMAL(10,2) NOT NULL,
    typeSol VARCHAR(30) NOT NULL,
    idUtilisateur INT NOT NULL,
    CONSTRAINT fk_terrain_utilisateur FOREIGN KEY (idUtilisateur)
        REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recolte (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    culture VARCHAR(100) NOT NULL,
    datePlantation DATE NOT NULL,
    dateRecolte DATE NOT NULL,
    quantite DECIMAL(12,2) NOT NULL,
    unite VARCHAR(10) NOT NULL,
    idTerrain INT NOT NULL,
    CONSTRAINT fk_recolte_terrain FOREIGN KEY (idTerrain)
        REFERENCES terrain(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS varietes (
    idVariete INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    nomLocal VARCHAR(120) NULL,
    region VARCHAR(120) NULL,
    saison VARCHAR(120) NULL,
    description TEXT NULL,
    image VARCHAR(500) NULL,
    estMenacee BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plantations (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    owner_verified TINYINT(1) NOT NULL DEFAULT 0,
    name VARCHAR(100) NOT NULL,
    variety VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    surface DECIMAL(12,2) NOT NULL,
    planted_date DATE NOT NULL,
    description TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Semis',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    address VARCHAR(255) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    type_support VARCHAR(30) NULL,
    variety_id INT NULL,
    UNIQUE KEY uq_plantations_id_user (id, user_id),
    KEY idx_plantations_user_date (user_id, planted_date),
    CONSTRAINT fk_plantations_utilisateur FOREIGN KEY (user_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_plantations_variety FOREIGN KEY (variety_id)
        REFERENCES varietes(idVariete) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suivis (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    plantation_id INT NOT NULL,
    user_id INT NULL,
    followup_date DATE NOT NULL,
    stage VARCHAR(30) NOT NULL,
    condition_state VARCHAR(30) NOT NULL,
    observation TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    photo VARCHAR(500) NULL,
    etat VARCHAR(100) NULL,
    arrosage VARCHAR(100) NULL,
    diagnosticIA TEXT NULL,
    KEY idx_suivis_plantation_date (plantation_id, followup_date),
    KEY idx_suivis_user (user_id),
    CONSTRAINT fk_suivis_plantation_user FOREIGN KEY (plantation_id, user_id)
        REFERENCES plantations(id, user_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
