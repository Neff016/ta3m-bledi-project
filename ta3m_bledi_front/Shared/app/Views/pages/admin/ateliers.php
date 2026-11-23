<section class="section">
 <div class="container">
  <div class="section-head"><div><div class="kicker">Administration</div><h2>Gérer les ateliers</h2><p>Ajouter, modifier ou supprimer un atelier.</p></div></div>
  <div class="panel form-panel">
   <h3>Ajouter / modifier un atelier</h3>
   <form method="post" action="index.php?page=admin-atelier-save" class="admin-form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="workshop-id" value="">
    <label>Titre<input name="title" required maxlength="180"></label>
    <label>Catégorie<input name="category" required placeholder="Jardinage, Semences..." maxlength="100"></label>
    <label>Description<textarea name="description" required rows="3"></textarea></label>
    <label>Date<input type="date" name="workshop_date" required></label>
    <label>Lieu<input name="location" required maxlength="150"></label>
    <label>Capacité<input type="number" name="capacity" min="1" value="15" required></label>
    <label>Icône (emoji)<input name="icon" value="🌱" maxlength="10"></label>
    <label>Statut<select name="status"><option value="active">Actif</option><option value="draft">Brouillon</option></select></label>
    <button class="btn" type="submit">Enregistrer l'atelier</button>
   </form>
  </div>
  <div class="panel table-panel"><h3>Liste des ateliers</h3>
   <div class="table-wrap"><table><thead><tr><th>Atelier</th><th>Date</th><th>Lieu</th><th>Places restantes</th><th>Statut</th><th>Action</th></tr></thead><tbody>
   <?php foreach ($workshops as $w): ?>
    <tr><td><strong><?= e($w['title']) ?></strong><small><?= e($w['category']) ?></small></td><td><?= e($w['workshop_date']) ?></td><td><?= e($w['location']) ?></td><td><?= max(0,(int)$w['places_left']) ?>/<?= (int)$w['capacity'] ?></td><td><?= e($w['status']) ?></td>
    <td class="actions-cell">
      <button type="button" class="btn edit-workshop"
       data-id="<?= (int)$w['id'] ?>" data-title="<?= e($w['title']) ?>"
       data-category="<?= e($w['category']) ?>" data-description="<?= e($w['description']) ?>"
       data-date="<?= e($w['workshop_date']) ?>" data-location="<?= e($w['location']) ?>"
       data-capacity="<?= (int)$w['capacity'] ?>" data-icon="<?= e($w['icon']) ?>"
       data-status="<?= e($w['status']) ?>">Modifier</button>
      <form method="post" action="index.php?page=admin-atelier-delete" onsubmit="return confirm('Supprimer cet atelier et ses inscriptions ?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$w['id'] ?>"><button class="cancel" type="submit">Supprimer</button></form>
    </td></tr>
   <?php endforeach; ?>
   </tbody></table></div>
  </div>
 </div>
</section>
