<?php
/*
 * Petites étiquettes affichées à côté d'une récolte : classe qualité, bio / en conversion, note environnementale
 */
require_once __DIR__ . '/../../../Controller/ControleQualiteController.php';
require_once __DIR__ . '/../../../Controller/CertificationBioController.php';

if (!function_exists('badgesRecolte')) {
    function badgesRecolte($qualite, ?string $statutBio, ?string $noteEnv = null): string
    {
        $html = '';
        if ($qualite) {
            $html .= '<span class="tb-classe tb-classe-' . strtolower(e($qualite['classe'])) . '" title="' . te('Contrôle qualité') . '">'
                   . te(ControleQualiteController::CLASSES[$qualite['classe']] ?? $qualite['classe']) . '</span> ';
        }
        if ($statutBio === 'CERTIFIE') {
            $html .= '<span class="tb-classe tb-classe-bio"><i class="bi bi-flower1"></i> ' . te('Bio') . '</span> ';
        } elseif ($statutBio === 'CONVERSION') {
            $html .= '<span class="tb-classe tb-classe-conversion" title="' . te('En conversion vers l’agriculture biologique') . '"><i class="bi bi-hourglass-split"></i> ' . te('Conversion') . '</span> ';
        }
        if ($noteEnv !== null) {
            $html .= '<span class="tb-note-env tb-note-' . strtolower(e($noteEnv)) . '" title="' . te('Bilan environnemental') . '">' . e($noteEnv) . '</span>';
        }
        return $html;
    }
}
