<section class="section">
 <div class="container">
  <div class="section-head"><div><div class="kicker">Espace utilisateur</div><h2>Mes inscriptions</h2><p>Consulte et annule tes réservations.</p></div></div>
  <div class="panel">
   <?php if (!$registrations): ?><div class="empty">Tu n'as pas encore d'inscription.</div><?php endif; ?>
   <?php foreach ($registrations as $r): ?>
    <div class="registration">
     <div><strong><?= e($r['title']) ?></strong><small><?= e(date('d/m/Y', strtotime($r['workshop_date']))) ?> · <?= e($r['location']) ?></small></div>
     <form method="post" action="index.php?page=annuler-inscription" onsubmit="return confirm('Annuler cette inscription ?')">
      <?= csrf_field() ?><input type="hidden" name="registration_id" value="<?= (int)$r['registration_id'] ?>">
      <button class="cancel" type="submit">Annuler</button>
     </form>
    </div>
   <?php endforeach; ?>
  </div>
 </div>
</section>
