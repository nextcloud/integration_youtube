<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added

- Added support for Nextcloud 36

### Removed

- Nextcloud 32 and PHP 8.1 support

### Fixed

- Keep the YouTube API key out of the log when a request to YouTube fails
- Show the results of a video search that YouTube answers with a channel among the videos
- Show apostrophes and ampersands in the titles of videos and playlists in search results instead of their HTML codes
- Answer a YouTube search with no results instead of a server error when a request to YouTube fails, for example with an invalid API key
- Show the next page of the same YouTube search on "load more", and start a repeated search at its first page

## 0.7.2 - 2026-10-14

### Changed

- Update dependencies

### Fixed

- Use the real key to get the config value

## 0.7.1 - 2026-08-19

### Added

- Added support for Nextcloud 35

### Changed

- Remove non-existent branches from the npm audit workflow
- Import from @nextcloud/vue/functions/registerReference
- Update dependencies

## 0.7.0 - 2026-04-16

### Added

- Added support for Nextcloud 34
- Modernize the personal and admin settings

### Changed

- Adapt the spelling of "API"
- Update the workflows
- Update dependencies

## 0.6.1 - 2025-11-17

### Fixed

- Fix the referrer policy in the YouTube iframe for error 153

## 0.6.0 - 2025-11-17

### Added

- Translations

### Changed

- Standardize "YouTube" spelling

## 0.5.0 - 2025-10-06

### Added

- Added support for Nextcloud 33
- Migrate to Vue 3, with lint fixes
- Lighter non-interactive reference widget by default

### Changed

- Use IExternalProvider, update the PHP code, and drop the reimplementation of reference population
- Get and set app config values lazily

### Removed

- Nextcloud 27 to 31 support

### Fixed

- Screenshot order in the README

## 0.4.0 - 2025-07-28

### Added

- Added support for Nextcloud 32

### Changed

- Use Psalm 6
- Update dependencies

### Fixed

- Style issues in the admin and personal settings, wrong title alignment
- Fix the link to the Google console

## 0.3.1 - 2024-11-07

### Added

- Added support for Nextcloud 31

### Changed

- Consistent margin in the personal and admin settings
- Password confirmation, and no decrypted API keys sent back to the frontend
- Update dependencies

### Removed

- Nextcloud 26 support

## 0.3.0 - 2024-07-26

### Added

- Added support for Nextcloud 30

### Changed

- Update the GitHub workflows
- Update dependencies

## 0.2.1 - 2024-03-14

### Fixed

- Fix the screenshot URLs in info.xml

## 0.2.0 - 2024-03-13

### Added

- Added support for Nextcloud 29
- Create appstore-build-publish.yml
- Add the Transifex config
- Add GitHub workflows
- Add the search provider and the smart picker
- Add some screenshots

### Changed

- Update the workflows from the templates
- Also check PHP 7.4 on Nextcloud 26 and 27
- Update dependencies

### Removed

- Nextcloud 25 support

### Fixed

- Stop using the OC namespace for LinkReferenceProvider

## 0.1.5 - 2023-11-16

### Added

- Added support for Nextcloud 28
- Update the links to point directly to the Nextcloud repository
- Privacy-friendly embed

## 0.1.4 - 2023-05-15

### Added

- Added support for Nextcloud 27

## 0.1.3 - 2023-02-17

### Added

- Added support for Nextcloud 26

### Fixed

- Fix a regression when matching links

## 0.1.2 - 2023-02-10

### Fixed

- Consider any YouTube link a reference

## 0.1.1 - 2022-11-07

### Changed

- Update dependencies

## 0.1.0 - 2022-09-20

### Added

- Initial release for Nextcloud 25
