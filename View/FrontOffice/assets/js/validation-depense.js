/*
 * Contrôle de saisie (JavaScript) : dépenses (page Détails terrain)
 * Mêmes règles que DepenseController::valider()
 */
document.addEventListener('DOMContentLoaded', function () {
  const t = window.tj || function (s) { return s; };
  const maintenant = new Date();   // date locale (pas UTC) au format AAAA-MM-JJ
  const aujourdhui = maintenant.getFullYear() + '-' + String(maintenant.getMonth() + 1).padStart(2, '0') + '-' + String(maintenant.getDate()).padStart(2, '0');

  function erreur(id, message) {
    const champ = document.getElementById(id), zone = document.getElementById('erreur-' + id);
    if (zone) zone.textContent = message ? t(message) : '';
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }
  function dateValide(v) { return /^\d{4}-\d{2}-\d{2}$/.test(v) && !isNaN(new Date(v + 'T00:00:00')); }

  const formDepense = document.getElementById('formDepense');
  if (formDepense) {
    formDepense.addEventListener('submit', function (ev) {
      let ok = true;
      const m = document.getElementById('montant').value.trim().replace(',', '.');
      ok = erreur('montant', m === '' ? 'Le montant est obligatoire.'
        : (isNaN(Number(m)) || Number(m) <= 0 || Number(m) > 10000000 ? 'Le montant doit être un nombre entre 0 et 10 000 000 DT.' : '')) && ok;
      const d = document.getElementById('dateDepense').value;
      ok = erreur('dateDepense', !dateValide(d) ? 'Date invalide.' : (d > aujourdhui ? 'La date ne peut pas être dans le futur.' : '')) && ok;
      ok = erreur('description', document.getElementById('description').value.trim().length > 150 ? '150 caractères maximum.' : '') && ok;
      if (!ok) ev.preventDefault();
    });
  }
});
