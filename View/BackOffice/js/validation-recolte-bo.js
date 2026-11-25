/*
 * Contrôle de saisie (JavaScript) : formulaire Récolte du Back Office
 * Mêmes règles que RecolteController::validateRecolte() ; parcelles filtrées selon le terrain choisi
 */
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formRecolteBo');
  if (!form) return;
  const terrain = document.getElementById('idTerrain');
  const parcelle = document.getElementById('idParcelle');

  function erreur(id, message) {
    document.getElementById('erreur-' + id).textContent = message;
    const champ = document.getElementById(id);
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }
  function dateValide(v) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return null;
    const [a, m, j] = v.split('-').map(Number);
    const d = new Date(a, m - 1, j);
    return d.getFullYear() === a && d.getMonth() === m - 1 && d.getDate() === j ? d : null;
  }
  function filtrerParcelles() {
    Array.prototype.forEach.call(parcelle.options, function (o) {
      if (!o.value) return;
      const visible = o.dataset.terrain === terrain.value;
      o.hidden = !visible; o.disabled = !visible;
      if (!visible && o.selected) parcelle.value = '';
    });
  }
  terrain.addEventListener('change', filtrerParcelles);
  filtrerParcelles();

  form.addEventListener('submit', function (ev) {
    let ok = true;
    const auj = new Date(); auj.setHours(0, 0, 0, 0);
    ok = erreur('idTerrain', terrain.value === '' ? 'Veuillez choisir un terrain.' : '') && ok;
    const opt = parcelle.options[parcelle.selectedIndex];
    ok = erreur('idParcelle', parcelle.value !== '' && opt.dataset.terrain !== terrain.value ? 'Cette parcelle n’appartient pas au terrain choisi.' : '') && ok;

    const c = document.getElementById('culture').value.trim();
    ok = erreur('culture', c === '' ? 'La culture est obligatoire.'
      : (c.length < 2 || c.length > 100 ? 'La culture doit contenir entre 2 et 100 caractères.'
      : (!/^[\p{L}0-9\s'’\-().]+$/u.test(c) ? 'La culture ne doit contenir que des lettres, chiffres, espaces, apostrophes, tirets ou parenthèses.' : ''))) && ok;

    const p = dateValide(document.getElementById('datePlantation').value);
    const r = dateValide(document.getElementById('dateRecolte').value);
    ok = erreur('datePlantation', !p ? 'Veuillez saisir une date de plantation valide.'
      : (p > auj ? 'La date de plantation ne peut pas être dans le futur.'
      : (p < new Date(2000, 0, 1) ? 'La date de plantation n’est pas réaliste.' : ''))) && ok;
    ok = erreur('dateRecolte', !r ? 'Veuillez saisir une date de récolte valide.'
      : (p && r <= p ? 'La date de récolte doit être après la date de plantation.'
      : (r > auj ? 'La date de récolte ne peut pas être dans le futur.' : ''))) && ok;

    const q = document.getElementById('quantite').value.trim().replace(',', '.');
    ok = erreur('quantite', q === '' ? 'La quantité est obligatoire.'
      : (isNaN(Number(q)) ? 'La quantité doit être un nombre.'
      : (Number(q) <= 0 ? 'La quantité doit être supérieure à 0.'
      : (Number(q) > 1000000 ? 'La quantité ne peut pas dépasser 1 000 000.' : '')))) && ok;
    if (!ok) ev.preventDefault();
  });
});
