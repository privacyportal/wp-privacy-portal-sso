<?php
/**
 * Privacy Portal SSO
 *
 * This plugin uses the Privacy Portal OAUTH provider to support user authentication and anonymous newsletter subscriptions.
 * It is originally forked from the "OpenID Connect Generic Client" plugin.
 *
 * @package   Privacy_Portal_SSO
 * @category  General
 * @author    Privacy Portal <support@privacyportal.org> (Forked from Jonathan Daggerhart <jonathan@daggerhart.com>)
 * @copyright 2015-2023 daggerhart, 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link      https://github.com/privacyportal
 *
 * @wordpress-plugin
 * Plugin Name:       Privacy Portal SSO
 * Plugin URI:        https://github.com/privacyportal/wp-privacy-portal-sso
 * Description:       Welcome privacy-conscious users to your website and/or email newsletter, with features like "Sign In With Privacy Portal" and "Subscribe Anonymously".
 * Version:           0.1.2
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Privacy Portal
 * Author URI:        https://privacyportal.org
 * Text Domain:       privacy-portal-sso
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/privacyportal/wp-privacy-portal-sso
 */

/*
Notes
  Spec Doc - http://openid.net/specs/openid-connect-basic-1_0-32.html

  Filters
  - pp-sso-alter-request       - 3 args: request array, plugin settings, specific request op
  - pp-sso-settings-fields     - modify the fields provided on the settings page
  - pp-sso-login-button-text   - modify the login button text
  - pp-sso-cookie-redirect-url - modify the redirect url stored as a cookie
  - pp-sso-user-login-test     - (bool) should the user be logged in based on their claim
  - pp-sso-user-creation-test  - (bool) should the user be created based on their claim
  - pp-sso-auth-url            - modify the authentication url
  - pp-sso-alter-user-data     - modify user data before a new user is created
  - pp-sso-modify-token-response-before-validation - modify the token response before validation
  - pp-sso-modify-id-token-claim-before-validation - modify the token claim before validation

  Actions
  - pp-sso-user-create                     - 2 args: fires when a new user is created by this plugin
  - pp-sso-user-update                     - 1 arg: user ID, fires when user is updated by this plugin
  - pp-sso-update-user-using-current-claim - 2 args: fires every time an existing user logs in and the claims are updated.
  - pp-sso-redirect-user-back              - 2 args: $redirect_url, $user. Allows interruption of redirect during login.
  - pp-sso-user-logged-in                  - 1 arg: $user, fires when user is logged in.
  - pp-sso-cron-daily                      - daily cron action
  - pp-sso-state-not-found                 - the given state does not exist in the database, regardless of its expiration.
  - pp-sso-state-expired                   - the given state exists, but expired before this login attempt.

  Callable actions

  User Meta
  - pp-sso-subject-identity    - the identity of the user provided by the idp
  - pp-sso-last-id-token-claim - the user's most recent id_token claim, decoded
  - pp-sso-last-user-claim     - the user's most recent user_claim
  - pp-sso-last-token-response - the user's most recent token response

  Options
  - pp_sso_settings     - plugin settings
  - pp-sso-valid-states - locally stored generated states
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PP_SSO_CLIENT_SCOPE', 'openid name email' );
define( 'PP_SSO_CLIENT_SCOPE_ENROLL', 'openid email' );
define( 'PP_SSO_ENDPOINT_LOGIN_URL', 'https://app.privacyportal.org/oauth/authorize' );
define( 'PP_SSO_ENDPOINT_USERINFO_URL', 'https://api.privacyportal.org/oauth/userinfo' );
define( 'PP_SSO_ENDPOINT_TOKEN_URL', 'https://api.privacyportal.org/oauth/token' );
define( 'PP_SSO_ENDPOINT_LOGOUT_URL', '' );
define( 'PP_SSO_ACR_VALUES', '' );
define( 'PP_SSO_NO_SSL_VERIFY', 0 );
define( 'PP_SSO_TOKEN_REFRESH_ENABLE', 1 );
define( 'PP_SSO_REDIRECT_USER_BACK', 1 );
define( 'PP_SSO_REDIRECT_ON_LOGOUT', 1 );
define( 'PP_SSO_IDENTITY_KEY', 'sub' );
define( 'PP_SSO_NICKNAME_KEY', 'name' );
define( 'PP_SSO_EMAIL_FORMAT', '{email}' );
define( 'PP_SSO_DISPLAY_NAME_FORMAT', '{email}' );
define( 'PP_SSO_IDENTIFY_WITH_USERNAME', 0 );
define( 'PP_SSO_ALTERNATE_REDIRECT_URI', 1 );
define( 'PP_SSO_HTTP_REQUEST_TIMEOUT', 5 );
define( 'PP_SSO_STATE_TIME_LIMIT', 300 );

/**
 * Privacy_Portal_SSO class.
 *
 * Defines plugin initialization functionality.
 *
 * @package Privacy_Portal_SSO
 * @category  General
 */
class Privacy_Portal_SSO {

	/**
	 * Singleton instance of self
	 *
	 * @var Privacy_Portal_SSO
	 */
	protected static $_instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '0.1.2';

	/**
	 * Plugin settings.
	 *
	 * @var PP_SSO_Option_Settings
	 */
	private $settings;

	/**
	 * Plugin logs.
	 *
	 * @var PP_SSO_Option_Logger
	 */
	private $logger;

	/**
	 * Openid Connect Generic client
	 *
	 * @var PP_SSO_Client
	 */
	private $client;

	/**
	 * Client wrapper.
	 *
	 * @var PP_SSO_Client_Wrapper
	 */
	public $client_wrapper;

	/**
	 * Setup the plugin
	 *
	 * @param PP_SSO_Option_Settings $settings The settings object.
	 * @param PP_SSO_Option_Logger   $logger   The loggin object.
	 *
	 * @return void
	 */
	public function __construct( PP_SSO_Option_Settings $settings, PP_SSO_Option_Logger $logger ) {
		$this->settings = $settings;
		$this->logger = $logger;
		self::$_instance = $this;
	}

	// @codeCoverageIgnoreStart

	/**
	 * WordPress Hook 'init'.
	 *
	 * @return void
	 */
	public function init() {

		$this->client = new PP_SSO_Client(
			$this->settings->client_id,
			$this->settings->client_secret,
			$this->settings->scope,
			$this->settings->scope_enroll,
			$this->settings->endpoint_login,
			$this->settings->endpoint_userinfo,
			$this->settings->endpoint_token,
			$this->get_redirect_uri( $this->settings, 'login' ),
			$this->get_redirect_uri( $this->settings, 'enroll' ),
			$this->settings->acr_values,
			$this->get_state_time_limit( $this->settings ),
			$this->logger
		);

		$this->client_wrapper = PP_SSO_Client_Wrapper::register( $this->client, $this->settings, $this->logger );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		PP_SSO_Login_Form::register( $this->settings, $this->client_wrapper );
		PP_SSO_Subscribe_Form::register( $this->settings, $this->client_wrapper );

		// Add a shortcode to get the auth URL.
		add_shortcode( PP_SSO_Login_Form::LOGIN_URL_SHORTCODE, array( $this->client_wrapper, 'get_authentication_url' ) );

		// Add actions to our scheduled cron jobs.
		add_action( 'pp-sso-cron-daily', array( $this, 'cron_states_garbage_collection' ) );

		$this->upgrade();

		if ( is_admin() ) {
			PP_SSO_Settings_Page::register( $this->settings, $this->logger );
		}
	}

	/**
	 * Get the default redirect URI.
	 *
	 * @param PP_SSO_Option_Settings $settings The settings object.
	 * @param string                 $type Can be set to 'login' or 'enroll'.
	 *
	 * @return string
	 */
	public function get_redirect_uri( PP_SSO_Option_Settings $settings, $type = 'login' ) {
		$slug = 'login' === $type ? 'pp-sso-authorize' : 'pp-sso-subscribe';
		$redirect_uri = admin_url( "admin-ajax.php?action={$slug}" );

		if ( $settings->alternate_redirect_uri ) {
			$redirect_uri = site_url( "/{$slug}" );
		}

		return $redirect_uri;
	}

	/**
	 * Get the default state time limit.
	 *
	 * @param PP_SSO_Option_Settings $settings The settings object.
	 *
	 * @return int
	 */
	public function get_state_time_limit( PP_SSO_Option_Settings $settings ) {
		$state_time_limit = 180;
		// State time limit cannot be zero.
		if ( $settings->state_time_limit ) {
			$state_time_limit = intval( $settings->state_time_limit );
		}

		return $state_time_limit;
	}

	/**
	 * Get the default login button text
	 *
	 * @return string
	 */
	public function get_login_button_text() {
		$text = __( 'Sign In With Privacy Portal', 'privacy-portal-sso' );
		return $text;
	}

	/**
	 * Remove any disabled settings to simplify UI
	 *
	 * @param array $settings settings array.
	 *
	 * @return array
	 */
	public function filter_unused_settings( $settings ) {
		return array_filter(
			$settings,
			function ( $item ) {
				return ( ! array_key_exists( 'disabled', $item ) || true !== $item['disabled'] ) &&
				( ! array_key_exists( 'depends_on', $item ) || $this->settings->{$item['depends_on']} );
			}
		);
	}

	/**
	 * Check if privacy enforcement is enabled, and redirect users that aren't
	 * logged in.
	 *
	 * @return void
	 */
	public function enforce_privacy_redirect() {
		if ( $this->settings->enforce_privacy && ! is_user_logged_in() ) {
			// The client endpoint relies on the wp-admin ajax endpoint.
			if (
				! defined( 'DOING_AJAX' ) ||
				! boolval( constant( 'DOING_AJAX' ) ) ||
				! isset( $_GET['action'] ) ||
				'pp-sso-authorize' != $_GET['action'] ) {
				auth_redirect();
			}
		}
	}

	/**
	 * Enforce privacy settings for rss feeds.
	 *
	 * @param string $content The content.
	 *
	 * @return mixed
	 */
	public function enforce_privacy_feeds( $content ) {
		if ( $this->settings->enforce_privacy && ! is_user_logged_in() ) {
			$content = __( 'Private site', 'privacy-portal-sso' );
		}
		return $content;
	}

	/**
	 * Handle plugin upgrades
	 *
	 * @return void
	 */
	public function upgrade() {
		$last_version = get_option( 'pp-sso-plugin-version', 0 );
		$settings = $this->settings;

		if ( version_compare( self::VERSION, $last_version, '>' ) ) {
			// An upgrade is required.
			self::setup_cron_jobs();

			// @todo move this to another file for upgrade scripts
			if ( isset( $settings->ep_login ) ) {
				$settings->endpoint_login = $settings->ep_login;
				$settings->endpoint_token = $settings->ep_token;
				$settings->endpoint_userinfo = $settings->ep_userinfo;

				unset( $settings->ep_login, $settings->ep_token, $settings->ep_userinfo );
				$settings->save();
			}

			// Update the stored version number.
			update_option( 'pp-sso-plugin-version', self::VERSION );
		}
	}

	/**
	 * Expire state transients by attempting to access them and allowing the
	 * transient's own mechanisms to delete any that have expired.
	 *
	 * @return void
	 */
	public function cron_states_garbage_collection() {
		global $wpdb;
		$states = $wpdb->get_col( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE 'pp-sso-state--%'" );

		if ( ! empty( $states ) ) {
			foreach ( $states as $state ) {
				$transient = str_replace( '_transient_', '', $state );
				get_transient( $transient );
			}
		}
	}

	/**
	 * Ensure cron jobs are added to the schedule.
	 *
	 * @return void
	 */
	public static function setup_cron_jobs() {
		if ( ! wp_next_scheduled( 'pp-sso-cron-daily' ) ) {
			wp_schedule_event( time(), 'daily', 'pp-sso-cron-daily' );
		}
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activation() {
		self::setup_cron_jobs();
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivation() {
		wp_clear_scheduled_hook( 'pp-sso-cron-daily' );
	}

	/**
	 * Simple autoloader.
	 *
	 * @param string $class The class name.
	 *
	 * @return void
	 */
	public static function autoload( $class ) {
		$prefix = 'PP_SSO_';

		if ( stripos( $class, $prefix ) !== 0 ) {
			return;
		}

		$filename = $class . '.php';

		// Internal files are all lowercase and use dashes in filenames.
		if ( false === strpos( $filename, '\\' ) ) {
			$filename = strtolower( str_replace( '_', '-', $filename ) );
		} else {
			$filename  = str_replace( '\\', DIRECTORY_SEPARATOR, $filename );
		}

		$filepath = __DIR__ . '/includes/' . $filename;

		if ( file_exists( $filepath ) ) {
			require_once $filepath;
		}
	}

	/**
	 * Instantiate the plugin and hook into WordPress.
	 *
	 * @return void
	 */
	public static function bootstrap() {
		/**
		 * This is a documented valid call for spl_autoload_register.
		 *
		 * @link https://www.php.net/manual/en/function.spl-autoload-register.php#71155
		 */
		spl_autoload_register( array( 'Privacy_Portal_SSO', 'autoload' ) );

		$settings = new PP_SSO_Option_Settings(
			// Default settings values.
			array(
				// OAuth client settings.
				'login_type'           => defined( 'PP_SSO_LOGIN_TYPE' ) ? PP_SSO_LOGIN_TYPE : 'button',
				'client_id'            => defined( 'PP_SSO_CLIENT_ID' ) ? PP_SSO_CLIENT_ID : '',
				'client_secret'        => defined( 'PP_SSO_CLIENT_SECRET' ) ? PP_SSO_CLIENT_SECRET : '',
				'scope'                => defined( 'PP_SSO_CLIENT_SCOPE' ) ? PP_SSO_CLIENT_SCOPE : '',
				'scope_enroll'         => defined( 'PP_SSO_CLIENT_SCOPE_ENROLL' ) ? PP_SSO_CLIENT_SCOPE_ENROLL : '',
				'endpoint_login'       => defined( 'PP_SSO_ENDPOINT_LOGIN_URL' ) ? PP_SSO_ENDPOINT_LOGIN_URL : '',
				'endpoint_userinfo'    => defined( 'PP_SSO_ENDPOINT_USERINFO_URL' ) ? PP_SSO_ENDPOINT_USERINFO_URL : '',
				'endpoint_token'       => defined( 'PP_SSO_ENDPOINT_TOKEN_URL' ) ? PP_SSO_ENDPOINT_TOKEN_URL : '',
				'endpoint_end_session' => defined( 'PP_SSO_ENDPOINT_LOGOUT_URL' ) ? PP_SSO_ENDPOINT_LOGOUT_URL : '',
				'acr_values'           => defined( 'PP_SSO_ACR_VALUES' ) ? PP_SSO_ACR_VALUES : '',

				// Non-standard settings.
				'no_sslverify'           => defined( 'PP_SSO_NO_SSL_VERIFY' ) ? intval( PP_SSO_NO_SSL_VERIFY ) : 0,
				'http_request_timeout'   => defined( 'PP_SSO_HTTP_REQUEST_TIMEOUT' ) ? intval( PP_SSO_HTTP_REQUEST_TIMEOUT ) : 5,
				'identity_key'           => defined( 'PP_SSO_IDENTITY_KEY' ) ? PP_SSO_IDENTITY_KEY : 'preferred_username',
				'nickname_key'           => defined( 'PP_SSO_NICKNAME_KEY' ) ? PP_SSO_NICKNAME_KEY : 'preferred_username',
				'email_format'           => defined( 'PP_SSO_EMAIL_FORMAT' ) ? PP_SSO_EMAIL_FORMAT : '{email}',
				'displayname_format'     => defined( 'PP_SSO_DISPLAY_NAME_FORMAT' ) ? PP_SSO_DISPLAY_NAME_FORMAT : '',
				'identify_with_username' => defined( 'PP_SSO_IDENTIFY_WITH_USERNAME' ) ? intval( PP_SSO_IDENTIFY_WITH_USERNAME ) : 0,
				'state_time_limit'       => defined( 'PP_SSO_STATE_TIME_LIMIT' ) ? intval( PP_SSO_STATE_TIME_LIMIT ) : 180,

				// Plugin settings.
				'enable_login'             => 0,
				'enforce_privacy'          => defined( 'PP_SSO_ENFORCE_PRIVACY' ) ? intval( PP_SSO_ENFORCE_PRIVACY ) : 0,
				'alternate_redirect_uri'   => defined( 'PP_SSO_ALTERNATE_REDIRECT_URI' ) ? intval( PP_SSO_ALTERNATE_REDIRECT_URI ) : 0,
				'token_refresh_enable'     => defined( 'PP_SSO_TOKEN_REFRESH_ENABLE' ) ? intval( PP_SSO_TOKEN_REFRESH_ENABLE ) : 1,
				'link_existing_users'      => defined( 'PP_SSO_LINK_EXISTING_USERS' ) ? intval( PP_SSO_LINK_EXISTING_USERS ) : 0,
				'create_if_does_not_exist' => defined( 'PP_SSO_CREATE_IF_DOES_NOT_EXIST' ) ? intval( PP_SSO_CREATE_IF_DOES_NOT_EXIST ) : 1,
				'redirect_user_back'       => defined( 'PP_SSO_REDIRECT_USER_BACK' ) ? intval( PP_SSO_REDIRECT_USER_BACK ) : 0,
				'redirect_on_logout'       => defined( 'PP_SSO_REDIRECT_ON_LOGOUT' ) ? intval( PP_SSO_REDIRECT_ON_LOGOUT ) : 1,
				'enable_logging'           => defined( 'PP_SSO_ENABLE_LOGGING' ) ? intval( PP_SSO_ENABLE_LOGGING ) : 0,
				'log_limit'                => defined( 'PP_SSO_LOG_LIMIT' ) ? intval( PP_SSO_LOG_LIMIT ) : 20,

				// Newsletter settings.
				'enable_enroll'            => 0,
				'newsletter_lists'         => array(),
				'newsletter_on_success'    => '',
				'newsletter_on_failure'    => '',
			)
		);

		$logger = new PP_SSO_Option_Logger( 'error', $settings->enable_logging, $settings->log_limit );

		$plugin = new self( $settings, $logger );

		add_action( 'init', array( $plugin, 'init' ) );

		// Privacy Portal Specific overrides.
		add_filter( 'pp-sso-login-button-text', array( $plugin, 'get_login_button_text' ) );
		add_filter( 'pp-sso-settings-fields', array( $plugin, 'filter_unused_settings' ) );

		// Privacy hooks.
		add_action( 'template_redirect', array( $plugin, 'enforce_privacy_redirect' ), 0 );
		add_filter( 'the_content_feed', array( $plugin, 'enforce_privacy_feeds' ), 999 );
		add_filter( 'the_excerpt_rss', array( $plugin, 'enforce_privacy_feeds' ), 999 );
		add_filter( 'comment_text_rss', array( $plugin, 'enforce_privacy_feeds' ), 999 );
	}

	/**
	 * Create (if needed) and return a singleton of self.
	 *
	 * @return Privacy_Portal_SSO
	 */
	public static function instance() {
		if ( null === self::$_instance ) {
			self::bootstrap();
		}
		return self::$_instance;
	}
}

Privacy_Portal_SSO::instance();

register_activation_hook( __FILE__, array( 'Privacy_Portal_SSO', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'Privacy_Portal_SSO', 'deactivation' ) );

// Provide publicly accessible plugin helper functions.
require_once 'includes/functions.php';
