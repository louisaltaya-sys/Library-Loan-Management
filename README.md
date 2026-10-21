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
6. Open `http://localhost/Midterm_Altaya/register.php` and register a staff
   account.

For local XAMPP, the defaults are `127.0.0.1:3306`, database
`library_management`, user `root`, and an empty password.

## Database configuration

Set these environment variables in the hosting provider's application settings.
Do not commit database credentials to GitHub.

| Variable | Default | Description |
| --- | --- | --- |
| `DB_HOST` | `127.0.0.1` | MySQL server hostname |
| `DB_PORT` | `3306` | MySQL server port |
| `DB_NAME` | `library_management` | Database name |
| `DB_USER` | `root` | Database username |
| `DB_PASSWORD` | (empty) | Database password |

Import `users_table.sql` into the configured database before the first request.
The hosting provider must allow the PHP app to connect to that MySQL server.

## Sessions and transactions

- **Sessions:** successful logins create a database session record. Authenticated
  requests refresh its activity and expiry; logout marks it as ended. The
  dashboard lists recent sessions.
- **Transactions:** creating, updating, and deleting a loan each writes a
  transaction record in the same database transaction as the loan change. The
  dashboard lists recent transaction records.
