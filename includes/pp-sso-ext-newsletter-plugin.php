<?php
/**
 * Plugin integration with The Newsletter Plugin (TNP) class.
 *
 * @package   Privacy_Portal_SSO
 * @category  Newsletter
 * @author    Privacy Portal <support@privacyportal.org>
 * @copyright 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * PP_SSO_Ext_Newsletter_Plugin class.
 *
 * This class adds support for the Newsletter
 *
 * @package  Privacy_Portal_SSO
 * @category Newsletter
 */
class PP_SSO_Ext_Newsletter_Plugin {

	const NAMESPACE  = 'newsletter';
	const FULL_NAME  = 'The Newsletter Plugin';
	const SHORT_NAME = 'Newsletter';

	/**
	 * Returns true if the plugin is installed and set up correctly
	 *
	 * @return bool
	 */
	public static function is_detected() {
		return ! (
			! class_exists( Newsletter::class ) ||
			! method_exists( Newsletter::class, 'instance' ) ||
			! method_exists( Newsletter::class, 'get_lists' ) ||
			! method_exists( Newsletter::class, 'get_user_by_email' ) ||
			! method_exists( Newsletter::class, 'save_user' ) ||
			! class_exists( TNP_User::class ) ||
			! defined( TNP_User::class . '::STATUS_CONFIRMED' ) ||
			! defined( TNP_User::class . '::STATUS_COMPLAINED' ) ||
			! defined( TNP_User::class . '::STATUS_NOT_CONFIRMED' ) ||
			! defined( TNP_User::class . '::STATUS_UNSUBSCRIBED' )
		);
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
				$newsletter_api = Newsletter::instance();

				// is there a check to make sure it's activated?
				$newsletter_lists = $newsletter_api->get_lists();

				if ( ! is_wp_error( $newsletter_lists ) ) {
					foreach ( $newsletter_lists as $id => $newsletter_list ) {
						// Use the page ID as the key and the page title as the value.
						$lists[ self::NAMESPACE . '::' . $newsletter_list->id ] = self::SHORT_NAME . ' - ' . $newsletter_list->name;
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
			// Get Newsletter API instance.
			$newsletter_api = Newsletter::instance();

			// get user.
			$user = $newsletter_api->get_user_by_email( $email );

			if ( $user ) {
				if ( TNP_User::STATUS_CONFIRMED === $user->status ) {
					return new WP_Error( 'email-already-exists', __( 'Email address already exists.', 'privacy-portal-sso' ), $user );
				} else if ( TNP_User::STATUS_COMPLAINED === $user->status ) {
					return new WP_Error( 'invalid-email', __( 'Please use a different address.', 'privacy-portal-sso' ), $user );
				} else if ( $opts['double_opt_in'] && TNP_User::STATUS_NOT_CONFIRMED === $user->status ) {
					// set as unsubscribed to retrigger.
					$newsletter_api->save_user(
						array(
							'id'      => $user->id,
							'email'   => $email,
							'status'  => TNP_User::STATUS_UNSUBSCRIBED,
							'updated' => time(),
						)
					);
				}
			}

			// subscribe user.
			$user = array(
				'id'    => $user->id,
				'email' => $email,
			);

			// Lists (an array under the key "lists").
			foreach ( $lists as $list_id ) {
				$user[ 'list_' . ( (int) $list_id ) ] = 1;
			}

			$user['status'] = $opts['double_opt_in'] ? TNP_User::STATUS_NOT_CONFIRMED : TNP_User::STATUS_CONFIRMED;
			$user['token'] = $newsletter_api->get_token();
			$user['updated'] = time();

			try {
				$newsletter_api->save_user( $user );
			} catch ( \Exception $e ) {
				return new WP_Error( 'subscription-failed', __( 'Failed to subscribe.', 'privacy-portal-sso' ), $e );
			}

			return true;
		}
		return false;
	}
}
