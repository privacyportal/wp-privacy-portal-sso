=== Privacy Portal SSO (for WP) ===
Contributors: privacyportal, daggerhart, tnolte
Tags: security, privacy, login, oauth, sso
Requires at least: 5.0
Tested up to: 6.6.1
Stable tag: 0.1.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Welcome privacy-conscious users to your website and/or email newsletter, with features like "Sign In With Privacy Portal"
and "Subscribe Anonymously".

== Description ==

This plugin allows you to integrate [Sign In With Privacy Portal](https://privacyportal.org/for-business/products#sign-in-with-privacy-portal) and [Subscribe Anonymously With Privacy Portal](https://privacyportal.org/for-business/products#subscribe-anonymously-with-privacy-portal) with your site.

[Privacy Portal](https://privacyportal.org) is a privacy-focused OAuth 2.0 provider and offers an integrated Mail Relay service to keep user emails private through Privacy Aliases.

Please check Privacy Portal's [Privacy Policy](https://privacyportal.org/privacy) and [Terms Of Service](https://privacyportal.org/tos) for more information.

== Sign In With Privacy Portal ==

[Privacy Portal](https://privacyportal.org) is an OAuth 2.0 Provider with Privacy baked-in. When *Sign In With Privacy Portal* is enabled, users can log in to your site while preserving their privacy using the Privacy Portal OAuth provider. During the login process, [Privacy Portal](https://privacyportal.org) protects user privacy by generating email aliases (also known as Privacy Aliases) that relay emails to users' personal email addresses, keeping them private.

1. The "Sign In With Privacy Portal" button will be displayed on your site. (It can be added to any page.)
2. Visitors to your site can click on the button to initiate the login process.
3. Once clicked, they will be redirected to the Privacy Portal web app to authorize access to your site.
4. After authorization, users will be redirected back to your site. Existing users will be automatically logged into WordPress, while new users will be created in the WordPress database and then logged in.

== Subscribe Anonymously With Privacy Portal ==

When "Subscribe Anonymously with Privacy Portal" is enabled, users can subscribe to your existing newsletter using email aliases instead of their personal email addresses. This feature does not replace your existing newsletter setup; instead, it integrates with some popular newsletter plugins.

1. The "Subscribe Anonymously" button will be displayed within your newsletter forms. (It can be added to any page.)
2. Visitors to your site can click on the button to enroll anonymously in your newsletter without having to enter their email address.
3. Once clicked, they will be redirected to the Privacy Portal web app to authorize access to your site.
4. After authorization, Privacy Portal generates an email alias that is used to enroll the user in your newsletter. Note that the user remains anonymous throughout the process.

== Important Notes ==

- With Privacy Portal SSO, users will be redirected to the Privacy Portal web app, where they need to log in with their Privacy Portal account to authorize access to your site. If they don't have an account, they can create a free one during this process.
- When sending an email to a user's email alias, your email is delivered to Privacy Portal's Mail Relay servers, which privately redirect it to the user's personal email address. Mail Relay does not store emails; it processes them in-memory and can add a layer of encryption when configured by users.

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin
1. Visit Settings > Privacy Portal SSO and configure to meet your needs

== Frequently Asked Questions ==

= What is the Redirect URI? =

Privacy Portal's OAUTH2 servers require a whitelist of redirect URIs for security purposes. When using Privacy Portal SSO, there are two redirect URIs that you need to add depending on the features you're looking to use. You must add both redirect URIs in your app configuration if you'd like to use both "Sign In With Privacy Portal" and "Subscribe Anonymously".

You can find the redirect URIs on the plugin's settings page.

= Is the Privacy Portal OAuth provider free? =

Privacy Portal has a generous free plan that covers the needs of most small to medium sized websites. It also offers a free upgrade for sites that fall under our *Freedom-Tech* discount. [Learn more](https://privacyportal.org/for-business/products#pricing).

== Changelog ==

= 0.1.2 =

* Fix: Handle Kit plugin error on empty lists.

= 0.1.1 =

* Improvement: Update plugin README.

= 0.1.0 =

* Feature: Configures "Sign In With Privacy Portal" as OAUTH provider and simplifies the settings page.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" to enable Newsletter subscriptions with Privacy Aliases.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" integration with the MailPoet Plugin.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" integration with the MC4WP Plugin.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" integration with The Newsletter Plugin.
* Feature: Adds "Subscribe Anonymously With Privacy Portal" integration with the ConvertKit Plugin.

= 3.10.0 (Forked from OpenId Connect Generic) =

* Chore: @timnolte - Dependency updates.
* Fix: @drzraf - Prevents running the auth url filter twice.
* Fix: @timnolte - Updates the log cleanup handling to properly retain the configured number of log entries.
* Fix: @timnolte - Updates the log display output to reflect the log retention policy.
* Chore: @timnolte - Adds Unit Testing & New Local Development Environment.
* Feature: @timnolte - Updates logging to allow for tracking processing time.
* Feature: @menno-ll - Adds a remember me feature via a new filter.
* Improvement: @menno-ll - Updates WP Cookie Expiration to Same as Session Length.

= 3.9.1 (pre-fork) =

* Improvement: @timnolte - Refactors Composer setup and GitHub Actions.
* Improvement: @timnolte - Bumps WordPress tested version compatibility.

= 3.9.0 (pre-fork) =

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

= 3.8.5 (pre-fork) =

* Fix: @timnolte - Fixed missing URL request validation before use & ensure proper current page URL is setup for Redirect Back.
* Fix: @timnolte - Fixed Redirect URL Logic to Handle Sub-directory Installs.
* Fix: @timnolte - Fixed issue with redirecting user back when the openid_connect_generic_auth_url shortcode is used.

= 3.8.4 (pre-fork) =

* Fix: @timnolte - Fixed invalid State object access for redirection handling.
* Improvement: @timnolte - Fixed local wp-env Docker development environment.
* Improvement: @timnolte - Fixed Composer scripts for linting and static analysis.

= 3.8.3 (pre-fork) =

* Fix: @timnolte - Fixed problems with proper redirect handling.
* Improvement: @timnolte - Changes redirect handling to use State instead of cookies.
* Improvement: @timnolte - Refactored additional code to meet coding standards.

= 3.8.2 (pre-fork) =

* Fix: @timnolte - Fixed reported XSS vulnerability on WordPress login screen.

= 3.8.1 (pre-fork) =

* Fix: @timnolte - Prevent SSO redirect on password protected posts.
* Fix: @timnolte - CI/CD build issues.
* Fix: @timnolte - Invalid redirect handling on logout for Auto Login setting.

= 3.8.0 (pre-fork) =

* Feature: @timnolte - Ability to use 6 new constants for setting client configuration instead of storing in the DB.
* Improvement: @timnolte - Plugin development & contribution updates.
* Improvement: @timnolte - Refactored to meet WordPress coding standards.
* Improvement: @timnolte - Refactored to provide localization.

--------

[See the previous changelogs here](https://github.com/privacyportal/wp-privacy-portal-sso/blob/main/CHANGELOG.md#changelog)
