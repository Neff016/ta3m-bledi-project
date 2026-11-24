<section class="page-banner">
  <div>
    <div class="eyebrow">Tunisie2050</div>
    <h1>Cultures menacées</h1>
    <p>Quelles cultures sont en danger, et dans quelles régions ? Choisissez un horizon.</p>
  </div>
</section>

<div class="year-pills mb-4">
  <?php foreach ($years as $y): ?>
    <a class="year-pill <?= $y === $annee ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'cultures', 'annee' => $y])) ?>"><?= $y ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <section class="panel">
    <p class="muted mb-0">Aucune culture menacée à cet horizon (<?= $annee ?>). Essayez une année plus lointaine.</p>
  </section>
<?php else: ?>
  <div class="row g-4">
    <div class="col-lg-4">
      <section class="panel h-100">
        <h2 class="mb-3">Les plus menacées</h2>
        <?php $max = max($ranking); foreach ($ranking as $name => $count): ?>
          <div class="rank-row">
            <div class="d-flex justify-content-between">
              <strong><?= e($name) ?></strong>
              <span class="muted"><?= $count ?> région(s)</span>
            </div>
            <div class="rank-bar"><span style="width: <?= round($count / $max * 100) ?>%"></span></div>
          </div>
        <?php endforeach; ?>
      </section>
    </div>

    <div class="col-lg-8">
      <div class="row g-4">
        <?php foreach ($rows as $r): ?>
          <div class="col-md-6">
            <article class="tile">
              <div class="tile-body">
                <div class="tile-head">
                  <h3><?= e($r['ville']->nom) ?></h3>
                  <span class="badge-pill <?= $r['projection']->stressClass() ?>"><?= e($r['projection']->stressHydrique) ?></span>
                </div>
                <div class="mt-3">
                  <?php foreach ($r['cultures'] as $c): ?>
                    <span class="chip"><?= e($c) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="tile-foot">
                <a class="stretched-link" href="<?= e(url(['page' => 'tounes', 'id' => $r['ville']->idVille, 'annee' => $annee])) ?>">
                  Voir la région <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>