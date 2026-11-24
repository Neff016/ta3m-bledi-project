<?php
<<<<<<< HEAD
/*
 * Model : Utilisateur
 * Représente une ligne de la table "utilisateur".
 * Le mot de passe est toujours stocké HACHÉ (password_hash), jamais en clair.
 */
class Utilisateur
{
    private ?int $id;
    private string $nom;
    private string $prenom;
    private string $email;
    private string $telephone;
    private string $motDePasse;   // hash
    private string $role;         // 'ADMIN' ou 'AGRICULTEUR'
    private string $statut;       // 'ACTIF' ou 'BLOQUE'
    private ?string $photo;

    public function __construct(
        string $nom,
        string $prenom,
        string $email,
        string $telephone,
        string $motDePasse = '',
        string $role = 'AGRICULTEUR',
        string $statut = 'ACTIF',
        ?string $photo = null,
        ?int $id = null
    ) {
        $this->id         = $id;
        $this->nom        = $nom;
        $this->prenom     = $prenom;
        $this->email      = $email;
        $this->telephone  = $telephone;
        $this->motDePasse = $motDePasse;
        $this->role       = $role;
        $this->statut     = $statut;
        $this->photo      = $photo;
    }

    // ----- Getters -----
    public function getId(): ?int          { return $this->id; }
    public function getNom(): string       { return $this->nom; }
    public function getPrenom(): string    { return $this->prenom; }
    public function getEmail(): string     { return $this->email; }
    public function getTelephone(): string { return $this->telephone; }
    public function getMotDePasse(): string{ return $this->motDePasse; }
    public function getRole(): string      { return $this->role; }
    public function getStatut(): string    { return $this->statut; }
    public function getPhoto(): ?string    { return $this->photo; }

    // ----- Setters -----
    public function setId(?int $id): void              { $this->id = $id; }
    public function setNom(string $nom): void          { $this->nom = $nom; }
    public function setPrenom(string $prenom): void    { $this->prenom = $prenom; }
    public function setEmail(string $email): void      { $this->email = $email; }
    public function setTelephone(string $tel): void    { $this->telephone = $tel; }
    public function setMotDePasse(string $hash): void  { $this->motDePasse = $hash; }
    public function setRole(string $role): void        { $this->role = $role; }
    public function setStatut(string $statut): void    { $this->statut = $statut; }
    public function setPhoto(?string $photo): void     { $this->photo = $photo; }
=======
require_once __DIR__ . '/Store.php';

// Utilisateur (Module 1 of the class diagram) - only what the back-office needs
class Utilisateur
{
    public const ROLES   = ['utilisateur', 'admin'];
    public const STATUTS = ['actif', 'bloqué'];

    public static function all(): array
    {
        return Store::all('utilisateurs');
    }

    public static function findById(int $id): ?array
    {
        foreach (self::all() as $u) {
            if ((int)$u['idUtilisateur'] === $id) {
                return $u;
            }
        }
        return null;
    }

    public static function findByEmail(string $email): ?array
    {
        foreach (self::all() as $u) {
            if (strcasecmp($u['email'], $email) === 0) {
                return $u;
            }
        }
        return null;
    }

    // Active admins, optionally ignoring one account (to check "is there another admin left?")
    public static function countActiveAdmins(int $exceptId = 0): int
    {
        $n = 0;
        foreach (self::all() as $u) {
            if ($u['role'] === 'admin' && $u['statut'] === 'actif' && (int)$u['idUtilisateur'] !== $exceptId) {
                $n++;
            }
        }
        return $n;
    }

    // Server-side validation. $in = nom, prenom, email, telephone, role, statut, motDePasse
    public static function validate(array $in, int $id = 0): array
    {
        $errors = [];
        $name = '/^\p{L}[\p{L} \'\-]{1,49}$/u';

        if (!preg_match($name, $in['nom']))    $errors[] = 'Nom invalide (2 à 50 lettres).';
        if (!preg_match($name, $in['prenom'])) $errors[] = 'Prénom invalide (2 à 50 lettres).';

        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || strlen($in['email']) > 100) {
            $errors[] = 'Adresse e-mail invalide.';
        } else {
            $other = self::findByEmail($in['email']);
            if ($other && (int)$other['idUtilisateur'] !== $id) {
                $errors[] = 'Cette adresse e-mail est déjà utilisée.';
            }
        }

        if ($in['telephone'] !== '' && !preg_match('/^\d{8}$/', $in['telephone'])) {
            $errors[] = 'Téléphone invalide (8 chiffres).';
        }
        if (!in_array($in['role'], self::ROLES, true))     $errors[] = 'Rôle invalide.';
        if (!in_array($in['statut'], self::STATUTS, true)) $errors[] = 'Statut invalide.';

        $pwd = $in['motDePasse'];
        if ($id === 0 && $pwd === '') {
            $errors[] = 'Le mot de passe est obligatoire.';
        } elseif ($pwd !== '' && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,72}$/', $pwd)) {
            $errors[] = 'Mot de passe : 8 caractères minimum avec majuscule, minuscule et chiffre.';
        }
        return $errors;
    }

    public static function save(array $row): void
    {
        $rows = self::all();
        $found = false;
        foreach ($rows as $k => $r) {
            if ((int)$r['idUtilisateur'] === (int)$row['idUtilisateur']) {
                $rows[$k] = $row;
                $found = true;
            }
        }
        if (!$found) {
            $rows[] = $row;
        }
        Store::replace('utilisateurs', $rows);
    }

    public static function delete(int $id): void
    {
        Store::replace('utilisateurs', array_filter(self::all(), fn($u) => (int)$u['idUtilisateur'] !== $id));
    }

    public static function nextId(): int
    {
        return Store::nextId('utilisateurs', 'idUtilisateur');
    }
>>>>>>> 8f8c5a5 (Add my project updates*)
}
