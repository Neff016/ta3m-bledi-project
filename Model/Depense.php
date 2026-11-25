<?php
/*
 * Model : Depense
 * Une dépense d'un terrain (Terrain 1 → * Depense) : sert au calcul de la rentabilité
 * et du bilan carbone.
 */
class Depense
{
    private ?int $id;
    private int $idTerrain;
    private string $categorie;      // Semences, Engrais, Eau, Main-d'oeuvre, Traitement, Matériel, Autre
    private float $montant;         // DT
    private string $dateDepense;    // AAAA-MM-JJ
    private ?string $description;

    public function __construct(int $idTerrain, string $categorie, float $montant, string $dateDepense,
                                ?string $description = null, ?int $id = null)
    {
        $this->id          = $id;
        $this->idTerrain   = $idTerrain;
        $this->categorie   = $categorie;
        $this->montant     = $montant;
        $this->dateDepense = $dateDepense;
        $this->description = $description;
    }

    // ----- Getters -----
    public function getId(): ?int              { return $this->id; }
    public function getIdTerrain(): int        { return $this->idTerrain; }
    public function getCategorie(): string     { return $this->categorie; }
    public function getMontant(): float        { return $this->montant; }
    public function getDateDepense(): string   { return $this->dateDepense; }
    public function getDescription(): ?string  { return $this->description; }

    // ----- Setters -----
    public function setId(?int $id): void              { $this->id = $id; }
    public function setIdTerrain(int $id): void        { $this->idTerrain = $id; }
    public function setCategorie(string $c): void      { $this->categorie = $c; }
    public function setMontant(float $m): void         { $this->montant = $m; }
    public function setDateDepense(string $d): void    { $this->dateDepense = $d; }
    public function setDescription(?string $d): void   { $this->description = $d; }
}
