<?php
/*
 * Controller : Utilisateur
 * Inscription, connexion (session unique), profil, mot de passe oublié par code,
 * gestion admin (CRUD, blocage, suppression).
 * Toutes les requêtes sont préparées (PDO).
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Model/Utilisateur.php';

class UtilisateurController
{
    public const ROLES   = ['AGRICULTEUR' => 'Agriculteur', 'ADMIN' => 'Admin'];
    public const STATUTS = ['ACTIF' => 'Actif', 'BLOQUE' => 'Bloqué'];   // 'SUPPRIME' : compte supprimé par l'admin

    // Mot de passe oublié : code à 6 chiffres
    public const CODE_DUREE_MINUTES = 15;
    public const CODE_ESSAIS_MAX    = 5;

    /* =========================================================
     *  LECTURE
     * ========================================================= */

    // Les comptes supprimés n'apparaissent plus dans la liste
    public function listUtilisateurs(string $recherche = ''): array
    {
        $sql = "SELECT id, nom, prenom, email, telephone, role, statut, dateInscription
                FROM utilisateur
                WHERE statut <> 'SUPPRIME'";
        $params = [];
        if ($recherche !== '') {
            $sql .= " AND (nom LIKE :q1 OR prenom LIKE :q2 OR email LIKE :q3)";
            $like = '%' . $recherche . '%';
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        $sql .= " ORDER BY id DESC";
        $req = config::getConnexion()->prepare($sql);
        $req->execute($params);
        return $req->fetchAll();
    }

    // Retourne aussi les comptes supprimés (pour pouvoir afficher "compte supprimé")
    public function getUtilisateur(int $id)
    {
        $req = config::getConnexion()->prepare("SELECT * FROM utilisateur WHERE id = :id");
        $req->execute(['id' => $id]);
        return $req->fetch();
    }

    // Compte existant et NON supprimé (sinon false)
    public function getUtilisateurActif(int $id)
    {
        $user = $this->getUtilisateur($id);
        return ($user && $user['statut'] !== 'SUPPRIME') ? $user : false;
    }

    public function getByEmail(string $email)
    {
        $req = config::getConnexion()->prepare("SELECT * FROM utilisateur WHERE email = :email");
        $req->execute(['email' => $email]);
        return $req->fetch();
    }

    // L'email est-il déjà pris (par quelqu'un d'autre que $exceptId) ?
    public function emailExiste(string $email, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM utilisateur WHERE email = :email";
        $params = ['email' => $email];
        if ($exceptId !== null) {
            $sql .= " AND id <> :id";
            $params['id'] = $exceptId;
        }
        $req = config::getConnexion()->prepare($sql);
        $req->execute($params);
        return (int) $req->fetchColumn() > 0;
    }

    /* =========================================================
     *  AJOUT / MODIFICATION
     * ========================================================= */

    public function addUtilisateur(Utilisateur $u): int
    {
        $sql = "INSERT INTO utilisateur (nom, prenom, email, telephone, motDePasse, role, statut)
                VALUES (:nom, :prenom, :email, :telephone, :motDePasse, :role, :statut)";
        $pdo = config::getConnexion();
        $req = $pdo->prepare($sql);
        $req->execute([
            'nom'        => $u->getNom(),
            'prenom'     => $u->getPrenom(),
            'email'      => $u->getEmail(),
            'telephone'  => $u->getTelephone(),
            'motDePasse' => $u->getMotDePasse(),
            'role'       => $u->getRole(),
            'statut'     => $u->getStatut(),
        ]);
        return (int) $pdo->lastInsertId();
    }

    // Modifie les informations (pas le mot de passe, pas la photo)
    public function updateUtilisateur(Utilisateur $u): void
    {
        $sql = "UPDATE utilisateur
                SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
                    role = :role, statut = :statut
                WHERE id = :id AND statut <> 'SUPPRIME'";
        $req = config::getConnexion()->prepare($sql);
        $req->execute([
            'nom'       => $u->getNom(),
            'prenom'    => $u->getPrenom(),
            'email'     => $u->getEmail(),
            'telephone' => $u->getTelephone(),
            'role'      => $u->getRole(),
            'statut'    => $u->getStatut(),
            'id'        => $u->getId(),
        ]);
    }

    // Change le mot de passe et annule tout code de réinitialisation en cours
    public function updateMotDePasse(int $id, string $motDePasseClair): void
    {
        $req = config::getConnexion()->prepare(
            "UPDATE utilisateur
             SET motDePasse = :mdp, resetToken = NULL, resetExpiration = NULL, resetTentatives = 0
             WHERE id = :id"
        );
        $req->execute(['mdp' => password_hash($motDePasseClair, PASSWORD_DEFAULT), 'id' => $id]);
    }

    public function updatePhoto(int $id, ?string $photo): void
    {
        $req = config::getConnexion()->prepare("UPDATE utilisateur SET photo = :photo WHERE id = :id");
        $req->execute(['photo' => $photo, 'id' => $id]);
    }

    public function changerStatut(int $id, string $statut): bool
    {
        $req = config::getConnexion()->prepare(
            "UPDATE utilisateur SET statut = :statut WHERE id = :id AND statut <> 'SUPPRIME'"
        );
        $req->execute(['statut' => $statut, 'id' => $id]);
        return $req->rowCount() > 0;
    }

    /* =========================================================
     *  SUPPRESSION (par l'admin)
     *  Le compte n'est pas effacé de la table : il passe au statut 'SUPPRIME'
     *  pour que l'utilisateur voie "Votre compte a été supprimé" s'il essaie d'y accéder.
     *  Ses données (terrains, récoltes, badges, photo, mot de passe) sont effacées.
     * ========================================================= */

    public function deleteUtilisateur(int $id): bool
    {
        $pdo = config::getConnexion();
        $pdo->beginTransaction();
        try {
            // Données liées au compte (les récoltes suivent les terrains : ON DELETE CASCADE)
            $pdo->prepare("DELETE FROM terrain WHERE idUtilisateur = :id")->execute(['id' => $id]);
            $pdo->prepare("DELETE FROM utilisateur_badge WHERE idUtilisateur = :id")->execute(['id' => $id]);

            // Compte marqué supprimé : plus de mot de passe valide, plus de session
            $req = $pdo->prepare(
                "UPDATE utilisateur
                 SET statut = 'SUPPRIME', motDePasse = '', photo = NULL, sessionToken = NULL,
                     resetToken = NULL, resetExpiration = NULL, resetTentatives = 0
                 WHERE id = :id AND statut <> 'SUPPRIME'"
            );
            $req->execute(['id' => $id]);
            $ok = $req->rowCount() > 0;

            $pdo->commit();
            return $ok;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }

    /* =========================================================
     *  CONNEXION + SESSION UNIQUE
     * ========================================================= */

    // Retourne ['ok' => true, 'user' => [...]] ou ['ok' => false, 'erreur' => '...']
    public function connecter(string $email, string $motDePasse): array
    {
        $user = $this->getByEmail($email);
        if ($user && $user['statut'] === 'SUPPRIME') {
            return ['ok' => false, 'erreur' => "Ce compte a été supprimé par l'administrateur."];
        }
        // Même message si l'email n'existe pas ou si le mot de passe est faux (sécurité)
        if (!$user || !password_verify($motDePasse, $user['motDePasse'])) {
            return ['ok' => false, 'erreur' => 'Email ou mot de passe incorrect.'];
        }
        if ($user['statut'] === 'BLOQUE') {
            return ['ok' => false, 'erreur' => "Votre compte est bloqué. Contactez l'administrateur."];
        }
        return ['ok' => true, 'user' => $user];
    }

    // Crée un nouveau jeton de session : toute autre session de ce compte devient invalide
    public function ouvrirSession(int $id): string
    {
        $jeton = bin2hex(random_bytes(32));
        $req = config::getConnexion()->prepare("UPDATE utilisateur SET sessionToken = :t WHERE id = :id");
        $req->execute(['t' => hash('sha256', $jeton), 'id' => $id]);
        return $jeton;
    }

    public function fermerSession(int $id, string $jeton): void
    {
        // Seulement si c'est bien la session active (ne déconnecte pas l'autre appareil)
        $req = config::getConnexion()->prepare(
            "UPDATE utilisateur SET sessionToken = NULL WHERE id = :id AND sessionToken = :t"
        );
        $req->execute(['id' => $id, 't' => hash('sha256', $jeton)]);
    }

    // Vérifie à chaque page protégée que la session est toujours valide
    public function validerSession(int $id, string $jeton): array
    {
        $user = $this->getUtilisateur($id);
        if (!$user) {
            return ['ok' => false, 'message' => "Session incorrecte : ce compte n'existe plus."];
        }
        if ($user['statut'] === 'SUPPRIME') {
            return ['ok' => false, 'message' => "Votre compte a été supprimé par l'administrateur."];
        }
        if ($user['statut'] === 'BLOQUE') {
            return ['ok' => false, 'message' => "Votre compte a été bloqué par l'administrateur."];
        }
        if ($jeton === '' || empty($user['sessionToken']) || !hash_equals($user['sessionToken'], hash('sha256', $jeton))) {
            return ['ok' => false, 'message' => 'Session incorrecte : votre compte a été ouvert sur un autre navigateur ou appareil. Vous avez été déconnecté.'];
        }
        return ['ok' => true, 'message' => ''];
    }

    /* =========================================================
     *  MOT DE PASSE OUBLIÉ (code à 6 chiffres)
     * ========================================================= */

    // Crée un code valable 15 minutes. Retourne le code (à envoyer par email) ou null.
    public function creerCodeReinitialisation(string $email): ?string
    {
        $user = $this->getByEmail($email);
        if (!$user || $user['statut'] !== 'ACTIF') {
            return null;
        }
        $code = (string) random_int(100000, 999999);
        $req = config::getConnexion()->prepare(
            "UPDATE utilisateur
             SET resetToken = :hash,
                 resetExpiration = DATE_ADD(NOW(), INTERVAL " . self::CODE_DUREE_MINUTES . " MINUTE),
                 resetTentatives = 0
             WHERE id = :id"
        );
        // On stocke seulement le hash du code
        $req->execute(['hash' => hash('sha256', $code), 'id' => $user['id']]);
        return $code;
    }

    // Retourne ['ok' => true, 'id' => ...] ou ['ok' => false, 'erreur' => '...']
    public function verifierCode(string $email, string $code): array
    {
        $req = config::getConnexion()->prepare(
            "SELECT id, resetToken, resetTentatives, (resetExpiration > NOW()) AS valide
             FROM utilisateur WHERE email = :email AND statut = 'ACTIF'"
        );
        $req->execute(['email' => $email]);
        $user = $req->fetch();

        if (!$user || empty($user['resetToken']) || !$user['valide']) {
            return ['ok' => false, 'erreur' => 'Code expiré ou invalide. Demandez un nouveau code.'];
        }
        if ((int) $user['resetTentatives'] >= self::CODE_ESSAIS_MAX) {
            return ['ok' => false, 'erreur' => 'Trop d’essais. Demandez un nouveau code.'];
        }
        if (!hash_equals($user['resetToken'], hash('sha256', $code))) {
            config::getConnexion()
                ->prepare("UPDATE utilisateur SET resetTentatives = resetTentatives + 1 WHERE id = :id")
                ->execute(['id' => $user['id']]);
            $reste = self::CODE_ESSAIS_MAX - (int) $user['resetTentatives'] - 1;
            return ['ok' => false, 'erreur' => "Code incorrect. Il vous reste $reste essai(s)."];
        }
        return ['ok' => true, 'id' => (int) $user['id']];
    }

    /* =========================================================
     *  CONTRÔLE DE SAISIE (côté serveur)
     * ========================================================= */

    // Champs communs : nom, prénom, email, téléphone
    public function validateInfos(array $data, ?int $exceptId = null): array
    {
        $erreurs = [];

        foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom'] as $champ => $libelle) {
            $valeur = trim($data[$champ] ?? '');
            if ($valeur === '') {
                $erreurs[$champ] = "$libelle est obligatoire.";
            } elseif (mb_strlen($valeur) < 2 || mb_strlen($valeur) > 50) {
                $erreurs[$champ] = "$libelle doit contenir entre 2 et 50 caractères.";
            } elseif (!preg_match("/^[\p{L}\s'\-]+$/u", $valeur)) {
                $erreurs[$champ] = "$libelle ne doit contenir que des lettres.";
            }
        }

        $email = trim($data['email'] ?? '');
        if ($email === '') {
            $erreurs['email'] = "L'email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            $erreurs['email'] = "L'email n'est pas valide (exemple@mail.com).";
        } elseif ($this->emailExiste($email, $exceptId)) {
            $erreurs['email'] = 'Cet email est déjà utilisé.';
        }

        $telephone = trim($data['telephone'] ?? '');
        if ($telephone === '') {
            $erreurs['telephone'] = 'Le téléphone est obligatoire.';
        } elseif (!preg_match('/^\d{8}$/', $telephone)) {
            $erreurs['telephone'] = 'Le téléphone doit contenir exactement 8 chiffres.';
        }

        return $erreurs;
    }

    // Mot de passe : 8 caractères min, au moins une lettre et un chiffre, + confirmation
    public function validateMotDePasse(string $motDePasse, string $confirmation, string $champ = 'motDePasse', string $champConfirmation = 'confirmation'): array
    {
        $erreurs = [];
        if ($motDePasse === '') {
            $erreurs[$champ] = 'Le mot de passe est obligatoire.';
        } elseif (strlen($motDePasse) < 8 || strlen($motDePasse) > 64) {
            $erreurs[$champ] = 'Le mot de passe doit contenir entre 8 et 64 caractères.';
        } elseif (!preg_match('/[A-Za-z]/', $motDePasse) || !preg_match('/\d/', $motDePasse)) {
            $erreurs[$champ] = 'Le mot de passe doit contenir au moins une lettre et un chiffre.';
        }
        if ($confirmation !== $motDePasse) {
            $erreurs[$champConfirmation] = 'Les deux mots de passe ne correspondent pas.';
        }
        return $erreurs;
    }

    public function validateRoleStatut(array $data): array
    {
        $erreurs = [];
        if (!array_key_exists($data['role'] ?? '', self::ROLES)) {
            $erreurs['role'] = 'Veuillez choisir un rôle.';
        }
        if (!array_key_exists($data['statut'] ?? '', self::STATUTS)) {
            $erreurs['statut'] = 'Veuillez choisir un statut.';
        }
        return $erreurs;
    }

    /* =========================================================
     *  STATISTIQUES
     * ========================================================= */

    public function countUtilisateurs(): int
    {
        return (int) config::getConnexion()
            ->query("SELECT COUNT(*) FROM utilisateur WHERE statut <> 'SUPPRIME'")
            ->fetchColumn();
    }
}
