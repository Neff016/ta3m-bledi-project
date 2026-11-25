/*
 * Carte du formulaire Terrain
 *  - mode « point » : un clic place le terrain (latitude / longitude)
 *  - mode « parcelle » : chaque clic ajoute un coin ; « Terminer » ferme la parcelle,
 *    calcule la surface en hectares et place le point au centre
 * La surface est recalculée par le serveur (TerrainController::surfaceHectares) : même formule.
 */
(function () {
  'use strict';

  // Limites de la Tunisie (mêmes valeurs que TerrainController::enTunisie)
  function enTunisie(lat, lng) {
    return lat >= 30.2 && lat <= 37.6 && lng >= 7.5 && lng <= 11.7;
  }

  // Surface d'un polygone sur la sphère terrestre, en hectares
  function surfaceHectares(points) {
    const R = 6378137, rad = Math.PI / 180;
    let aire = 0;
    for (let i = 0; i < points.length; i++) {
      const p1 = points[i], p2 = points[(i + 1) % points.length];
      aire += (p2[1] - p1[1]) * rad * (2 + Math.sin(p1[0] * rad) + Math.sin(p2[0] * rad));
    }
    return Math.round(Math.abs(aire * R * R / 2) / 10000 * 100) / 100;
  }

  window.TerrainCarte = { enTunisie: enTunisie, surfaceHectares: surfaceHectares };   // utilisé par les tests

  document.addEventListener('DOMContentLoaded', function () {
    const zone = document.getElementById('carteSaisie');
    if (!zone) return;
    const champLat = document.getElementById('latitude');
    const champLng = document.getElementById('longitude');
    const champPoly = document.getElementById('polygone');
    const champSurface = document.getElementById('superficie');
    const champWilaya = document.getElementById('localisation');
    const aide = document.getElementById('aideCarte');
    const btnTerminer = document.getElementById('btnTerminerParcelle');

    if (!window.L) {
      zone.innerHTML = '<p class="tb-carte-indispo"><i class="bi bi-wifi-off"></i> ' +
        tj('Carte indisponible : vérifiez votre connexion Internet.') + '</p>';
      return;
    }

    const centres = JSON.parse(zone.dataset.centres || '{}');
    const carte = L.map(zone).setView([34.8, 9.6], 6);
    const plan = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' });
    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
      { maxZoom: 19, attribution: 'Imagerie © Esri' }).addTo(carte);   // satellite : plus facile pour dessiner un champ
    L.control.layers({ 'Satellite': satellite, 'Plan': plan }).addTo(carte);

    let mode = 'point';
    let marqueur = null;
    let coins = [];           // coins en cours de dessin
    let trace = null;         // ligne ou polygone affiché

    function majAide(texte) { aide.innerHTML = '<i class="bi bi-info-circle"></i> ' + texte; }

    function placerPoint(lat, lng) {
      lat = Math.round(lat * 1e6) / 1e6;
      lng = Math.round(lng * 1e6) / 1e6;
      champLat.value = lat;
      champLng.value = lng;
      if (marqueur) marqueur.setLatLng([lat, lng]);
      else marqueur = L.marker([lat, lng]).addTo(carte);
    }

    function dessiner(ferme) {
      if (trace) carte.removeLayer(trace);
      trace = null;
      if (coins.length === 0) return;
      trace = ferme
        ? L.polygon(coins, { color: '#f4d35e', weight: 3, fillOpacity: 0.3 }).addTo(carte)
        : L.polyline(coins, { color: '#f4d35e', weight: 3, dashArray: '6' }).addTo(carte);
    }

    function changerMode(nouveau) {
      mode = nouveau;
      document.querySelectorAll('.tb-outil[data-mode]').forEach(function (b) {
        b.classList.toggle('actif', b.dataset.mode === mode);
      });
      btnTerminer.hidden = mode !== 'parcelle';
      majAide(mode === 'point' ? zone.dataset.texteAidePoint : zone.dataset.texteAideParcelle);
      if (mode === 'parcelle') {
        coins = [];
        champPoly.value = '';
        dessiner(false);
      }
    }

    // ---------- Valeurs existantes (modification) ----------
    let parcelle = null;
    try { parcelle = champPoly.value ? JSON.parse(champPoly.value) : null; } catch (e) { parcelle = null; }
    if (Array.isArray(parcelle) && parcelle.length >= 3) {
      coins = parcelle;
      dessiner(true);
      carte.fitBounds(coins, { padding: [20, 20] });
    }
    if (champLat.value !== '' && champLng.value !== '') {
      placerPoint(parseFloat(champLat.value), parseFloat(champLng.value));
      if (!parcelle) carte.setView([parseFloat(champLat.value), parseFloat(champLng.value)], 15);
    } else if (champWilaya.value && centres[champWilaya.value]) {
      carte.setView(centres[champWilaya.value], 11);
    }

    // ---------- Actions ----------
    document.querySelectorAll('.tb-outil[data-mode]').forEach(function (b) {
      b.addEventListener('click', function () { changerMode(b.dataset.mode); });
    });

    carte.on('click', function (ev) {
      const lat = ev.latlng.lat, lng = ev.latlng.lng;
      if (!enTunisie(lat, lng)) {
        majAide('<span class="text-danger">' + tj('Ce point est en dehors de la Tunisie.') + '</span>');
        return;
      }
      if (mode === 'point') {
        placerPoint(lat, lng);
        majAide(tj('Position enregistrée : {lat}, {lng}', { lat: lat.toFixed(5), lng: lng.toFixed(5) }));
      } else {
        coins.push([Math.round(lat * 1e6) / 1e6, Math.round(lng * 1e6) / 1e6]);
        dessiner(false);
        majAide(tj('{n} coin(s) placé(s). Cliquez sur « Terminer la parcelle » quand le contour est fini.', { n: coins.length }));
      }
    });

    btnTerminer.addEventListener('click', function () {
      if (coins.length < 3) {
        majAide('<span class="text-danger">' + tj('Placez au moins 3 coins pour former une parcelle.') + '</span>');
        return;
      }
      dessiner(true);
      const ha = surfaceHectares(coins);
      champPoly.value = JSON.stringify(coins);
      champSurface.value = ha;
      const lat = coins.reduce(function (s, c) { return s + c[0]; }, 0) / coins.length;
      const lng = coins.reduce(function (s, c) { return s + c[1]; }, 0) / coins.length;
      placerPoint(lat, lng);
      majAide(zone.dataset.texteSurface.replace('{ha}', String(ha).replace('.', ',')));
      changerModeSansEffacer('point');
    });

    function changerModeSansEffacer(nouveau) {
      mode = nouveau;
      document.querySelectorAll('.tb-outil[data-mode]').forEach(function (b) {
        b.classList.toggle('actif', b.dataset.mode === mode);
      });
      btnTerminer.hidden = true;
    }

    document.getElementById('btnEffacerCarte').addEventListener('click', function () {
      coins = [];
      dessiner(false);
      champPoly.value = '';
      champLat.value = '';
      champLng.value = '';
      if (marqueur) { carte.removeLayer(marqueur); marqueur = null; }
      changerMode('point');
    });

    document.getElementById('btnMaPosition').addEventListener('click', function () {
      if (!navigator.geolocation) {
        majAide(tj('La géolocalisation n’est pas disponible sur ce navigateur.'));
        return;
      }
      navigator.geolocation.getCurrentPosition(function (pos) {
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        if (!enTunisie(lat, lng)) {
          majAide('<span class="text-danger">' + tj('Votre position est en dehors de la Tunisie.') + '</span>');
          return;
        }
        carte.setView([lat, lng], 16);
        if (mode === 'point') placerPoint(lat, lng);
      }, function () {
        majAide(tj('Position refusée ou introuvable.'));
      }, { enableHighAccuracy: true, timeout: 10000 });
    });

    champWilaya.addEventListener('change', function () {
      if (!marqueur && coins.length === 0 && centres[champWilaya.value]) {
        carte.setView(centres[champWilaya.value], 11);
      }
    });

    // La carte est parfois dans un conteneur qui change de taille
    setTimeout(function () { carte.invalidateSize(); }, 300);
  });
})();
