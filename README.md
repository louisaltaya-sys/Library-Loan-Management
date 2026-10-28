# Library Loan Management

A PHP and MySQL application for managing library book loans. Registered staff
can sign in, create and edit loan records, and review a log of changes and
login sessions.

## Requirements

- PHP 8.0 or later with PDO MySQL enabled
- MySQL 5.7 or later (or a compatible MariaDB release)
- Apache or another PHP-capable web server

## Local setup with XAMPP

1. Copy this project into the web server document root.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Create a database named `library_management` in phpMyAdmin.
4. Import [`users_table.sql`](./users_table.sql) into that database. The script
   creates the user, session, transaction, and book-loan tables.
5. If your local MySQL settings differ from the defaults, set the database
   environment variables listed below in the Apache/PHP environment.
6. Open `http://localhost/midterm/login.php` and use the demo account below, or
   create a staff account.

For local XAMPP, the defaults are `127.0.0.1:3306`, database
`library_management`, user `root`, and an empty password.

## Database configuration

The app first checks for a private `config.local.php` in the project folder. If
it is absent, it uses these environment variables. Never commit real database
credentials to GitHub.

| Variable | Default | Description |
| --- | --- | --- |
| `DB_HOST` | `127.0.0.1` | MySQL server hostname |
| `DB_PORT` | `3306` | MySQL server port |
| `DB_NAME` | `library_management` | Database name |
| `DB_USER` | `root` | Database username |
| `DB_PASSWORD` | (empty) | Database password |

Import `users_table.sql` into the configured database before the first request.
The SQL script also creates a demo account if one does not already exist:
username `demo`, password `Demo123!`. This account is intended for local demos;
change or remove it before deploying the application publicly. The hosting
provider must allow the PHP app to connect to that MySQL server.

An optional `config.local.php` should return an array with `host`, `port`,
`name`, `user`, and `password` keys. For example:

```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'library_management',
    'user' => 'root',
    'password' => '',
];
```

### InfinityFree setup

1. Create a MySQL database in the InfinityFree control panel and import
   `users_table.sql` using its phpMyAdmin link.
2. Create `config.local.php` on the hosting account and return an array with
   the database host, name, username, and password shown in the InfinityFree
   control panel. Use the exact MySQL host shown there; it is not necessarily
   `localhost`.
3. Upload the PHP files and the `classes` folder into the domain's `htdocs`
   folder. Do not upload `config.local.php` to a public GitHub repository.
4. Open the domain's `register.php` page to create the first staff account.

`config.local.php` is excluded from Git by `.gitignore`. Keep a private backup
of this file; it is needed when deploying the app.

## Sessions and transactions

- **Sessions:** successful logins create a database session record. Authenticated
  requests refresh its activity and expiry; logout marks it as ended. The
  dashboard lists recent sessions.
- **Transactions:** creating, updating, and deleting a loan each writes a
  transaction record in the same database transaction as the loan change. The
  dashboard lists recent transaction records.
