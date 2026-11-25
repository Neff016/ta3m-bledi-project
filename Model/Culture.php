<?php
/*
 * Model : Culture
 * Une culture du catalogue (géré par l'admin) : sert à la recommandation,
 * au calcul du besoin en eau (kc), au rendement de référence et au prix du marché.
 */
class Culture
{
    private ?int $id;
    private string $nom;
    private string $categorie;     // Céréales, Oliviers, Fruits, Légumes, Plantes
    private array $sols;           // types de sol adaptés
    private array $saisons;        // saisons de plantation
    private array $regions;        // wilayas typiques (vide = toute la Tunisie)
    private string $besoinEau;     // FAIBLE / MOYEN / ELEVE
    private float $kc;             // coefficient cultural FAO-56
    private float $rendement;      // t/ha
    private int $dureeJours;
    private float $prixKg;         // DT/kg
    private string $conseil;

    public function __construct(string $nom, string $categorie, array $sols, array $saisons, array $regions,
                                string $besoinEau, float $kc, float $rendement, int $dureeJours, float $prixKg,
                                string $conseil, ?int $id = null)
    {
        $this->id         = $id;
        $this->nom        = $nom;
        $this->categorie  = $categorie;
        $this->sols       = $sols;
        $this->saisons    = $saisons;
        $this->regions    = $regions;
        $this->besoinEau  = $besoinEau;
        $this->kc         = $kc;
        $this->rendement  = $rendement;
        $this->dureeJours = $dureeJours;
        $this->prixKg     = $prixKg;
        $this->conseil    = $conseil;
    }

    // Valeur rangée en base : 'Argileux,Limoneux' ; régions vides → '*'
    public function getSolsTexte(): string    { return implode(',', $this->sols); }
    public function getSaisonsTexte(): string { return implode(',', $this->saisons); }
    public function getRegionsTexte(): string { return empty($this->regions) ? '*' : implode(',', $this->regions); }

    // ----- Getters -----
    public function getId(): ?int           { return $this->id; }
    public function getNom(): string        { return $this->nom; }
    public function getCategorie(): string  { return $this->categorie; }
    public function getSols(): array        { return $this->sols; }
    public function getSaisons(): array     { return $this->saisons; }
    public function getRegions(): array     { return $this->regions; }
    public function getBesoinEau(): string  { return $this->besoinEau; }
    public function getKc(): float          { return $this->kc; }
    public function getRendement(): float   { return $this->rendement; }
    public function getDureeJours(): int    { return $this->dureeJours; }
    public function getPrixKg(): float      { return $this->prixKg; }
    public function getConseil(): string    { return $this->conseil; }

    // ----- Setters -----
    public function setId(?int $id): void             { $this->id = $id; }
    public function setNom(string $n): void           { $this->nom = $n; }
    public function setCategorie(string $c): void     { $this->categorie = $c; }
    public function setSols(array $s): void           { $this->sols = $s; }
    public function setSaisons(array $s): void        { $this->saisons = $s; }
    public function setRegions(array $r): void        { $this->regions = $r; }
    public function setBesoinEau(string $b): void     { $this->besoinEau = $b; }
    public function setKc(float $kc): void            { $this->kc = $kc; }
    public function setRendement(float $r): void      { $this->rendement = $r; }
    public function setDureeJours(int $d): void       { $this->dureeJours = $d; }
    public function setPrixKg(float $p): void         { $this->prixKg = $p; }
    public function setConseil(string $c): void       { $this->conseil = $c; }
}
