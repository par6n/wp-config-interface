# WordPress Config Interface
WordPress Configuration Interface (also known as wp-config.php editor) is an user interface for editing wp-config constants and variables.

It's designed well to utility this file for all WordPress users.

## Requirements
* PHP >= 5.3
* WordPress >= 4.0

## Installation
There is various ways for installing the plugin.

1. [Download the latest developer version](https://github.com/EhsaanF/wp-config-interface/archive/master.zip) from GitHub, copy & paste it to `wp-content/plugins/` directory. Then activate it through WordPress managment.
2. Clone the repository into `wp-content/plugins/` directory and then activate it through WordPress managment.
3. [Download latest production version](https://wordpress.org/plugins/config-interface) from WordPress official repository.

## Usage
After you made sure that plugin is activated, navigate to Settings > Edit wp-config.php

If you've done everything right, there should be options for editing.

If because of any reason wp-config.php is not writable for PHP, you'll get new file as plain text, you may apply it through file managment system or FTP.

## Issues
Follow [GitHub issue tracker](https://github.com/EhsaanF/wp-config-interface/issues)

## Future releases
Future enhancements and fixes will tagged as "future" in [Issue Tracker](https://github.com/EhsaanF/wp-config-interface/labels/future)

## Translations
Translations welcome! Translators name will credited in WordPress repository page.

To translate the plugin:

1. Fork the repository
2. Translate strings with poEdit or anyother software you want.
3. Send us a pull request :smirk:


## Changelog
**Version 1.1:**
* New: `SUBDOMAIN_INSTALL` option
* New: Tables prefix option
* Fixed: Some string didn't have text domain.
* Tweak: Squished a bug when network is enabled, but network options don't save.

**20150829 Commit:**
* Translated to Persian by myself
* Optimized for I18N
* Added `FS_METHOD` option


**20150827 Commit:**
* Fixed an XSS bug in Tabs [Thanks to Babak_T for reporting]
* Made compatible with WordPress Network
* Fixed a mistake with `CONFIG_EDITOR_VERSION` [Thanks to Farhan for reporting]
