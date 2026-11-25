/*
 * Saisie vocale d'une récolte (derja, arabe ou français)
 * Le navigateur transforme la voix en texte (Web Speech API, Chrome / Edge),
 * puis analyser() retrouve : culture, quantité, unité, terrain et date.
 * Exemple : « zid récolte 200 kilo tmatem terrain Nabeul » → Tomate · 200 kg · Nabeul · Récoltée
 */
(function () {
  'use strict';

  // Mots (derja, arabe, français) → nom de la culture dans le catalogue
  const CULTURES = {
    'Tomate': ['tmatem', 'tamatem', 'tomate', 'tomates', 'طماطم', 'طماطة', 'طماطيم'],
    'Blé dur': ['qamh', 'gamh', 'ble', 'ble dur', 'قمح'],
    'Orge': ['chiir', 'chair', 'chaier', 'orge', 'شعير'],
    'Olivier': ['zitoun', 'zitoune', 'zaytoun', 'olive', 'olives', 'olivier', 'زيتون', 'زيتونة'],
    'Pomme de terre': ['batata', 'pomme de terre', 'pommes de terre', 'بطاطا', 'بطاطس'],
    'Oignon': ['bsal', 'oignon', 'oignons', 'بصل'],
    'Pastèque': ['dellaa', 'dalaa', 'pasteque', 'pasteques', 'دلاع'],
    'Melon': ['bettikh', 'battikh', 'melon', 'melons', 'بطيخ'],
    'Piment': ['felfel', 'filfil', 'piment', 'piments', 'فلفل'],
    'Fève': ['foul', 'fool', 'feve', 'feves', 'فول'],
    'Pois chiche': ['hommos', 'hommes', 'pois chiche', 'pois chiches', 'حمص'],
    'Amandier': ['louz', 'amande', 'amandes', 'لوز'],
    'Grenadier': ['rommen', 'romman', 'grenade', 'grenades', 'رمان'],
    'Dattier': ['tmar', 'dglet', 'deglet', 'datte', 'dattes', 'تمر', 'دقلة'],
    'Agrumes': ['gares', 'qares', 'bordgen', 'orange', 'oranges', 'agrumes', 'قوارص', 'برتقال', 'بردقان'],
    'Menthe': ['nana', 'naana', 'menthe', 'نعناع'],
    'Romarin': ['klil', 'romarin', 'إكليل', 'اكليل'],
    'Luzerne': ['fessa', 'luzerne', 'فصة']
  };
  const UNITES = {
    kg: ['kilo', 'kilos', 'kg', 'kilogramme', 'kilogrammes', 'كيلو', 'كغ', 'كلغ', 'كيلوغرام'],
    quintal: ['quintal', 'quintaux', 'qantar', 'gantar', 'قنطار', 'قناطر'],
    tonne: ['tonne', 'tonnes', 'tonna', 'طن', 'أطنان', 'اطنان']
  };
  const NOMBRES = {
    'un': 1, 'une': 1, 'deux': 2, 'trois': 3, 'quatre': 4, 'cinq': 5, 'six': 6, 'sept': 7, 'huit': 8, 'neuf': 9, 'dix': 10,
    'wahed': 1, 'zouz': 2, 'thletha': 3, 'arbaa': 4, 'khamsa': 5, 'achra': 10,
    'واحد': 1, 'زوز': 2, 'اثنين': 2, 'ثلاثة': 3, 'أربعة': 4, 'خمسة': 5, 'عشرة': 10
  };
  const CENT = ['cent', 'cents', 'mia', 'miya', 'مية', 'مائة', 'ميه'];
  const DEUX_CENTS = ['mitin', 'miteyn', 'ميتين', 'مئتين'];
  const MILLE = ['mille', 'alf', 'الف', 'ألف'];
  const WILAYAS_AR = {
    'Ariana': 'أريانة', 'Béja': 'باجة', 'Ben Arous': 'بن عروس', 'Bizerte': 'بنزرت', 'Gabès': 'قابس', 'Gafsa': 'قفصة',
    'Jendouba': 'جندوبة', 'Kairouan': 'القيروان', 'Kasserine': 'القصرين', 'Kébili': 'قبلي', 'Le Kef': 'الكاف',
    'Mahdia': 'المهدية', 'La Manouba': 'منوبة', 'Médenine': 'مدنين', 'Monastir': 'المنستير', 'Nabeul': 'نابل',
    'Sfax': 'صفاقس', 'Sidi Bouzid': 'سيدي بوزيد', 'Siliana': 'سليانة', 'Sousse': 'سوسة', 'Tataouine': 'تطاوين',
    'Tozeur': 'توزر', 'Tunis': 'تونس', 'Zaghouan': 'زغوان'
  };

  // Minuscules, sans accents, chiffres arabes (٠١٢…) → 0 1 2…
  function normaliser(texte) {
    return String(texte).toLowerCase()
      .replace(/[٠-٩]/g, function (c) { return String(c.charCodeAt(0) - 0x0660); })
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[’'`]/g, ' ').replace(/[,،]/g, ' ').replace(/\s+/g, ' ').trim();
  }

  function contientMot(texte, mot) {
    const m = normaliser(mot).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return new RegExp('(^|[^\\p{L}])' + m + '($|[^\\p{L}])', 'u').test(texte);
  }

  function chercher(texte, dictionnaire) {
    let trouve = null, longueur = 0;
    Object.keys(dictionnaire).forEach(function (cle) {
      dictionnaire[cle].forEach(function (mot) {
        if (contientMot(texte, mot) && mot.length > longueur) { trouve = cle; longueur = mot.length; }
      });
    });
    return trouve;
  }

  function quantite(texte) {
    const mots = texte.split(' ');
    for (let i = 0; i < mots.length; i++) {
      const brut = mots[i].replace(',', '.');
      let n = /^\d+(\.\d+)?$/.test(brut) ? parseFloat(brut) : (NOMBRES[mots[i]] !== undefined ? NOMBRES[mots[i]] : null);
      const suivant = mots[i + 1] || '';
      if (n !== null) {
        if (CENT.indexOf(suivant) !== -1) n *= 100;
        else if (MILLE.indexOf(suivant) !== -1) n *= 1000;
        return n;
      }
      if (DEUX_CENTS.indexOf(mots[i]) !== -1) return 200;
      if (CENT.indexOf(mots[i]) !== -1) return 100;
      if (MILLE.indexOf(mots[i]) !== -1) return 1000;
    }
    return null;
  }

  function dateIso(decalage) {
    const d = new Date();
    d.setDate(d.getDate() + decalage);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }

  /**
   * analyser('zid recolte 200 kilo tmatem terrain nabeul', { terrains: [{id, nom, wilaya}] })
   * → { culture, quantite, unite, idTerrain, dateRecolte }
   */
  function analyser(phrase, contexte) {
    const texte = normaliser(phrase);
    const resultat = {};
    resultat.culture = chercher(texte, CULTURES);
    resultat.quantite = quantite(texte);
    resultat.unite = chercher(texte, UNITES) || (resultat.quantite !== null ? 'kg' : null);
    if (/(^| )(lyoum|lyoum|aujourd hui|اليوم)( |$)/.test(texte)) resultat.dateRecolte = dateIso(0);
    else if (/(^| )(lbereh|lbarah|hier|البارح|امس|أمس)( |$)/.test(texte)) resultat.dateRecolte = dateIso(-1);
    else resultat.dateRecolte = null;

    resultat.idTerrain = null;
    ((contexte && contexte.terrains) || []).forEach(function (t) {
      const nom = normaliser(t.nom), wilaya = normaliser(t.wilaya || '');
      const wilayaAr = WILAYAS_AR[t.wilaya] ? normaliser(WILAYAS_AR[t.wilaya]) : null;
      if (resultat.idTerrain === null && (contientMot(texte, nom) || (wilaya && contientMot(texte, wilaya)) || (wilayaAr && contientMot(texte, wilayaAr)))) {
        resultat.idTerrain = t.id;
      }
    });
    return resultat;
  }

  window.ParserVoix = { analyser: analyser, normaliser: normaliser };
  if (typeof module !== 'undefined') module.exports = window.ParserVoix;   // tests avec Node

  /* ---------------- Interface (page Ajouter / Modifier une récolte) ---------------- */
  if (typeof document === 'undefined') return;
  document.addEventListener('DOMContentLoaded', function () {
    const bouton = document.getElementById('btnMicro');
    if (!bouton) return;
    const t = window.tj || function (s) { return s; };
    const zoneTexte = document.getElementById('texteVoix');
    const Reconnaissance = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Reconnaissance) {
      bouton.disabled = true;
      zoneTexte.textContent = t('La saisie vocale fonctionne avec Google Chrome ou Microsoft Edge.');
      return;
    }

    const terrains = Array.prototype.map.call(document.querySelectorAll('#idTerrain option[value]'), function (o) {
      return { id: o.value, nom: o.textContent.replace(/\(.*\)/, '').trim(), wilaya: o.dataset.wilaya };
    }).filter(function (x) { return x.id !== ''; });

    let ecoute = false, reco = null;
    bouton.addEventListener('click', function () {
      if (ecoute && reco) { reco.stop(); return; }
      reco = new Reconnaissance();
      reco.lang = document.getElementById('langueVoix').value;
      reco.interimResults = false;
      reco.maxAlternatives = 1;
      reco.onstart = function () { ecoute = true; bouton.classList.add('ecoute'); zoneTexte.textContent = t('Je vous écoute…'); };
      reco.onend = function () { ecoute = false; bouton.classList.remove('ecoute'); };
      reco.onerror = function (ev) {
        zoneTexte.textContent = ev.error === 'not-allowed' ? t('Autorisez le micro dans votre navigateur.') : t('Je n’ai pas compris, réessayez.');
      };
      reco.onresult = function (ev) {
        const phrase = ev.results[0][0].transcript;
        const r = analyser(phrase, { terrains: terrains });
        const compris = [];
        if (r.idTerrain) { document.getElementById('idTerrain').value = r.idTerrain; compris.push(t('terrain')); }
        if (r.culture) { document.getElementById('culture').value = r.culture; compris.push(r.culture); }
        if (r.quantite !== null) { document.getElementById('quantite').value = r.quantite; compris.push(r.quantite + ' ' + (r.unite || 'kg')); }
        if (r.unite) document.getElementById('unite').value = r.unite;
        if (r.dateRecolte) document.getElementById('dateRecolte').value = r.dateRecolte;
        zoneTexte.innerHTML = '« ' + phrase.replace(/[<>&]/g, '') + ' » → ' +
          (compris.length ? '<strong>' + compris.join(' · ').replace(/[<>&]/g, '') + '</strong>' : t('rien reconnu'));
      };
      reco.start();
    });
  });
})();
