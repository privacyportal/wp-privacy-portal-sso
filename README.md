# Privacy Portal SSO (for WP) #
**Contributors:** [privacyportal](https://profiles.wordpress.org/privacyportal/), [daggerhart](https://profiles.wordpress.org/daggerhart/), [tnolte](https://profiles.wordpress.org/tnolte/)  
**Tags:** security, privacy, login, oauth, sso  
**Requires at least:** 5.0  
**Tested up to:** 6.6.1  
**Stable tag:** 0.1.0  
**Requires PHP:** 7.4  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Welcome privacy-conscious users to your website and/or email newsletter, with features like "Sign In With Privacy Portal"
and "Subscribe Anonymously".

## Description ##

This plugin allows users to authenticate using "Sign In With Privacy Portal". After obtaining consent, an existing user
is automatically logged into WordPress, while new users are created in the WordPress database. [Privacy Portal](https://privacyportal.org)
protects user privacy by generating email aliases (AKA Privacy Alias) that relay emails to their personal email addresses,
keeping them private.

Similarly, it allows visitors to anonymously subscribe your newsletter without ever exposing their personal emails to
your site. It provides a "Subscribe Anonymously" button that authenticates visitors through Privacy Portal and enrolls
them in your newsletter using an automatically generated email alias.

Much of the documentation can be found on the Settings > Privacy Portal SSO dashboard page.

## Installation ##

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin
1. Visit Settings > Privacy Portal SSO and configure to meet your needs

## Frequently Asked Questions ##

### What is the Redirect URI? ###

Privacy Portal's OAUTH2 servers require a whitelist of redirect URIs for security purposes. When using Privacy Portal SSO,
there are two redirect URIs that you need to add depending on the features you're looking to use. You must add both redirect
URIs in your app configuration if you'd like to use both "Sign In With Privacy Portal" and "Subscribe Anonymously".

You can find the redirect URIs on the plugin's settings page.

## Changelog ##

### 0.1.0 ###

* Feature: Configures "Sign In With Privacy Portal" as OAUTH provider and simplifies the settings page.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" to enable Newsletter subscriptions with Privacy Aliases.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" integration with the MailPoet Plugin.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" integration with the MC4WP Plugin.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" integration with The Newsletter Plugin.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" integration with the ConvertKit Plugin.

## Pre-Fork Changelog (from OpenId Connect Generic) ##

### 3.10.0 ###

* Chore: @timnolte - Dependency updates.
* Fix: @drzraf - Prevents running the auth url filter twice.
* Fix: @timnolte - Updates the log cleanup handling to properly retain the configured number of log entries.
* Fix: @timnolte - Updates the log display output to reflect the log retention policy.
* Chore: @timnolte - Adds Unit Testing & New Local Development Environment.
* Feature: @timnolte - Updates logging to allow for tracking processing time.
* Feature: @menno-ll - Adds a remember me feature via a new filter.
* Improvement: @menno-ll - Updates WP Cookie Expiration to Same as Session Length.

### 3.9.1 ###

* Improvement: @timnolte - Refactors Composer setup and GitHub Actions.
* Improvement: @timnolte - Bumps WordPress tested version compatibility.

### 3.9.0 ###

* Feature: @matchaxnb - Added support for additional configuration constants.
* Feature: @schanzen - Added support for agregated claims.
* Fix: @rkcreation - Fixed access token not updating user metadata after login.
* Fix: @danc1248 - Fixed user creation issue on Multisite Networks.
* Feature: @RobjS - Added plugin singleton to support for more developer customization.
* Feature: @jkouris - Added action hook to allow custom handling of session expiration.
* Fix: @tommcc - Fixed admin CSS loading only on the plugin settings screen.
* Feature: @rkcreation - Added method to refresh the user claim.
* Feature: @Glowsome - Added acr_values support & verification checks that it when defined in options is honored.
* Fix: @timnolte - Fixed regression which caused improper fallback on missing claims.
* Fix: @slykar - Fixed missing query string handling in redirect URL.
* Fix: @timnolte - Fixed issue with some user linking and user creation handling.
* Improvement: @timnolte - Fixed plugin settings typos and screen formatting.
* Security: @timnolte - Updated build tooling security vulnerabilities.
* Improvement: @timnolte - Changed build tooling scripts.

### 3.8.5 ###

* Fix: @timnolte - Fixed missing URL request validation before use & ensure proper current page URL is setup for Redirect Back.
* Fix: @timnolte - Fixed Redirect URL Logic to Handle Sub-directory Installs.
* Fix: @timnolte - Fixed issue with redirecting user back when the openid_connect_generic_auth_url shortcode is used.

### 3.8.4 ###

* Fix: @timnolte - Fixed invalid State object access for redirection handling.
* Improvement: @timnolte - Fixed local wp-env Docker development environment.
* Improvement: @timnolte - Fixed Composer scripts for linting and static analysis.

### 3.8.3 ###

* Fix: @timnolte - Fixed problems with proper redirect handling.
* Improvement: @timnolte - Changes redirect handling to use State instead of cookies.
* Improvement: @timnolte - Refactored additional code to meet coding standards.

### 3.8.2 ###

* Fix: @timnolte - Fixed reported XSS vulnerability on WordPress login screen.

### 3.8.1 ###

* Fix: @timnolte - Prevent SSO redirect on password protected posts.
* Fix: @timnolte - CI/CD build issues.
* Fix: @timnolte - Invalid redirect handling on logout for Auto Login setting.

### 3.8.0 ###

* Feature: @timnolte - Ability to use 6 new constants for setting client configuration instead of storing in the DB.
* Improvement: @timnolte - Plugin development & contribution updates.
* Improvement: @timnolte - Refactored to meet WordPress coding standards.
* Improvement: @timnolte - Refactored to provide localization.

--------

[See the previous changelogs here](https://github.com/privacyportal/wp-privacy-portal-sso/blob/main/CHANGELOG.md#changelog)
