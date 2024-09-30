<?php
/**
 * Phpstan bootstrap file.
 *
 * @package   Privacy_Portal_SSO
 * @author    Privacy Portal <support@privacyportal.org> (Forked from Jonathan Daggerhart <jonathan@daggerhart.com> and Tim Nolte <tim.nolte@ndigitals.com>)
 * @copyright 2015-2020 daggerhart, 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link      https://github.com/privacyportal
 */

// Define whether running under WP-CLI.
defined( 'WP_CLI' ) || define( 'WP_CLI', false );

// Define WordPress language directory.
defined( 'WP_LANG_DIR' ) || define( 'WP_LANG_DIR', 'wordpress/src/wp-includes/languages/' );

defined( 'COOKIE_DOMAIN' ) || define( 'COOKIE_DOMAIN', 'localhost' );
defined( 'COOKIEPATH' ) || define( 'COOKIEPATH', '/');

// Define Plugin Globals.
defined( 'PP_SSO_CLIENT_ID' ) || define( 'PP_SSO_CLIENT_ID', bin2hex( random_bytes( 32 ) ) );
defined( 'PP_SSO_CLIENT_SECRET' ) || define( 'PP_SSO_CLIENT_SECRET', bin2hex( random_bytes( 16 ) ) );
defined( 'PP_SSO_ENDPOINT_LOGIN_URL' ) || define( 'PP_SSO_ENDPOINT_LOGIN_URL', 'https://app.privacyportal.org/oauth/authorize' );
defined( 'PP_SSO_ENDPOINT_USERINFO_URL' ) || define( 'PP_SSO_ENDPOINT_USERINFO_URL', 'https://api.privacyportal.org/oauth/userinfo' );
defined( 'PP_SSO_ENDPOINT_TOKEN_URL' ) || define( 'PP_SSO_ENDPOINT_TOKEN_URL', 'https://api.privacyportal.org/oauth/token' );
defined( 'PP_SSO_ENDPOINT_LOGOUT_URL' ) || define( 'PP_SSO_ENDPOINT_LOGOUT_URL', '' );
defined( 'PP_SSO_CLIENT_SCOPE' ) || define( 'PP_SSO_CLIENT_SCOPE', 'email profile openid' );
defined( 'PP_SSO_LOGIN_TYPE' ) || define( 'PP_SSO_LOGIN_TYPE', 'button' );
defined( 'PP_SSO_LINK_EXISTING_USERS' ) || define( 'PP_SSO_LINK_EXISTING_USERS', 0 );
defined( 'PP_SSO_ENFORCE_PRIVACY' ) || define( 'PP_SSO_ENFORCE_PRIVACY', 0 );
defined( 'PP_SSO_CREATE_IF_DOES_NOT_EXIST' ) || define( 'PP_SSO_CREATE_IF_DOES_NOT_EXIST', 1 );
defined( 'PP_SSO_REDIRECT_USER_BACK' ) || define( 'PP_SSO_REDIRECT_USER_BACK', 0 );
defined( 'PP_SSO_REDIRECT_ON_LOGOUT' ) || define( 'PP_SSO_REDIRECT_ON_LOGOUT', 1 );
defined( 'PP_SSO_ACR_VALUES' ) || define( 'PP_SSO_ACR_VALUES', '' );
defined( 'PP_SSO_ENABLE_LOGGING' ) || define( 'PP_SSO_ENABLE_LOGGING', 0 );
defined( 'PP_SSO_LOG_LIMIT' ) || define( 'PP_SSO_LOG_LIMIT', 20 );
