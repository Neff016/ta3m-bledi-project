<section class="section">
 <div class="container">
  <div class="section-head"><div><div class="kicker">Espace administration</div><h2>Tableau de bord</h2><p>Vue générale du module Ateliers.</p></div></div>
  <div class="stats-grid">
   <div class="stat-card"><span>Ateliers</span><strong><?= (int)$stats['workshops'] ?></strong></div>
   <div class="stat-card"><span>Inscriptions</span><strong><?= (int)$stats['registrations'] ?></strong></div>
   <div class="stat-card"><span>Utilisateurs</span><strong><?= (int)$stats['users'] ?></strong></div>
   <div class="stat-card"><span>Ateliers à venir</span><strong><?= (int)$stats['upcoming'] ?></strong></div>
  </div>
  <div class="admin-actions">
   <a class="btn" href="index.php?page=admin-ateliers">Gérer les ateliers</a>
   <a class="btn btn-outline" href="index.php?page=admin-inscriptions">Gérer les inscriptions</a>
  </div>
 </div>
</section>
