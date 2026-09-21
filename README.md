# StaffSync

StaffSync is a local web application for staff scheduling, attendance, leave, notifications, and staff management.

## Quick start

1. Copy the local config template:
   ```bash
   cp config.php.example config.php
   ```
2. Update your local values in `config.php`:
   - database credentials
   - SMTP sender details for email delivery
3. Start MySQL:
   ```bash
   sudo /opt/lampp/lampp startmysql
   ```
4. Build the schema and seed sample data:
   ```bash
   php database/migrate.php --seed
   ```
5. Open the app from the project public folder.

## Local secrets

Real credentials must stay local and never be committed to Git.

- `config.php` is gitignored and should contain your real local settings.
- `config.php.example` is the shared template that teammates can copy.
- For email delivery, set the Gmail sender account in `SMTP_USERNAME` and use a Gmail App Password in `SMTP_APP_PASSWORD`.

Do not commit real SMTP usernames, passwords, or personal database credentials.

## Seeded demo accounts

A list of built-in seeded users is available in [docs/users.md](docs/users.md).

## Project docs

Use the docs index in [docs/README.md](docs/README.md) for the main project documentation map.

## Important notes

- Database migrations are managed in `database/migrations/`.
- Seed data is managed in `database/seeds/`.
- The app uses a shared schema across the team; run migrations after pulling updates.

## Useful commands

```bash
php database/migrate.php
php database/migrate.php --status
php database/migrate.php --seed
php database/migrate.php --fresh --seed
```
