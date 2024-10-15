<?php
/**
 * Plugin integration with MailPoet class.
 *
 * @package   Privacy_Portal_SSO
 * @category  Newsletter
 * @author    Privacy Portal <support@privacyportal.org>
 * @copyright 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * PP_SSO_Ext_MailPoet_Plugin class.
 *
 * This class adds support for the MailPoet
 *
 * @package  Privacy_Portal_SSO
 * @category Newsletter
 */
class PP_SSO_Ext_MailPoet_Plugin {

	const NAMESPACE  = 'mailpoet';
	const FULL_NAME  = 'MailPoet Plugin';
	const SHORT_NAME = 'MailPoet';

	/**
	 * Returns true if the plugin is installed and set up correctly
	 *
	 * @return bool
	 */
	public static function is_detected() {
		if (
			! class_exists( \MailPoet\API\API::class ) ||
			! method_exists( \MailPoet\API\API::class, 'MP' )
		) {
			return false;
		}
		$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
		return isset( $mailpoet_api ) && $mailpoet_api->isSetupComplete();
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
				// Get MailPoet API instance and fetch the lists.
				$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
				$mailpoet_lists = $mailpoet_api->getLists();

				if ( ! is_wp_error( $mailpoet_lists ) ) {
					foreach ( $mailpoet_lists as $id => $mailpoet_list ) {
						// Use the page ID as the key and the page title as the value.
						$lists[ self::NAMESPACE . '::' . $mailpoet_list['id'] ] = self::SHORT_NAME . ' - ' . $mailpoet_list['name'];
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
			// Get MailPoet API instance.
			$mailpoet_api = \MailPoet\API\API::MP( 'v1' );

			try {
				$subscriber = $mailpoet_api->getSubscriber( $email );
				// @codingStandardsIgnoreLine
			} catch ( \Exception $e ) {
				// Do nothing.
			}

			try {
				if ( ! isset( $subscriber ) || ! $subscriber ) {
					// Subscriber doesn't exist let's create one.
					$mailpoet_api->addSubscriber( array( 'email' => $email ), $lists );
				} else {
					// In case subscriber exists just add him to new lists.
					$mailpoet_api->subscribeToLists( $email, $lists );
				}

				return true;
			} catch ( \Exception $e ) {
				return new WP_Error( 'subscription-failed', __( 'Failed to subscribe.', 'privacy-portal-sso' ), $e );
			}
		}
		return false;
	}
}
