/*
 * Contrôle de saisie (JavaScript) : formulaire Terrain du Back Office
 * Mêmes règles que TerrainController::validateTerrain() et validateProprietaire()
 */
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formTerrainBo');
  if (!form) return;

  function erreur(id, message) {
    document.getElementById('erreur-' + id).textContent = message;
    const champ = document.getElementById(id);
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }
  function valeur(id) { return document.getElementById(id).value.trim(); }

  form.addEventListener('submit', function (ev) {
    let ok = true;
    ok = erreur('idUtilisateur', valeur('idUtilisateur') === '' ? 'Veuillez choisir un agriculteur actif.' : '') && ok;

    const nom = valeur('nom');
    ok = erreur('nom', nom === '' ? 'Le nom du terrain est obligatoire.'
      : (nom.length < 3 || nom.length > 100 ? 'Le nom doit contenir entre 3 et 100 caractères.'
      : (!/^[\p{L}0-9\s'\-]+$/u.test(nom) ? 'Le nom ne doit contenir que des lettres, chiffres, espaces, apostrophes ou tirets.' : ''))) && ok;

    ok = erreur('localisation', valeur('localisation') === '' ? 'Veuillez choisir une wilaya.' : '') && ok;

    const s = valeur('superficie').replace(',', '.');
    ok = erreur('superficie', s === '' ? 'La superficie est obligatoire.'
      : (isNaN(Number(s)) ? 'La superficie doit être un nombre.'
      : (Number(s) <= 0 ? 'La superficie doit être supérieure à 0.'
      : (Number(s) > 100000 ? 'La superficie ne peut pas dépasser 100 000 ha.' : '')))) && ok;

    ok = erreur('typeSol', valeur('typeSol') === '' ? 'Veuillez choisir un type de sol.' : '') && ok;

    // Position : les deux ou aucune, et en Tunisie (mêmes limites que TerrainController::enTunisie)
    const lat = valeur('latitude'), lng = valeur('longitude');
    let messagePos = '';
    if (lat !== '' || lng !== '') {
      const la = Number(lat), lo = Number(lng);
      if (lat === '' || lng === '' || isNaN(la) || isNaN(lo) || la < 30.2 || la > 37.6 || lo < 7.5 || lo > 11.7) {
        messagePos = 'La position doit être un point en Tunisie (cliquez sur la carte).';
      }
    }
    ok = erreur('position', messagePos) && ok;
    ['latitude', 'longitude'].forEach(function (id) { document.getElementById(id).classList.toggle('is-invalid', !!messagePos); });

    if (!ok) ev.preventDefault();
  });
});
