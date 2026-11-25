-- =====================================================================
--  Taam Bledi — Nettoyage : retrait des fonctions en double avec le
--  module « Plantation et suivi » (carnet de terrain, diagnostic photo,
--  statuts Prévue / En cours, rappels).
--
--  À importer UNE FOIS, seulement si vous aviez déjà importé une ancienne
--  version de ta3m_bledi_terrains_avance.sql.
--  Le script peut être importé plusieurs fois sans erreur.
-- =====================================================================
USE ta3m_bledi;

-- Les récoltes pas encore faites (Prévue / En cours) sont des plantations :
-- elles appartiennent au module « Plantation et suivi ».
DELETE FROM recolte WHERE quantite IS NULL;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'recolte' AND COLUMN_NAME = 'statut');
SET @sql = IF(@col > 0, "DELETE FROM recolte WHERE statut <> 'RECOLTEE'", 'SELECT 1');
PREPARE q FROM @sql; EXECUTE q; DEALLOCATE PREPARE q;

ALTER TABLE recolte
  DROP COLUMN IF EXISTS statut,
  DROP COLUMN IF EXISTS rappelEnvoye,
  MODIFY quantite DECIMAL(12,2) NOT NULL;

DROP TABLE IF EXISTS journal;
DROP TABLE IF EXISTS diagnostic;

-- Le badge « Carnet assidu » est remplacé par « Comptable rigoureux »
DELETE ub FROM utilisateur_badge ub JOIN badge b ON b.id = ub.idBadge WHERE b.nom = 'Carnet assidu';
DELETE FROM badge WHERE nom = 'Carnet assidu';
INSERT IGNORE INTO badge (nom, description, critere, points, icone) VALUES
('Comptable rigoureux', 'Dix dépenses notées pour suivre la rentabilité.', '10 dépenses notées', 15, NULL);
