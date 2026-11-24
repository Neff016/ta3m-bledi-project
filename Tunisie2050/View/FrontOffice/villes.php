<?php
$mapData = array_map(fn($c) => [
    'nom' => $c['ville']->nom,
    'lat' => $c['ville']->latitude,
    'lng' => $c['ville']->longitude,
    'url' => url(['page' => 'tounes', 'id' => $c['ville']->idVille]),
], $cards);
?>
<section class="page-banner">
  <div>
    <div class="eyebrow">Climat &amp; agriculture</div>
    <h1>Tunisie2050</h1>
    <p>Explorez comment le climat transformera l'agriculture dans chaque région.</p>
  </div>
  <a href="#regions" class="btn-dark-green">Choisir une région</a>
</section>

<section class="panel p-0 overflow-hidden mb-4">
  <div id="map" class="map-lg"></div>
</section>

<div class="toolbar" id="regions">
  <div class="search-box">
    <i class="bi bi-search"></i>
    <input type="search" id="search" placeholder="Rechercher une région…">
  </div>
  <span class="muted"><span id="count"><?= count($cards) ?></span> région(s)</span>
</div>

<?php if (!$cards): ?>
  <p class="muted">Aucune région disponible.</p>
<?php endif; ?>

<div class="row g-4">
  <?php foreach ($cards as $c):
    $v = $c['ville']; $b = $c['base']; $t = $c['cible'];
    $img = regionImage($v->image);
  ?>
    <div class="col-lg-4 col-md-6 card-col" data-name="<?= e(mb_strtolower($v->nom . ' ' . $v->gouvernorat)) ?>">
      <article class="tile">
        <div class="tile-cover">
          <?php if ($img): ?>
            <img src="<?= e($img) ?>" alt="<?= e($v->nom) ?>">
          <?php else: ?>
            <i class="bi bi-tree"></i>
          <?php endif; ?>
        </div>

        <div class="tile-body">
          <div class="tile-head">
            <h3><?= e($v->nom) ?></h3>
            <?php if ($t): ?>
              <span class="badge-pill <?= $t->stressClass() ?>">Stress <?= e(mb_strtolower($t->stressHydrique)) ?> en <?= $t->annee ?></span>
            <?php endif; ?>
          </div>
          <p class="tile-sub"><?= e($v->description) ?></p>

          <?php if ($b && $t):
            $pct = $b->pluviometrie > 0 ? ($t->pluviometrie - $b->pluviometrie) / $b->pluviometrie * 100 : 0;
          ?>
            <div class="tile-row">
              <i class="bi bi-thermometer-half"></i>
              <span><?= signed($t->temperatureMoy - $b->temperatureMoy) ?> °C d'ici <?= $t->annee ?></span>
            </div>
            <div class="tile-row">
              <i class="bi bi-cloud-rain"></i>
              <span><?= signed($pct, 0) ?> % de pluie</span>
            </div>
            <div class="tile-row">
              <i class="bi bi-flower1"></i>
              <span><?= count($t->cultures()) ?> culture(s) menacée(s)</span>
            </div>
          <?php else: ?>
            <p class="muted">Aucune projection pour le moment.</p>
          <?php endif; ?>
        </div>

        <div class="tile-foot">
          <a class="stretched-link" href="<?= e(url(['page' => 'tounes', 'id' => $v->idVille])) ?>">
            Explorer le futur <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </article>
    </div>
  <?php endforeach; ?>
</div>

<script>
  const regions = <?= json_encode($mapData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

  const map = L.map('map', { scrollWheelZoom: false }).setView([34.2, 9.6], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 12, attribution: '&copy; OpenStreetMap'
  }).addTo(map);
  regions.forEach(r => L.marker([r.lat, r.lng]).addTo(map)
    .bindPopup('<strong>' + esc(r.nom) + '</strong><br><a href="' + r.url + '">Explorer le futur</a>'));

  const input = document.getElementById('search');
  const cols = document.querySelectorAll('.card-col');
  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    let n = 0;
    cols.forEach(c => {
      const ok = c.dataset.name.includes(q);
      c.classList.toggle('d-none', !ok);
      if (ok) n++;
    });
    document.getElementById('count').textContent = n;
  });
</script>