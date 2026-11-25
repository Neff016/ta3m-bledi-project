/*
 * Service worker de Taam Bledi (application installable, mode hors ligne)
 *  - fichiers CSS / JS / images : servis depuis le cache, mis à jour en arrière-plan
 *  - pages du module : réseau d'abord ; sans Internet → dernière version enregistrée
 *  - aucune page en cache → page « Vous êtes hors ligne »
 * Les pages sont effacées du cache à la déconnexion (sécurité sur un ordinateur partagé).
 */
const VERSION = 'taambledi-v2';
const CACHE_FICHIERS = VERSION + '-fichiers';
const CACHE_PAGES = VERSION + '-pages';

const A_PRECHARGER = [
  'horsLigne.html',
  'assets/vendor/bootstrap/css/bootstrap.min.css',
  'assets/vendor/bootstrap/css/bootstrap.rtl.min.css',
  'assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
  'assets/vendor/bootstrap-icons/bootstrap-icons.css',
  'assets/vendor/aos/aos.css',
  'assets/vendor/aos/aos.js',
  'assets/vendor/glightbox/js/glightbox.min.js',
  'assets/vendor/swiper/swiper-bundle.min.js',
  'assets/css/main.css',
  'assets/css/terrains.css',
  'assets/css/terrains-avance.css',
  'assets/js/main.js',
  'assets/js/terrains-commun.js',
  'assets/js/hors-ligne.js',
  'assets/js/graphiques.js',
  'assets/js/validation-recolte.js',
  'assets/js/saisie-vocale.js',
  'assets/img/logo.png',
  'assets/img/icone-192.png'
];

// Pages du module consultables hors ligne
const PAGES = ['tableauDeBord.php', 'mesTerrains.php', 'mesRecoltes.php', 'ajouterRecolte.php', 'detailsTerrain.php'];

self.addEventListener('install', function (ev) {
  ev.waitUntil(caches.open(CACHE_FICHIERS).then(function (cache) {
    return Promise.all(A_PRECHARGER.map(function (url) {
      return cache.add(url).catch(function () { /* fichier absent : on continue */ });
    }));
  }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (ev) {
  ev.waitUntil(caches.keys().then(function (noms) {
    return Promise.all(noms.filter(function (n) { return n.indexOf(VERSION) !== 0; }).map(function (n) { return caches.delete(n); }));
  }).then(function () { return self.clients.claim(); }));
});

function nomPage(url) {
  return url.pathname.split('/').pop();
}

self.addEventListener('fetch', function (ev) {
  const requete = ev.request;
  if (requete.method !== 'GET') return;                       // les formulaires (POST) passent toujours par le réseau
  const url = new URL(requete.url);
  if (url.origin !== self.location.origin) return;            // cartes, polices, API : pas de cache ici

  const page = nomPage(url);

  // Déconnexion ou page de connexion : on efface les pages enregistrées
  if (page === 'deconnexion.php' || page === 'connexion.php') {
    ev.waitUntil(caches.delete(CACHE_PAGES));
    return;
  }

  // Pages HTML du module : réseau d'abord
  if (requete.mode === 'navigate') {
    ev.respondWith(fetch(requete).then(function (reponse) {
      if (reponse.ok && reponse.type === 'basic' && !reponse.redirected && PAGES.indexOf(page) !== -1) {
        const copie = reponse.clone();
        caches.open(CACHE_PAGES).then(function (cache) { cache.put(requete, copie); });
      }
      return reponse;
    }).catch(function () {
      return caches.match(requete, { cacheName: CACHE_PAGES }).then(function (enCache) {
        return enCache || caches.match('horsLigne.html');
      });
    }));
    return;
  }

  // Fichiers statiques : cache d'abord, mise à jour en arrière-plan
  if (/\.(css|js|png|jpe?g|webp|gif|svg|woff2?|ttf)$/.test(url.pathname)) {
    ev.respondWith(caches.open(CACHE_FICHIERS).then(function (cache) {
      return cache.match(requete).then(function (enCache) {
        const reseau = fetch(requete).then(function (reponse) {
          if (reponse.ok) cache.put(requete, reponse.clone());
          return reponse;
        }).catch(function () { return enCache || Response.error(); });
        return enCache || reseau;
      });
    }));
  }
});
