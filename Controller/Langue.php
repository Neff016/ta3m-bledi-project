<?php
/*
 * Traduction Français / Arabe des pages du module Terrains et Récoltes
 *  - t('Mes terrains') → « أراضيّ » si la langue choisie est l'arabe, sinon le texte français
 *  - la langue est gardée en session + cookie (30 jours)
 *  - l'arabe s'affiche de droite à gauche (dir="rtl", Bootstrap RTL)
 * Dictionnaire : View/FrontOffice/lang/ar.php (texte français => traduction arabe)
 */

const LANGUES = ['fr' => 'Français', 'ar' => 'العربية'];

function langue(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $l = $_SESSION['langue'] ?? $_COOKIE['langue'] ?? 'fr';
    return isset(LANGUES[$l]) ? $l : 'fr';
}

function estRtl(): bool
{
    return langue() === 'ar';
}

function dictionnaireArabe(): array
{
    static $dictionnaire = null;
    return $dictionnaire ??= require __DIR__ . '/../View/FrontOffice/lang/ar.php';
}

function t(string $texte, array $valeurs = []): string
{
    if (langue() === 'ar') {
        $texte = dictionnaireArabe()[$texte] ?? $texte;
    }
    // Remplace {nom} par sa valeur : t('Bonjour {prenom}', ['prenom' => 'Salma'])
    foreach ($valeurs as $cle => $valeur) {
        $texte = str_replace('{' . $cle . '}', (string) $valeur, $texte);
    }
    return $texte;
}

// Texte traduit ET protégé pour le HTML
function te(string $texte, array $valeurs = []): string
{
    return htmlspecialchars(t($texte, $valeurs), ENT_QUOTES, 'UTF-8');
}
