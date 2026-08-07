# Changelog

## [1.4.1] - 2026-08-07

### Added
- To clean up HTML, blacklists of tags and attributes with default values have been added to the component settings.

### Changed
- **Improving security**. Multiple minor code refactoring.
- When the number of attempts for a specific question in a quiz is exceeded, this question is now blocked instead of ending the quiz.

### Fixed
- Continuing using filters on the results page in the admin panel after clicking the "Export to Excel" button.
- When clicking on the certificate preview in the admin panel, a row (checkbox in the first column) was selected in the list of certificates.
- Uploading a certificate image with spaces in the file name.
- Using language variables from the front when generating a PDF report with the result in the admin panel.
- If the user answered False to a "True/false" question and then returned to it (button "Prev"), the user's previously given answer was be lost.
- Calculating the "total" points at the start of a quiz consisting of a pool of questions.

## [1.4.0] - 2026-06-03

### Added
- New question type - "Hotspots Multiple".
- Possibility to generate PDF reports with SVG images in questions.
- The Changelog File for the update server. On the package update admin page, it displays a "ChangeLog" button.

### Changed
- Minor styling improvements on the front.

## [1.3.3] - 2026-05-25

### Added
- Events processed by plugins of the "quiztools" group.

### Fixed
- Comparison of fields in different encodings in core Joomla database tables and component tables.

## [1.3.2] - 2026-05-19

### Fixed
- Correct loading of an article in the selected language in the Learning Path on a multilingual site.
- Paths to images on the front end and in the admin panel, if the site is located in the site folder.

## [1.3.1] - 2026-05-18

### Added
- An event for loading assets from question plugins has been added to the question editing in admin panel.
- Creating a folder for questions assets in the component's installation script.
- Creating a separate folder for question images when saving a question in the admin panel.
- Deleting the separate folder for question images when deleting a question in the admin panel.

### Changed
- Change in the component installation script: the method for copying certificates during installation/update has become more universal and does not depend on the GLOB_BRACE flag.

### Fixed
- In the admin panel: paths to certificate images if the site is located in a website folder.
- On the front end: Ajax request URLs if the site is located in a website folder.

## [1.3.0] - 2026-05-12

### Added
- New "True/False" question type.

### Changed
- Preparing for the JED Checker.

## [1.2.3] - 2026-05-10

### Changed
- Preparing for the JED Checker.

### Fixed
- Typo in language file.

## [1.2.2] - 2026-04-28

### Added
- Schema.org markup on pages: Quiz, Quizzes List, Learning Path, Learning Paths List.

### Fixed
- The check for 'this is a Learning Path' on the results page on the front end.

## [1.2.1] - 2026-04-27

### Fixed
- Corrections to the certificate generation process from the front end.

## [1.2.0] - 2026-04-25

### Added
- Subscriptions to paid quizzes and Learning Paths.
- Creating orders ("manual" payment).
- Integration with the VirtueMart e-store component (automatic order creation).
- Reactivating an order in the admin panel.

### Changed
- The minimum version of Joomla is from 5.3.0 to 5.4.0.
- Dependencies updated to the latest stable versions.

### Fixed
- Lots of minor code fixes and refactoring of code fragments.

## [1.1.2] - 2025-11-16

### Changed
- Refactoring Vue Components.

## [1.1.1] - 2025-11-13

### Changed
- Refactoring of asset creation and deletion methods during package installation and removal.

### Fixed
- When choosing to remove only a package in the extension manager, related plugins were not removed.

## [1.1.0] - 2025-11-10

### Added
- Learning Paths

### Changed
- 'Quizzeslist' field. Simplified field logic. Added easy extensibility.
- Updated asset build dependency versions.
- Lots of minor code fixes and refactoring of code fragments.

### Fixed
- Display previously saved subforms in the quiz settings.
