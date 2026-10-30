# MediStock — Hospital Pharmacy Stock & Expiry Management

A responsive HTML/CSS/PHP interface for the existing MySQL database `hospital_pharmacy_db`.

## What is included

- Responsive pharmacy dashboard with stock, low-stock, expiry and recent movement summaries.
- Medicine catalogue, supplier directory and patient registry.
- Batch inventory with expiry status.
- Stock receipt through `sp_receive_stock`.
- FEFO dispensing through `sp_dispense_medicine`.
- Reports for stock, expired batches, near-expiry batches, dispensing and the immutable movement ledger.
- PDO prepared statements, server-side validation, output escaping, CSRF protection, secure session settings, role checks, password hashing and controlled exception handling.
- The database remains the authority for integrity: foreign keys, UNIQUE/CHECK constraints, triggers, stored procedures, transactions and views.

## Database compatibility

This web application targets the SQL schema previously created for this project:

- Database: `hospital_pharmacy_db`
- MySQL: 8.0.16+ (InnoDB)
- Tables: `medicine_categories`, `suppliers`, `staff_users`, `patients`, `medicines`, `medicine_batches`, `purchases`, `purchase_items`, `dispensings`, `dispensing_items`, `stock_movements`
- Views: `vw_medicine_stock`, `vw_low_stock_medicines`, `vw_expired_batches`, `vw_near_expiry_batches`, `vw_dispensing_summary`
- Procedures: `sp_receive_stock`, `sp_dispense_medicine`, `sp_cancel_dispensing`

The PHP code does not recreate or weaken the database rules. It invokes the stock procedures so that the stock ledger and balances stay consistent.

## Requirements

- PHP 8.1+ with `pdo_mysql`, `mbstring` and standard session support.
- MySQL 8.0.16+.
- Apache/Nginx or PHP's built-in development server.

## Installation

1. Import the project's existing `hospital_pharmacy_stock_expiry.sql` into MySQL. It creates the database, tables, triggers, procedures, views and sample records.
2. Extract this ZIP into your web project directory.
3. Copy `config.example.php` to `config.php`.
4. Edit `config.php` with the MySQL host, database user and password. The database name is already set to `hospital_pharmacy_db`.
5. Create a first administrator from the project root:

   ```bash
   php scripts/create_admin.php
   ```

   Choose a strong password of at least 12 characters. The script is CLI-only and stores a PHP `password_hash()` result. Do not use the SQL sample account hashes as login passwords; they are placeholders.
6. Point your web server document root to the `public/` directory. This is important: `app/`, `config.php` and `scripts/` should not be publicly accessible.
7. Open the application in a browser.

For a local-only development run from the project root:

```bash
php -S 127.0.0.1:8000 -t public
```

Then open `http://127.0.0.1:8000`.

## Suggested first-use sequence

1. Sign in with the administrator created in step 5.
2. Add medicine categories in MySQL (the supplied SQL script seeds three categories).
3. Add suppliers if not already seeded.
4. Add medicines.
5. Register patients.
6. Receive stock against a supplier, medicine, batch and future expiry date.
7. Dispense medicine to a registered patient.
8. Review expiry, stock and movement reports.

## Security and integrity notes

- All database values from users are passed through PDO prepared statements. Dynamic SQL identifiers are not built from user input.
- All displayed user/database content is HTML-escaped with `htmlspecialchars`.
- Forms use per-session CSRF tokens and POST for state-changing operations.
- Login uses `password_verify`, session ID regeneration, HTTP-only and SameSite cookies, and active-account checks.
- Role checks are performed on the server for sensitive actions.
- Server validation checks type, length, numeric range, date format, email format and code formats. MySQL constraints/triggers/procedures remain the final authority.
- Database exceptions are logged server-side; users receive safe, actionable messages rather than raw SQL errors.
- Do not expose PHP error display in production. Configure PHP logging, HTTPS, backups and least-privilege MySQL accounts.
- Use a dedicated database account rather than `root` in production. Grant only the required SELECT/INSERT privileges and EXECUTE on the approved stock procedures. Because the current UI adds master records, grant only the relevant INSERT privileges for those tables.
- Protect patient information under your hospital's privacy and access policies. This is an academic project starter, not a certified clinical information system.

## Important database note

The SQL schema's sample staff records contain `REPLACE_WITH_SECURE_HASH` placeholders. They are not usable passwords. Create the real administrator with `scripts/create_admin.php`; deactivate or remove any placeholder sample staff accounts before a real deployment.

## Troubleshooting

- **Database unavailable:** confirm MySQL is running, `config.php` credentials are correct, and the schema was imported.
- **Unknown procedure/view/table:** import the matching SQL schema completely into `hospital_pharmacy_db`.
- **Blank/500 page:** check the PHP/Apache error log; ensure `pdo_mysql` and `mbstring` are enabled.
- **Permission denied:** verify the logged-in user's role and the database grants.
- **Duplicate value:** medicine codes, supplier names/emails, patient numbers and supplier invoice numbers have uniqueness rules.
