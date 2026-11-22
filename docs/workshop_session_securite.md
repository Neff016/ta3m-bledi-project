# Workshop: Sessions and PHP Security

This project follows the "Sessions et Sécurité PHP" workshop.

## Architecture

- MVC: Model, View, Controller
- `config/database.php`: secure PDO connection
- `models/UserModel.php`: user attributes and getters/setters
- `controllers/AuthController.php`: authentication, authorization, validation, sessions and CSRF
- `views/`: HTML output
- `index.php`: application routing

## Security

- Prepared PDO statements prevent SQL injection.
- `password_hash()` and `password_verify()` protect passwords.
- `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` prevents XSS.
- Session cookies use strict mode, HttpOnly and SameSite=Lax.
- Session IDs are regenerated after login.
- Sessions expire after 1800 seconds of inactivity.
- CSRF tokens are verified for sensitive POST actions.
- HTTP headers protect against clickjacking and MIME sniffing.
- `requireRole('admin')` protects backoffice and user management actions.
