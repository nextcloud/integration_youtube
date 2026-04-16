<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## 0.6.1 - 2025-11-17

### Fixed
- fix referrer policy in youtube iframe for error 153 ([#44](https://github.com/nextcloud/integration_youtube/pull/44)) @kyteinsky


## 0.6.0 - 2025-11-17

### Added
- i18n translation strings

### Changed
- Standardize 'YouTube' spelling ([#40](https://github.com/nextcloud/integration_youtube/pull/40)) @rakekniven


## 0.5.0 - 2025-10-06

### Added
- migrate to vue3 + lint fixes ([#32](https://github.com/nextcloud/integration_youtube/pull/32)) @kyteinsky
- lighter non-interactive reference widget by default ([#35](https://github.com/nextcloud/integration_youtube/pull/35)) @kyteinsky

### Changed
- set min NC to 32 ([#33](https://github.com/nextcloud/integration_youtube/pull/33)) @kyteinsky
- use IExternalProvider, php updates, drop reimpl of reference population ([#33](https://github.com/nextcloud/integration_youtube/pull/33)) @kyteinsky
- get-set app config values lazily ([#34](https://github.com/nextcloud/integration_youtube/pull/34)) @kyteinsky

### Fixed
- fix(readme): screenshot order ([#36](https://github.com/nextcloud/integration_youtube/pull/36)) @hamza221


## 0.4.0 - 2025-07-28

### Changed
- Bump max Nextcloud version to 28 @julien-nc
- Use Psalm 6 @julien-nc
- Update composer dependencies @julien-nc

### Fixed
- Style issues in admin and personal settings, wrong title alignment @julien-nc
- Fix link to google console @julien-nc


## 0.3.1 - 2024-11-07

### Changed
- update npm+composer deps @kyteinsky
- consistent margin in personal and admin settings @kyteinsky
- bump max NC version to 31 and drop support for 26 @kyteinsky
- password confirmation + do not send back decrypted api keys to frontend @kyteinsky


## 0.3.0 - 2024-07-26

### Changed
- update composer deps @kyteinsky
- update gh workflows @kyteinsky
- bump max NC version to 30 @kyteinsky


## 0.2.1 - 2024-03-14

### Fixed
- Fix screenshot URLs in info.xml


## 0.2.0 - 2024-03-13

### Added
- Create appstore-build-publish.yml
- Add transfix config
- Add gh workflows
- Nextcloud 29 compatibility
- Add search provider and smart picker functionality
- Add some screenshots

### Changed
- Update workflows from templates
- Drop Nextcloud 25 compatibility
- Update node and composer deps
- Checks adjusted to also check for php 7.4 on NC 26/27

### Fixed
- Stop using OC namespace for LinkReferenceProvider


## 0.1.5 - 2023-11-16

### Added
- Nextcloud 28 compatibility
- Update links to directly point to the Nextcloud repo @SuperSandro2000
- Privacy friendly embed @kyteinsky


## 0.1.4 - 2023-05-15

### Added
- Nextcloud 27 compatibility


## 0.1.3 - 2023-02-17

### Added
- Nextcloud 26 compatibility

### Fixed
- Fix regression when matching links


## 0.1.2 - 2023-02-10

### Fixed
- Make sure to consider any youtube like a reference


## 0.1.1 - 2022-11-07

### Changed
- Update dependencies


## 0.1.0 - 2022-09-20

### Added
- Initial release for Nextcloud 25
