/*
 * Carte de plusieurs terrains (Mes terrains, Back Office)
 * <div id="carteTerrains" data-points='[{"id":1,"nom":"…","lat":36.4,"lng":10.1,"exact":true,"polygone":[[lat,lng],…],"info":"…"}]'>
 * Leaflet + OpenStreetMap (plan) / Esri (satellite)
 */
document.addEventListener('DOMContentLoaded', function () {
  'use strict';
  const zone = document.getElementById('carteTerrains');
  if (!zone) return;

  if (!window.L) {
    zone.innerHTML = '<p class="tb-carte-indispo"><i class="bi bi-wifi-off"></i> ' +
      (window.tj ? tj('Carte indisponible : vérifiez votre connexion Internet.') : 'Carte indisponible.') + '</p>';
    return;
  }

  const points = JSON.parse(zone.dataset.points || '[]');
  const lienDetails = zone.dataset.lienDetails || 'detailsTerrain.php?id=';
  const texteDetails = zone.dataset.texteDetails || 'Voir les détails';
  const texteApprox = zone.dataset.texteApprox || 'Position approximative';

  const carte = L.map(zone, { scrollWheelZoom: false }).setView([34.8, 9.6], 6);   // Tunisie
  const plan = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap'
  }).addTo(carte);
  const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, attribution: 'Imagerie © Esri'
  });
  L.control.layers({ 'Plan': plan, 'Satellite': satellite }).addTo(carte);

  // Échappe le texte avant de le mettre dans le HTML de la bulle
  function texte(s) {
    const div = document.createElement('div');
    div.textContent = s == null ? '' : String(s);
    return div.innerHTML;
  }

  const limites = [];
  points.forEach(function (p) {
    const bulle = '<strong>' + texte(p.nom) + '</strong><br>' + texte(p.info) +
      (p.proprietaire ? '<br><i class="bi bi-person"></i> ' + texte(p.proprietaire) : '') +
      (p.exact ? '' : '<br><small><em>' + texte(texteApprox) + '</em></small>') +
      (p.id ? '<br><a href="' + lienDetails + encodeURIComponent(p.id) + '">' + texte(texteDetails) + '</a>' : '');

    if (Array.isArray(p.polygone) && p.polygone.length >= 3) {
      const forme = L.polygon(p.polygone, { color: '#2f5d3a', weight: 2, fillColor: '#6a994e', fillOpacity: 0.35 }).addTo(carte);
      forme.bindPopup(bulle);
      p.polygone.forEach(function (c) { limites.push(c); });
    } else if (p.exact) {
      L.marker([p.lat, p.lng]).addTo(carte).bindPopup(bulle);
      limites.push([p.lat, p.lng]);
    } else {
      L.circleMarker([p.lat, p.lng], { radius: 9, color: '#c9a227', fillColor: '#c9a227', fillOpacity: 0.5, dashArray: '4' })
        .addTo(carte).bindPopup(bulle);
      limites.push([p.lat, p.lng]);
    }
  });

  if (limites.length === 1) {
    carte.setView(limites[0], 13);
  } else if (limites.length > 1) {
    carte.fitBounds(limites, { padding: [30, 30], maxZoom: 14 });
  }
});
