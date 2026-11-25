/* Contrôle des filtres de dates (Back Office Récoltes) : « du » doit être avant « au » */
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('filtresRecoltes');
  if (!form) return;
  form.addEventListener('submit', function (ev) {
    const du = form.elements.du.value, au = form.elements.au.value;
    const zone = document.getElementById('erreurFiltres');
    zone.textContent = '';
    if (du && au && du > au) {
      zone.textContent = 'La date « du » doit être avant la date « au ». ';
      ev.preventDefault();
    }
  });
});
