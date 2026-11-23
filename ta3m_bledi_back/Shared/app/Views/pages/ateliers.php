<section class="hero" id="accueil">
 <div class="container"><div class="hero-banner">
  <div><div class="eyebrow">Ateliers & agriculture</div><h1>Découvrez nos ateliers</h1>
   <p>Apprenez, partagez et préservez les savoir-faire agricoles tunisiens dans votre région.</p></div>
  <a class="hero-action" href="#ateliers">Choisir un atelier</a>
 </div></div>
</section>
<section class="section" id="ateliers">
 <div class="container">
  <div class="section-head"><div><div class="kicker">Apprendre & partager</div><h2>Nos prochains ateliers</h2><p>Choisissez une activité et réservez votre place.</p></div></div>
  <div class="filters">
   <input id="search" type="search" placeholder="Rechercher un atelier...">
   <select id="category"><option value="">Toutes les catégories</option>
    <?php foreach (array_unique(array_column($workshops, 'category')) as $category): ?>
      <option value="<?= e($category) ?>"><?= e($category) ?></option>
    <?php endforeach; ?>
   </select>
  </div>
  <div class="cards" id="workshopCards">
   <?php foreach ($workshops as $w): ?>
    <article class="card workshop-card" data-title="<?= e(mb_strtolower($w['title'])) ?>" data-description="<?= e(mb_strtolower($w['description'])) ?>" data-category="<?= e($w['category']) ?>">
     <div class="card-art"><?= e($w['icon']) ?></div>
     <div class="card-body"><span class="tag"><?= e($w['category']) ?></span>
      <h3><?= e($w['title']) ?></h3><p><?= e($w['description']) ?></p>
      <div class="meta"><span>◷ <?= e(date('d/m/Y', strtotime($w['workshop_date']))) ?></span><span>⌖ <?= e($w['location']) ?></span></div>
     </div>
     <div class="card-foot"><span class="places"><?= max(0, (int)$w['places_left']) ?> places disponibles</span>
      <?php if (is_logged_in() && !is_admin()): ?>
       <form method="post" action="index.php?page=inscription">
        <?= csrf_field() ?><input type="hidden" name="workshop_id" value="<?= (int)$w['id'] ?>">
        <button class="btn" type="submit" <?= (int)$w['places_left'] <= 0 ? 'disabled' : '' ?>>S'inscrire</button>
       </form>
      <?php elseif (!is_logged_in()): ?>
       <a class="btn" href="index.php?page=login">Se connecter</a>
      <?php endif; ?>
     </div>
    </article>
   <?php endforeach; ?>
   <?php if (!$workshops): ?><div class="empty">Aucun atelier disponible pour le moment.</div><?php endif; ?>
  </div>
  <p id="noResults" class="empty" hidden>Aucun atelier ne correspond à votre recherche.</p>
 </div>
</section>
<section class="lower">
 <div class="container two-col">
  <div class="panel">
   <h3>Mes inscriptions</h3><p>Connecte-toi pour consulter et gérer tes réservations.</p>
   <a class="btn" href="index.php?page=mes-inscriptions">Consulter mes inscriptions</a>
  </div>
  <div class="panel ai-panel">
   <h3>Recommandation IA</h3><p>Choisis un domaine pour obtenir une suggestion adaptée.</p>
   <select id="interest" class="form-control">
    <?php foreach (array_unique(array_column($workshops, 'category')) as $category): ?>
      <option value="<?= e($category) ?>"><?= e($category) ?></option>
    <?php endforeach; ?>
   </select>
   <button class="btn" id="recommendBtn" type="button">Obtenir une recommandation →</button>
   <div class="result" id="aiResult"></div>
  </div>
 </div>
</section>
