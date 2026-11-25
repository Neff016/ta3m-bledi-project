/*
 * Graphiques en SVG, sans bibliothèque (fonctionnent aussi hors ligne)
 *
 * <div class="tb-graphique" data-type="barres|ligne|anneau|barresH"
 *      data-libelles='["Jan","Fév"]'
 *      data-series='[{"nom":"2026","valeurs":[10,20],"couleur":"#008a17"}]'
 *      data-unite="kg" data-titre="Production par mois"></div>
 *
 * Chaque graphique a : une info-bulle au survol, une légende (2 séries ou plus)
 * et un tableau « Voir les données » (accessibilité).
 * Couleurs : palette catégorielle validée (daltonisme), dans un ordre fixe.
 */
(function () {
  'use strict';

  const PALETTE = ['#257de0', '#f47230', '#00b894', '#f2a600', '#ee7fac', '#008a17', '#6a4ce2', '#ec4647'];
  const GRIS = '#9aa5a0';
  const NS = 'http://www.w3.org/2000/svg';
  const t = function (s, v) { return window.tj ? window.tj(s, v) : s; };

  function el(nom, attributs, parent) {
    const n = document.createElementNS(NS, nom);
    Object.keys(attributs || {}).forEach(function (k) { n.setAttribute(k, attributs[k]); });
    if (parent) parent.appendChild(n);
    return n;
  }

  function nombre(n, dec) {
    return Number(n).toLocaleString('fr-FR', { maximumFractionDigits: dec === undefined ? 1 : dec });
  }

  // Graduation « jolie » de l'axe : 0, 50, 100, 150…
  function graduations(max) {
    if (max <= 0) return [0, 1];
    const brut = max / 4;
    const puissance = Math.pow(10, Math.floor(Math.log10(brut)));
    const pas = [1, 2, 2.5, 5, 10].map(function (m) { return m * puissance; }).find(function (p) { return p >= brut; });
    const ticks = [];
    for (let v = 0; v <= max + pas * 0.001; v += pas) ticks.push(v);
    if (ticks[ticks.length - 1] < max) ticks.push(ticks[ticks.length - 1] + pas);
    return ticks;
  }

  // Info-bulle partagée
  let bulle = null;
  function montrerBulle(ev, html) {
    if (!bulle) {
      bulle = document.createElement('div');
      bulle.className = 'tb-bulle';
      document.body.appendChild(bulle);
    }
    bulle.innerHTML = html;
    bulle.hidden = false;
    const x = Math.min(ev.clientX + 14, window.innerWidth - bulle.offsetWidth - 8);
    bulle.style.left = x + 'px';
    bulle.style.top = (ev.clientY + 14) + 'px';
  }
  function cacherBulle() { if (bulle) bulle.hidden = true; }

  function echapper(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  // Barre aux coins arrondis seulement en haut (ou à droite pour les barres horizontales)
  function cheminBarre(x, y, l, h, horizontale) {
    const r = Math.min(4, horizontale ? h / 2 : l / 2, horizontale ? l : h);
    if (horizontale) {
      return 'M' + x + ',' + y + 'h' + (l - r) + 'a' + r + ',' + r + ' 0 0 1 ' + r + ',' + r + 'v' + (h - 2 * r) +
        'a' + r + ',' + r + ' 0 0 1 ' + (-r) + ',' + r + 'h' + (r - l) + 'z';
    }
    return 'M' + x + ',' + (y + h) + 'v' + (r - h) + 'a' + r + ',' + r + ' 0 0 1 ' + r + ',' + (-r) + 'h' + (l - 2 * r) +
      'a' + r + ',' + r + ' 0 0 1 ' + r + ',' + r + 'v' + (h - r) + 'z';
  }

  function legende(zone, series) {
    if (series.length < 2) return;
    const div = document.createElement('div');
    div.className = 'tb-legende';
    series.forEach(function (s) {
      const item = document.createElement('span');
      item.innerHTML = '<i style="background:' + s.couleur + '"></i>' + echapper(s.nom);
      div.appendChild(item);
    });
    zone.appendChild(div);
  }

  function tableau(zone, libelles, series, unite) {
    const details = document.createElement('details');
    details.className = 'tb-donnees';
    let html = '<summary>' + t('Voir les données') + '</summary><table class="table table-sm"><thead><tr><th></th>';
    series.forEach(function (s) { html += '<th>' + echapper(s.nom) + '</th>'; });
    html += '</tr></thead><tbody>';
    libelles.forEach(function (l, i) {
      html += '<tr><th>' + echapper(l) + '</th>';
      series.forEach(function (s) { html += '<td>' + nombre(s.valeurs[i] || 0, 2) + ' ' + echapper(unite) + '</td>'; });
      html += '</tr>';
    });
    details.innerHTML = html + '</tbody></table>';
    zone.appendChild(details);
  }

  /* ---------- Barres verticales (une ou plusieurs séries) ---------- */
  // Largeur réelle du graphique (le texte garde sa taille au lieu d'être réduit)
  function largeur(zone) { return Math.max(320, Math.round(zone.clientWidth || 640)); }

  function barres(zone, libelles, series, unite) {
    const L = largeur(zone), H = 280, m = { g: 52, d: 12, h: 14, b: 34 };
    const svg = el('svg', { viewBox: '0 0 ' + L + ' ' + H, role: 'img', class: 'tb-svg' }, zone);
    const max = Math.max.apply(null, series.flatMap(function (s) { return s.valeurs; }).concat([0]));
    const ticks = graduations(max);
    const haut = ticks[ticks.length - 1];
    const y = function (v) { return m.h + (H - m.h - m.b) * (1 - v / haut); };

    ticks.forEach(function (v) {
      el('line', { x1: m.g, x2: L - m.d, y1: y(v), y2: y(v), class: 'tb-grille' }, svg);
      el('text', { x: m.g - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'tb-axe' }, svg).textContent = nombre(v, 0);
    });

    const largeurGroupe = (L - m.g - m.d) / libelles.length;
    const largeurBarre = Math.max(3, Math.min(34, (largeurGroupe - 8) / series.length - 2));
    libelles.forEach(function (lib, i) {
      const debut = m.g + i * largeurGroupe + (largeurGroupe - series.length * (largeurBarre + 2)) / 2;
      series.forEach(function (s, j) {
        const v = s.valeurs[i] || 0;
        const h = Math.max(v > 0 ? 1 : 0, y(0) - y(v));
        const x = debut + j * (largeurBarre + 2);
        if (h > 0) el('path', { d: cheminBarre(x, y(0) - h, largeurBarre, h, false), fill: s.couleur }, svg);
        // zone de survol plus grande que la barre
        const cible = el('rect', { x: x - 1, y: m.h, width: largeurBarre + 2, height: H - m.h - m.b, fill: 'transparent' }, svg);
        cible.addEventListener('mousemove', function (ev) {
          montrerBulle(ev, '<strong>' + echapper(lib) + '</strong><br>' + (series.length > 1 ? echapper(s.nom) + ' : ' : '') +
            nombre(v, 2) + ' ' + echapper(unite));
        });
        cible.addEventListener('mouseleave', cacherBulle);
      });
      if (libelles.length <= 12 || i % 2 === 0) {
        el('text', { x: m.g + i * largeurGroupe + largeurGroupe / 2, y: H - m.b + 18, 'text-anchor': 'middle', class: 'tb-axe' }, svg).textContent = lib;
      }
    });
    el('line', { x1: m.g, x2: L - m.d, y1: y(0), y2: y(0), class: 'tb-base' }, svg);
  }

  /* ---------- Barres horizontales (classements) ---------- */
  function barresH(zone, libelles, series, unite) {
    const s = series[0];
    const L = largeur(zone), ligne = 34, m = { g: 150, d: 80, h: 6, b: 6 };
    const H = m.h + m.b + ligne * libelles.length;
    const svg = el('svg', { viewBox: '0 0 ' + L + ' ' + H, role: 'img', class: 'tb-svg' }, zone);
    const max = Math.max.apply(null, s.valeurs.concat([0.0001]));
    libelles.forEach(function (lib, i) {
      const v = s.valeurs[i] || 0;
      const yy = m.h + i * ligne;
      const l = Math.max(v > 0 ? 2 : 0, (L - m.g - m.d) * v / max);
      el('text', { x: m.g - 10, y: yy + ligne / 2 + 4, 'text-anchor': 'end', class: 'tb-axe tb-axe-fort' }, svg).textContent =
        lib.length > 20 ? lib.slice(0, 19) + '…' : lib;
      if (l > 0) el('path', { d: cheminBarre(m.g, yy + 7, l, ligne - 14, true), fill: (s.couleurs && s.couleurs[i]) || s.couleur }, svg);
      el('text', { x: m.g + l + 8, y: yy + ligne / 2 + 4, class: 'tb-axe' }, svg).textContent = nombre(v, 2) + ' ' + unite;
      const cible = el('rect', { x: 0, y: yy, width: L, height: ligne, fill: 'transparent' }, svg);
      cible.addEventListener('mousemove', function (ev) {
        montrerBulle(ev, '<strong>' + echapper(lib) + '</strong><br>' + nombre(v, 2) + ' ' + echapper(unite));
      });
      cible.addEventListener('mouseleave', cacherBulle);
    });
  }

  /* ---------- Courbe (évolution) ---------- */
  function ligne(zone, libelles, series, unite) {
    const L = largeur(zone), H = 260, m = { g: 52, d: 16, h: 14, b: 34 };
    const svg = el('svg', { viewBox: '0 0 ' + L + ' ' + H, role: 'img', class: 'tb-svg' }, zone);
    const toutes = series.flatMap(function (s) { return s.valeurs; });
    let min = Math.min.apply(null, toutes.concat([0]));
    const max = Math.max.apply(null, toutes.concat([0]));
    const ticks = min < 0 ? [min, 0, max] : graduations(max);
    const bas = Math.min(min, ticks[0]), haut = Math.max(max, ticks[ticks.length - 1]) || 1;
    const x = function (i) { return m.g + (L - m.g - m.d) * (libelles.length === 1 ? 0.5 : i / (libelles.length - 1)); };
    const y = function (v) { return m.h + (H - m.h - m.b) * (1 - (v - bas) / (haut - bas)); };

    ticks.forEach(function (v) {
      el('line', { x1: m.g, x2: L - m.d, y1: y(v), y2: y(v), class: 'tb-grille' }, svg);
      el('text', { x: m.g - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'tb-axe' }, svg).textContent = nombre(v, haut <= 2 ? 2 : 0);
    });
    const pas = Math.ceil(libelles.length / 8);
    libelles.forEach(function (lib, i) {
      if (i % pas === 0) el('text', { x: x(i), y: H - m.b + 18, 'text-anchor': 'middle', class: 'tb-axe' }, svg).textContent = lib;
    });
    series.forEach(function (s) {
      const d = s.valeurs.map(function (v, i) { return (i ? 'L' : 'M') + x(i) + ',' + y(v); }).join('');
      el('path', { d: d, fill: 'none', stroke: s.couleur, 'stroke-width': 2, 'stroke-linejoin': 'round' }, svg);
      s.valeurs.forEach(function (v, i) {
        el('circle', { cx: x(i), cy: y(v), r: 4, fill: s.couleur, stroke: '#fff', 'stroke-width': 2 }, svg);
      });
    });
    // Ligne verticale + info-bulle au survol
    const repere = el('line', { y1: m.h, y2: H - m.b, class: 'tb-repere', visibility: 'hidden' }, svg);
    const cible = el('rect', { x: m.g, y: m.h, width: L - m.g - m.d, height: H - m.h - m.b, fill: 'transparent' }, svg);
    cible.addEventListener('mousemove', function (ev) {
      const boite = svg.getBoundingClientRect();
      const xs = (ev.clientX - boite.left) * L / boite.width;
      const i = Math.max(0, Math.min(libelles.length - 1, Math.round((xs - m.g) / ((L - m.g - m.d) / Math.max(1, libelles.length - 1)))));
      repere.setAttribute('x1', x(i));
      repere.setAttribute('x2', x(i));
      repere.setAttribute('visibility', 'visible');
      montrerBulle(ev, '<strong>' + echapper(libelles[i]) + '</strong>' + series.map(function (s) {
        return '<br><i class="tb-pastille" style="background:' + s.couleur + '"></i>' + echapper(s.nom) + ' : ' + nombre(s.valeurs[i], 2) + ' ' + echapper(unite);
      }).join(''));
    });
    cible.addEventListener('mouseleave', function () { repere.setAttribute('visibility', 'hidden'); cacherBulle(); });
  }

  /* ---------- Anneau (répartition) ---------- */
  function anneau(zone, libelles, series, unite) {
    let valeurs = series[0].valeurs.slice(), noms = libelles.slice();
    // Au-delà de 7 parts : regroupées dans « Autres »
    if (noms.length > 7) {
      const reste = valeurs.slice(7).reduce(function (a, b) { return a + b; }, 0);
      valeurs = valeurs.slice(0, 7).concat([reste]);
      noms = noms.slice(0, 7).concat([t('Autres')]);
    }
    const total = valeurs.reduce(function (a, b) { return a + b; }, 0) || 1;
    const couleurs = noms.map(function (n, i) { return i < 7 || noms.length <= 7 ? PALETTE[i] : GRIS; });
    const conteneur = document.createElement('div');
    conteneur.className = 'tb-anneau';
    zone.appendChild(conteneur);
    const svg = el('svg', { viewBox: '0 0 200 200', role: 'img', class: 'tb-svg tb-svg-anneau' }, conteneur);
    const R = 90, r = 58, cx = 100, cy = 100;
    let angle = -Math.PI / 2;
    valeurs.forEach(function (v, i) {
      if (v <= 0) return;
      const part = v / total * Math.PI * 2;
      const a1 = angle, a2 = angle + part - (valeurs.length > 1 ? 0.02 : 0);   // petit espace entre les parts
      const grand = a2 - a1 > Math.PI ? 1 : 0;
      const p = function (rr, a) { return (cx + rr * Math.cos(a)) + ',' + (cy + rr * Math.sin(a)); };
      const d = valeurs.filter(function (x) { return x > 0; }).length === 1
        ? 'M' + (cx + R) + ',' + cy + 'A' + R + ',' + R + ' 0 1 1 ' + (cx - R) + ',' + cy + 'A' + R + ',' + R + ' 0 1 1 ' + (cx + R) + ',' + cy +
          'M' + (cx + r) + ',' + cy + 'A' + r + ',' + r + ' 0 1 0 ' + (cx - r) + ',' + cy + 'A' + r + ',' + r + ' 0 1 0 ' + (cx + r) + ',' + cy + 'Z'
        : 'M' + p(R, a1) + 'A' + R + ',' + R + ' 0 ' + grand + ' 1 ' + p(R, a2) + 'L' + p(r, a2) + 'A' + r + ',' + r + ' 0 ' + grand + ' 0 ' + p(r, a1) + 'Z';
      const forme = el('path', { d: d, fill: couleurs[i], 'fill-rule': 'evenodd' }, svg);
      forme.addEventListener('mousemove', function (ev) {
        montrerBulle(ev, '<strong>' + echapper(noms[i]) + '</strong><br>' + nombre(v, 2) + ' ' + echapper(unite) + ' (' + nombre(v / total * 100, 0) + ' %)');
      });
      forme.addEventListener('mouseleave', cacherBulle);
      angle += part;
    });
    el('text', { x: cx, y: cy - 2, 'text-anchor': 'middle', class: 'tb-anneau-total' }, svg).textContent = nombre(total >= 1000 ? total / 1000 : total, 1);
    el('text', { x: cx, y: cy + 18, 'text-anchor': 'middle', class: 'tb-axe' }, svg).textContent = total >= 1000 && unite === 'kg' ? 't' : unite;

    const liste = document.createElement('ul');
    liste.className = 'tb-legende-anneau';
    noms.forEach(function (n, i) {
      liste.innerHTML += '<li><i style="background:' + couleurs[i] + '"></i><span>' + echapper(n) + '</span><strong>' +
        nombre(valeurs[i] / total * 100, 0) + ' %</strong></li>';
    });
    conteneur.appendChild(liste);
  }

  function dessiner(zone) {
    zone.innerHTML = '';
    const libelles = JSON.parse(zone.dataset.libelles || '[]');
    const series = JSON.parse(zone.dataset.series || '[]');
    const unite = zone.dataset.unite || '';
    if (!libelles.length || !series.length || series.every(function (s) { return s.valeurs.every(function (v) { return !v; }); })) {
      zone.innerHTML = '<p class="tb-graphique-vide"><i class="bi bi-bar-chart"></i> ' + t('Pas encore de données à afficher.') + '</p>';
      return;
    }
    series.forEach(function (s, i) { s.couleur = s.couleur || PALETTE[i % PALETTE.length]; });
    const type = zone.dataset.type || 'barres';
    if (type === 'anneau') anneau(zone, libelles, series, unite);
    else if (type === 'barresH') barresH(zone, libelles, series, unite);
    else if (type === 'ligne') ligne(zone, libelles, series, unite);
    else barres(zone, libelles, series, unite);
    if (type !== 'anneau') legende(zone, series);
    tableau(zone, libelles, series, unite);
  }

  window.TBGraphiques = { dessiner: dessiner };
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tb-graphique').forEach(dessiner);
    // Graphiques cachés dans un onglet : dessinés quand l'onglet s'ouvre
    document.addEventListener('shown.bs.tab', function () {
      document.querySelectorAll('.tab-pane.active .tb-graphique').forEach(dessiner);
    });
    // Redessinés si la largeur de la fenêtre change
    let attente = null;
    window.addEventListener('resize', function () {
      clearTimeout(attente);
      attente = setTimeout(function () { document.querySelectorAll('.tb-graphique').forEach(dessiner); }, 250);
    });
  });
})();
