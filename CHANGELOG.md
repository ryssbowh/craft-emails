# Web Puzzlers Emails Changelog

## 2.1.0 - 2023-07-21

> **Warning**
> Composer dependency to `craftcms/redactor:^3.0` has been removed, if this wasn't in your composer.json the Redactor plugin will be removed, require it manually to be able to write Html emails : `composer require craftcms/redactor:^3.0`

### Added
- CKEditor support. Wysiwyg editors must now be installed separately and chosen in the plugin settings

## 2.0.11 - 2023-06-21

### Fixed

- Fixed error when no redactor config are found [#15](https://github.com/ryssbowh/craft-emails/issues/15)

## 2.0.10 - 2023-04-13

### Fixed

- Disable upload on attachements field [#13](https://github.com/ryssbowh/craft-emails/issues/13)

## 2.0.9 - 2023-03-13

### Fixed

- Fixed asset modal not showing any volumes [#11](https://github.com/ryssbowh/craft-emails/issues/11)

## 2.0.8 - 2023-03-08

### Fixed

- Fixed emails not being sent when other plugins would not use `Mailer::composeFromKey()` [#10](https://github.com/ryssbowh/craft-emails/issues/10)
- Make sure system messages registered by other plugins are properly installed and uninstalled [#10](https://github.com/ryssbowh/craft-emails/issues/10)
- Order emails by heading on dashboard
- Make sure Redactor is installed along with Emails
- Ensure project config dateModified is not updated one more time when uninstalling this plugin
- Added a helper on the settings to reinstall email in case of issues [#10](https://github.com/ryssbowh/craft-emails/issues/10)

## 2.0.7 - 2023-02-15

### Fixed

- Fixed email log replyTo wrong typing [#9](https://github.com/ryssbowh/craft-emails/issues/9)

## 2.0.6 - 2022-11-17

### Changed

- Updated documentation urls

## 2.0.5 - 2022-10-12

### Changed

- Changed plugin icons

## 2.0.4 - 2022-09-27

### Fixed

- Fixed empty spaces in text emails. [#8](https://github.com/ryssbowh/craft-emails/issues/8)

## 2.0.3 - 2022-09-24

### Fixed

- Fixed issue where the wrong method was used to get the text body. [#7](https://github.com/ryssbowh/craft-emails/issues/7)

## 2.0.2 - 2022-05-15

### Fixed

- Fixed issue with permissions

## 2.0.1 - 2022-05-09

### Fixed

- Fixed composer requirements

## 2.0.0 - 2022-05-09

### Changed

- Craft 4 support

### Fixed

- Enforce maxlength on all varchar fields
- Fixed issues showing wrong users triggering email shots through console in logs
- Issue in emailer when sending a message without key