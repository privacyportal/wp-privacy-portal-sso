<?php
/**
 * Plugin integration with ConvertKit class.
 *
 * @package   Privacy_Portal_SSO
 * @category  Newsletter
 * @author    Privacy Portal <support@privacyportal.org>
 * @copyright 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * PP_SSO_Ext_ConvertKit_Plugin class.
 *
 * This class adds support for the ConvertKit
 *
 * @package  Privacy_Portal_SSO
 * @category Newsletter
 */
class PP_SSO_Ext_ConvertKit_Plugin {

	const NAMESPACE  = 'convertkit';
	const FULL_NAME  = 'ConvertKit Plugin';
	const SHORT_NAME = 'ConvertKit';

	/**
	 * Returns true if the plugin is installed and set up correctly
	 *
	 * @return bool
	 */
	public static function is_detected() {
		if ( ! class_exists( WP_ConvertKit::class ) ||
			 ! class_exists( ConvertKit_Settings::class ) ||
				 ! class_exists( ConvertKit_API_V4::class ) ||
				 ! defined( 'CONVERTKIT_OAUTH_CLIENT_ID' ) ||
				 ! defined( 'CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI' ) ||
				 ! method_exists( 'ConvertKit_Settings', 'has_access_and_refresh_token' ) ||
				 ! method_exists( 'ConvertKit_Settings', 'get_access_token' ) ||
				 ! method_exists( 'ConvertKit_Settings', 'get_refresh_token' ) ||
				 ! method_exists( 'ConvertKit_Settings', 'debug_enabled' ) ||
				 ! method_exists( 'ConvertKit_API_V4', 'get_forms' ) ||
				 ! method_exists( 'ConvertKit_API_V4', 'create_subscriber' ) ||
				 ! method_exists( 'ConvertKit_API_V4', 'add_subscriber_to_form' ) ) {
			return false;
		}
		$convertkit_settings = new ConvertKit_Settings();
		return $convertkit_settings->has_access_and_refresh_token();
	}

	/**
	 * Returns array of newsletter lists
	 *
	 * @return array
	 */
	public static function get_newsletter_lists() {
		$lists = array();
		if ( self::is_detected() ) {
			try {
				$convertkit_settings = new ConvertKit_Settings();
				$convertkit_api = new ConvertKit_API_V4(
					CONVERTKIT_OAUTH_CLIENT_ID,
					CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI,
					$convertkit_settings->get_access_token(),
					$convertkit_settings->get_refresh_token(),
					$convertkit_settings->debug_enabled()
				);
				// convertkit is connected.
				$convertkit_forms = $convertkit_api->get_forms();
				if ( ! is_wp_error( $convertkit_forms ) ) {
					foreach ( $convertkit_forms['forms'] as $convertkit_form ) {
						// Use the page ID as the key and the page title as the value.
						$lists[ self::NAMESPACE . '::' . $convertkit_form['id'] ] = self::SHORT_NAME . ' - ' . $convertkit_form['name'];
					}
				}
				// @codingStandardsIgnoreLine
			} catch ( \Exception ) {
				// do nothing.
			}
		}
		return $lists;
	}

	/**
	 * Returns true if the email was successfully subscribed
	 *
	 * @param string $email email address of the subscriber.
	 * @param array  $lists enabled mailing lists for this provider.
	 * @param array  $opts  subscription options (e.g. double_opt_in).
	 *
	 * @return bool|WP_Error
	 */
	public static function handle_subscription( $email, $lists, $opts ) {
		if ( self::is_detected() && ! empty( $lists ) ) {
			$convertkit_settings = new ConvertKit_Settings();
			$convertkit_api = new ConvertKit_API_V4(
				CONVERTKIT_OAUTH_CLIENT_ID,
				CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI,
				$convertkit_settings->get_access_token(),
				$convertkit_settings->get_refresh_token(),
				$convertkit_settings->debug_enabled()
			);

			// create subscriber.
			$convertkit_subscriber = $convertkit_api->create_subscriber( $email );

			// handle error.
			if ( is_wp_error( $convertkit_subscriber ) ) {
				return $convertkit_subscriber;
			}

			try {
				// No global double opt-in settings in the plugin. Double opt-in is managed at the form level.
				foreach ( $lists as $convertkit_form_id ) {
					$convertkit_api->add_subscriber_to_form( $convertkit_form_id, $convertkit_subscriber['subscriber']['id'] );
				}
			} catch ( \Exception $e ) {
				return new WP_Error( 'subscription-failed', __( 'Failed to subscribe.', 'privacy-portal-sso' ), $e );
			}

			return true;
		}
		return false;
	}
}
