-- Plantation/Suivi: schema additif pour la base Taam Bledi existante.
-- Pre-requis: executer les scripts principaux qui creent utilisateur(id).
-- Ce script ne supprime aucune table, donnee ou cle et ne migre aucune donnee.

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
    status ENUM('Semis','Germination','Croissance','Floraison','Maturité','Récolte') NOT NULL DEFAULT 'Semis',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    address VARCHAR(255) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    type_support VARCHAR(30) NULL,
    variety_id INT NULL,
    UNIQUE KEY uq_plantations_id_user (id, user_id),
    KEY idx_plantations_user_date (user_id, planted_date),
    CONSTRAINT fk_plantations_utilisateur FOREIGN KEY (user_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suivis (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    plantation_id INT NOT NULL,
    user_id INT NULL,
    followup_date DATE NOT NULL,
    stage ENUM('Semis','Germination','Croissance','Floraison','Maturité','Récolte') NOT NULL,
    condition_state ENUM('Bon','Moyen','À surveiller') NOT NULL,
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

-- Ajouts de colonnes seulement: les anciennes lignes gardent owner_verified = 0.
ALTER TABLE plantations
    ADD COLUMN IF NOT EXISTS owner_verified TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS type_support VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS variety_id INT NULL;

ALTER TABLE suivis
    ADD COLUMN IF NOT EXISTS photo VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS etat VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS arrosage VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS diagnosticIA TEXT NULL;

-- Ajouter la relation utilisateur seulement si les donnees existantes sont compatibles.
SET @plantation_user_fk = (
    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'plantations'
      AND CONSTRAINT_NAME = 'fk_plantations_utilisateur'
);
SET @plantation_user_orphans = (
    SELECT COUNT(*) FROM plantations p LEFT JOIN utilisateur u ON u.id = p.user_id
    WHERE p.user_id IS NOT NULL AND u.id IS NULL
);
SET @plantation_user_sql = IF(
    @plantation_user_fk = 0 AND @plantation_user_orphans = 0,
    'ALTER TABLE plantations ADD CONSTRAINT fk_plantations_utilisateur FOREIGN KEY (user_id) REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE plantation_user_stmt FROM @plantation_user_sql;
EXECUTE plantation_user_stmt;
DEALLOCATE PREPARE plantation_user_stmt;

-- Ajouter la relation des suivis seulement si aucune ligne ne reference une plantation absente.
SET @suivi_plantation_fk = (
    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'suivis'
      AND CONSTRAINT_NAME = 'fk_suivis_plantation_user'
);
SET @suivi_plantation_orphans = (
    SELECT COUNT(*) FROM suivis s LEFT JOIN plantations p
      ON p.id = s.plantation_id AND (s.user_id = p.user_id OR s.user_id IS NULL)
    WHERE p.id IS NULL
);
SET @suivi_plantation_sql = IF(
    @suivi_plantation_fk = 0 AND @suivi_plantation_orphans = 0,
    'ALTER TABLE suivis ADD CONSTRAINT fk_suivis_plantation_user FOREIGN KEY (plantation_id, user_id) REFERENCES plantations(id, user_id) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE suivi_plantation_stmt FROM @suivi_plantation_sql;
EXECUTE suivi_plantation_stmt;
DEALLOCATE PREPARE suivi_plantation_stmt;

-- Relation aux varietes: ignore l'ajout si des references preexistantes sont orphelines.
SET @variety_fk = (
    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'plantations'
      AND CONSTRAINT_NAME = 'fk_plantations_variety'
);
SET @variety_orphans = (
    SELECT COUNT(*) FROM plantations p LEFT JOIN varietes v ON v.idVariete = p.variety_id
    WHERE p.variety_id IS NOT NULL AND v.idVariete IS NULL
);
SET @variety_sql = IF(
    @variety_fk = 0 AND @variety_orphans = 0,
    'ALTER TABLE plantations ADD CONSTRAINT fk_plantations_variety FOREIGN KEY (variety_id) REFERENCES varietes(idVariete) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE variety_stmt FROM @variety_sql;
EXECUTE variety_stmt;
DEALLOCATE PREPARE variety_stmt;