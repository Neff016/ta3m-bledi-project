/*
 * Page Contrôle qualité : contrôle de saisie (mêmes règles que ControleQualiteController::valider())
 * et aperçu en direct du chiffre d'affaires : kg × (1 − déchets) × prix × classe × bio
 */
document.addEventListener('DOMContentLoaded', function () {
  'use strict';
  const form = document.getElementById('formQualite');
  if (!form) return;
  const t = window.tj || function (s) { return s; };
  const maintenant = new Date();
  const aujourdhui = maintenant.getFullYear() + '-' + String(maintenant.getMonth() + 1).padStart(2, '0') + '-' + String(maintenant.getDate()).padStart(2, '0');
  const mesure = form.dataset.mesure;

  function erreur(id, message) {
    const champ = document.getElementById(id), zone = document.getElementById('erreur-' + id);
    if (zone) zone.textContent = message ? t(message) : '';
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }
  function nombre(id) {
    const champ = document.getElementById(id);
    return champ ? champ.value.trim().replace(',', '.') : '';
  }
  function classe() {
    const c = form.querySelector('input[name="classe"]:checked');
    return c ? c.value : '';
  }
  function dateValide(v) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false;
    const [a, m, j] = v.split('-').map(Number);
    const d = new Date(a, m - 1, j);
    return d.getFullYear() === a && d.getMonth() === m - 1 && d.getDate() === j;
  }

  /* ---------- Aperçu du prix ---------- */
  const revenu = document.getElementById('apercuRevenu');
  const detail = document.getElementById('apercuDetail');
  const coefs = JSON.parse(form.dataset.coefs || '{}');
  function majApercu() {
    if (!revenu || form.dataset.prix === '') return;
    const d = Number(nombre('tauxDechets') || 0);
    if (isNaN(d) || d < 0 || d > 100 || !coefs[classe()]) { revenu.textContent = '—'; detail.textContent = ''; return; }
    const kg = Number(form.dataset.quantite) * (1 - d / 100);
    const bio = form.dataset.bio === '1' ? 1.25 : 1;
    const total = kg * Number(form.dataset.prix) * coefs[classe()] * bio;
    revenu.textContent = Math.round(total).toLocaleString('fr-FR') + ' DT';
    detail.textContent = t('{kg} kg vendables × {p} DT × {c}').replace('{kg}', Math.round(kg).toLocaleString('fr-FR'))
      .replace('{p}', Number(form.dataset.prix).toLocaleString('fr-FR')).replace('{c}', String(coefs[classe()] * bio).replace('.', ','));
  }
  form.addEventListener('input', majApercu);
  form.addEventListener('change', majApercu);
  majApercu();

  /* ---------- Contrôle de saisie ---------- */
  form.addEventListener('submit', function (ev) {
    let ok = true;
    ok = erreur('classe', ['EXTRA', 'I', 'II'].indexOf(classe()) === -1 ? 'Veuillez choisir une classe.' : '') && ok;
    const cal = document.getElementById('calibre').value.trim();
    ok = erreur('calibre', cal !== '' && !/^[\p{L}0-9\s+\-\/.,]{1,30}$/u.test(cal)
      ? 'Le calibre contient au plus 30 lettres ou chiffres (ex : 57-67 mm, A, 3).' : '') && ok;
    const d = nombre('tauxDechets');
    ok = erreur('tauxDechets', d === '' ? 'Le taux de déchets est obligatoire (0 si aucun).'
      : (isNaN(Number(d)) || Number(d) < 0 || Number(d) > 100 ? 'Le taux de déchets doit être un pourcentage entre 0 et 100.' : '')) && ok;
    if (mesure === 'humidite') {
      const h = nombre('humidite');
      ok = erreur('humidite', h === '' ? 'L’humidité du grain est obligatoire pour une céréale.'
        : (isNaN(Number(h)) || Number(h) < 0 || Number(h) > 40 ? 'L’humidité doit être un pourcentage entre 0 et 40.' : '')) && ok;
    }
    if (mesure === 'brix') {
      const b = nombre('brix');
      ok = erreur('brix', b !== '' && (isNaN(Number(b)) || Number(b) < 0 || Number(b) > 40) ? 'Le taux de sucre doit être entre 0 et 40 °Brix.' : '') && ok;
    }
    const date = document.getElementById('dateControle').value;
    ok = erreur('dateControle', !dateValide(date) ? 'Veuillez saisir la date du contrôle.'
      : (date > aujourdhui ? 'La date du contrôle ne peut pas être dans le futur.'
      : (date < form.dataset.plantation ? 'Le contrôle ne peut pas être avant la plantation.' : ''))) && ok;
    ok = erreur('remarque', document.getElementById('remarque').value.trim().length > 255 ? '255 caractères maximum.' : '') && ok;
    if (!ok) ev.preventDefault();
  });
});
