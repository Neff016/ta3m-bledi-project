-- =====================================================================
--  Taam Bledi — Module Terrains et Récoltes : extensions
--   · ContratFoncier   (Terrain 1 → 0..1)  propriété, location, khammès, métayage
--   · CertificationBio (Terrain 1 → 0..1)  conversion puis certification bio
--   · Parcelle         (Terrain 1 → *)     découpage du terrain + plan de rotation
--   · ControleQualite  (Recolte 1 → 0..1)  classe, calibre, déchets, humidité, °Brix
--   (le bilan environnemental est calculé, il n'a pas de table)
--
--  À importer APRÈS ta3m_bledi_terrains_avance.sql
--  Le script peut être importé plusieurs fois sans erreur (MariaDB / XAMPP).
-- =====================================================================
USE ta3m_bledi;

-- ---------------------------------------------------------
-- Contrat foncier : sous quel régime l'agriculteur exploite le terrain
--   type : PROPRIETE / LOCATION / KHAMMES (association) / METAYAGE
--   loyerAnnuel : seulement pour LOCATION (en dinars)
--   partProprietaire : seulement pour KHAMMES et METAYAGE (% de la récolte)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS contrat_foncier (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idTerrain INT NOT NULL UNIQUE,
  type VARCHAR(10) NOT NULL,
  proprietaire VARCHAR(100) NULL,
  loyerAnnuel DECIMAL(10,3) NULL,
  partProprietaire DECIMAL(5,2) NULL,
  dateDebut DATE NOT NULL,
  dateFin DATE NULL,
  CONSTRAINT fk_contrat_terrain FOREIGN KEY (idTerrain) REFERENCES terrain(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Certification bio d'un terrain
--   date de certification = dateDebutConversion + dureeConversionMois
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS certification_bio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idTerrain INT NOT NULL UNIQUE,
  organisme VARCHAR(100) NOT NULL,
  numeroCertificat VARCHAR(50) NULL,
  dateDebutConversion DATE NOT NULL,
  dureeConversionMois INT NOT NULL DEFAULT 24,   -- 24 (cultures annuelles) ou 36 (arbres)
  dateExpiration DATE NULL,                      -- fin de validité du certificat (renouvelé chaque année)
  CONSTRAINT fk_bio_terrain FOREIGN KEY (idTerrain) REFERENCES terrain(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Parcelles d'un terrain (rotation des cultures)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS parcelle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idTerrain INT NOT NULL,
  nom VARCHAR(50) NOT NULL,
  surface DECIMAL(10,2) NOT NULL,                -- en hectares
  polygone TEXT NULL,                            -- contour dessiné sur la carte (JSON)
  CONSTRAINT fk_parcelle_terrain FOREIGN KEY (idTerrain) REFERENCES terrain(id) ON DELETE CASCADE,
  CONSTRAINT uq_parcelle_nom UNIQUE (idTerrain, nom)
) ENGINE=InnoDB;

-- Une récolte peut être faite sur une parcelle précise (facultatif)
ALTER TABLE recolte ADD COLUMN IF NOT EXISTS idParcelle INT NULL;
ALTER TABLE recolte ADD CONSTRAINT fk_recolte_parcelle FOREIGN KEY IF NOT EXISTS (idParcelle)
  REFERENCES parcelle(id) ON DELETE SET NULL;

-- ---------------------------------------------------------
-- Contrôle qualité d'une récolte
--   classe : EXTRA / I / II  (prix du marché × 1,20 / × 1 / × 0,80)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS controle_qualite (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idRecolte INT NOT NULL UNIQUE,
  classe VARCHAR(5) NOT NULL,
  calibre VARCHAR(30) NULL,
  tauxDechets DECIMAL(5,2) NOT NULL DEFAULT 0,   -- en %
  humidite DECIMAL(5,2) NULL,                    -- en % (céréales)
  brix DECIMAL(5,2) NULL,                        -- en °Brix (fruits)
  dateControle DATE NOT NULL,
  remarque VARCHAR(255) NULL,
  CONSTRAINT fk_qualite_recolte FOREIGN KEY (idRecolte) REFERENCES recolte(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Badge automatique
-- ---------------------------------------------------------
INSERT IGNORE INTO badge (nom, description, critere, points, icone) VALUES
('Producteur bio', 'Un terrain certifié en agriculture biologique.', '1 terrain certifié bio', 20, NULL);

-- ---------------------------------------------------------
-- Données de démonstration (terrains de test)
-- ---------------------------------------------------------
INSERT IGNORE INTO contrat_foncier (idTerrain, type, proprietaire, loyerAnnuel, partProprietaire, dateDebut, dateFin)
SELECT id, 'LOCATION', 'Famille Trabelsi', 1200.000, NULL, '2024-09-01', DATE_ADD(CURDATE(), INTERVAL 20 DAY)
FROM terrain WHERE nom = 'Terrain Zaghouan';

INSERT IGNORE INTO contrat_foncier (idTerrain, type, proprietaire, loyerAnnuel, partProprietaire, dateDebut, dateFin)
SELECT id, 'KHAMMES', 'Hédi Ben Salah', NULL, 80.00, '2023-10-01', '2028-09-30'
FROM terrain WHERE nom = 'Terrain Béja';

INSERT IGNORE INTO contrat_foncier (idTerrain, type, proprietaire, loyerAnnuel, partProprietaire, dateDebut, dateFin)
SELECT id, 'PROPRIETE', NULL, NULL, NULL, '2015-01-01', NULL
FROM terrain WHERE nom = 'Terrain Nabeul';

INSERT IGNORE INTO certification_bio (idTerrain, organisme, numeroCertificat, dateDebutConversion, dureeConversionMois, dateExpiration)
SELECT id, 'Ecocert', 'TN-BIO-0457', '2022-01-15', 24, DATE_ADD(CURDATE(), INTERVAL 8 MONTH)
FROM terrain WHERE nom = 'Terrain Nabeul';

INSERT IGNORE INTO parcelle (idTerrain, nom, surface)
SELECT id, 'Parcelle Nord', 1.20 FROM terrain WHERE nom = 'Terrain Zaghouan';
INSERT IGNORE INTO parcelle (idTerrain, nom, surface)
SELECT id, 'Parcelle Sud', 1.30 FROM terrain WHERE nom = 'Terrain Zaghouan';
