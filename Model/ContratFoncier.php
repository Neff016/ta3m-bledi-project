<?php
/*
 * Model : ContratFoncier
 * Sous quel régime l'agriculteur exploite un terrain (Terrain 1 → 0..1 ContratFoncier).
 *   PROPRIETE : il est propriétaire (pas de loyer, pas de part)
 *   LOCATION  : il paie un loyer annuel au propriétaire
 *   KHAMMES   : association traditionnelle, le propriétaire garde un % de la récolte
 *   METAYAGE  : le propriétaire garde un % de la récolte (souvent 50 %)
 */
class ContratFoncier
{
    private ?int $id;
    private int $idTerrain;
    private string $type;
    private ?string $proprietaire;
    private ?float $loyerAnnuel;        // LOCATION seulement (DT)
    private ?float $partProprietaire;   // KHAMMES / METAYAGE seulement (%)
    private string $dateDebut;          // AAAA-MM-JJ
    private ?string $dateFin;           // AAAA-MM-JJ (vide pour PROPRIETE)

    public function __construct(
        int $idTerrain,
        string $type,
        ?string $proprietaire,
        ?float $loyerAnnuel,
        ?float $partProprietaire,
        string $dateDebut,
        ?string $dateFin,
        ?int $id = null
    ) {
        $this->id               = $id;
        $this->idTerrain        = $idTerrain;
        $this->type             = $type;
        $this->proprietaire     = $proprietaire;
        $this->loyerAnnuel      = $loyerAnnuel;
        $this->partProprietaire = $partProprietaire;
        $this->dateDebut        = $dateDebut;
        $this->dateFin          = $dateFin;
    }

    // Nombre de jours avant la fin du contrat (null si pas de date de fin)
    public function joursRestants(?string $aujourdhui = null): ?int
    {
        if ($this->dateFin === null) {
            return null;
        }
        $debut = new DateTime($aujourdhui ?? 'today');
        return (int) $debut->diff(new DateTime($this->dateFin))->format('%r%a');
    }

    // ----- Getters -----
    public function getId(): ?int                  { return $this->id; }
    public function getIdTerrain(): int            { return $this->idTerrain; }
    public function getType(): string              { return $this->type; }
    public function getProprietaire(): ?string     { return $this->proprietaire; }
    public function getLoyerAnnuel(): ?float       { return $this->loyerAnnuel; }
    public function getPartProprietaire(): ?float  { return $this->partProprietaire; }
    public function getDateDebut(): string         { return $this->dateDebut; }
    public function getDateFin(): ?string          { return $this->dateFin; }

    // ----- Setters -----
    public function setId(?int $id): void                     { $this->id = $id; }
    public function setIdTerrain(int $id): void               { $this->idTerrain = $id; }
    public function setType(string $type): void               { $this->type = $type; }
    public function setProprietaire(?string $nom): void       { $this->proprietaire = $nom; }
    public function setLoyerAnnuel(?float $loyer): void       { $this->loyerAnnuel = $loyer; }
    public function setPartProprietaire(?float $part): void   { $this->partProprietaire = $part; }
    public function setDateDebut(string $date): void          { $this->dateDebut = $date; }
    public function setDateFin(?string $date): void           { $this->dateFin = $date; }
}
