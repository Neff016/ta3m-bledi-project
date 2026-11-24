<?php
$startIndex = max(0, count($timeline) - 1);
foreach ($timeline as $i => $p) {
    if ($p['annee'] === $annee) {
        $startIndex = $i;
    }
}
$img = regionImage($ville->image);
$js = [
    'timeline'   => $timeline,
    'startIndex' => $startIndex,
    'explainUrl' => url(['page' => 'explication', 'id' => $ville->idVille]),
    'ville'      => ['nom' => $ville->nom, 'lat' => $ville->latitude, 'lng' => $ville->longitude],
];
?>
<section class="page-banner">
  <div>
    <div class="eyebrow"><?= e($ville->gouvernorat) ?></div>
    <h1><?= e($ville->nom) ?></h1>
    <p><?= e($ville->description) ?></p>
  </div>
  <a href="<?= e(url(['page' => 'tounes'])) ?>" class="btn-dark-green">← Toutes les régions</a>
</section>

<?php if (!$timeline): ?>
  <section class="panel"><p class="muted mb-0">Aucune projection pour cette région.</p></section>
<?php else: ?>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="panel h-100">
      <div class="panel-head">
        <h2>Naviguer dans le temps</h2>
        <span class="muted">Choisissez un horizon</span>
      </div>

      <div class="year-pills">
        <?php foreach ($timeline as $i => $p): ?>
          <button type="button" class="year-pill"><?= $p['annee'] ?><?= $i === 0 ? ' · référence' : '' ?></button>
        <?php endforeach; ?>
      </div>
      <input type="range" class="form-range year-range" id="yearRange" min="0" max="<?= count($timeline) - 1 ?>" step="1" value="<?= $startIndex ?>">

      <div class="year-big">
        <span class="year" id="yearLabel"></span>
        <span class="badge-pill" id="stressBadge"></span>
      </div>

      <div class="row g-3">
        <div class="col-sm-6">
          <div class="stat-tile">
            <i class="bi bi-thermometer-half"></i>
            <div><small>Température moyenne</small><strong id="tempValue"></strong><em id="tempDelta"></em></div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="stat-tile">
            <i class="bi bi-cloud-rain"></i>
            <div><small>Pluviométrie annuelle</small><strong id="rainValue"></strong><em id="rainDelta"></em></div>
          </div>
        </div>
      </div>

      <p class="muted mt-3 mb-3" id="projDesc"></p>

      <span class="label-small">Cultures menacées</span>
      <div id="crops"></div>

      <button type="button" class="btn-dark-green mt-4" id="btnExplain">
        <i class="bi bi-stars"></i> Demander une explication
      </button>

      <div class="explain-box d-none" id="explainBox">
        <div class="ai-tag"><i class="bi bi-stars"></i> Explication IA</div>
        <div class="explain-text" id="explainText"></div>
      </div>
    </section>
  </div>

  <div class="col-lg-5">
    <section class="panel p-0 overflow-hidden h-100">
      <div class="tile-cover cover-lg">
        <?php if ($img): ?>
          <img src="<?= e($img) ?>" alt="<?= e($ville->nom) ?>">
        <?php else: ?>
          <i class="bi bi-tree"></i>
        <?php endif; ?>
      </div>
      <div id="regionMap" class="map-sm"></div>
      <div class="p-3 muted small">
        <i class="bi bi-geo-alt"></i> <?= $ville->latitude ?>° N, <?= $ville->longitude ?>° E
      </div>
    </section>
  </div>
</div>

<section class="panel mt-4">
  <div class="panel-head">
    <h2>Évolution jusqu'en <?= $timeline[count($timeline) - 1]['annee'] ?></h2>
    <span class="muted">Température (courbe) et pluie (barres)</span>
  </div>
  <canvas id="chart" height="110"></canvas>
</section>

<script>
  window.TOUNES = <?= json_encode($js, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= FO_URL ?>assets/js/ville.js"></script>

<?php endif; ?>