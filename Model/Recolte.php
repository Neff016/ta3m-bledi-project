<?php
/*
 * Model : Recolte
 * Représente une ligne de la table "recolte".
 * Une récolte est toujours terminée (les plantations en cours sont gérées
 * par le module « Plantation et suivi »).
 */
class Recolte
{
    private ?int $id;
    private string $culture;
    private string $datePlantation;   // format AAAA-MM-JJ
    private string $dateRecolte;      // format AAAA-MM-JJ
    private float $quantite;
    private string $unite;
    private int $idTerrain;

    public function __construct(
        string $culture,
        string $datePlantation,
        string $dateRecolte,
        float $quantite,
        string $unite,
        int $idTerrain,
        ?int $id = null
    ) {
        $this->id             = $id;
        $this->culture        = $culture;
        $this->datePlantation = $datePlantation;
        $this->dateRecolte    = $dateRecolte;
        $this->quantite       = $quantite;
        $this->unite          = $unite;
        $this->idTerrain      = $idTerrain;
    }

    // Quantité convertie en kilogrammes (pour additionner et comparer)
    public function getQuantiteKg(): float
    {
        $facteurs = ['kg' => 1, 'quintal' => 100, 'tonne' => 1000];
        return round($this->quantite * ($facteurs[$this->unite] ?? 1), 2);
    }

    // ----- Getters -----
    public function getId(): ?int               { return $this->id; }
    public function getCulture(): string        { return $this->culture; }
    public function getDatePlantation(): string { return $this->datePlantation; }
    public function getDateRecolte(): string    { return $this->dateRecolte; }
    public function getQuantite(): float        { return $this->quantite; }
    public function getUnite(): string          { return $this->unite; }
    public function getIdTerrain(): int         { return $this->idTerrain; }

    // ----- Setters -----
    public function setId(?int $id): void                  { $this->id = $id; }
    public function setCulture(string $culture): void      { $this->culture = $culture; }
    public function setDatePlantation(string $date): void  { $this->datePlantation = $date; }
    public function setDateRecolte(string $date): void     { $this->dateRecolte = $date; }
    public function setQuantite(float $quantite): void     { $this->quantite = $quantite; }
    public function setUnite(string $unite): void          { $this->unite = $unite; }
    public function setIdTerrain(int $id): void            { $this->idTerrain = $id; }
}
