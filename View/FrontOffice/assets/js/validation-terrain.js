/*
 * Contrôle de saisie (JavaScript) : formulaire Terrain
 * Mêmes règles que validateTerrain() dans TerrainController.php
 */
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formTerrain');
  if (!form) return;
  const t = window.tj || function (s) { return s; };

  // Affiche (ou efface) le message d'erreur sous un champ
  function afficherErreur(idChamp, message) {
    const champ = document.getElementById(idChamp);
    const zone = document.getElementById('erreur-' + idChamp);
    zone.textContent = message ? t(message) : '';
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }

  // Aperçu de la photo choisie
  const photo = document.getElementById('photo');
  const apercu = document.getElementById('apercuPhotoTerrain');
  if (photo && apercu) {
    photo.addEventListener('change', function () {
      if (photo.files[0] && /^image\//.test(photo.files[0].type)) {
        apercu.src = URL.createObjectURL(photo.files[0]);
        apercu.classList.remove('d-none');
      }
    });
  }

  form.addEventListener('submit', function (event) {
    let valide = true;

    // Nom
    const nom = document.getElementById('nom').value.trim();
    if (nom === '') valide = afficherErreur('nom', 'Le nom du terrain est obligatoire.') && valide;
    else if (nom.length < 3 || nom.length > 100) valide = afficherErreur('nom', 'Le nom doit contenir entre 3 et 100 caractères.') && valide;
    else if (!/^[\p{L}0-9\s'\-]+$/u.test(nom)) valide = afficherErreur('nom', 'Le nom ne doit contenir que des lettres, chiffres, espaces, apostrophes ou tirets.') && valide;
    else afficherErreur('nom', '');

    // Wilaya
    valide = afficherErreur('localisation', document.getElementById('localisation').value === '' ? 'Veuillez choisir une wilaya.' : '') && valide;

    // Superficie
    const texte = document.getElementById('superficie').value.trim().replace(',', '.');
    const superficie = Number(texte);
    if (texte === '') valide = afficherErreur('superficie', 'La superficie est obligatoire.') && valide;
    else if (isNaN(superficie)) valide = afficherErreur('superficie', 'La superficie doit être un nombre.') && valide;
    else if (superficie <= 0) valide = afficherErreur('superficie', 'La superficie doit être supérieure à 0.') && valide;
    else if (superficie > 100000) valide = afficherErreur('superficie', 'La superficie ne peut pas dépasser 100 000 ha.') && valide;
    else afficherErreur('superficie', '');

    // Type de sol
    valide = afficherErreur('typeSol', document.getElementById('typeSol').value === '' ? 'Veuillez choisir un type de sol.' : '') && valide;

    // Position (facultative) : en Tunisie
    const lat = document.getElementById('latitude').value, lng = document.getElementById('longitude').value;
    const zonePos = document.getElementById('erreur-position');
    if ((lat !== '' || lng !== '') && !(window.TerrainCarte && TerrainCarte.enTunisie(parseFloat(lat), parseFloat(lng)))) {
      zonePos.textContent = t('La position doit être un point en Tunisie (cliquez sur la carte).');
      valide = false;
    } else {
      zonePos.textContent = '';
    }

    // Photo (facultative) : image de 2 Mo maximum
    if (photo && photo.files[0]) {
      const f = photo.files[0];
      if (!/^image\/(jpeg|png|webp|gif)$/.test(f.type)) valide = afficherErreur('photo', 'Format accepté : JPG, PNG, WEBP ou GIF.') && valide;
      else if (f.size > 2 * 1024 * 1024) valide = afficherErreur('photo', "L'image ne doit pas dépasser 2 Mo.") && valide;
      else afficherErreur('photo', '');
    }

    // Une erreur → on bloque l'envoi du formulaire
    if (!valide) event.preventDefault();
  });
});
