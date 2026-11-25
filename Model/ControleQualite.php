<?php
/*
 * Model : ControleQualite
 * Résultat du contrôle qualité d'une récolte (Recolte 1 → 0..1 ControleQualite).
 *   classe : EXTRA, I ou II (normes de commercialisation des fruits et légumes)
 *   humidite : surtout pour les céréales (au-dessus de 14,5 % : risque de moisissure au stockage)
 *   brix : taux de sucre des fruits
 */
class ControleQualite
{
    private ?int $id;
    private int $idRecolte;
    private string $classe;
    private ?string $calibre;
    private float $tauxDechets;   // %
    private ?float $humidite;     // %
    private ?float $brix;         // °Brix
    private string $dateControle; // AAAA-MM-JJ
    private ?string $remarque;

    public function __construct(
        int $idRecolte,
        string $classe,
        ?string $calibre,
        float $tauxDechets,
        ?float $humidite,
        ?float $brix,
        string $dateControle,
        ?string $remarque = null,
        ?int $id = null
    ) {
        $this->id           = $id;
        $this->idRecolte    = $idRecolte;
        $this->classe       = $classe;
        $this->calibre      = $calibre;
        $this->tauxDechets  = $tauxDechets;
        $this->humidite     = $humidite;
        $this->brix         = $brix;
        $this->dateControle = $dateControle;
        $this->remarque     = $remarque;
    }

    // ----- Getters -----
    public function getId(): ?int            { return $this->id; }
    public function getIdRecolte(): int      { return $this->idRecolte; }
    public function getClasse(): string      { return $this->classe; }
    public function getCalibre(): ?string    { return $this->calibre; }
    public function getTauxDechets(): float  { return $this->tauxDechets; }
    public function getHumidite(): ?float    { return $this->humidite; }
    public function getBrix(): ?float        { return $this->brix; }
    public function getDateControle(): string { return $this->dateControle; }
    public function getRemarque(): ?string   { return $this->remarque; }

    // ----- Setters -----
    public function setId(?int $id): void            { $this->id = $id; }
    public function setIdRecolte(int $id): void      { $this->idRecolte = $id; }
    public function setClasse(string $c): void       { $this->classe = $c; }
    public function setCalibre(?string $c): void     { $this->calibre = $c; }
    public function setTauxDechets(float $t): void   { $this->tauxDechets = $t; }
    public function setHumidite(?float $h): void     { $this->humidite = $h; }
    public function setBrix(?float $b): void         { $this->brix = $b; }
    public function setDateControle(string $d): void { $this->dateControle = $d; }
    public function setRemarque(?string $r): void    { $this->remarque = $r; }
}
