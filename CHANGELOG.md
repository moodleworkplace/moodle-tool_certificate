# Changelog

## 4.0.1 (2022051000)
### Changed
- Prevent race condition resulting in issuing course certificate twice

## 4.0.0 (2022042000)
### Changed
- This version of the plugin is only for Moodle LMS 4.0 and above

## 3.11.6 (2022031500)
### Added
- Setting 'Show share on LinkedIn'. When enabled users can add their certificates to LinkedIn
  from the 'My certificates' page in their profile
- Show identity fields in the list of issued certificates
- Allow relative dates for expiry dates (i.e. 1 year after issue)

## 3.11.5 (2022011800)
### Added
- Added mobile support to mod_coursecertificate (small changes required in this plugin)

### Changed
- Compliance with codechecker v3.0.5

## 3.11.1 (2021072000)
### Changed
- Shared image types are now limited to "web_image". Non "web_image" images previously uploaded 
  did not work properly.

## 3.11 (2021060800)
### Changed
- Compatibility with Moodle 3.9 - 3.11

## 3.10.4 (2021051100)
### Changed
- New index allowing to search for certificates quicker
- Fixes to coding style to make new version of codechecker happy

## 3.10.1+ (2021020800)
### Changed
- Small UI changes in forms displaying metric system
- Viewing and previewing certificates now open a new browser tab

## 3.10.1 (2021011900)
### Changed
- Fixed issue when moving/deleting categories that contained certificates. All pages/elements
  and issued certificates are now handled correctly
- Fixed issue when generating certificate codes with firstname/lastname with non-latin characters.
  All non-latin characters are now converted for the code.

## 3.10+ (2020121700)
### Changed
- Fixed a bug in how a 'Text area' course custom field is handled in the certificate templates
- Fixed occasional double modal popups when editing templates
- Small visual fixes in the template editing UI

## Previous versions
Changelog was not maintained before version 3.10
