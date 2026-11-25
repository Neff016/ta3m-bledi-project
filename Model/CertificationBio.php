<?php
/*
 * Model : CertificationBio
 * Certification en agriculture biologique d'un terrain (Terrain 1 → 0..1 CertificationBio).
 * Le terrain est « en conversion » pendant dureeConversionMois (24 ou 36 mois),
 * puis « certifié » jusqu'à la date d'expiration du certificat.
 */
class CertificationBio
{
    private ?int $id;
    private int $idTerrain;
    private string $organisme;
    private ?string $numeroCertificat;
    private string $dateDebutConversion;   // AAAA-MM-JJ
    private int $dureeConversionMois;
    private ?string $dateExpiration;       // AAAA-MM-JJ

    public function __construct(
        int $idTerrain,
        string $organisme,
        ?string $numeroCertificat,
        string $dateDebutConversion,
        int $dureeConversionMois,
        ?string $dateExpiration,
        ?int $id = null
    ) {
        $this->id                  = $id;
        $this->idTerrain           = $idTerrain;
        $this->organisme           = $organisme;
        $this->numeroCertificat    = $numeroCertificat;
        $this->dateDebutConversion = $dateDebutConversion;
        $this->dureeConversionMois = $dureeConversionMois;
        $this->dateExpiration      = $dateExpiration;
    }

    // Date à partir de laquelle les récoltes sont « bio »
    public function getDateCertification(): string
    {
        return (new DateTime($this->dateDebutConversion))->modify("+{$this->dureeConversionMois} months")->format('Y-m-d');
    }

    // Statut à une date : AVANT, CONVERSION, CERTIFIE ou EXPIRE
    public function statutA(string $date): string
    {
        if ($date < $this->dateDebutConversion) {
            return 'AVANT';
        }
        if ($date < $this->getDateCertification()) {
            return 'CONVERSION';
        }
        if ($this->dateExpiration !== null && $date > $this->dateExpiration) {
            return 'EXPIRE';
        }
        return 'CERTIFIE';
    }

    // ----- Getters -----
    public function getId(): ?int                   { return $this->id; }
    public function getIdTerrain(): int             { return $this->idTerrain; }
    public function getOrganisme(): string          { return $this->organisme; }
    public function getNumeroCertificat(): ?string  { return $this->numeroCertificat; }
    public function getDateDebutConversion(): string { return $this->dateDebutConversion; }
    public function getDureeConversionMois(): int   { return $this->dureeConversionMois; }
    public function getDateExpiration(): ?string    { return $this->dateExpiration; }

    // ----- Setters -----
    public function setId(?int $id): void                      { $this->id = $id; }
    public function setIdTerrain(int $id): void                { $this->idTerrain = $id; }
    public function setOrganisme(string $o): void              { $this->organisme = $o; }
    public function setNumeroCertificat(?string $n): void      { $this->numeroCertificat = $n; }
    public function setDateDebutConversion(string $d): void    { $this->dateDebutConversion = $d; }
    public function setDureeConversionMois(int $m): void       { $this->dureeConversionMois = $m; }
    public function setDateExpiration(?string $d): void        { $this->dateExpiration = $d; }
}
