<?php
/*
 * Model : Terrain
 * Représente une ligne de la table "terrain".
 */
class Terrain
{
    private ?int $id;
    private string $nom;
    private string $localisation;
    private float $superficie;
    private string $typeSol;
    private int $idUtilisateur;
    private ?float $latitude;
    private ?float $longitude;
    private ?string $polygone;   // coordonnées de la parcelle dessinée (JSON)

    public function __construct(
        string $nom,
        string $localisation,
        float $superficie,
        string $typeSol,
        int $idUtilisateur,
        ?int $id = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $polygone = null
    ) {
        $this->id            = $id;
        $this->nom           = $nom;
        $this->localisation  = $localisation;
        $this->superficie    = $superficie;
        $this->typeSol       = $typeSol;
        $this->idUtilisateur = $idUtilisateur;
        $this->latitude      = $latitude;
        $this->longitude     = $longitude;
        $this->polygone      = $polygone;
    }

    // ----- Getters -----
    public function getId(): ?int             { return $this->id; }
    public function getNom(): string          { return $this->nom; }
    public function getLocalisation(): string { return $this->localisation; }
    public function getSuperficie(): float    { return $this->superficie; }
    public function getTypeSol(): string      { return $this->typeSol; }
    public function getIdUtilisateur(): int   { return $this->idUtilisateur; }
    public function getLatitude(): ?float     { return $this->latitude; }
    public function getLongitude(): ?float    { return $this->longitude; }
    public function getPolygone(): ?string    { return $this->polygone; }

    // ----- Setters -----
    public function setId(?int $id): void                  { $this->id = $id; }
    public function setNom(string $nom): void              { $this->nom = $nom; }
    public function setLocalisation(string $loc): void     { $this->localisation = $loc; }
    public function setSuperficie(float $superficie): void { $this->superficie = $superficie; }
    public function setTypeSol(string $typeSol): void      { $this->typeSol = $typeSol; }
    public function setIdUtilisateur(int $id): void        { $this->idUtilisateur = $id; }
    public function setLatitude(?float $lat): void         { $this->latitude = $lat; }
    public function setLongitude(?float $lng): void        { $this->longitude = $lng; }
    public function setPolygone(?string $polygone): void   { $this->polygone = $polygone; }
}
