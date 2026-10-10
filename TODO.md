# Vehicle Log roadmap

## V1 starter included
- [x] Portable single-folder PHP application
- [x] First-run MySQL setup and admin account
- [x] Protected `dontlookhere` config directory for Apache
- [x] Admin vehicle/driver creation and activation
- [x] Three selectable driver access modes (initial implementation)
- [x] Start/end odometer readings and recent history

## Legacy database compatibility
- [x] Remove unsupported DATETIME CURRENT_TIMESTAMP defaults for MariaDB 5.5
- [x] Explicit UTC timestamps on reading inserts
- [ ] Test complete installation and record submission on MariaDB 5.5.68
- [ ] Add database version/compatibility warning to installer
- [ ] Display timestamps in configured local timezone

## Before production
- [ ] Rate limiting, login lockout and session hardening
- [ ] Username + password login screen (rather than name selection)
- [ ] Manage/edit driver secrets and names
- [ ] Validate server deny rules during setup
- [ ] Transaction-safe installation and migration version tracking
- [ ] Edit erroneous readings with audit trail
- [ ] Remember last selected vehicle
- [ ] Odometer plausibility checks and trip pairing
- [ ] Verify on target hosting and mobile devices

## V2
- [ ] Fuel purchases, servicing, WOF and registration
- [ ] Reports and export
