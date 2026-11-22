(function () {
    "use strict";

    var registerForm = document.querySelector('form[action="index.php?action=register"]');
    if (registerForm) {
        registerForm.addEventListener("submit", function (event) {
            var username = registerForm.elements.username.value.trim();
            var email = registerForm.elements.email.value.trim();
            var password = registerForm.elements.password.value;
            var confirm = registerForm.elements.password_confirm.value;
            var validUsername = /^[a-zA-Z0-9_-]{3,50}$/.test(username);
            var validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

            if (!validUsername || !validEmail || password.length < 8 || password !== confirm) {
                event.preventDefault();
            }
        });
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
