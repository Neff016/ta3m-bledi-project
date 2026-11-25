/*
 * Contrôle de saisie (JavaScript) : formulaire Culture du Back Office
 * Mêmes règles que CultureController::validateCulture()
 */
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formCulture');
  if (!form) return;

  function erreur(id, message) {
    document.getElementById('erreur-' + id).textContent = message;
    const champ = document.getElementById(id);
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }
  function nombreEntre(id, min, max, message) {
    const v = document.getElementById(id).value.trim().replace(',', '.');
    return erreur(id, v === '' || isNaN(Number(v)) || Number(v) < min || Number(v) > max ? message : '');
  }
  function coches(nom) { return form.querySelectorAll('input[name="' + nom + '[]"]:checked').length; }

  form.addEventListener('submit', function (ev) {
    let ok = true;
    const nom = document.getElementById('nom');
    if (!nom.readOnly) {
      const v = nom.value.trim();
      ok = erreur('nom', v.length < 2 || v.length > 60 || !/^[\p{L}\s'\-]+$/u.test(v) ? 'Le nom doit contenir 2 à 60 lettres.' : '') && ok;
    }
    ok = erreur('categorie', document.getElementById('categorie').value === '' ? 'Choisissez une catégorie.' : '') && ok;
    ok = erreur('besoinEau', document.getElementById('besoinEau').value === '' ? 'Choisissez le besoin en eau.' : '') && ok;
    ok = erreur('sols', coches('sols') === 0 ? 'Cochez au moins un type de sol.' : '') && ok;
    ok = erreur('saisons', coches('saisons') === 0 ? 'Cochez au moins une saison.' : '') && ok;
    ok = nombreEntre('kc', 0.2, 1.5, 'Le coefficient kc doit être entre 0,2 et 1,5.') && ok;
    ok = nombreEntre('rendement', 0.1, 200, 'Le rendement doit être entre 0,1 et 200 t/ha.') && ok;
    ok = nombreEntre('dureeJours', 20, 400, 'La durée doit être entre 20 et 400 jours.') && ok;
    ok = nombreEntre('prixKg', 0.01, 500, 'Le prix doit être entre 0,01 et 500 DT/kg.') && ok;
    const c = document.getElementById('conseil').value.trim();
    ok = erreur('conseil', c.length < 5 || c.length > 255 ? 'Le conseil doit contenir 5 à 255 caractères.' : '') && ok;
    if (!ok) ev.preventDefault();
  });
});
