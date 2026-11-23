-- Installation additive du module Plantation/Suivi.
-- À exécuter après avoir sélectionné la base de données.
-- Les tables déjà présentes sont conservées ; seules les tables absentes sont créées.

CREATE TABLE IF NOT EXISTS varietes (
    idVariete INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    nomLocal VARCHAR(120) NULL,
    region VARCHAR(120) NULL,
    saison VARCHAR(120) NULL,
    description TEXT NULL,
    image VARCHAR(500) NULL,
    estMenacee BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plantations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    variety VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    surface DECIMAL(12,2) NOT NULL,
    planted_date DATE NOT NULL,
    description TEXT NULL,
    status ENUM('Semis','Germination','Croissance','Floraison','Maturité','Récolte') NOT NULL DEFAULT 'Semis',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plantations_id_user (id, user_id),
    KEY idx_plantations_user_date (user_id, planted_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suivis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plantation_id INT NOT NULL,
    user_id INT NULL,
    followup_date DATE NOT NULL,
    stage ENUM('Semis','Germination','Croissance','Floraison','Maturité','Récolte') NOT NULL,
    condition_state ENUM('Bon','Moyen','À surveiller') NOT NULL,
    observation TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_suivis_plantation_date (plantation_id, followup_date),
    KEY idx_suivis_user (user_id),
    CONSTRAINT fk_suivis_plantation_user FOREIGN KEY (plantation_id, user_id)
        REFERENCES plantations(id, user_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE plantations
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS type_support VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS variety_id INT NULL;

SET @plantation_variety_fk = (
    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'plantations'
      AND CONSTRAINT_NAME = 'fk_plantations_variety'
);
SET @plantation_variety_sql = IF(
    @plantation_variety_fk = 0,
    'ALTER TABLE plantations ADD CONSTRAINT fk_plantations_variety FOREIGN KEY (variety_id) REFERENCES varietes(idVariete) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE plantation_variety_stmt FROM @plantation_variety_sql;
EXECUTE plantation_variety_stmt;
DEALLOCATE PREPARE plantation_variety_stmt;

ALTER TABLE suivis
    ADD COLUMN IF NOT EXISTS photo VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS etat VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS arrosage VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS diagnosticIA TEXT NULL;

-- Le module fonctionne sans compte : on conserve les éventuelles anciennes
-- associations, mais elles ne conditionnent plus l'accès aux plantations.
SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'plantations' AND CONSTRAINT_NAME = 'fk_plantations_user');
SET @ddl = IF(@has_fk > 0, 'ALTER TABLE plantations DROP FOREIGN KEY fk_plantations_user', 'SELECT 1');
PREPARE public_stmt FROM @ddl; EXECUTE public_stmt; DEALLOCATE PREPARE public_stmt;

SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'suivis' AND CONSTRAINT_NAME = 'fk_suivis_plantation_user');
SET @ddl = IF(@has_fk > 0, 'ALTER TABLE suivis DROP FOREIGN KEY fk_suivis_plantation_user', 'SELECT 1');
PREPARE public_stmt FROM @ddl; EXECUTE public_stmt; DEALLOCATE PREPARE public_stmt;

SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'suivis' AND CONSTRAINT_NAME = 'fk_suivis_user');
SET @ddl = IF(@has_fk > 0, 'ALTER TABLE suivis DROP FOREIGN KEY fk_suivis_user', 'SELECT 1');
PREPARE public_stmt FROM @ddl; EXECUTE public_stmt; DEALLOCATE PREPARE public_stmt;

ALTER TABLE plantations MODIFY user_id INT NULL;
ALTER TABLE suivis MODIFY user_id INT NULL;

SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'suivis' AND CONSTRAINT_NAME = 'fk_suivis_plantation_public');
SET @ddl = IF(@has_fk = 0, 'ALTER TABLE suivis ADD CONSTRAINT fk_suivis_plantation_public FOREIGN KEY (plantation_id) REFERENCES plantations(id) ON UPDATE CASCADE ON DELETE CASCADE', 'SELECT 1');
PREPARE public_stmt FROM @ddl; EXECUTE public_stmt; DEALLOCATE PREPARE public_stmt;
