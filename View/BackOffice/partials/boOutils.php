<?php
/*
 * Outils du Back Office : tri par colonne et pagination (les filtres restent dans l'URL)
 */

// En-tête de colonne cliquable : ↑ / ↓
function enteteTri(string $colonne, string $libelle, string $triActuel, string $ordreActuel, array $params): string
{
    $ordre = ($triActuel === $colonne && $ordreActuel === 'ASC') ? 'DESC' : 'ASC';
    $url   = '?' . http_build_query(array_merge($params, ['tri' => $colonne, 'ordre' => $ordre, 'page' => 1]));
    $icone = $triActuel === $colonne ? ($ordreActuel === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort text-gray-400';
    return '<a href="' . e($url) . '" class="lien-tri">' . e($libelle) . ' <i class="fas ' . $icone . '"></i></a>';
}

// Boutons de pagination Bootstrap
function pagination(int $page, int $total, int $parPage, array $params): string
{
    $pages = max(1, (int) ceil($total / $parPage));
    if ($pages === 1) {
        return '';
    }
    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center mb-0">';
    $lien = fn(int $p, string $texte, bool $actif = false, bool $off = false) =>
        '<li class="page-item' . ($actif ? ' active' : '') . ($off ? ' disabled' : '') . '"><a class="page-link" href="'
        . e('?' . http_build_query(array_merge($params, ['page' => $p]))) . '">' . $texte . '</a></li>';
    $html .= $lien(max(1, $page - 1), '&laquo;', false, $page === 1);
    for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) {
        $html .= $lien($p, (string) $p, $p === $page);
    }
    $html .= $lien(min($pages, $page + 1), '&raquo;', false, $page === $pages);
    return $html . '</ul></nav>';
}
