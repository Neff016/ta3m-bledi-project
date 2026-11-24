<?php
// idVille => base temperature (°C), yearly rain (mm), crops that become threatened (in order)
$regions = [
    1 => ['temp' => 19.2, 'rain' => 460, 'crops' => ['Blé dur', 'Orge', 'Agrumes']],
    2 => ['temp' => 20.1, 'rain' => 210, 'crops' => ['Olivier', 'Amandier', 'Orge']],
    3 => ['temp' => 19.8, 'rain' => 300, 'crops' => ['Blé dur', 'Pistachier', 'Piment']],
    4 => ['temp' => 17.8, 'rain' => 520, 'crops' => ['Blé tendre', 'Orge', 'Pois chiche']],
    5 => ['temp' => 20.8, 'rain' => 160, 'crops' => ['Grenadier', 'Abricotier', 'Palmier dattier']],
    6 => ['temp' => 21.0, 'rain' => 140, 'crops' => ['Olivier', 'Figuier', 'Orge']],
    7 => ['temp' => 18.4, 'rain' => 600, 'crops' => ['Tomate', 'Pomme de terre', 'Artichaut']],
    8 => ['temp' => 23.5, 'rain' => 90,  'crops' => ['Palmier dattier', 'Henné', 'Grenadier']],
];

// year => [temperature increase, rain factor]
$years = [
    2025 => [0.0, 1.00],
    2030 => [0.6, 0.96],
    2040 => [1.3, 0.90],
    2050 => [2.1, 0.84],
];

$rows = [];
$id = 1;
foreach ($regions as $idVille => $r) {
    foreach ($years as $annee => [$dT, $kRain]) {
        $rain = (int)round($r['rain'] * $kRain);
        $stress = $rain >= 450 ? 'Faible' : ($rain >= 300 ? 'Modéré' : ($rain >= 180 ? 'Élevé' : 'Critique'));
        $nb = match ($annee) { 2025 => 0, 2030 => 1, 2040 => 2, default => 3 };

        $rows[] = [
            'idProjection'     => $id++,
            'annee'            => $annee,
            'temperatureMoy'   => round($r['temp'] + $dT, 1),
            'pluviometrie'     => $rain,
            'stressHydrique'   => $stress,
            'culturesMenacees' => implode(', ', array_slice($r['crops'], 0, $nb)),
            'description'      => $annee === 2025
                ? "Situation actuelle, utilisée comme référence."
                : "Horizon {$annee} : stress hydrique {$stress} (données illustratives).",
            'idVille'          => $idVille,
        ];
    }
}

return $rows;