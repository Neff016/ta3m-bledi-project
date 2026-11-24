(function () {
  const cfg = window.TOUNES;
  if (!cfg || !cfg.timeline.length) return;

  const T = cfg.timeline;
  const base = T[0];
  const $ = (id) => document.getElementById(id);
  const fmt = (n, d = 1) => Number(n).toLocaleString('fr-FR', { minimumFractionDigits: d, maximumFractionDigits: d });
  const signed = (n, d = 1) => (n >= 0 ? '+' : '−') + fmt(Math.abs(n), d);
  const esc = (s) => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

  const pills = Array.from(document.querySelectorAll('.year-pill'));
  const range = $('yearRange');
  let current = cfg.startIndex;
  let typing = null;

  function stopTyping() {
    if (typing) { clearInterval(typing); typing = null; }
  }

  // Updates the whole page for the selected year
  function render(i) {
    current = i;
    const p = T[i];

    $('yearLabel').textContent = p.annee;
    const badge = $('stressBadge');
    badge.textContent = 'Stress hydrique : ' + p.stressHydrique;
    badge.className = 'badge-pill ' + p.stressClass;

    $('tempValue').textContent = fmt(p.temperatureMoy) + ' °C';
    $('tempDelta').textContent = i === 0
      ? 'Année de référence'
      : signed(p.temperatureMoy - base.temperatureMoy) + ' °C par rapport à ' + base.annee;

    $('rainValue').textContent = fmt(p.pluviometrie, 0) + ' mm';
    const pct = base.pluviometrie > 0 ? (p.pluviometrie - base.pluviometrie) / base.pluviometrie * 100 : 0;
    $('rainDelta').textContent = i === 0
      ? 'Année de référence'
      : signed(pct, 0) + ' % par rapport à ' + base.annee;

    $('projDesc').textContent = p.description || '';
    $('crops').innerHTML = p.cultures.length
      ? p.cultures.map(c => '<span class="chip">' + esc(c) + '</span>').join('')
      : '<span class="muted">Aucune culture menacée à cet horizon.</span>';

    pills.forEach((b, k) => b.classList.toggle('active', k === i));
    range.value = i;

    stopTyping();
    $('explainBox').classList.add('d-none');
  }

  pills.forEach((b, k) => b.addEventListener('click', () => render(k)));
  range.addEventListener('input', () => render(Number(range.value)));

  // "Demander une explication": asks the server, then types the answer
  $('btnExplain').addEventListener('click', async () => {
    const year = T[current].annee;
    const box = $('explainBox');
    const out = $('explainText');

    stopTyping();
    box.classList.remove('d-none');
    out.textContent = 'Analyse en cours…';

    try {
      const res = await fetch(cfg.explainUrl + '&annee=' + year);
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Erreur');
      if (T[current].annee !== year) return;   // the visitor changed year meanwhile

      const text = data.texte;
      out.textContent = '';
      let k = 0;
      typing = setInterval(() => {
        out.textContent += text.charAt(k++);
        if (k >= text.length) stopTyping();
      }, 12);
    } catch (e) {
      out.textContent = "Impossible d'obtenir l'explication pour le moment.";
    }
  });

  // Small map of the region
  if (window.L && $('regionMap')) {
    const m = L.map('regionMap', { scrollWheelZoom: false }).setView([cfg.ville.lat, cfg.ville.lng], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 12, attribution: '&copy; OpenStreetMap'
    }).addTo(m);
    L.marker([cfg.ville.lat, cfg.ville.lng]).addTo(m).bindPopup(esc(cfg.ville.nom));
  }

  // Evolution chart
  if (window.Chart && $('chart')) {
    new Chart($('chart'), {
      type: 'bar',
      data: {
        labels: T.map(p => p.annee),
        datasets: [
          { type: 'line', label: 'Température (°C)', data: T.map(p => p.temperatureMoy),
            borderColor: '#3f7a55', backgroundColor: '#3f7a55', tension: 0.3, yAxisID: 'y' },
          { type: 'bar', label: 'Pluie (mm)', data: T.map(p => p.pluviometrie),
            backgroundColor: 'rgba(127,169,127,.45)', yAxisID: 'y1' }
        ]
      },
      options: {
        responsive: true,
        scales: {
          y:  { position: 'left',  title: { display: true, text: '°C' } },
          y1: { position: 'right', title: { display: true, text: 'mm' }, grid: { drawOnChartArea: false } }
        }
      }
    });
  }

  render(cfg.startIndex);
})();