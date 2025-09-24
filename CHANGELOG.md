# Changelog

All notable changes to this project will be documented in this file.
See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## Unreleased
### Added
- Initialized coding standards and test suite.
- Updated tooling paths and bootstrap references.
- Added unit coverage for status identifier normalization and filesystem sanitization.

### Fixed
- Prevented HTML encoding of account identifiers before database lookups in `StatusService`, keeping special characters intact while securing image storage paths.
