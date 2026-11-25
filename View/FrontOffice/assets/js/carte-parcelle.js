/*
 * Page Parcelle : dessin du contour sur la carte + contrôle de saisie
 * (mêmes règles que ParcelleController::valider() ; la surface est recalculée par le serveur)
 * Utilise les fonctions de carte-saisie.js : window.TerrainCarte.surfaceHectares() et enTunisie()
 */
document.addEventListener('DOMContentLoaded', function () {
  'use strict';
  const t = window.tj || function (s) { return s; };
  const form = document.getElementById('formParcelle');
  if (!form) return;
  const champPoly = document.getElementById('polygone');
  const champSurface = document.getElementById('superficie');
  const zone = document.getElementById('carteParcelle');
  const aide = document.getElementById('aideCarte');
  const outils = window.TerrainCarte;

  function erreur(id, message) {
    const champ = document.getElementById(id), boite = document.getElementById('erreur-' + id);
    if (boite) boite.textContent = message ? t(message) : '';
    if (champ) champ.classList.toggle('is-invalid', !!message);
    return !message;
  }

  /* ---------------- Contrôle de saisie ---------------- */
  form.addEventListener('submit', function (ev) {
    let ok = true;
    const nom = document.getElementById('nom').value.trim();
    ok = erreur('nom', nom === '' ? 'Le nom de la parcelle est obligatoire.'
      : (nom.length < 2 || nom.length > 50 ? 'Le nom doit contenir entre 2 et 50 caractères.'
      : (!/^[\p{L}0-9\s'’\-]+$/u.test(nom) ? 'Le nom ne doit contenir que des lettres, chiffres, espaces, apostrophes ou tirets.' : ''))) && ok;
    const s = champSurface.value.trim().replace(',', '.');
    const disponible = parseFloat(form.dataset.disponible || '0');
    ok = erreur('superficie', s === '' ? 'La surface est obligatoire.'
      : (isNaN(Number(s)) || Number(s) <= 0 ? 'La surface doit être un nombre supérieur à 0.'
      : (Number(s) > disponible * 1.01 + 0.01 ? t('Il reste seulement {d} ha sur ce terrain.').replace('{d}', String(disponible).replace('.', ',')) : ''))) && ok;
    if (!ok) ev.preventDefault();
  });

  /* ---------------- Carte ---------------- */
  if (!zone) return;
  if (!window.L) {
    zone.innerHTML = '<p class="tb-carte-indispo"><i class="bi bi-wifi-off"></i> ' + t('Carte indisponible : vérifiez votre connexion Internet.') + '</p>';
    return;
  }
  const carte = L.map(zone).setView(JSON.parse(zone.dataset.centre || '[34.8,9.6]'), 15);
  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    { maxZoom: 19, attribution: 'Imagerie © Esri' }).addTo(carte);

  const limites = [];
  const terrain = JSON.parse(zone.dataset.terrain || 'null');
  if (Array.isArray(terrain)) {
    L.polygon(terrain, { color: '#ffffff', weight: 2, dashArray: '6', fillOpacity: 0.05, interactive: false }).addTo(carte);
    terrain.forEach(function (c) { limites.push(c); });
  }
  JSON.parse(zone.dataset.reperes || '[]').forEach(function (r) {
    L.polygon(r.polygone, { color: '#9aa5a0', weight: 1, fillOpacity: 0.35, interactive: false }).addTo(carte)
      .bindTooltip(r.nom, { permanent: true, direction: 'center', className: 'tb-etiquette-carte' });
  });

  let coins = [];
  let trace = null;
  let dessin = false;
  const btnDessiner = document.getElementById('btnDessiner');
  const btnTerminer = document.getElementById('btnTerminerParcelle');

  function majAide(html) { aide.innerHTML = '<i class="bi bi-info-circle"></i> ' + html; }
  function dessiner(ferme) {
    if (trace) carte.removeLayer(trace);
    trace = null;
    if (!coins.length) return;
    trace = ferme
      ? L.polygon(coins, { color: '#f4d35e', weight: 3, fillOpacity: 0.35 }).addTo(carte)
      : L.polyline(coins, { color: '#f4d35e', weight: 3, dashArray: '6' }).addTo(carte);
  }

  // Contour existant (modification)
  try { coins = champPoly.value ? JSON.parse(champPoly.value) : []; } catch (e) { coins = []; }
  if (coins.length >= 3) {
    dessiner(true);
    coins.forEach(function (c) { limites.push(c); });
  } else {
    coins = [];
  }
  if (limites.length) carte.fitBounds(limites, { padding: [30, 30], maxZoom: 18 });

  btnDessiner.addEventListener('click', function () {
    dessin = true;
    coins = [];
    champPoly.value = '';
    dessiner(false);
    btnDessiner.classList.add('actif');
    btnTerminer.hidden = false;
    majAide(zone.dataset.texteAide);
  });

  carte.on('click', function (ev) {
    if (!dessin) return;
    const lat = ev.latlng.lat, lng = ev.latlng.lng;
    if (outils && !outils.enTunisie(lat, lng)) {
      majAide('<span class="text-danger">' + t('Ce point est en dehors de la Tunisie.') + '</span>');
      return;
    }
    coins.push([Math.round(lat * 1e6) / 1e6, Math.round(lng * 1e6) / 1e6]);
    dessiner(false);
    majAide(t('{n} coin(s) placé(s). Cliquez sur « Terminer la parcelle » quand le contour est fini.').replace('{n}', coins.length));
  });

  btnTerminer.addEventListener('click', function () {
    if (coins.length < 3) {
      majAide('<span class="text-danger">' + t('Placez au moins 3 coins pour former une parcelle.') + '</span>');
      return;
    }
    dessin = false;
    dessiner(true);
    champPoly.value = JSON.stringify(coins);
    const ha = outils ? outils.surfaceHectares(coins) : '';
    champSurface.value = ha;
    btnTerminer.hidden = true;
    btnDessiner.classList.remove('actif');
    majAide(zone.dataset.texteSurface.replace('{ha}', String(ha).replace('.', ',')));
  });

  document.getElementById('btnEffacerCarte').addEventListener('click', function () {
    dessin = false;
    coins = [];
    champPoly.value = '';
    dessiner(false);
    btnTerminer.hidden = true;
    btnDessiner.classList.remove('actif');
  });

  setTimeout(function () { carte.invalidateSize(); }, 300);
});
