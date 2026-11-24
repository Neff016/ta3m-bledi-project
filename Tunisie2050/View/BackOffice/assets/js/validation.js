// Client-side validation for every <form data-validate>.
// Rules come from standard HTML attributes (required, type, minlength, maxlength, min, max, step, pattern);
// data-msg on a field gives the message shown when its pattern fails.
// The server re-validates everything: this script is only there for a fast, friendly feedback.
(function () {
  function message(el) {
    const v = el.value;
    if (el.required && v.trim() === '') return 'Ce champ est obligatoire.';
    if (v === '') return '';
    if (el.minLength > 0 && v.length < el.minLength) return 'Minimum ' + el.minLength + ' caractères.';
    if (el.maxLength > 0 && v.length > el.maxLength) return 'Maximum ' + el.maxLength + ' caractères.';
    const d = el.validity;
    if (d.typeMismatch) return el.type === 'email' ? 'Adresse e-mail invalide.' : 'Valeur invalide.';
    if (d.badInput || d.stepMismatch) return 'Nombre invalide.';
    if (d.rangeUnderflow) return 'La valeur minimale est ' + el.min + '.';
    if (d.rangeOverflow) return 'La valeur maximale est ' + el.max + '.';
    if (d.patternMismatch) return el.dataset.msg || 'Format invalide.';
    return '';
  }

  function show(el, text) {
    let box = el.parentElement.querySelector('.field-error');
    if (!text) {
      el.classList.remove('is-invalid');
      if (box) box.remove();
      return;
    }
    el.classList.add('is-invalid');
    if (!box) {
      box = document.createElement('div');
      box.className = 'field-error';
      el.parentElement.appendChild(box);
    }
    box.textContent = text;
  }

  function check(el) {
    const text = message(el);
    show(el, text);
    return text === '';
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    const fields = form.querySelectorAll('input:not([type=hidden]), textarea, select');
    let tried = false;

    fields.forEach(function (el) {
      el.addEventListener('blur', function () { if (tried || el.value !== '') check(el); });
      el.addEventListener('input', function () { if (tried) check(el); });
    });

    form.addEventListener('submit', function (ev) {
      tried = true;
      let firstBad = null;
      fields.forEach(function (el) {
        if (!check(el) && !firstBad) firstBad = el;
      });
      if (firstBad) {
        ev.preventDefault();
        firstBad.focus();
      }
    });
  });
})();
