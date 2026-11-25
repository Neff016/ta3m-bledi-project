-- =========================================================
--  Ta3m Bledi : mise à jour sécurité (module Utilisateurs)
--  À exécuter UNE fois sur une base déjà créée :
--  phpMyAdmin → base ta3m_bledi → onglet SQL → coller → Exécuter
-- =========================================================
USE ta3m_bledi;

ALTER TABLE utilisateur
  ADD COLUMN IF NOT EXISTS resetTentatives INT NOT NULL DEFAULT 0,   -- essais du code "mot de passe oublié"
  ADD COLUMN IF NOT EXISTS sessionToken VARCHAR(64) NULL,            -- session unique (hash du jeton)
  ADD COLUMN IF NOT EXISTS derniereConnexion DATETIME NULL,          -- affichée dans le profil
  ADD COLUMN IF NOT EXISTS dateSuppression DATETIME NULL;            -- corbeille : compte restaurable

-- statut : ACTIF / BLOQUE / SUPPRIME

-- ---------------------------------------------------------
-- Table passkey : clés d'accès (Face ID, empreinte, Windows Hello)
-- On garde seulement la clé PUBLIQUE : l'empreinte ne quitte jamais l'appareil.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS passkey (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idUtilisateur INT NOT NULL,
  credentialId VARCHAR(255) NOT NULL UNIQUE,
  clePublique TEXT NOT NULL,
  compteur INT UNSIGNED NOT NULL DEFAULT 0,
  nom VARCHAR(50) NOT NULL,
  dateCreation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  derniereUtilisation DATETIME NULL,
  CONSTRAINT fk_passkey_utilisateur FOREIGN KEY (idUtilisateur) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;
