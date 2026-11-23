<section class="section">
 <div class="container">
  <div class="section-head"><div><div class="kicker">Administration</div><h2>Gérer les inscriptions</h2><p>Consulter les réservations des utilisateurs.</p></div></div>
  <div class="panel table-panel"><div class="table-wrap"><table><thead><tr><th>Utilisateur</th><th>E-mail</th><th>Atelier</th><th>Date atelier</th><th>Lieu</th><th>Action</th></tr></thead><tbody>
   <?php foreach ($registrations as $r): ?>
    <tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['workshop_title']) ?></td><td><?= e($r['workshop_date']) ?></td><td><?= e($r['location']) ?></td>
    <td><form method="post" action="index.php?page=admin-inscription-delete" onsubmit="return confirm('Supprimer cette inscription ?')"><?= csrf_field() ?><input type="hidden" name="registration_id" value="<?= (int)$r['registration_id'] ?>"><button class="cancel" type="submit">Supprimer</button></form></td></tr>
   <?php endforeach; ?>
   <?php if (!$registrations): ?><tr><td colspan="6">Aucune inscription.</td></tr><?php endif; ?>
  </tbody></table></div></div>
 </div>
</section>
