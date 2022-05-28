# Web Puzzlers Emails Changelog

## 1.4.1 - 2022-05-28

### Fixed
- Fixed logging issue on sending shots through console

## 1.4.0 - 2022-05-09

### Changed
- Javascript rehaul with webpack
- Renamed all services `all()` methods to `getAll()`

### Added
- Integration to [triggers](https://plugins.craftcms.com/triggers)
- New trigger action "Send email"

## 1.3.0 - 2022-01-31

### Removed
- Test setting email

### Fixed
- Ensure system emails can't be deleted

### Changed
- Require Craft 3.6.5 before which craft\mail\Mailer::EVENT_BEFORE_PREP doesn't exist
- Exclude test settings email from creation, as it's never used by the system

## 1.2.5 - 2022-01-31

### Fixed
- Issue setting reply to email when not defined

## 1.2.4 - 2022-01-18

### Fixed
- Issue with redactor helper (php 7.4)
- Issue with event before prep (php 7.4)

## 1.2.3 - 2022-01-11

### Fixed
- Better error handling when sending shot through command line

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