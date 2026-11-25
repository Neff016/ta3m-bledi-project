/*
 * Mode hors ligne (PWA)
 *  1. installe le service worker (sw.js) : l'application s'installe sur le téléphone
 *  2. affiche un bandeau quand Internet est coupé
 *  3. page « Ajouter une récolte » sans Internet : la récolte est gardée sur l'appareil (IndexedDB)
 *  4. au retour d'Internet : les récoltes en attente sont envoyées à recolteHorsLigne.php
 */
(function () {
  'use strict';
  const t = function (s, v) { return window.tj ? window.tj(s, v) : s; };

  // ---------- 1. Service worker ----------
  if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('sw.js').catch(function () { /* pas grave : le site marche sans */ });
    });
  }

  // ---------- Petite base IndexedDB « taambledi » / table « recoltes » ----------
  function ouvrirBase() {
    return new Promise(function (resolve, reject) {
      if (!window.indexedDB) { reject(new Error('IndexedDB indisponible')); return; }
      const demande = indexedDB.open('taambledi', 1);
      demande.onupgradeneeded = function () { demande.result.createObjectStore('recoltes', { keyPath: 'id', autoIncrement: true }); };
      demande.onsuccess = function () { resolve(demande.result); };
      demande.onerror = function () { reject(demande.error); };
    });
  }
  function operation(mode, action) {
    return ouvrirBase().then(function (base) {
      return new Promise(function (resolve, reject) {
        const tx = base.transaction('recoltes', mode);
        const req = action(tx.objectStore('recoltes'));
        tx.oncomplete = function () { resolve(req ? req.result : undefined); };
        tx.onerror = function () { reject(tx.error); };
      });
    });
  }
  const enAttente = {
    ajouter: function (r) { return operation('readwrite', function (s) { return s.add(r); }); },
    lister: function () { return operation('readonly', function (s) { return s.getAll(); }); },
    supprimer: function (id) { return operation('readwrite', function (s) { return s.delete(id); }); }
  };
  window.TBHorsLigne = enAttente;   // utilisé par les tests

  // ---------- 2. Bandeau hors ligne ----------
  function majBandeau() {
    const b = document.getElementById('bandeauHorsLigne');
    if (b) b.hidden = navigator.onLine;
  }

  // ---------- Affichage des récoltes en attente (page Mes récoltes) ----------
  function afficherAttente(messageEnPlus) {
    const zone = document.getElementById('recoltesEnAttente');
    if (!zone) return Promise.resolve();
    return enAttente.lister().then(function (liste) {
      if (!liste.length && !messageEnPlus) { zone.hidden = true; return; }
      zone.hidden = false;
      zone.innerHTML = (messageEnPlus ? messageEnPlus + '<br>' : '') + (liste.length
        ? '<i class="bi bi-cloud-upload"></i> ' + t('{n} récolte(s) enregistrée(s) sur cet appareil, en attente d’envoi.', { n: liste.length })
        : '');
    }).catch(function () {});
  }

  // ---------- 4. Synchronisation ----------
  let envoiEnCours = false;
  function synchroniser() {
    if (!navigator.onLine || envoiEnCours) return Promise.resolve();
    envoiEnCours = true;
    const jetonPage = document.querySelector('input[name="csrf_token"]');
    return enAttente.lister().then(function (liste) {
      let envoyees = 0;
      const refusees = [];
      let sessionExpiree = false;
      return liste.reduce(function (promesse, r) {
        return promesse.then(function () {
          if (sessionExpiree) return;
          const donnees = new FormData();
          Object.keys(r.champs).forEach(function (k) { donnees.append(k, r.champs[k]); });
          donnees.set('csrf_token', jetonPage ? jetonPage.value : r.jeton);
          return fetch('recolteHorsLigne.php', { method: 'POST', body: donnees, credentials: 'same-origin' })
            .then(function (rep) {
              return rep.json().then(function (json) { return { statut: rep.status, json: json }; },
                                     function () { return { statut: rep.status, json: null }; });
            })
            .then(function (res) {
              if (res.json && res.json.ok) { envoyees++; return enAttente.supprimer(r.id); }
              if (res.statut === 422) { refusees.push(r.champs.culture + ' (' + Object.values(res.json.erreurs).join(' ') + ')'); return enAttente.supprimer(r.id); }
              sessionExpiree = true;   // pas connecté ou jeton expiré : on garde la récolte pour plus tard
            });
        });
      }, Promise.resolve()).then(function () {
        let message = '';
        if (envoyees) message += '<i class="bi bi-check-circle"></i> ' + t('{n} récolte(s) hors ligne envoyée(s) avec succès.', { n: envoyees }) + ' ';
        if (refusees.length) message += '<i class="bi bi-x-circle"></i> ' + t('Refusée(s) :') + ' ' + refusees.join(' ; ').replace(/[<>&]/g, '') + ' ';
        if (sessionExpiree) message += '<i class="bi bi-person-lock"></i> ' + t('Connectez-vous pour envoyer les récoltes en attente.');
        if (envoyees && document.getElementById('recoltesEnAttente') && !refusees.length && !sessionExpiree) {
          setTimeout(function () { location.reload(); }, 1500);   // affiche les nouvelles récoltes
        }
        return afficherAttente(message);
      });
    }).catch(function () {}).then(function () { envoiEnCours = false; });
  }

  document.addEventListener('DOMContentLoaded', function () {
    majBandeau();
    afficherAttente().then(synchroniser);

    // ---------- 3. Enregistrer une récolte sans Internet ----------
    // Écouteur sur document (phase de remontée) : il passe APRÈS le contrôle de saisie du formulaire
    document.addEventListener('submit', function (ev) {
      const form = ev.target;
      if (form.id !== 'formRecolte' || navigator.onLine || !/ajouterRecolte\.php/.test(location.pathname)) return;
      ev.preventDefault();
      const champs = {};
      ['idTerrain', 'idParcelle', 'culture', 'datePlantation', 'dateRecolte', 'quantite', 'unite'].forEach(function (k) {
        champs[k] = form.elements[k] ? form.elements[k].value : '';
      });
      const jeton = form.elements.csrf_token ? form.elements.csrf_token.value : '';
      enAttente.ajouter({ champs: champs, jeton: jeton, date: new Date().toISOString() }).then(function () {
        const info = document.createElement('div');
        info.className = 'alert alert-success';
        info.innerHTML = '<i class="bi bi-cloud-check"></i> ' + t('Récolte enregistrée sur cet appareil. Elle sera envoyée automatiquement dès le retour d’Internet.') +
          (form.elements.photo && form.elements.photo.files.length ? '<br><small>' + t('La photo n’est pas gardée hors ligne : ajoutez-la ensuite en modifiant la récolte.') + '</small>' : '');
        form.parentNode.insertBefore(info, form);
        form.reset();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }).catch(function () {
        alert(t('Impossible d’enregistrer hors ligne sur ce navigateur.'));
      });
    });
  });

  window.addEventListener('online', function () { majBandeau(); synchroniser(); });
  window.addEventListener('offline', majBandeau);
})();
