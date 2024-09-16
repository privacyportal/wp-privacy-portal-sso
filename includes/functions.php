<?php
/**
 * Global Privacy Portal SSO functions.
 *
 * @package   Privacy_Portal_SSO
 * @author    Privacy Portal <support@privacyportal.org> (Forked from Jonathan Daggerhart <jonathan@daggerhart.com>)
 * @copyright 2015-2020 daggerhart, 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Return a single use authentication URL.
 *
 * @return string
 */
function pp_sso_get_authentication_url() {
	return \Privacy_Portal_SSO::instance()->client_wrapper->get_authentication_url();
}

/**
 * Refresh a user claim and update the user metadata.
 *
 * @param WP_User $user             The user object.
 * @param array   $token_response   The token response.
 *
 * @return WP_Error|array
 */
function pp_sso_refresh_user_claim( $user, $token_response ) {
	return \Privacy_Portal_SSO::instance()->client_wrapper->refresh_user_claim( $user, $token_response );
}
