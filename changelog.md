# Ⓐ LiquidDesign/Admin - CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0-beta.1]

Note to versioning: version 1 is skipped to match version 2 with other packages.

### Added

- *AdminGrid* now has column to show Shop of entity
  - Used only when some Shop is available
- *AdminForm* via *AdminFormFactory* has option to add Shop container to save Shop to entities
- *BackendPresenter* has Shop property to access currently selected Shop in all presenters and templates in Admin
- `FormValidators` new method `checkUniqueCode`
- `AdminFormFactory` new method `addCodeValidationToInput` to simply validate code in all forms
### Changed

- **BREAKING:** PHP version 8.2 or higher is required

### Removed

### Deprecated

### Fixed