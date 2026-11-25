/*
 * Dessine un QR code en SVG dans chaque <div class="qr" data-lien="…">
 * (bibliothèque QRCode de Kazuhiko Arase, licence MIT, dans assets/vendor/qrcode)
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.qr[data-lien]').forEach(function (zone) {
    const qr = new QRCodeLib.QRCode(-1, QRCodeLib.niveaux.M);   // taille automatique, correction d'erreur moyenne (15 %)
    qr.addData(zone.dataset.lien);
    qr.make();
    const n = qr.getModuleCount(), marge = 4, total = n + marge * 2;
    let chemin = '';
    for (let y = 0; y < n; y++) {
      for (let x = 0; x < n; x++) {
        if (qr.isDark(y, x)) chemin += 'M' + (x + marge) + ',' + (y + marge) + 'h1v1h-1z';
      }
    }
    zone.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + total + ' ' + total + '" shape-rendering="crispEdges" role="img" aria-label="QR code">' +
      '<rect width="100%" height="100%" fill="#fff"/><path d="' + chemin + '" fill="#000"/></svg>';
  });
});
