(function () {
  "use strict";
  document.querySelectorAll("form[data-validated-form]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      if (!form.reportValidity()) event.preventDefault();
    });
  });
  document.querySelectorAll("form[data-confirm]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      if (!window.confirm(form.dataset.confirm || "Confirmer cette suppression ?")) event.preventDefault();
    });
  });
})();
