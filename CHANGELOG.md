# Web Puzzlers Emails Changelog

## 1.2.2 - 2022-01-11

### Fixed
- Error when redactor config is not populated
- Redactor config defaults to 'Default.json'

## 1.2.1 - 2022-01-11

### Fixed
- Remove test content in default email template

## 1.2.0 - 2022-01-10

### Fixed
- Plain text emails dont have html

### Changed
- move messages into their own service
- better quick shot error handling
- better preview
- better redactor integration (links to entry, category, assets), enabled elements refs

## 1.1.1 - 2022-01-10

### Fixed
- French translations

## 1.1.0 - 2022-01-10

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

## 1.0.1 - 2022-01-09

### Fixed
- Compress email logs setting
- Email duplication in email shots

### Added
- "View emails" button on email shots dashboard
- Mailchimp lists integration through API

## 1.0.0 - 2022-01-08

### Added
- Initial release