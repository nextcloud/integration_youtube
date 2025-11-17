## 0.6.0 – 2025-11-17

### Changed
- Standardize 'YouTube' spelling (#40) @rakekniven

### Added
- i18n translation strings


## 0.5.0 – 2025-10-06

### Changed
- set min NC to 32 (#33) @kyteinsky
- use IExternalProvider, php updates, drop reimpl of reference population (#33) @kyteinsky
- get-set app config values lazily (#34) @kyteinsky

### Fixed
- fix(readme): screenshot order (#36) @hamza221

### Added
- migrate to vue3 + lint fixes (#32) @kyteinsky
- lighter non-interactive reference widget by default (#35) @kyteinsky


## 0.4.0 – 2025-07-28

### Changed

- Bump max Nextcloud version to 28 @julien-nc
- Use Psalm 6 @julien-nc
- Update composer dependencies @julien-nc

### Fixed

- Style issues in admin and personal settings, wrong title alignment @julien-nc
- Fix link to google console @julien-nc

## 0.3.1 – 2024-11-07

### Changed
- update npm+composer deps @kyteinsky
- consistent margin in personal and admin settings @kyteinsky
- bump max NC version to 31 and drop support for 26 @kyteinsky
- password confirmation + do not send back decrypted api keys to frontend @kyteinsky


## 0.3.0 – 2024-07-26

### Changed
- update composer deps @kyteinsky
- update gh workflows @kyteinsky
- bump max NC version to 30 @kyteinsky


## 0.2.1 – 2024-03-14

### Fixed

- Fix screenshot URLs in info.xml


## 0.2.0 – 2024-03-13

### Changed

- Update workflows from templates
- Drop Nextcloud 25 compatibility
- Update node and composer deps
- Checks adjusted to also check for php 7.4 on NC 26/27

### Added

- Create appstore-build-publish.yml
- Add transfix config
- Add gh workflows
- Nextcloud 29 compatibility
- Add search provider and smart picker functionality
- Add some screenshots

### Fixed

- Stop using OC namespace for LinkReferenceProvider

## 0.1.5

### Added

- Nextcloud 28 compatibility
- @SuperSandro2000 Update links to directly point to the Nextcloud repo
- @kyteinsky Privacy friendly embed

## 0.1.4

### Added

- Nextcloud 27 compatibility

## 0.1.3

### Added

- Nextcloud 26 compatibility

### Fixed

- fix: Fix regression when matching links

## 0.1.2

### Fixed

- fix: Make sure to consider any youtube like a reference

## 0.1.1

### Dependencies

- Update dependencies

## 0.1.0

Initial release for Nextcloud 25
