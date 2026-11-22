# Mail - Validation Requirements

## Users

- User registration and login
- Administrator and regular-user roles
- Complete user CRUD
- Server-side authorization
- Administrators cannot delete their own account

## Security

- Prepared PDO queries
- Password hashing
- XSS escaping
- CSRF protection
- Secure session configuration
- Session expiration
- Access control on sensitive routes

## Input Validation

All form inputs are validated on the server. JavaScript is an additional client-side validation layer.

## Installation

Use XAMPP, install the project in `htdocs`, start Apache and MySQL, import `taam_bledi.sql`, and open the root `index.php`.
