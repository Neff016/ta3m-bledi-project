<?php
/*
 * Détails d'un terrain (seulement s'il appartient à l'utilisateur)
 * Onglets : Aperçu · Météo · Récoltes · Parcelles et rotation · Rentabilité · Contrat et bio · Satellite
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/DepenseController.php';
require_once __DIR__ . '/../../Controller/ContratFoncierController.php';
require_once __DIR__ . '/../../Controller/CertificationBioController.php';
require_once __DIR__ . '/../../Controller/ParcelleController.php';
require_once __DIR__ . '/../../Controller/ControleQualiteController.php';
require_once __DIR__ . '/../../Controller/AnalyseController.php';
require_once __DIR__ . '/../../Controller/MeteoController.php';
require_once __DIR__ . '/../../Controller/SatelliteController.php';
require_once __DIR__ . '/../../Controller/BadgeAutoController.php';
require_once __DIR__ . '/../../Controller/UploadHelper.php';
require_once __DIR__ . '/../../Controller/Langue.php';
require_once __DIR__ . '/partials/badgesRecolte.php';

$idUtilisateur = requireLogin();
$terrainC      = new TerrainController();
$depenseC      = new DepenseController();
$contratC      = new ContratFoncierController();
$bioC          = new CertificationBioController();
$parcelleC     = new ParcelleController();

$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$terrain = $id ? $terrainC->getTerrainOfUser($id, $idUtilisateur) : false;
if (!$terrain) {
    setFlash(t("Ce terrain n'existe pas ou ne vous appartient pas."), 'danger');
    header('Location: mesTerrains.php');
    exit;
}

$ongletsValides = ['apercu', 'meteo', 'recoltes', 'parcelles', 'depenses', 'foncier', 'satellite'];
$onglet = in_array($_GET['onglet'] ?? '', $ongletsValides, true) ? $_GET['onglet'] : 'apercu';
$erreursDepense = [];
$erreursContrat = [];
$erreursBio     = [];
$contrat = $contratC->getParTerrain($id);
$certif  = $bioC->getParTerrain($id);
$contratForm = $contrat ?: ['type' => 'PROPRIETE', 'proprietaire' => '', 'loyerAnnuel' => '', 'partProprietaire' => '',
                            'dateDebut' => '', 'dateFin' => ''];
$bioForm = $certif ?: ['organisme' => '', 'numeroCertificat' => '', 'dateDebutConversion' => '',
                       'dureeConversionMois' => 24, 'dateExpiration' => ''];
$depense = ['categorie' => 'Semences', 'montant' => '', 'dateDepense' => date('Y-m-d'), 'description' => ''];
$retour  = 'detailsTerrain.php?id=' . $id . '&onglet=';

/* ---------------- Actions (formulaires des onglets) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ----- Contrat foncier -----
    if ($action === 'enregistrerContrat') {
        $onglet = 'foncier';
        $contratForm = ['type' => $_POST['type'] ?? '', 'proprietaire' => trim($_POST['proprietaire'] ?? ''),
                        'loyerAnnuel' => trim($_POST['loyerAnnuel'] ?? ''), 'partProprietaire' => trim($_POST['partProprietaire'] ?? ''),
                        'dateDebut' => $_POST['dateDebut'] ?? '', 'dateFin' => $_POST['dateFin'] ?? ''];
        $erreursContrat = $contratC->valider($contratForm);
        if (empty($erreursContrat)) {
            $contratC->enregistrer($contratC->versObjet($contratForm, $id));
            setFlash(t('Contrat foncier enregistré.'));
            header('Location: ' . $retour . 'foncier');
            exit;
        }
    }
    if ($action === 'supprimerContrat') {
        $ok = $contratC->supprimer($id);
        setFlash(t($ok ? 'Contrat foncier supprimé.' : 'Aucun contrat à supprimer.'), $ok ? 'success' : 'danger');
        header('Location: ' . $retour . 'foncier');
        exit;
    }

    // ----- Certification bio -----
    if ($action === 'enregistrerBio') {
        $onglet = 'foncier';
        $bioForm = ['organisme' => trim($_POST['organisme'] ?? ''), 'numeroCertificat' => trim($_POST['numeroCertificat'] ?? ''),
                    'dateDebutConversion' => $_POST['dateDebutConversion'] ?? '', 'dureeConversionMois' => $_POST['dureeConversionMois'] ?? '',
                    'dateExpiration' => $_POST['dateExpiration'] ?? ''];
        $erreursBio = $bioC->valider($bioForm);
        if (empty($erreursBio)) {
            $bioC->enregistrer(CertificationBioController::versObjet($bioForm + ['idTerrain' => $id]));
            $badges = (new BadgeAutoController())->verifier($idUtilisateur);
            setFlash(t('Certification bio enregistrée.') . BadgeAutoController::message($badges));
            header('Location: ' . $retour . 'foncier');
            exit;
        }
    }
    if ($action === 'supprimerBio') {
        $ok = $bioC->supprimer($id);
        setFlash(t($ok ? 'Certification bio supprimée.' : 'Aucune certification à supprimer.'), $ok ? 'success' : 'danger');
        header('Location: ' . $retour . 'foncier');
        exit;
    }

    // ----- Parcelle (l'ajout et la modification sont sur parcelle.php) -----
    if ($action === 'supprimerParcelle') {
        $ok = $parcelleC->supprimer((int) ($_POST['idParcelle'] ?? 0), $idUtilisateur);
        setFlash(t($ok ? 'Parcelle supprimée. Ses récoltes restent sur le terrain.' : 'Parcelle introuvable.'), $ok ? 'success' : 'danger');
        header('Location: ' . $retour . 'parcelles');
        exit;
    }

    if ($action === 'ajouterDepense') {
        $onglet = 'depenses';
        $depense = ['categorie' => $_POST['categorie'] ?? '', 'montant' => trim($_POST['montant'] ?? ''),
                    'dateDepense' => $_POST['dateDepense'] ?? '', 'description' => trim($_POST['description'] ?? '')];
        $erreursDepense = $depenseC->valider($depense);
        if (empty($erreursDepense)) {
            $depenseC->ajouter($id, $depense);
            $badges = (new BadgeAutoController())->verifier($idUtilisateur);
            setFlash(t('Dépense enregistrée.') . BadgeAutoController::message($badges));
            header('Location: ' . $retour . 'depenses');
            exit;
        }
    }

    if ($action === 'supprimerDepense') {
        $ok = $depenseC->supprimer((int) ($_POST['idDepense'] ?? 0), $idUtilisateur);
        setFlash(t($ok ? 'Dépense supprimée.' : 'Dépense introuvable.'), $ok ? 'success' : 'danger');
        header('Location: ' . $retour . 'depenses');
        exit;
    }
}

/* ---------------- Données affichées ---------------- */
$flash     = getFlash();
$recoltes  = (new RecolteController())->listRecoltesByTerrain($id);
$depenses  = $depenseC->listParTerrain($id);
$rentab    = (new AnalyseController())->rentabilite($idUtilisateur, $id);
$rentabT   = $rentab['terrains'][0] ?? ['revenu' => 0, 'depenses' => 0, 'foncier' => 0, 'benefice' => 0];

[$lat, $lng, $positionExacte] = TerrainController::position($terrain);
$meteo   = (new MeteoController())->previsions($lat, $lng);
$alertes = $meteo['ok'] ? MeteoController::alertes($meteo['jours']) : [];

$kgTotal   = array_sum(array_map(fn($r) => (float) $r['quantiteKg'], $recoltes));
$parcelles = $parcelleC->listParTerrain($id);
$qualites  = (new ControleQualiteController())->parRecolteDeUtilisateur($idUtilisateur);
$plan      = $parcelleC->planRotation($terrain, $parcelles, $recoltes);
$statutBio = CertificationBioController::statut($certif);
$joursContrat = $contrat ? $contratC->versObjet($contrat)->joursRestants() : null;
// Carte des parcelles : le terrain en pointillés + chaque parcelle d'une couleur
$couleursParcelles = ['#008a17', '#6a4ce2', '#d1495b', '#00798c', '#edae49', '#3d5a80', '#8f2d56', '#5c946e'];
$pointsParcelles = [];
if ($parcelle = TerrainController::lirePolygone($terrain['polygone'])) {
    $pointsParcelles[] = ['id' => null, 'nom' => $terrain['nom'], 'lat' => $lat, 'lng' => $lng, 'exact' => true,
                          'polygone' => $parcelle, 'info' => $terrain['superficie'] . ' ha', 'couleur' => '#2f5d3a', 'pointille' => true];
}
foreach ($parcelles as $i => $p) {
    if ($poly = TerrainController::lirePolygone($p['polygone'])) {
        $pointsParcelles[] = ['id' => null, 'nom' => $p['nom'], 'lat' => $poly[0][0], 'lng' => $poly[0][1], 'exact' => true,
                              'polygone' => $poly, 'info' => $p['surface'] . ' ha', 'couleur' => $couleursParcelles[$i % 8]];
    }
}
$parcelle  = TerrainController::lirePolygone($terrain['polygone']);

// Satellite : chargé seulement quand on ouvre l'onglet (appel d'API plus long)
$satellite = $onglet === 'satellite' ? (new SatelliteController())->ndvi($terrain) : null;

// Dépenses par catégorie (graphique)
$parCategorie = [];
foreach ($depenses as $d) {
    $parCategorie[$d['categorie']] = ($parCategorie[$d['categorie']] ?? 0) + (float) $d['montant'];
}
arsort($parCategorie);

$titrePage  = $terrain['nom'];
$pageActive = 'mesTerrains.php';
$avecCarte  = true;
$jsSupp     = ['assets/js/carte-terrains.js', 'assets/js/graphiques.js', 'assets/js/validation-depense.js', 'assets/js/validation-foncier.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">

        <!-- En-tête du terrain -->
        <div class="tb-entete-terrain" <?php if ($terrain['photo']): ?>style="background-image:linear-gradient(90deg,rgba(31,46,38,.85),rgba(31,46,38,.35)),url('assets/img/uploads/terrains/<?= e($terrain['photo']) ?>')"<?php endif; ?>>
          <div>
            <p class="sur-titre"><i class="bi bi-geo-alt"></i> <?= e($terrain['localisation']) ?> · <?= te($terrain['typeSol']) ?></p>
            <h1><?= e($terrain['nom']) ?></h1>
            <p class="mb-2 d-flex flex-wrap gap-2">
              <?php if ($contrat): ?><span class="tb-etiquette"><i class="bi bi-file-earmark-text"></i> <?= te(ContratFoncierController::TYPES[$contrat['type']]) ?></span><?php endif; ?>
              <?php if ($statutBio === 'CERTIFIE'): ?><span class="tb-etiquette tb-etiquette-bio"><i class="bi bi-flower1"></i> <?= te('Certifié bio') ?></span>
              <?php elseif ($statutBio === 'CONVERSION'): ?><span class="tb-etiquette"><i class="bi bi-hourglass-split"></i> <?= te('En conversion bio') ?></span><?php endif; ?>
              <?php if ($parcelles): ?><span class="tb-etiquette"><i class="bi bi-grid-3x3"></i> <?= te('{n} parcelle(s)', ['n' => count($parcelles)]) ?></span><?php endif; ?>
            </p>
            <p class="mb-0"><?= e($terrain['superficie']) ?> ha
              <?php if ($positionExacte): ?> · <i class="bi bi-crosshair"></i> <?= e(number_format($lat, 4)) ?>, <?= e(number_format($lng, 4)) ?><?php endif; ?></p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="ajouterRecolte.php?idTerrain=<?= e($id) ?>" class="btn-hero"><i class="bi bi-plus-lg"></i> <?= te('Ajouter une récolte') ?></a>
            <a href="recommandation.php?terrain=<?= e($id) ?>" class="btn-hero btn-hero-clair"><i class="bi bi-stars"></i> <?= te('Que planter ?') ?></a>
            <a href="ficheTerrain.php?id=<?= e($id) ?>" class="btn-hero btn-hero-clair" target="_blank"><i class="bi bi-file-earmark-pdf"></i> <?= te('Fiche PDF') ?></a>
            <a href="modifierTerrain.php?id=<?= e($id) ?>" class="btn-hero btn-hero-clair"><i class="bi bi-pencil"></i></a>
          </div>
        </div>

        <?php if ($flash): ?>
          <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($joursContrat !== null && $joursContrat <= ContratFoncierController::JOURS_ALERTE): ?>
          <div class="alert alert-<?= $joursContrat < 0 ? 'danger' : 'warning' ?> d-flex gap-2 align-items-center"><i class="bi bi-calendar-x fs-5"></i>
            <?= $joursContrat < 0 ? te('Le contrat foncier de ce terrain est terminé depuis le {d}.', ['d' => date('d/m/Y', strtotime($contrat['dateFin']))])
                                  : te('Le contrat foncier se termine dans {n} jour(s), le {d} : pensez à le renouveler.', ['n' => $joursContrat, 'd' => date('d/m/Y', strtotime($contrat['dateFin']))]) ?></div>
        <?php endif; ?>

        <?php foreach (array_slice($alertes, 0, 2) as [$type, $icone, $texte, $valeurs]): ?>
          <div class="alert alert-<?= e($type) ?> d-flex gap-2 align-items-center"><i class="bi <?= e($icone) ?> fs-5"></i> <?= te($texte, $valeurs) ?></div>
        <?php endforeach; ?>

        <!-- Onglets -->
        <ul class="nav tb-onglets" role="tablist">
          <?php foreach (['apercu' => ['bi-grid', 'Aperçu'], 'meteo' => ['bi-cloud-sun', 'Météo et eau'],
                          'recoltes' => ['bi-basket', 'Récoltes'], 'parcelles' => ['bi-grid-3x3', 'Parcelles'],
                          'depenses' => ['bi-cash-coin', 'Rentabilité'], 'foncier' => ['bi-file-earmark-text', 'Contrat et bio'], 'satellite' => ['bi-globe-europe-africa', 'Satellite']] as $cle => [$icone, $libelle]): ?>
            <li class="nav-item">
              <?php if ($cle === 'satellite'): ?>
                <a class="nav-link <?= $onglet === $cle ? 'active' : '' ?>" href="<?= e($retour . 'satellite') ?>"><i class="bi <?= $icone ?>"></i> <?= te($libelle) ?></a>
              <?php else: ?>
                <button class="nav-link <?= $onglet === $cle ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#onglet-<?= $cle ?>" type="button" role="tab">
                  <i class="bi <?= $icone ?>"></i> <?= te($libelle) ?>
                  <?php if ($cle === 'recoltes'): ?><span class="tb-compteur"><?= count($recoltes) ?></span><?php endif; ?>
                </button>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="tab-content tb-panneau tb-panneau-onglets">

          <!-- ===================== APERÇU ===================== -->
          <div class="tab-pane fade <?= $onglet === 'apercu' ? 'show active' : '' ?>" id="onglet-apercu" role="tabpanel">
            <div class="row g-3 mb-4">
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-basket"></i><strong><?= count($recoltes) ?></strong><span><?= te('Récoltes') ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-box-seam"></i><strong><?= e(number_format($kgTotal / 1000, 2, ',', ' ')) ?> t</strong><span><?= te('Production totale') ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-graph-up"></i><strong><?= e(number_format($kgTotal / 1000 / max(0.01, (float) $terrain['superficie']), 2, ',', ' ')) ?> t/ha</strong><span><?= te('Rendement') ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi <?= $rentabT['benefice'] < 0 ? 'negatif' : '' ?>"><i class="bi bi-cash-stack"></i><strong><?= e(number_format($rentabT['benefice'], 0, ',', ' ')) ?> DT</strong><span><?= te('Bénéfice estimé') ?></span></div></div>
            </div>
            <div class="row g-4">
              <div class="col-lg-7">
                <div id="carteTerrains" class="tb-carte" data-lien-details=""
                     data-points='<?= e(json_encode([['id' => null, 'nom' => $terrain['nom'], 'lat' => $lat, 'lng' => $lng, 'exact' => $positionExacte, 'polygone' => $parcelle, 'info' => $terrain['superficie'] . ' ha']])) ?>'
                     data-texte-approx="<?= te('Position approximative (centre de la wilaya)') ?>"></div>
                <?php if (!$positionExacte): ?>
                  <p class="small text-muted mt-2"><i class="bi bi-info-circle"></i> <?= te('Position approximative (centre de la wilaya)') ?> — <a href="modifierTerrain.php?id=<?= e($id) ?>"><?= te('placer le terrain sur la carte') ?></a></p>
                <?php endif; ?>
              </div>
              <div class="col-lg-5">
                <h3 class="tb-sous-titre"><?= te('Dernières récoltes') ?></h3>
                <?php if (empty($recoltes)): ?>
                  <p class="text-muted"><?= te('Aucune récolte sur ce terrain.') ?> <a href="recommandation.php?terrain=<?= e($id) ?>"><?= te('Voir les cultures conseillées') ?></a></p>
                <?php else: ?>
                  <?php foreach (array_slice($recoltes, 0, 4) as $r): ?>
                    <div class="tb-ligne-culture">
                      <i class="bi bi-basket"></i>
                      <div class="flex-grow-1"><strong><?= e($r['culture']) ?></strong>
                        <small class="d-block text-muted"><?= e(date('d/m/Y', strtotime($r['dateRecolte']))) ?> · <?= e(RecolteController::nombreCourt((float) $r['quantite'])) ?> <?= te($r['unite']) ?></small></div>
                      <a href="consommationEau.php?terrain=<?= e($id) ?>&amp;culture=<?= e(rawurlencode($r['culture'])) ?>" class="tb-lien" title="<?= te("Consommation d'eau") ?>"><i class="bi bi-droplet"></i></a>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- ===================== MÉTÉO ET EAU ===================== -->
          <div class="tab-pane fade <?= $onglet === 'meteo' ? 'show active' : '' ?>" id="onglet-meteo" role="tabpanel">
            <?php if (!$meteo['ok']): ?>
              <div class="alert alert-warning"><i class="bi bi-wifi-off"></i> <?= te('Météo indisponible pour le moment.') ?> <small>(<?= e($meteo['erreur']) ?>)</small></div>
            <?php else: ?>
              <div class="tb-meteo">
                <?php foreach ($meteo['jours'] as $j): ?>
                  <div class="tb-jour <?= $j['date'] === date('Y-m-d') ? 'aujourdhui' : '' ?>">
                    <span class="tb-jour-nom"><?= te(MeteoController::libelleJour($j['date'])) ?> <small><?= e(date('d/m', strtotime($j['date']))) ?></small></span>
                    <i class="bi <?= e($j['icone']) ?>" title="<?= te($j['libelle']) ?>"></i>
                    <strong><?= e(round($j['tmax'])) ?>° <small><?= e(round($j['tmin'])) ?>°</small></strong>
                    <span class="tb-jour-detail"><i class="bi bi-droplet"></i> <?= e($j['pluie']) ?> mm</span>
                    <span class="tb-jour-detail"><i class="bi bi-wind"></i> <?= e(round($j['vent'])) ?> km/h</span>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if (empty($alertes)): ?>
                <div class="alert alert-success mt-3"><i class="bi bi-check-circle"></i> <?= te('Aucune alerte météo pour les 7 prochains jours.') ?></div>
              <?php else: ?>
                <?php foreach ($alertes as [$type, $icone, $texte, $valeurs]): ?>
                  <div class="alert alert-<?= e($type) ?> mt-2 mb-0 d-flex gap-2"><i class="bi <?= e($icone) ?>"></i> <?= te($texte, $valeurs) ?></div>
                <?php endforeach; ?>
              <?php endif; ?>
              <div class="text-center mt-4">
                <a href="consommationEau.php?terrain=<?= e($id) ?>" class="btn-hero"><i class="bi bi-droplet-half"></i> <?= te("Calculer le besoin en eau de ce terrain") ?></a>
              </div>
              <p class="small text-muted mt-3 mb-0"><?= te('Source : Open-Meteo, mise à jour toutes les heures.') ?></p>
            <?php endif; ?>
          </div>

          <!-- ===================== RÉCOLTES ===================== -->
          <div class="tab-pane fade <?= $onglet === 'recoltes' ? 'show active' : '' ?>" id="onglet-recoltes" role="tabpanel">
            <?php if (empty($recoltes)): ?>
              <p class="text-muted"><?= te('Aucune récolte sur ce terrain.') ?> <a href="ajouterRecolte.php?idTerrain=<?= e($id) ?>"><?= te('Ajouter une récolte') ?></a></p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle tb-table">
                  <thead><tr><th><?= te('Culture') ?></th><th><?= te('Parcelle') ?></th><th><?= te('Plantation') ?></th><th><?= te('Récolte') ?></th><th><?= te('Quantité') ?></th><th></th></tr></thead>
                  <tbody>
                    <?php foreach ($recoltes as $r): ?>
                      <tr>
                        <td><strong><?= e($r['culture']) ?></strong>
                          <div><?= badgesRecolte($qualites[(int) $r['id']] ?? null, CertificationBioController::statutRecolte($certif, $r['dateRecolte'])) ?></div></td>
                        <td><?= $r['parcelleNom'] ? e($r['parcelleNom']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= e(date('d/m/Y', strtotime($r['datePlantation']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($r['dateRecolte']))) ?></td>
                        <td><?= e(RecolteController::nombreCourt((float) $r['quantite'])) ?> <?= te($r['unite']) ?></td>
                        <td class="text-end text-nowrap">
                          <?php if ($r['codeTracabilite']): ?>
                            <a href="etiquette.php?id=<?= e($r['id']) ?>" class="btn-icone" title="<?= te('Étiquette QR') ?>" target="_blank"><i class="bi bi-qr-code"></i></a>
                          <?php endif; ?>
                          <a href="qualiteRecolte.php?id=<?= e($r['id']) ?>" class="btn-icone" title="<?= te('Contrôle qualité') ?>"><i class="bi bi-award"></i></a>
                          <a href="modifierRecolte.php?id=<?= e($r['id']) ?>" class="btn-icone" title="<?= te('Modifier') ?>"><i class="bi bi-pencil"></i></a>
                          <form action="supprimerRecolte.php" method="POST" class="d-inline" data-confirm="<?= te('Supprimer cette récolte ?') ?>">
                            <?= champCsrf() ?>
                            <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                            <input type="hidden" name="retour" value="detailsTerrain.php?id=<?= e($id) ?>">
                            <button type="submit" class="btn-icone danger" title="<?= te('Supprimer') ?>"><i class="bi bi-trash"></i></button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- ===================== PARCELLES ET ROTATION ===================== -->
          <div class="tab-pane fade <?= $onglet === 'parcelles' ? 'show active' : '' ?>" id="onglet-parcelles" role="tabpanel">
            <?php $surfaceParcelles = array_sum(array_map(fn($p) => (float) $p['surface'], $parcelles)); ?>
            <div class="row g-4 mb-4">
              <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h3 class="tb-sous-titre mb-0"><?= te('Parcelles du terrain') ?></h3>
                  <a href="parcelle.php?terrain=<?= e($id) ?>" class="btn-hero btn-sm"><i class="bi bi-plus-lg"></i> <?= te('Ajouter une parcelle') ?></a>
                </div>
                <?php $pourcent = min(100, round($surfaceParcelles / max(0.01, (float) $terrain['superficie']) * 100)); ?>
                <div class="tb-jauge my-3" role="progressbar" aria-valuenow="<?= e($pourcent) ?>" aria-valuemin="0" aria-valuemax="100">
                  <div style="width: <?= e($pourcent) ?>%"></div>
                </div>
                <p class="small text-muted"><?= te('{u} ha découpés sur {t} ha', ['u' => CultureController::nombre($surfaceParcelles, 2), 't' => CultureController::nombre((float) $terrain['superficie'], 2)]) ?></p>
                <?php if (empty($parcelles)): ?>
                  <p class="text-muted"><?= te('Découpez le terrain en parcelles pour suivre la rotation des cultures sur chacune.') ?></p>
                <?php else: ?>
                  <table class="table table-sm align-middle tb-table">
                    <thead><tr><th></th><th><?= te('Parcelle') ?></th><th class="text-end"><?= te('Surface') ?></th><th class="text-end"><?= te('Récoltes') ?></th><th></th></tr></thead>
                    <tbody>
                      <?php foreach ($parcelles as $i => $p): ?>
                        <tr>
                          <td><span class="tb-pastille" style="background: <?= e($couleursParcelles[$i % 8]) ?>"></span></td>
                          <td><strong><?= e($p['nom']) ?></strong><?= $p['polygone'] ? '' : ' <small class="text-muted">(' . te('pas de contour') . ')</small>' ?></td>
                          <td class="text-end"><?= e(CultureController::nombre((float) $p['surface'], 2)) ?> ha</td>
                          <td class="text-end"><?= e($p['nbRecoltes']) ?></td>
                          <td class="text-end text-nowrap">
                            <a href="ajouterRecolte.php?idTerrain=<?= e($id) ?>&amp;idParcelle=<?= e($p['id']) ?>" class="btn-icone" title="<?= te('Ajouter une récolte') ?>"><i class="bi bi-basket"></i></a>
                            <a href="parcelle.php?terrain=<?= e($id) ?>&amp;id=<?= e($p['id']) ?>" class="btn-icone" title="<?= te('Modifier') ?>"><i class="bi bi-pencil"></i></a>
                            <form action="<?= e($retour . 'parcelles') ?>" method="POST" class="d-inline" data-confirm="<?= te('Supprimer cette parcelle ? Ses récoltes restent sur le terrain.') ?>">
                              <?= champCsrf() ?>
                              <input type="hidden" name="action" value="supprimerParcelle">
                              <input type="hidden" name="idParcelle" value="<?= e($p['id']) ?>">
                              <button type="submit" class="btn-icone danger" title="<?= te('Supprimer') ?>"><i class="bi bi-trash"></i></button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </div>
              <div class="col-lg-6">
                <?php if (count($pointsParcelles) > 0): ?>
                  <div class="tb-carte tb-carte-petite tb-carte-multi" data-lien-details=""
                       data-points='<?= e(json_encode($pointsParcelles)) ?>'></div>
                <?php else: ?>
                  <div class="tb-carte tb-carte-petite tb-carte-vide"><p class="text-muted"><i class="bi bi-pentagon"></i> <?= te('Dessinez le contour des parcelles pour les voir sur la carte.') ?></p></div>
                <?php endif; ?>
              </div>
            </div>

            <h3 class="tb-sous-titre"><i class="bi bi-arrow-repeat"></i> <?= te('Plan de rotation sur 4 ans') ?></h3>
            <p class="small text-muted"><?= te('Cycle conseillé : céréale → légumineuse (remet de l’azote dans le sol) → légume → jachère ou engrais vert. Une culture de la même famille deux ans de suite fatigue le sol.') ?></p>
            <div class="table-responsive">
              <table class="table tb-rotation align-middle">
                <thead>
                  <tr>
                    <th><?= te('Parcelle') ?></th>
                    <?php foreach ($plan['annees'] as $a): ?>
                      <th class="text-center <?= $a > $plan['courante'] ? 'tb-rotation-plan' : '' ?>"><?= e($a) ?>
                        <small class="d-block fw-normal"><?= te($a > $plan['courante'] ? 'plan' : 'historique') ?></small></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($plan['lignes'] as $ligne): ?>
                    <tr>
                      <th scope="row"><?= te($ligne['nom']) ?>
                        <small class="d-block fw-normal text-muted"><?= te($ligne['conseil'][0], array_map('t', $ligne['conseil'][1])) ?></small></th>
                      <?php foreach ($ligne['cases'] as $c): ?>
                        <?php if ($c['famille'] === null && $c['type'] === 'vide'): ?>
                          <td class="text-center text-muted">—</td>
                        <?php else: ?>
                          <?php [$libelleFamille, $iconeFamille] = ParcelleController::FAMILLES[$c['famille']] ?? ['Autre', 'bi-question-circle']; ?>
                          <td class="tb-famille tb-famille-<?= strtolower(e($c['famille'] ?? 'autre')) ?> <?= $c['type'] === 'plan' ? 'tb-case-plan' : '' ?> <?= $c['alerte'] ? 'tb-case-alerte' : '' ?>">
                            <i class="bi <?= e($iconeFamille) ?>"></i> <strong><?= te($libelleFamille) ?></strong>
                            <small class="d-block"><?= $c['culture'] ? e(t($c['culture'])) : ($c['famille'] === 'JACHERE' ? te('repos ou engrais vert') : '') ?></small>
                            <?php if ($c['alerte']): ?><small class="d-block fw-bold"><i class="bi bi-exclamation-triangle"></i> <?= te('même famille 2 ans de suite') ?></small><?php endif; ?>
                          </td>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="small text-muted mb-0"><i class="bi bi-info-circle"></i> <?= te('La culture proposée est choisie selon le sol ({sol}) et la wilaya ({w}) du terrain.', ['sol' => t($terrain['typeSol']), 'w' => $terrain['localisation']]) ?></p>
          </div>

          <!-- ===================== DÉPENSES ET RENTABILITÉ ===================== -->
          <div class="tab-pane fade <?= $onglet === 'depenses' ? 'show active' : '' ?>" id="onglet-depenses" role="tabpanel">
            <div class="row g-3 mb-4">
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-arrow-down-circle"></i><strong><?= e(number_format($rentabT['revenu'], 0, ',', ' ')) ?> DT</strong><span><?= te("Chiffre d'affaires estimé") ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-arrow-up-circle"></i><strong><?= e(number_format($rentabT['depenses'], 0, ',', ' ')) ?> DT</strong><span><?= te('Dépenses') ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-house-door"></i><strong><?= e(number_format($rentabT['foncier'], 0, ',', ' ')) ?> DT</strong><span><?= te('Loyer ou part du propriétaire') ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi <?= $rentabT['benefice'] < 0 ? 'negatif' : 'positif' ?>"><i class="bi bi-cash-stack"></i><strong><?= e(number_format($rentabT['benefice'], 0, ',', ' ')) ?> DT</strong><span><?= te('Bénéfice') ?></span></div></div>
            </div>
            <p class="small text-muted"><i class="bi bi-info-circle"></i> <?= te("Chiffre d'affaires = quantités vendables × prix du marché × classe qualité (Extra +20 %, II −20 %) × prime bio (+25 %). Bénéfice = chiffre d'affaires − dépenses − loyer ou part du propriétaire.") ?></p>
            <div class="row g-4">
              <div class="col-lg-5">
                <h3 class="tb-sous-titre"><?= te('Nouvelle dépense') ?></h3>
                <form action="<?= e($retour . 'depenses') ?>" method="POST" id="formDepense" novalidate>
                  <?= champCsrf() ?>
                  <input type="hidden" name="action" value="ajouterDepense">
                  <div class="row">
                    <div class="col-6 mb-3">
                      <label for="categorie" class="form-label"><?= te('Catégorie') ?></label>
                      <select class="form-select <?= isset($erreursDepense['categorie']) ? 'is-invalid' : '' ?>" id="categorie" name="categorie">
                        <?php foreach (DepenseController::CATEGORIES as $c): ?>
                          <option value="<?= e($c) ?>" <?= $depense['categorie'] === $c ? 'selected' : '' ?>><?= te($c) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div class="erreur" id="erreur-categorie"><?= te($erreursDepense['categorie'] ?? '') ?></div>
                    </div>
                    <div class="col-6 mb-3">
                      <label for="montant" class="form-label"><?= te('Montant (DT)') ?></label>
                      <input type="text" class="form-control <?= isset($erreursDepense['montant']) ? 'is-invalid' : '' ?>" id="montant" name="montant" value="<?= e($depense['montant']) ?>" placeholder="150">
                      <div class="erreur" id="erreur-montant"><?= te($erreursDepense['montant'] ?? '') ?></div>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label for="dateDepense" class="form-label"><?= te('Date') ?></label>
                    <input type="date" class="form-control <?= isset($erreursDepense['dateDepense']) ? 'is-invalid' : '' ?>" id="dateDepense" name="dateDepense" value="<?= e($depense['dateDepense']) ?>">
                    <div class="erreur" id="erreur-dateDepense"><?= te($erreursDepense['dateDepense'] ?? '') ?></div>
                  </div>
                  <div class="mb-3">
                    <label for="description" class="form-label"><?= te('Description (facultatif)') ?></label>
                    <input type="text" class="form-control <?= isset($erreursDepense['description']) ? 'is-invalid' : '' ?>" id="description" name="description" value="<?= e($depense['description']) ?>">
                    <div class="erreur" id="erreur-description"><?= te($erreursDepense['description'] ?? '') ?></div>
                  </div>
                  <button type="submit" class="btn-hero border-0"><i class="bi bi-plus-lg"></i> <?= te('Enregistrer la dépense') ?></button>
                </form>
              </div>
              <div class="col-lg-7">
                <h3 class="tb-sous-titre"><?= te('Dépenses par catégorie') ?></h3>
                <div class="tb-graphique" data-type="barresH" data-unite="DT"
                     data-libelles='<?= e(json_encode(array_map('t', array_keys($parCategorie)), JSON_UNESCAPED_UNICODE)) ?>'
                     data-series='<?= e(json_encode([['nom' => t('Dépenses'), 'valeurs' => array_values($parCategorie), 'couleur' => '#6a4ce2']], JSON_UNESCAPED_UNICODE)) ?>'></div>
                <?php if (!empty($depenses)): ?>
                  <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle tb-table">
                      <thead><tr><th><?= te('Date') ?></th><th><?= te('Catégorie') ?></th><th><?= te('Description') ?></th><th class="text-end"><?= te('Montant') ?></th><th></th></tr></thead>
                      <tbody>
                        <?php foreach ($depenses as $d): ?>
                          <tr>
                            <td><?= e(date('d/m/Y', strtotime($d['dateDepense']))) ?></td>
                            <td><?= te($d['categorie']) ?></td>
                            <td><?= e($d['description'] ?? '') ?></td>
                            <td class="text-end"><?= e(number_format((float) $d['montant'], 3, ',', ' ')) ?> DT</td>
                            <td class="text-end">
                              <form action="<?= e($retour . 'depenses') ?>" method="POST" data-confirm="<?= te('Supprimer cette dépense ?') ?>">
                                <?= champCsrf() ?>
                                <input type="hidden" name="action" value="supprimerDepense">
                                <input type="hidden" name="idDepense" value="<?= e($d['id']) ?>">
                                <button type="submit" class="btn-icone danger" title="<?= te('Supprimer') ?>"><i class="bi bi-x-lg"></i></button>
                              </form>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- ===================== CONTRAT FONCIER ET BIO ===================== -->
          <div class="tab-pane fade <?= $onglet === 'foncier' ? 'show active' : '' ?>" id="onglet-foncier" role="tabpanel">
            <div class="row g-4">
              <!-- Contrat foncier -->
              <div class="col-lg-6">
                <h3 class="tb-sous-titre"><i class="bi bi-file-earmark-text"></i> <?= te('Contrat foncier') ?></h3>
                <?php if ($contrat): ?>
                  <div class="tb-resume mb-3">
                    <strong><?= te(ContratFoncierController::TYPES[$contrat['type']]) ?></strong>
                    <?php if ($contrat['type'] === 'LOCATION'): ?>
                      · <?= te('{m} DT par an à {p}', ['m' => number_format((float) $contrat['loyerAnnuel'], 0, ',', ' '), 'p' => $contrat['proprietaire']]) ?>
                    <?php elseif ($contrat['type'] !== 'PROPRIETE'): ?>
                      · <?= te('{p} % de la récolte pour {n}', ['p' => CultureController::nombre((float) $contrat['partProprietaire'], 0), 'n' => $contrat['proprietaire']]) ?>
                    <?php endif; ?>
                    <?php if ($contrat['dateFin']): ?>
                      <small class="d-block text-muted"><?= te('Du {d} au {f}', ['d' => date('d/m/Y', strtotime($contrat['dateDebut'])), 'f' => date('d/m/Y', strtotime($contrat['dateFin']))]) ?>
                        <?= $joursContrat >= 0 ? '· ' . te('{n} jour(s) restant(s)', ['n' => $joursContrat]) : '· ' . te('terminé') ?></small>
                    <?php endif; ?>
                    <small class="d-block"><?= te('Charge foncière déduite de la rentabilité : {m} DT', ['m' => number_format($rentabT['foncier'], 0, ',', ' ')]) ?></small>
                  </div>
                <?php endif; ?>
                <form action="<?= e($retour . 'foncier') ?>" method="POST" id="formContrat" novalidate>
                  <?= champCsrf() ?>
                  <input type="hidden" name="action" value="enregistrerContrat">
                  <div class="row">
                    <div class="col-sm-6 mb-3">
                      <label for="typeContrat" class="form-label"><?= te('Régime') ?></label>
                      <select class="form-select <?= isset($erreursContrat['type']) ? 'is-invalid' : '' ?>" id="typeContrat" name="type"
                              data-parts='<?= e(json_encode(ContratFoncierController::PARTS_CONSEILLEES)) ?>'>
                        <?php foreach (ContratFoncierController::TYPES as $code => $libelle): ?>
                          <option value="<?= e($code) ?>" <?= $contratForm['type'] === $code ? 'selected' : '' ?>><?= te($libelle) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div class="erreur" id="erreur-typeContrat"><?= te($erreursContrat['type'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3">
                      <label for="dateDebut" class="form-label"><?= te('Depuis le') ?></label>
                      <input type="date" class="form-control <?= isset($erreursContrat['dateDebut']) ? 'is-invalid' : '' ?>" id="dateDebut" name="dateDebut" value="<?= e($contratForm['dateDebut']) ?>">
                      <div class="erreur" id="erreur-dateDebut"><?= te($erreursContrat['dateDebut'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3 champ-contrat" data-types="LOCATION KHAMMES METAYAGE">
                      <label for="proprietaire" class="form-label"><?= te('Propriétaire') ?></label>
                      <input type="text" class="form-control <?= isset($erreursContrat['proprietaire']) ? 'is-invalid' : '' ?>" id="proprietaire" name="proprietaire" value="<?= e($contratForm['proprietaire'] ?? '') ?>" placeholder="<?= te('Ex : Famille Trabelsi') ?>">
                      <div class="erreur" id="erreur-proprietaire"><?= te($erreursContrat['proprietaire'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3 champ-contrat" data-types="LOCATION KHAMMES METAYAGE">
                      <label for="dateFin" class="form-label"><?= te('Fin du contrat') ?></label>
                      <input type="date" class="form-control <?= isset($erreursContrat['dateFin']) ? 'is-invalid' : '' ?>" id="dateFin" name="dateFin" value="<?= e($contratForm['dateFin'] ?? '') ?>">
                      <div class="erreur" id="erreur-dateFin"><?= te($erreursContrat['dateFin'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3 champ-contrat" data-types="LOCATION">
                      <label for="loyerAnnuel" class="form-label"><?= te('Loyer annuel (DT)') ?></label>
                      <input type="text" class="form-control <?= isset($erreursContrat['loyerAnnuel']) ? 'is-invalid' : '' ?>" id="loyerAnnuel" name="loyerAnnuel" value="<?= e($contratForm['loyerAnnuel'] ?? '') ?>" placeholder="1200">
                      <div class="erreur" id="erreur-loyerAnnuel"><?= te($erreursContrat['loyerAnnuel'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3 champ-contrat" data-types="KHAMMES METAYAGE">
                      <label for="partProprietaire" class="form-label"><?= te('Part du propriétaire (%)') ?></label>
                      <input type="text" class="form-control <?= isset($erreursContrat['partProprietaire']) ? 'is-invalid' : '' ?>" id="partProprietaire" name="partProprietaire" value="<?= e($contratForm['partProprietaire'] ?? '') ?>">
                      <small class="text-muted"><?= te('Khammès : le propriétaire garde souvent 4/5 (80 %). Métayage : souvent 50 %.') ?></small>
                      <div class="erreur" id="erreur-partProprietaire"><?= te($erreursContrat['partProprietaire'] ?? '') ?></div>
                    </div>
                  </div>
                  <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn-hero border-0"><i class="bi bi-check-lg"></i> <?= te('Enregistrer le contrat') ?></button>
                  </div>
                </form>
                <?php if ($contrat): ?>
                  <form action="<?= e($retour . 'foncier') ?>" method="POST" class="mt-2" data-confirm="<?= te('Supprimer le contrat foncier ?') ?>">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="supprimerContrat">
                    <button type="submit" class="btn btn-link text-danger p-0"><i class="bi bi-trash"></i> <?= te('Supprimer le contrat') ?></button>
                  </form>
                <?php endif; ?>
              </div>

              <!-- Certification bio -->
              <div class="col-lg-6">
                <h3 class="tb-sous-titre"><i class="bi bi-flower1"></i> <?= te('Certification bio') ?></h3>
                <?php if ($certif): ?>
                  <?php $objBio = CertificationBioController::versObjet($certif);
                        $debutC = strtotime($certif['dateDebutConversion']); $finC = strtotime($objBio->getDateCertification());
                        $avancement = (int) max(0, min(100, round((time() - $debutC) / max(1, $finC - $debutC) * 100))); ?>
                  <div class="tb-resume mb-3">
                    <strong class="tb-statut-bio tb-statut-<?= strtolower(e($statutBio)) ?>"><?= te(CertificationBioController::STATUTS[$statutBio]) ?></strong>
                    · <?= e($certif['organisme']) ?><?= $certif['numeroCertificat'] ? ' · ' . e($certif['numeroCertificat']) : '' ?>
                    <div class="tb-jauge my-2" role="progressbar" aria-valuenow="<?= e($avancement) ?>" aria-valuemin="0" aria-valuemax="100"
                         aria-label="<?= te('Avancement de la conversion') ?>"><div style="width: <?= e($avancement) ?>%"></div></div>
                    <small class="d-block text-muted"><?= te('Conversion du {d} au {f} ({p} %)', ['d' => date('d/m/Y', $debutC), 'f' => date('d/m/Y', $finC), 'p' => $avancement]) ?>
                      <?= $certif['dateExpiration'] ? '· ' . te('certificat valable jusqu’au {e}', ['e' => date('d/m/Y', strtotime($certif['dateExpiration']))]) : '' ?></small>
                    <small class="d-block"><?= te('Seules les récoltes faites après la conversion, certificat valide, sont vendues « bio » (+25 % sur le prix).') ?></small>
                  </div>
                <?php else: ?>
                  <p class="text-muted"><?= te('Pas de certification bio. La conversion dure 24 mois (36 pour les arbres) avant de pouvoir vendre en bio.') ?></p>
                <?php endif; ?>
                <form action="<?= e($retour . 'foncier') ?>" method="POST" id="formBio" novalidate>
                  <?= champCsrf() ?>
                  <input type="hidden" name="action" value="enregistrerBio">
                  <div class="row">
                    <div class="col-sm-6 mb-3">
                      <label for="organisme" class="form-label"><?= te('Organisme certificateur') ?></label>
                      <input type="text" class="form-control <?= isset($erreursBio['organisme']) ? 'is-invalid' : '' ?>" id="organisme" name="organisme" list="listeOrganismes" value="<?= e($bioForm['organisme']) ?>">
                      <datalist id="listeOrganismes">
                        <?php foreach (CertificationBioController::ORGANISMES as $o): ?><option value="<?= e($o) ?>"></option><?php endforeach; ?>
                      </datalist>
                      <div class="erreur" id="erreur-organisme"><?= te($erreursBio['organisme'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3">
                      <label for="numeroCertificat" class="form-label"><?= te('N° de certificat (facultatif)') ?></label>
                      <input type="text" class="form-control <?= isset($erreursBio['numeroCertificat']) ? 'is-invalid' : '' ?>" id="numeroCertificat" name="numeroCertificat" value="<?= e($bioForm['numeroCertificat'] ?? '') ?>" placeholder="TN-BIO-0457">
                      <div class="erreur" id="erreur-numeroCertificat"><?= te($erreursBio['numeroCertificat'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3">
                      <label for="dateDebutConversion" class="form-label"><?= te('Début de la conversion') ?></label>
                      <input type="date" class="form-control <?= isset($erreursBio['dateDebutConversion']) ? 'is-invalid' : '' ?>" id="dateDebutConversion" name="dateDebutConversion" value="<?= e($bioForm['dateDebutConversion']) ?>">
                      <div class="erreur" id="erreur-dateDebutConversion"><?= te($erreursBio['dateDebutConversion'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3">
                      <label for="dureeConversionMois" class="form-label"><?= te('Durée de conversion') ?></label>
                      <select class="form-select <?= isset($erreursBio['dureeConversionMois']) ? 'is-invalid' : '' ?>" id="dureeConversionMois" name="dureeConversionMois">
                        <?php foreach (CertificationBioController::DUREES as $m => $libelle): ?>
                          <option value="<?= e($m) ?>" <?= (int) $bioForm['dureeConversionMois'] === $m ? 'selected' : '' ?>><?= te($libelle) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div class="erreur" id="erreur-dureeConversionMois"><?= te($erreursBio['dureeConversionMois'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6 mb-3">
                      <label for="dateExpiration" class="form-label"><?= te('Certificat valable jusqu’au (facultatif)') ?></label>
                      <input type="date" class="form-control <?= isset($erreursBio['dateExpiration']) ? 'is-invalid' : '' ?>" id="dateExpiration" name="dateExpiration" value="<?= e($bioForm['dateExpiration'] ?? '') ?>">
                      <div class="erreur" id="erreur-dateExpiration"><?= te($erreursBio['dateExpiration'] ?? '') ?></div>
                    </div>
                  </div>
                  <button type="submit" class="btn-hero border-0"><i class="bi bi-check-lg"></i> <?= te('Enregistrer la certification') ?></button>
                </form>
                <?php if ($certif): ?>
                  <form action="<?= e($retour . 'foncier') ?>" method="POST" class="mt-2" data-confirm="<?= te('Supprimer la certification bio ?') ?>">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="supprimerBio">
                    <button type="submit" class="btn btn-link text-danger p-0"><i class="bi bi-trash"></i> <?= te('Supprimer la certification') ?></button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- ===================== SATELLITE (NDVI) ===================== -->
          <div class="tab-pane fade <?= $onglet === 'satellite' ? 'show active' : '' ?>" id="onglet-satellite" role="tabpanel">
            <p><?= te('Le NDVI mesure la vigueur de la végétation à partir des images satellites (de -1 à 1) : plus il est élevé, plus les plantes sont vertes et en bonne santé.') ?></p>
            <?php if ($satellite === null): ?>
              <a href="<?= e($retour . 'satellite') ?>" class="btn-hero"><?= te("Charger l'analyse satellite") ?></a>
            <?php elseif (!$satellite['ok']): ?>
              <div class="alert alert-info"><i class="bi bi-info-circle"></i> <?= te($satellite['erreur']) ?></div>
            <?php else: ?>
              <?php $derniere = end($satellite['historique']) ?: null; ?>
              <div class="row g-4">
                <div class="col-lg-5">
                  <?php if ($derniere): ?>
                    <?php [$couleurNdvi, $texteNdvi] = SatelliteController::interpreter($derniere['ndvi']); ?>
                    <div class="tb-kpi mb-3"><i class="bi bi-globe-europe-africa"></i><strong><?= e(number_format($derniere['ndvi'], 2, ',', ' ')) ?></strong>
                      <span><?= te('NDVI moyen au {date}', ['date' => date('d/m/Y', strtotime($derniere['date']))]) ?></span></div>
                    <div class="alert alert-<?= e($couleurNdvi) ?>"><?= te($texteNdvi) ?></div>
                  <?php endif; ?>
                  <?php if ($satellite['image']): ?>
                    <img src="ndviImage.php?id=<?= e($id) ?>" alt="NDVI" class="tb-ndvi-image">
                    <p class="small text-muted"><?= te('Image {source} du {date}', ['source' => $satellite['image']['source'], 'date' => date('d/m/Y', strtotime($satellite['image']['date']))]) ?>
                      — <?= te('vert = végétation dense, rouge = sol nu ou plantes en souffrance') ?></p>
                  <?php endif; ?>
                </div>
                <div class="col-lg-7">
                  <h3 class="tb-sous-titre"><?= te('Évolution du NDVI (6 mois)') ?></h3>
                  <div class="tb-graphique" data-type="ligne" data-unite=""
                       data-libelles='<?= e(json_encode(array_map(fn($h) => date('d/m', strtotime($h['date'])), $satellite['historique']))) ?>'
                       data-series='<?= e(json_encode([['nom' => 'NDVI', 'valeurs' => array_column($satellite['historique'], 'ndvi'), 'couleur' => '#008a17']])) ?>'></div>
                </div>
              </div>
              <p class="small text-muted mt-3 mb-0"><?= te('Source : Agromonitoring (Sentinel-2, Landsat 8). Images très nuageuses ignorées.') ?></p>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
