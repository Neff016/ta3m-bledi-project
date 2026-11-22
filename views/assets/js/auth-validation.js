(function () {
    "use strict";

    var registerForm = document.querySelector('form[action="index.php?action=register"]');
    if (registerForm) {
        var password = registerForm.elements.password;
        var confirm = registerForm.elements.password_confirm;

        function validatePasswordConfirmation() {
            confirm.setCustomValidity(
                password.value === confirm.value ? "" : "Les deux mots de passe ne correspondent pas."
            );
        }

        password.addEventListener("input", validatePasswordConfirmation);
        confirm.addEventListener("input", validatePasswordConfirmation);
        registerForm.addEventListener("submit", validatePasswordConfirmation);
    }

    var loginForm = document.querySelector('form[action="index.php?action=login"]');
    if (loginForm) {
        loginForm.addEventListener("submit", function (event) {
            var email = loginForm.elements.email.value.trim();
            var password = loginForm.elements.password.value;
            if (email === "" || password === "") {
                event.preventDefault();
            }
        });
    }
})();
