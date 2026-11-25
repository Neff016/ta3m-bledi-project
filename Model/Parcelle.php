<?php
/*
 * Model : Parcelle
 * Une partie d'un terrain (Terrain 1 → * Parcelle → * Recolte).
 * Sert à suivre la rotation des cultures parcelle par parcelle.
 */
class Parcelle
{
    private ?int $id;
    private int $idTerrain;
    private string $nom;
    private float $surface;      // hectares
    private ?string $polygone;   // contour dessiné (JSON)

    public function __construct(int $idTerrain, string $nom, float $surface, ?string $polygone = null, ?int $id = null)
    {
        $this->id        = $id;
        $this->idTerrain = $idTerrain;
        $this->nom       = $nom;
        $this->surface   = $surface;
        $this->polygone  = $polygone;
    }

    // ----- Getters -----
    public function getId(): ?int          { return $this->id; }
    public function getIdTerrain(): int    { return $this->idTerrain; }
    public function getNom(): string       { return $this->nom; }
    public function getSurface(): float    { return $this->surface; }
    public function getPolygone(): ?string { return $this->polygone; }

    // ----- Setters -----
    public function setId(?int $id): void            { $this->id = $id; }
    public function setIdTerrain(int $id): void      { $this->idTerrain = $id; }
    public function setNom(string $nom): void        { $this->nom = $nom; }
    public function setSurface(float $s): void       { $this->surface = $s; }
    public function setPolygone(?string $p): void    { $this->polygone = $p; }
}
