<?php
require_once __DIR__ . '/Store.php';

class Projection
{
    public const STRESS = ['Faible', 'Modéré', 'Élevé', 'Critique'];

    public function __construct(
        public readonly int $idProjection,
        public readonly int $annee,
        public readonly float $temperatureMoy,
        public readonly float $pluviometrie,
        public readonly string $stressHydrique,
        public readonly string $culturesMenacees,
        public readonly string $description,
        public readonly int $idVille
    ) {}

    private static function fromArray(array $r): Projection
    {
        return new Projection(
            (int)$r['idProjection'], (int)$r['annee'],
            (float)$r['temperatureMoy'], (float)$r['pluviometrie'],
            $r['stressHydrique'], $r['culturesMenacees'],
            $r['description'], (int)$r['idVille']
        );
    }

    public static function findAll(): array
    {
        return array_map([self::class, 'fromArray'], Store::all('projections'));
    }

    // Projections of one region, sorted by year
    public static function findByVille(int $idVille): array
    {
        $mine = array_filter(self::findAll(), fn($p) => $p->idVille === $idVille);
        usort($mine, fn($a, $b) => $a->annee <=> $b->annee);
        return array_values($mine);
    }

    public static function findByYear(int $annee): array
    {
        return array_values(array_filter(self::findAll(), fn($p) => $p->annee === $annee));
    }

    // All available years, sorted: [2025, 2030, 2040, 2050]
    public static function years(): array
    {
        $years = array_unique(array_map(fn($p) => $p->annee, self::findAll()));
        sort($years);
        return array_values($years);
    }

    // "Blé dur, Orge" => ['Blé dur', 'Orge']
    public function cultures(): array
    {
        $list = array_map('trim', explode(',', $this->culturesMenacees));
        return array_values(array_filter($list, fn($c) => $c !== ''));
    }

    public function stressClass(): string
    {
        return match ($this->stressHydrique) {
            'Faible'   => 'stress-faible',
            'Modéré'   => 'stress-modere',
            'Élevé'    => 'stress-eleve',
            'Critique' => 'stress-critique',
            default    => 'stress-default',
        };
    }
}