# Vehicle Log

Self-hosted PHP/MySQL odometer log for small groups and families. This is an **initial V1 starter**, not a production-hardened release.

## Requirements
- PHP 8.1+ with PDO MySQL
- MariaDB 5.5.68 compatibility targeted; newer supported MariaDB/MySQL strongly recommended, with CREATE TABLE permissions
- Apache 2.4+ with `.htaccess` support (or equivalent Nginx deny rules)
- HTTPS strongly recommended

Timestamps are currently stored and displayed in UTC. Local timezone presentation is planned.

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
- No odometer validation against previous entries, editing, matching journeys, or reporting yet.
- No migration runner/version tracking beyond the initial SQL schema.
- MariaDB 5.5 is end-of-life and not secure for new public deployments. Compatibility is provided for legacy hosting, not as an endorsement.
- The setup wizard is not atomic: if schema creation succeeds but config writing fails, manually clean the partially installed database before retrying.
- `.htaccess` protections require Apache to honour overrides; configure equivalent deny rules for Nginx. **Do not assume a renamed directory is secure.**
- The `remember` mode is identification, not authentication. Avoid it for sensitive use cases.

## Git
`dontlookhere/config.php` is intentionally ignored. Do not commit credentials. Add a licence before publishing the repository publicly.
