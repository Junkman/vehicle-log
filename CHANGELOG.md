# Changelog

## 1.1.0 (in development)
- Added an optional trip description to mileage entries and the History table (automatically migrated on the next visit).
- Added automatic database upgrade checks with a concurrency lock and schema version tracking.
- Added administrator-selectable colour schemes: Forest Green, Ocean Blue, Slate & Orange, and Burgundy & Cream.
- Stored the chosen theme in the existing settings table, without a database migration.
- Added a development version label in the page footer.
- Retained the recent mileage-entry usability improvements and Change Driver fix.

## 1.0.0 (initial starter)
- Initial installation wizard, driver and vehicle management, odometer entry and history.
- MariaDB 5.5 compatibility changes.
