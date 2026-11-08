# MediStock

Hospital pharmacy stock and expiry management application built with PHP, PDO and MySQL.

## Requirements

- PHP 8.1 or newer (PHP 8.2/8.3/8.4 recommended)
- PHP extensions: `pdo_mysql`, `session`, `filter`, `json`
- MySQL 8.0.16 or newer; MySQL 8.0.30+ recommended
- InnoDB enabled
- A web server whose document root points to the `public/` directory

The application uses UTF-8 helper functions that do not require `mbstring`.

## Project structure

```text
MediStock/
├── app/
│   ├── auth.php          # Login state, account checks and role authorization
│   ├── bootstrap.php     # Loads configuration and initializes each request
│   ├── db.php            # Creates the PDO/MySQL connection
│   ├── helpers.php       # Escaping, validation, CSRF, flash messages, procedures
│   └── layout.php        # Shared navigation, header and footer
├── database/
│   ├── medistock_schema.sql  # Tables, indexes, constraints, triggers, procedures, views
│   └── create_app_user.sql   # Restricted MySQL account for the PHP application
├── public/               # Only this directory should be exposed by the web server
│   ├── assets/            # CSS and JavaScript
│   ├── index.php          # Dashboard
│   ├── medicines.php      # Medicine catalogue
│   ├── batches.php        # Batch inventory
│   ├── suppliers.php      # Supplier directory
│   ├── patients.php       # Patient registry
│   ├── receive.php        # Receive stock (stored procedure)
│   ├── dispense.php       # Dispense medicine (stored procedure)
│   ├── reports.php        # Stock, expiry, dispensing and movement reports
│   ├── login.php
│   └── logout.php
├── scripts/
│   ├── check_environment.php
│   └── create_admin.php
├── config.example.php
└── README.md
```

## Installation (fresh database)

1. Extract the project to your development directory.
2. Open MySQL Workbench or a MySQL terminal using an administrator account.
3. Import `database/medistock_schema.sql`.
4. Change the placeholder password in `database/create_app_user.sql`, then execute that file as the MySQL administrator.
5. Copy `config.example.php` to `config.php` in the project root.
6. Update the database username and password in `config.php` to match the restricted account created in step 4.
7. From the project root, run:

   ```bash
   php scripts/check_environment.php
   ```

8. Create the first administrator:

   ```bash
   php scripts/create_admin.php
   ```

9. Start the PHP development server:

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

10. Open `http://127.0.0.1:8000/login.php`.

The web server document root must be `public/`. Do not expose the project root, `app/`, `database/`, `scripts/`, or `config.php` through the web server.

## Database installation safety

`medistock_schema.sql` creates the database if it does not exist and creates tables with `IF NOT EXISTS`. It replaces the project's views, stored procedures and triggers, but it does **not** drop tables or delete existing table data.

Important: `CREATE TABLE IF NOT EXISTS` is not a schema migration tool. If you already have an older MediStock database, do not assume this script upgrades its existing tables. Back up that database and install into a separate empty database, or prepare and test a dedicated migration first. Never experiment on the only copy of real patient or pharmacy data.

The schema seeds only the medicine categories. It does not insert fake patients, fake suppliers, demo staff accounts or placeholder password hashes. Create a real administrator using the CLI script.

## Important stock rules

- Stock is stored per batch, including its expiry date.
- New batches start with zero stock. Receipts are processed by `sp_receive_stock`.
- Dispensing is processed by `sp_dispense_medicine`, which allocates stock using FEFO (First Expiry, First Out).
- A dispensing request that cannot be fulfilled from unexpired stock is rolled back as a whole.
- Stock movements are append-only. Corrections are represented by compensating movements, not edits to old movements.
- `sp_cancel_dispensing` requires an explicit choice about whether the medicine is physically returned and eligible for reuse. Pass `1` only after that has been verified. Pass `0` when no usable stock is being returned.
- The current web interface does not expose a dispensing-cancellation screen; cancellation is available at the database procedure level and should be added to the UI only with an approved return workflow.
- The database account used by the web application should not have general `UPDATE` or `DELETE` privileges on stock, purchase, dispensing or audit tables.

## Roles

- `ADMIN`: catalogue, suppliers, patients, receiving, dispensing and reports.
- `PHARMACIST`: medicine catalogue, patients, dispensing and reports.
- `STOREKEEPER`: medicine catalogue, suppliers, batch inventory and receiving.

Authorization is checked by PHP and key stock operations are additionally validated by the database procedures and triggers.

## Environment check

Run:

```bash
php scripts/check_environment.php
```

This checks PHP's MySQL driver, the MySQL version, and the presence of all expected tables, views and stored procedures. Trigger definitions are installed by the SQL script and should be reviewed by a MySQL administrator. It does not replace functional tests of every database operation. See `TEST_STATUS.md` for exactly which checks were and were not run while preparing this package.

## Testing before real use

Use a separate test database and test accounts. Verify at least:

1. Duplicate medicine codes and patient numbers are rejected.
2. Invalid and future patient dates of birth are rejected.
3. Expired batches cannot be received or dispensed.
4. Stock receipt creates a purchase, purchase item, batch balance and movement record together.
5. FEFO dispensing consumes the earliest-expiring unexpired batches first.
6. Insufficient stock leaves no partial dispensing or stock movement.
7. Two concurrent dispensing requests cannot oversell a batch.
8. Movement, purchase-item and dispensing-item history cannot be silently updated or deleted.
9. Inactive staff accounts cannot continue using an existing browser session.
10. Role restrictions work when a user manually enters a protected URL.

## Security notes

- Keep `config.php` private; it is excluded by `.gitignore`.
- Change any database password that has ever been shared or committed.
- Use HTTPS in production.
- Use a dedicated MySQL account rather than `root` for the web application.
- Back up the database regularly and test restoration.
- Restrict access to patient information according to the hospital's policies and applicable privacy law.
- The application is a project baseline, not a certified clinical or pharmacy system. Complete operational, security, backup and regulatory reviews before real patient use.
