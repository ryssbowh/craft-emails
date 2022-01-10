# ryssbowh/craft-emails Changelog

## 1.2.0 10-01-2022

### Fixed
- Plain text emails dont have html

### Changed
- move messages into their own service
- better quick shot error handling
- better preview
- better redactor integration (links to entry, category, assets), enabled elements refs

## 1.1.1 - 10/01/2022

### Fixed
- French translations

## 1.1.0 - 10/01/2022

### Fixed
- Plugin would show up as disabled after install

### Changed
- Mailchimp caching can be set to -1 to skip caching
- Email log stored in filesystem instead of database
- Replace mailer with custom one
- Removed config driven attributes

### Added
- Email translations
- Email templates
- Email preview
- Translatable attachements

## 1.0.1 - 09/01/2022

### Fixed
- Compress email logs setting
- Email duplication in email shots

### Added
- "View emails" button on email shots dashboard
- Mailchimp lists integration through API

## 1.0.0 - 08/01/2022

### Added
- Initial release