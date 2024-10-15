<?php
/**
 * Plugin integration with MC4WP (MailChimp for WordPress) class.
 *
 * @package   Privacy_Portal_SSO
 * @category  Newsletter
 * @author    Privacy Portal <support@privacyportal.org>
 * @copyright 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * PP_SSO_Ext_MC4WP_Plugin class.
 *
 * This class adds support for the MC4WP
 *
 * @package  Privacy_Portal_SSO
 * @category Newsletter
 */
class PP_SSO_Ext_MC4WP_Plugin {

	const NAMESPACE  = 'mc4wp';
	const FULL_NAME  = 'MC4WP Plugin';
	const SHORT_NAME = 'MC4WP';

	/**
	 * Returns true if the plugin is installed and set up correctly
	 *
	 * @return bool
	 */
	public static function is_detected() {
		if (
			! class_exists( MC4WP_API_V3::class ) ||
			! class_exists( MC4WP_MailChimp::class ) ||
			! function_exists( 'mc4wp_get_api_v3' ) ||
			! method_exists( MC4WP_MailChimp::class, 'list_subscribe' ) ||
			empty( mc4wp_get_api_key() )
		) {
			return false;
		}
		$mailchimp_api = mc4wp_get_api_v3();
		return isset( $mailchimp_api ) && $mailchimp_api->is_connected();
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
				// Get MailChimp API instance and fetch the lists.
				$mailchimp_api = mc4wp_get_api_v3();
				$mailchimp_lists = $mailchimp_api->get_lists();

				if ( ! is_wp_error( $mailchimp_lists ) ) {
					foreach ( $mailchimp_lists as $id => $mailchimp_list ) {
						// Use the page ID as the key and the page title as the value.
						$lists[ self::NAMESPACE . '::' . $mailchimp_list->id ] = self::SHORT_NAME . ' - ' . $mailchimp_list->name;
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
			// it overrides previoulsy added.
			$mailchimp = new MC4WP_MailChimp();

			$subscribed = false;

			try {
				foreach ( $lists as $list_id ) {
					// In order to send an opt-in email for existing users, the list member need to be added as 'unsubscribed' then updated to 'subscribed'.
					$result = $mailchimp->list_subscribe(
						$list_id,
						$email,
						array(
							'status' => $opts['double_opt_in'] ? 'pending' : 'subscribed',
							'email_address' => $email,
						),
						false, // update existing (applies to subscribed => should just return error).
						false  // replace interests.
					);

					if ( is_object( $result ) && ! empty( $result->id ) ) {
						$subscribed = true;
					}
				}
			} catch ( \Exception $e ) {
				return new WP_Error( 'subscription-failed', __( 'Failed to subscribe.', 'privacy-portal-sso' ), $e );
			}

			return $subscribed;
		}
		return false;
	}
}
