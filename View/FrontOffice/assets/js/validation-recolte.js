/*
 * Contrôle de saisie (JavaScript) : formulaire Récolte
 * Mêmes règles que validateRecolte() dans RecolteController.php
 * (récolte terminée : dates passées, quantité obligatoire)
 */
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formRecolte');
  if (!form) return;
  const t = window.tj || function (s) { return s; };

  function afficherErreur(idChamp, message) {
    const champ = document.getElementById(idChamp);
    const zone = document.getElementById('erreur-' + idChamp);
    zone.textContent = message ? t(message) : '';
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }

  // Vraie date au format AAAA-MM-JJ ? (refuse par ex. 2026-02-31)
  function dateValide(texte) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(texte)) return null;
    const [a, m, j] = texte.split('-').map(Number);
    const d = new Date(a, m - 1, j);
    if (d.getFullYear() !== a || d.getMonth() !== m - 1 || d.getDate() !== j) return null;
    return d;
  }

  form.addEventListener('submit', function (event) {
    let valide = true;
    const aujourdhui = new Date();
    aujourdhui.setHours(0, 0, 0, 0);

    // Terrain
    valide = afficherErreur('idTerrain', document.getElementById('idTerrain').value === '' ? 'Veuillez choisir un terrain.' : '') && valide;

    // Culture
    const culture = document.getElementById('culture').value.trim();
    if (culture === '') valide = afficherErreur('culture', 'La culture est obligatoire.') && valide;
    else if (culture.length < 2 || culture.length > 100) valide = afficherErreur('culture', 'La culture doit contenir entre 2 et 100 caractères.') && valide;
    else if (!/^[\p{L}0-9\s'’\-().]+$/u.test(culture)) valide = afficherErreur('culture', 'La culture ne doit contenir que des lettres, chiffres, espaces, apostrophes, tirets ou parenthèses.') && valide;
    else afficherErreur('culture', '');

    // Dates
    const plantation = dateValide(document.getElementById('datePlantation').value);
    const recolte = dateValide(document.getElementById('dateRecolte').value);
    if (!plantation) valide = afficherErreur('datePlantation', 'Veuillez saisir une date de plantation valide.') && valide;
    else if (plantation > aujourdhui) valide = afficherErreur('datePlantation', 'La date de plantation ne peut pas être dans le futur.') && valide;
    else if (plantation < new Date(2000, 0, 1)) valide = afficherErreur('datePlantation', 'La date de plantation n’est pas réaliste.') && valide;
    else afficherErreur('datePlantation', '');

    if (!recolte) valide = afficherErreur('dateRecolte', 'Veuillez saisir une date de récolte valide.') && valide;
    else if (plantation && recolte <= plantation) valide = afficherErreur('dateRecolte', 'La date de récolte doit être après la date de plantation.') && valide;
    else if (recolte > aujourdhui) valide = afficherErreur('dateRecolte', 'La date de récolte ne peut pas être dans le futur.') && valide;
    else afficherErreur('dateRecolte', '');

    // Quantité
    const texte = document.getElementById('quantite').value.trim().replace(',', '.');
    const q = Number(texte);
    if (texte === '') valide = afficherErreur('quantite', 'La quantité est obligatoire.') && valide;
    else if (isNaN(q)) valide = afficherErreur('quantite', 'La quantité doit être un nombre.') && valide;
    else if (q <= 0) valide = afficherErreur('quantite', 'La quantité doit être supérieure à 0.') && valide;
    else if (q > 1000000) valide = afficherErreur('quantite', 'La quantité ne peut pas dépasser 1 000 000.') && valide;
    else afficherErreur('quantite', '');

    // Unité
    valide = afficherErreur('unite', ['kg', 'quintal', 'tonne'].indexOf(document.getElementById('unite').value) === -1 ? 'Veuillez choisir une unité.' : '') && valide;

    // Photo
    const photo = document.getElementById('photo');
    if (photo && photo.files[0]) {
      const f = photo.files[0];
      if (!/^image\/(jpeg|png|webp|gif)$/.test(f.type)) valide = afficherErreur('photo', 'Format accepté : JPG, PNG, WEBP ou GIF.') && valide;
      else if (f.size > 2 * 1024 * 1024) valide = afficherErreur('photo', "L'image ne doit pas dépasser 2 Mo.") && valide;
      else afficherErreur('photo', '');
    }

    if (!valide) {
      event.preventDefault();
      event.stopImmediatePropagation();   // pas d'enregistrement hors ligne si le formulaire est faux
    }
  });
});
