<?php
require_once __DIR__ . '/Store.php';

class Ville
{
    public function __construct(
        public readonly int $idVille,
        public readonly string $nom,
        public readonly string $gouvernorat,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $description,
        public readonly string $image,
        public readonly ?int $idAdmin      // Utilisateur (role admin) from Module 1
    ) {}

    private static function fromArray(array $r): Ville
    {
        return new Ville(
            (int)$r['idVille'], $r['nom'], $r['gouvernorat'],
            (float)$r['latitude'], (float)$r['longitude'],
            $r['description'], $r['image'],
            isset($r['idAdmin']) ? (int)$r['idAdmin'] : null
        );
    }

    public static function findAll(): array
    {
        return array_map([self::class, 'fromArray'], Store::all('villes'));
    }

    public static function findById(int $id): ?Ville
    {
        foreach (self::findAll() as $v) {
            if ($v->idVille === $id) {
                return $v;
            }
        }
        return null;
    }
}