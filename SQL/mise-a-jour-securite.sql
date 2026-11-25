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
