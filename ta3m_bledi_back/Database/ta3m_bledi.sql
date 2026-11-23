CREATE DATABASE IF NOT EXISTS ta3m_bledi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ta3m_bledi;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS workshops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    workshop_date DATE NOT NULL,
    location VARCHAR(150) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 15,
    icon VARCHAR(20) NOT NULL DEFAULT '🌱',
    status ENUM('active','draft') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    workshop_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_workshop (user_id, workshop_id),
    CONSTRAINT fk_reg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_workshop FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO workshops (title, category, description, workshop_date, location, capacity, icon, status)
SELECT 'Créer son jardin sur le toit','Jardinage','Apprenez à aménager un petit espace vert chez vous et à cultiver en ville.','2026-11-22','Tunis',15,'🪴','active'
WHERE NOT EXISTS (SELECT 1 FROM workshops WHERE title='Créer son jardin sur le toit');

INSERT INTO workshops (title, category, description, workshop_date, location, capacity, icon, status)
SELECT 'À la découverte des semences locales','Semences','Découvrez les graines traditionnelles tunisiennes et leur importance pour la biodiversité.','2026-11-28','Manouba',20,'🌾','active'
WHERE NOT EXISTS (SELECT 1 FROM workshops WHERE title='À la découverte des semences locales');

INSERT INTO workshops (title, category, description, workshop_date, location, capacity, icon, status)
SELECT 'Composter pour une terre vivante','Agriculture durable','Initiez-vous au compostage et aux pratiques agricoles respectueuses de l’environnement.','2026-12-05','Ariana',10,'🌱','active'
WHERE NOT EXISTS (SELECT 1 FROM workshops WHERE title='Composter pour une terre vivante');
