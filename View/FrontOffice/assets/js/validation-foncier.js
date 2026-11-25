/*
 * Onglet « Contrat et bio » (page Détails terrain)
 *  - affiche seulement les champs utiles au régime choisi (propriété, location, khammès, métayage)
 *  - contrôle de saisie : mêmes règles que ContratFoncierController::valider() et CertificationBioController::valider()
 */
document.addEventListener('DOMContentLoaded', function () {
  'use strict';
  const t = window.tj || function (s) { return s; };
  const maintenant = new Date();
  const aujourdhui = maintenant.getFullYear() + '-' + String(maintenant.getMonth() + 1).padStart(2, '0') + '-' + String(maintenant.getDate()).padStart(2, '0');

  function erreur(id, message) {
    const champ = document.getElementById(id), zone = document.getElementById('erreur-' + id);
    if (zone) zone.textContent = message ? t(message) : '';
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }
  function dateValide(v) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false;
    const [a, m, j] = v.split('-').map(Number);
    const d = new Date(a, m - 1, j);
    return d.getFullYear() === a && d.getMonth() === m - 1 && d.getDate() === j;
  }
  function ajouterMois(v, mois) {
    const [a, m, j] = v.split('-').map(Number);
    const d = new Date(a, m - 1 + mois, j);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }
  function nombre(v) { return v.trim().replace(',', '.'); }

  /* ---------------- Contrat foncier ---------------- */
  const formContrat = document.getElementById('formContrat');
  if (formContrat) {
    const type = document.getElementById('typeContrat');
    const parts = JSON.parse(type.dataset.parts || '{}');
    const champPart = document.getElementById('partProprietaire');

    function majChamps() {
      formContrat.querySelectorAll('.champ-contrat').forEach(function (bloc) {
        bloc.hidden = bloc.dataset.types.split(' ').indexOf(type.value) === -1;
      });
      if (parts[type.value] && champPart.value.trim() === '') champPart.value = parts[type.value];
    }
    type.addEventListener('change', majChamps);
    majChamps();

    formContrat.addEventListener('submit', function (ev) {
      let ok = true;
      const v = type.value;
      ok = erreur('typeContrat', ['PROPRIETE', 'LOCATION', 'KHAMMES', 'METAYAGE'].indexOf(v) === -1 ? 'Veuillez choisir le type de contrat.' : '') && ok;
      const debut = document.getElementById('dateDebut').value;
      const limite = ajouterMois(aujourdhui, 12);
      ok = erreur('dateDebut', !dateValide(debut) ? 'Veuillez saisir une date de début valide.'
        : (debut < '1950-01-01' || debut > limite ? 'La date de début n’est pas réaliste.' : '')) && ok;

      if (v !== 'PROPRIETE') {
        const p = document.getElementById('proprietaire').value.trim();
        ok = erreur('proprietaire', p === '' ? 'Le nom du propriétaire est obligatoire.'
          : (p.length < 3 || p.length > 100 ? 'Le nom du propriétaire doit contenir entre 3 et 100 caractères.'
          : (!/^[\p{L}\s'’\-.]+$/u.test(p) ? 'Le nom du propriétaire ne doit contenir que des lettres, espaces, apostrophes ou tirets.' : ''))) && ok;
        const fin = document.getElementById('dateFin').value;
        ok = erreur('dateFin', !dateValide(fin) ? 'Veuillez saisir la date de fin du contrat.'
          : (dateValide(debut) && fin <= debut ? 'La date de fin doit être après la date de début.'
          : (dateValide(debut) && fin > ajouterMois(debut, 99 * 12) ? 'Un contrat ne peut pas dépasser 99 ans.' : ''))) && ok;
      } else {
        ['proprietaire', 'dateFin'].forEach(function (id) { erreur(id, ''); });
      }
      if (v === 'LOCATION') {
        const l = nombre(document.getElementById('loyerAnnuel').value);
        ok = erreur('loyerAnnuel', l === '' ? 'Le loyer annuel est obligatoire.'
          : (isNaN(Number(l)) || Number(l) <= 0 || Number(l) > 1000000 ? 'Le loyer doit être un nombre entre 0 et 1 000 000 DT.' : '')) && ok;
      } else {
        erreur('loyerAnnuel', '');
      }
      if (v === 'KHAMMES' || v === 'METAYAGE') {
        const p = nombre(champPart.value);
        ok = erreur('partProprietaire', p === '' ? 'La part du propriétaire est obligatoire.'
          : (isNaN(Number(p)) || Number(p) < 1 || Number(p) > 99 ? 'La part du propriétaire doit être un pourcentage entre 1 et 99.' : '')) && ok;
      } else {
        erreur('partProprietaire', '');
      }
      if (!ok) ev.preventDefault();
    });
  }

  /* ---------------- Certification bio ---------------- */
  const formBio = document.getElementById('formBio');
  if (formBio) {
    formBio.addEventListener('submit', function (ev) {
      let ok = true;
      const o = document.getElementById('organisme').value.trim();
      ok = erreur('organisme', o === '' ? 'L’organisme certificateur est obligatoire.'
        : (o.length < 2 || o.length > 100 ? 'L’organisme doit contenir entre 2 et 100 caractères.'
        : (!/^[\p{L}0-9\s'’\-.&]+$/u.test(o) ? 'L’organisme ne doit contenir que des lettres, chiffres, espaces, points ou tirets.' : ''))) && ok;
      const n = document.getElementById('numeroCertificat').value.trim();
      ok = erreur('numeroCertificat', n !== '' && !/^[A-Za-z0-9\-\/ ]{3,50}$/.test(n)
        ? 'Le numéro de certificat contient 3 à 50 lettres, chiffres, tirets ou barres obliques.' : '') && ok;
      const d = document.getElementById('dateDebutConversion').value;
      ok = erreur('dateDebutConversion', !dateValide(d) ? 'Veuillez saisir la date de début de conversion.'
        : (d > aujourdhui ? 'La conversion ne peut pas commencer dans le futur.'
        : (d < '1990-01-01' ? 'La date de début de conversion n’est pas réaliste.' : ''))) && ok;
      const duree = parseInt(document.getElementById('dureeConversionMois').value, 10);
      ok = erreur('dureeConversionMois', [24, 36].indexOf(duree) === -1 ? 'Veuillez choisir la durée de conversion.' : '') && ok;
      const exp = document.getElementById('dateExpiration').value;
      ok = erreur('dateExpiration', exp === '' ? ''
        : (!dateValide(exp) ? 'Date d’expiration invalide.'
        : (dateValide(d) && exp <= ajouterMois(d, duree) ? 'Le certificat doit expirer après la fin de la conversion.' : ''))) && ok;
      if (!ok) ev.preventDefault();
    });
  }
});
