# Vehicle Log

Self-hosted PHP/MySQL odometer log for small groups and families. This is an **V1.1 development build**, not a production-hardened release.

## Requirements
- PHP 8.1+ with PDO MySQL
- MariaDB 5.5.68 compatibility targeted; newer supported MariaDB/MySQL strongly recommended, with CREATE TABLE permissions
- Apache 2.4+ with `.htaccess` support (or equivalent Nginx deny rules)
- HTTPS strongly recommended

Timestamps are currently stored and displayed in UTC. Local timezone presentation is planned.

## Recording journeys
Start Trip is the default. An unfinished trip appears on the main page for its original driver to finish. Alternatively choose Enter completed trip to supply both odometer readings at once. Each journey is stored as one row in `trips`. Only one unfinished trip per vehicle is allowed. Existing individual `readings` are kept and shown separately in History; they are not automatically paired. Times are recorded in UTC.

## Appearance
Administrators can choose Forest Green (default), Ocean Blue, Slate & Orange, or Burgundy & Cream in Admin > Appearance. The selection applies to the entire installation and is stored in the existing `settings` table. No schema change is needed.

## Installation
1. Upload the entire `vehicle-log` folder, e.g. to `https://example.org/vehicle-log/`.
2. Create an empty MySQL database and database user.
3. Confirm `dontlookhere/` is writable by PHP during setup, but **not web-accessible**. Deny HTTP access to `dontlookhere/`, `includes/`, and `migrations/`.
4. Visit `/vehicle-log/`; the setup wizard will ask for database details and an admin login.
5. Log in at `/vehicle-log/admin/` and add vehicles and drivers.
6. Choose driver access mode. In PIN/password modes, set each driver's secret when creating them.
7. After setup, remove write permission from `dontlookhere/` where practical.

## Important limitations of this starter
- PIN and password modes currently use the same driver selection screen; username is stored but username entry is not yet implemented.
- No rate limiting for driver or administrator login yet. Do not deploy publicly without it.
- Odometer entries are checked for non-negative values and finish >= start, but not against all historical vehicle readings. No trip editing, reporting or historical timestamp entry yet.
- Automatic database upgrades run at the first request after new files are uploaded. Database ALTER permissions are required.
- MariaDB 5.5 is end-of-life and not secure for new public deployments. Compatibility is provided for legacy hosting, not as an endorsement.
- The setup wizard is not atomic: if schema creation succeeds but config writing fails, manually clean the partially installed database before retrying.
- `.htaccess` protections require Apache to honour overrides; configure equivalent deny rules for Nginx. **Do not assume a renamed directory is secure.**
- The `remember` mode is identification, not authentication. Avoid it for sensitive use cases.

## Git
`dontlookhere/config.php` is intentionally ignored. Do not commit credentials. Review licensing before distributing modified versions.

### Upgrading an existing installation to V1.1 (trips)

Back up your database, then upload the updated files together. On the next page visit, Vehicle Log checks whether the `trip_description` column exists, adds it if missing, and records schema version 2, then creates the `trips` table and records schema version 3. No manual SQL is required. The database user needs ALTER TABLE permission. Concurrent visits are protected by a database advisory lock. If an upgrade fails, the application shows a maintenance error and logs the details on the server. New installations apply all three migration SQL files during setup.
