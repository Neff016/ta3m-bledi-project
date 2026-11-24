<?php
// Simulated AI service. Later, replace generate() with a call to a real AI API:
// the controller and the views will not need to change.
class ExplicationIA
{
    private static function n(float $v, int $d = 1): string
    {
        return number_format($v, $d, ',', ' ');
    }

    private static function sign(float $v): string
    {
        return $v >= 0 ? '+' : '−';
    }

    public static function generate(Ville $v, Projection $base, Projection $cible): string
    {
        if ($cible->annee === $base->annee) {
            return "À {$v->nom}, la situation actuelle ({$base->annee}) est de "
                . self::n($base->temperatureMoy) . " °C en moyenne et "
                . self::n($base->pluviometrie, 0) . " mm de pluie par an, avec un stress hydrique « {$base->stressHydrique} ».\n\n"
                . "Choisissez un horizon plus lointain pour voir comment cela évolue.";
        }

        $dT = $cible->temperatureMoy - $base->temperatureMoy;
        $dR = $base->pluviometrie > 0
            ? ($cible->pluviometrie - $base->pluviometrie) / $base->pluviometrie * 100
            : 0;

        $p1 = "À {$v->nom}, la température moyenne passerait de " . self::n($base->temperatureMoy)
            . " °C ({$base->annee}) à " . self::n($cible->temperatureMoy) . " °C en {$cible->annee}, soit "
            . self::sign($dT) . self::n(abs($dT)) . " °C. Les précipitations évolueraient de "
            . self::n($base->pluviometrie, 0) . " mm à " . self::n($cible->pluviometrie, 0) . " mm ("
            . self::sign($dR) . self::n(abs($dR), 0) . " %).";

        $p2 = match ($cible->stressHydrique) {
            'Critique' => "Le stress hydrique serait critique : l'eau deviendrait le premier facteur limitant pour l'agriculture locale.",
            'Élevé'    => "Le stress hydrique serait élevé : les périodes sèches pèseraient fortement sur les rendements.",
            'Modéré'   => "Le stress hydrique resterait modéré, mais demanderait une gestion plus attentive de l'eau.",
            default    => "Le stress hydrique resterait faible : la région garderait une bonne marge de sécurité.",
        };

        $cultures = $cible->cultures();
        if ($cultures) {
            $conseil = in_array($cible->stressHydrique, ['Critique', 'Élevé'], true)
                ? "Pistes : irrigation goutte à goutte, paillage, variétés locales tolérantes à la sécheresse et calendrier de semis adapté."
                : "Pistes : surveiller la ressource en eau et diversifier les variétés locales.";
            $p3 = "Cultures les plus exposées : " . implode(', ', $cultures) . ". " . $conseil;
        } else {
            $p3 = "Aucune culture n'est signalée comme menacée à cet horizon.";
        }

        $p4 = "Ces valeurs sont des projections illustratives, à confirmer avec des données climatiques officielles.";

        return implode("\n\n", [$p1, $p2, $p3, $p4]);
    }
}