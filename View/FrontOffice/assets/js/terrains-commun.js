/*
 * Outils communs du module Terrains et Récoltes
 *  - tj('texte') : traduction côté JavaScript (dictionnaire window.TRAD rempli en arabe)
 *  - data-confirm : demande de confirmation avant d'envoyer un formulaire de suppression
 *  - formatNombre : 1234.5 → « 1 234,5 »
 */
window.tj = function (texte, valeurs) {
  let resultat = (window.TRAD && window.TRAD[texte]) || texte;
  Object.keys(valeurs || {}).forEach(function (cle) {
    resultat = resultat.split('{' + cle + '}').join(valeurs[cle]);
  });
  return resultat;
};

window.formatNombre = function (n, decimales) {
  return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: decimales === undefined ? 1 : decimales });
};

document.addEventListener('submit', function (event) {
  const form = event.target;
  if (form.dataset && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
    event.stopImmediatePropagation();
  }
}, true);
