# MyApp

MVC authentication project based on the "Sessions and PHP Security" workshop.

## Features

- Registration with `password_hash()`
- Login with `password_verify()`
- Logout and secure session management
- User and administrator roles
- Prepared PDO queries
- CSRF token verification
- XSS output escaping
- HTTP security headers
- User CRUD for administrators
- Server-side and JavaScript input validation

## Structure

- `config/database.php`
- `models/UserModel.php`
- `controllers/AuthController.php`
- `views/`
- `index.php`
- `taam_bledi.sql`
- `docs/`

## XAMPP Installation

1. Copy the project to `C:\xampp\htdocs\ta3m-bledi-project`.
2. Start Apache and MySQL in XAMPP.
3. Create or select the `taam_bledi` database in phpMyAdmin.
4. Import `taam_bledi.sql`.
5. Open `http://localhost/ta3m-bledi-project/index.php`.

## Database Configuration

The PDO configuration is in [config/database.php](config/database.php). The workshop base name is `taam_bledi`, the XAMPP host is `localhost`, and the default user is `root`.

## Run

- Home: `http://localhost/ta3m-bledi-project/index.php?action=home`
- Register: `http://localhost/ta3m-bledi-project/index.php?action=register`
- Login: `http://localhost/ta3m-bledi-project/index.php?action=login`
- User space: `http://localhost/ta3m-bledi-project/index.php?action=dashboard`
- Admin space: `http://localhost/ta3m-bledi-project/index.php?action=admin`
- User CRUD: `http://localhost/ta3m-bledi-project/index.php?action=users`

## Test Accounts

Create a test account through the registration page, then promote it to administrator through phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'test@example.com';
```

Reconnect after changing the role.
