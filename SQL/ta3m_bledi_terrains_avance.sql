-- =====================================================================
--  Taam Bledi — Module Terrains et Récoltes : fonctions avancées
--  À importer APRÈS :
--    1) ta3m_bledi_utilisateurs_badges.sql
--    2) ta3m_bledi_terrains_recoltes.sql
--  Le script peut être importé plusieurs fois sans erreur.
-- =====================================================================
USE ta3m_bledi;

-- ---------------------------------------------------------
-- Terrain : position GPS, parcelle dessinée, photo, satellite
-- ---------------------------------------------------------
ALTER TABLE terrain
  ADD COLUMN IF NOT EXISTS latitude DECIMAL(9,6) NULL,
  ADD COLUMN IF NOT EXISTS longitude DECIMAL(9,6) NULL,
  ADD COLUMN IF NOT EXISTS polygone TEXT NULL,                 -- coordonnées de la parcelle (JSON)
  ADD COLUMN IF NOT EXISTS photo VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS agroPolygonId VARCHAR(64) NULL,     -- identifiant chez Agromonitoring (NDVI)
  ADD COLUMN IF NOT EXISTS dateCreation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ---------------------------------------------------------
-- Récolte : quantité en kg, photo, code de traçabilité (QR)
-- (les plantations en cours sont gérées par le module « Plantation et suivi »)
-- ---------------------------------------------------------
ALTER TABLE recolte
  ADD COLUMN IF NOT EXISTS quantiteKg DECIMAL(14,2) NULL,       -- quantité convertie en kg (totaux, rendement)
  ADD COLUMN IF NOT EXISTS photo VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS codeTracabilite CHAR(12) NULL;      -- lien public du QR code

ALTER TABLE recolte ADD UNIQUE INDEX IF NOT EXISTS uq_recolte_code (codeTracabilite);

-- Anciennes récoltes : conversion en kg et code de traçabilité
UPDATE recolte SET quantiteKg = quantite * CASE unite WHEN 'tonne' THEN 1000 WHEN 'quintal' THEN 100 ELSE 1 END
WHERE quantiteKg IS NULL AND quantite IS NOT NULL;
UPDATE recolte SET codeTracabilite = UPPER(SUBSTRING(MD5(CONCAT(id, RAND())), 1, 12)) WHERE codeTracabilite IS NULL;

-- ---------------------------------------------------------
-- Catalogue des cultures (moteur de recommandation, eau, prix du marché)
--   sols / saisons / regions : listes séparées par des virgules ('*' = toutes les régions)
--   kc : coefficient cultural FAO-56 (besoin en eau = ET0 × kc)
--   rendement : tonnes par hectare (moyenne en Tunisie)
--   prixKg : prix moyen du marché en dinars (géré par l'admin)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS culture (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(60) NOT NULL UNIQUE,
  categorie VARCHAR(20) NOT NULL,
  sols VARCHAR(100) NOT NULL,
  saisons VARCHAR(60) NOT NULL,
  regions VARCHAR(400) NOT NULL DEFAULT '*',
  besoinEau VARCHAR(10) NOT NULL,            -- FAIBLE / MOYEN / ELEVE
  kc DECIMAL(3,2) NOT NULL,
  rendement DECIMAL(6,2) NOT NULL,
  dureeJours INT NOT NULL,
  prixKg DECIMAL(8,3) NOT NULL,
  conseil VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO culture (nom, categorie, sols, saisons, regions, besoinEau, kc, rendement, dureeJours, prixKg, conseil) VALUES
('Blé dur',      'Céréales', 'Argileux,Limoneux,Calcaire', 'Automne', 'Béja,Jendouba,Bizerte,Le Kef,Siliana,Zaghouan,La Manouba,Ariana,Kairouan', 'MOYEN', 1.15, 3.00, 210, 1.100, 'Semer de mi-novembre à mi-décembre ; un apport d''azote au tallage.'),
('Blé tendre',   'Céréales', 'Argileux,Limoneux',          'Automne', 'Béja,Jendouba,Bizerte,Le Kef,Siliana,Zaghouan', 'MOYEN', 1.15, 3.20, 210, 0.950, 'Préférer les sols profonds et bien drainés.'),
('Orge',         'Céréales', 'Calcaire,Sableux,Argileux,Limoneux', 'Automne', '*', 'FAIBLE', 1.10, 2.00, 180, 0.750, 'Très bonne résistance à la sécheresse : idéale au centre et au sud.'),
('Triticale',    'Céréales', 'Sableux,Calcaire',           'Automne', 'Kairouan,Sidi Bouzid,Kasserine,Siliana,Le Kef', 'FAIBLE', 1.10, 2.20, 190, 0.700, 'Céréale fourragère rustique pour sols pauvres.'),
('Olivier',      'Oliviers', 'Calcaire,Sableux,Limoneux,Argileux', 'Hiver,Printemps', '*', 'FAIBLE', 0.65, 2.50, 240, 2.500, 'Taille légère après la récolte ; irrigation d''appoint en été.'),
('Tomate',       'Légumes',  'Limoneux,Humifère,Sableux',  'Printemps,Été', 'Nabeul,Sousse,Monastir,Mahdia,Sfax,Kairouan,Sidi Bouzid,Ben Arous,Bizerte', 'ELEVE', 1.15, 45.00, 120, 1.200, 'Arrosage régulier au goutte-à-goutte ; tuteurer les plants.'),
('Piment',       'Légumes',  'Limoneux,Humifère,Sableux',  'Printemps', 'Nabeul,Kairouan,Sidi Bouzid,Sousse,Monastir', 'ELEVE', 1.05, 20.00, 150, 2.000, 'Craint le froid : planter après les dernières gelées.'),
('Pomme de terre','Légumes', 'Sableux,Limoneux,Humifère',  'Hiver,Printemps,Automne', 'Nabeul,Bizerte,Jendouba,Ben Arous,Ariana,La Manouba,Monastir', 'ELEVE', 1.15, 25.00, 110, 1.300, 'Butter les plants et éviter l''excès d''eau en fin de cycle.'),
('Oignon',       'Légumes',  'Limoneux,Sableux,Humifère',  'Automne,Hiver', '*', 'MOYEN', 1.05, 30.00, 150, 0.900, 'Arrêter l''irrigation deux semaines avant la récolte.'),
('Pastèque',     'Fruits',   'Sableux,Limoneux',           'Printemps', 'Kairouan,Sidi Bouzid,Mahdia,Sfax,Gabès,Médenine', 'MOYEN', 1.00, 35.00, 100, 0.600, 'Sol chaud et léger ; paillage conseillé.'),
('Melon',        'Fruits',   'Sableux,Limoneux',           'Printemps', 'Kairouan,Sidi Bouzid,Mahdia,Monastir,Nabeul', 'MOYEN', 1.00, 25.00, 100, 1.100, 'Limiter l''arrosage à la maturation pour plus de sucre.'),
('Agrumes',      'Fruits',   'Limoneux,Humifère,Sableux',  'Printemps', 'Nabeul,Bizerte,Ben Arous,La Manouba,Ariana', 'ELEVE', 0.70, 25.00, 300, 1.500, 'Le Cap Bon est la région idéale ; brise-vent recommandé.'),
('Dattier',      'Fruits',   'Sableux,Calcaire',           'Printemps', 'Tozeur,Kébili,Gabès,Gafsa,Tataouine', 'ELEVE', 0.95, 6.00, 365, 4.500, 'Pollinisation manuelle au printemps ; ensacher les régimes.'),
('Amandier',     'Fruits',   'Calcaire,Sableux',           'Hiver', 'Sfax,Sidi Bouzid,Kairouan,Kasserine,Gafsa,Mahdia', 'FAIBLE', 0.90, 1.20, 270, 12.000, 'Arbre sobre ; tailler en hiver, récolter fin été.'),
('Grenadier',    'Fruits',   'Calcaire,Limoneux,Sableux',  'Hiver', 'Gabès,Médenine,Sfax,Kairouan,Tozeur', 'MOYEN', 0.80, 15.00, 210, 2.200, 'Supporte la salinité ; récolte en automne.'),
('Fève',         'Légumes',  'Argileux,Limoneux,Calcaire', 'Automne', '*', 'FAIBLE', 1.05, 2.50, 160, 1.600, 'Légumineuse : enrichit le sol en azote (bonne rotation après le blé).'),
('Pois chiche',  'Légumes',  'Argileux,Limoneux,Calcaire', 'Hiver,Printemps', 'Béja,Bizerte,Le Kef,Siliana,Jendouba,Zaghouan', 'FAIBLE', 1.00, 1.50, 140, 3.500, 'Légumineuse : idéale en rotation avec les céréales.'),
('Luzerne',      'Plantes',  'Limoneux,Calcaire,Argileux', 'Automne,Printemps', '*', 'ELEVE', 0.95, 12.00, 365, 0.400, 'Fourrage pérenne : plusieurs coupes par an.'),
('Menthe',       'Plantes',  'Humifère,Limoneux',          'Printemps', '*', 'ELEVE', 0.90, 15.00, 90, 3.000, 'Aime l''ombre légère et un sol toujours frais.'),
('Romarin',      'Plantes',  'Calcaire,Sableux',           'Printemps,Automne', 'Kasserine,Siliana,Le Kef,Zaghouan,Kairouan', 'FAIBLE', 0.50, 3.00, 365, 2.000, 'Plante aromatique de montagne, presque sans irrigation.');

-- ---------------------------------------------------------
-- Dépenses par terrain (rentabilité)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS depense (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idTerrain INT NOT NULL,
  categorie VARCHAR(20) NOT NULL,            -- Semences, Engrais, Eau, Main-d'oeuvre, Traitement, Matériel, Autre
  montant DECIMAL(10,3) NOT NULL,            -- en dinars
  dateDepense DATE NOT NULL,
  description VARCHAR(150) NULL,
  CONSTRAINT fk_depense_terrain FOREIGN KEY (idTerrain) REFERENCES terrain(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Badges attribués automatiquement par le module
-- ---------------------------------------------------------
INSERT IGNORE INTO badge (nom, description, critere, points, icone) VALUES
('Premier terrain',     'Premier terrain enregistré sur Taam Bledi.',      '1 terrain',                 5,  NULL),
('Première récolte',    'Première récolte terminée et enregistrée.',       '1 récolte récoltée',        10, NULL),
('Une tonne récoltée',  'Au moins 1 000 kg récoltés au total.',            '1 000 kg récoltés',         20, NULL),
('Comptable rigoureux', 'Dix dépenses notées pour suivre la rentabilité.', '10 dépenses notées',        15, NULL),
('Producteur traçable', 'Une récolte avec un QR code de traçabilité.',     '1 étiquette QR imprimée',   10, NULL);

-- ---------------------------------------------------------
-- Données de démonstration (terrains existants)
-- ---------------------------------------------------------
UPDATE terrain SET latitude = 36.402100, longitude = 10.142300 WHERE nom = 'Terrain Zaghouan' AND latitude IS NULL;
UPDATE terrain SET latitude = 36.451300, longitude = 10.735700 WHERE nom = 'Terrain Nabeul'   AND latitude IS NULL;
UPDATE terrain SET latitude = 36.725600, longitude = 9.181700  WHERE nom = 'Terrain Béja'     AND latitude IS NULL;
