<?php
/**
 * Plugin Admin settings page class.
 *
 * @package   Privacy_Portal_SSO
 * @category  Settings
 * @author    Privacy Portal <support@privacyportal.org> (Forked from Jonathan Daggerhart <jonathan@daggerhart.com>)
 * @copyright 2015-2023 daggerhart, 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * PP_SSO_Settings_Page class.
 *
 * Admin settings page.
 *
 * @package Privacy_Portal_SSO
 * @category  Settings
 */
class PP_SSO_Settings_Page {

	/**
	 * Local copy of the settings provided by the base plugin.
	 *
	 * @var PP_SSO_Option_Settings
	 */
	private $settings;

	/**
	 * Instance of the plugin logger.
	 *
	 * @var PP_SSO_Option_Logger
	 */
	private $logger;

	/**
	 * The controlled list of settings & associated defined during
	 * construction for i18n reasons.
	 *
	 * @var array
	 */
	private $settings_fields = array();

	/**
	 * Options page slug.
	 *
	 * @var string
	 */
	private $options_page_name = 'privacy-portal-sso';

	/**
	 * Options page settings group name.
	 *
	 * @var string
	 */
	private $settings_field_group;

	/**
	 * Section-specific Footers.
	 *
	 * @var array
	 */
	private $settings_footers;

	/**
	 * Settings page class constructor.
	 *
	 * @param PP_SSO_Option_Settings $settings The plugin settings object.
	 * @param PP_SSO_Option_Logger   $logger   The plugin logging class object.
	 */
	public function __construct( PP_SSO_Option_Settings $settings, PP_SSO_Option_Logger $logger ) {

		$this->settings             = $settings;
		$this->logger               = $logger;
		$this->settings_field_group = $this->settings->get_option_name() . '-group';

		$fields = $this->get_settings_fields();

		// Some simple pre-processing.
		foreach ( $fields as $key => &$field ) {
			$field['key']  = $key;
			$field['name'] = $this->settings->get_option_name() . '[' . $key . ']';
		}

		// Allow alterations of the fields.
		$this->settings_fields = $fields;

		// store footers.
		$this->settings_footers = $this->get_settings_footers();
	}

	/**
	 * Hook the settings page into WordPress.
	 *
	 * @param PP_SSO_Option_Settings $settings A plugin settings object instance.
	 * @param PP_SSO_Option_Logger   $logger   A plugin logger object instance.
	 *
	 * @return void
	 */
	public static function register( PP_SSO_Option_Settings $settings, PP_SSO_Option_Logger $logger ) {
		$settings_page = new self( $settings, $logger );

		// Add our options page the the admin menu.
		add_action( 'admin_menu', array( $settings_page, 'admin_menu' ) );

		// Register our settings.
		add_action( 'admin_init', array( $settings_page, 'admin_init' ) );
	}

	/**
	 * Implements hook admin_menu to add our options/settings page to the
	 *  dashboard menu.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'Privacy Portal SSO', 'privacy-portal-sso' ),
			__( 'Privacy Portal SSO', 'privacy-portal-sso' ),
			'manage_options',
			$this->options_page_name,
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Converts the section specific footer data into html
	 *
	 * @param string $section_id ID of the settings section.
	 *
	 * @return string
	 */
	public function format_section_footer( $section_id ) {
		return "<div style='padding: 20px; border: 0.5px solid; border-radius: 6px;'>" . join(
			'<br/>',
			array_map(
				function ( $value ) {
					$title_and_description = "<span><strong>> {$value['title']}</strong></span>";
					if ( isset( $value['description'] ) && ! empty( $value['description'] ) ) {
						$title_and_description .= "<br/><span>{$value['description']}</span>";
					}
					return $title_and_description . '<br/>' .
					join(
						'',
						array_map(
							function ( $key, $value ) {
								return "<p class=\"description\"><small><strong>{$key}</strong></small> <code><small>{$value}</small></code></p>";
							},
							array_keys( $value['properties'] ),
							$value['properties']
						)
					);
				},
				$this->settings_footers[ $section_id ]
			)
		) . '</div>';
	}

	/**
	 * Implements hook admin_init to register our settings.
	 *
	 * @return void
	 */
	public function admin_init() {
		register_setting(
			$this->settings_field_group,
			$this->settings->get_option_name(),
			array(
				$this,
				'sanitize_settings',
			)
		);

		add_settings_section(
			'common_settings',
			__( 'Common OAUTH Settings (required)', 'privacy-portal-sso' ),
			array( $this, 'common_settings_description' ),
			$this->options_page_name,
			array(
				'after_section' => '<br/><hr style="height: 0.5px; background-color: #333; border: none;">',
			)
		);

		add_settings_section(
			'login_settings',
			__( 'Configure "Sign In With Privacy Portal"', 'privacy-portal-sso' ),
			array( $this, 'login_settings_description' ),
			$this->options_page_name,
			array(
				'before_section' => '<br/>',
				'after_section' => $this->settings->enable_login ? $this->format_section_footer( 'login_settings' ) : '',
			)
		);

		add_settings_section(
			'enroll_settings',
			__( 'Configure "Subscribe Anonymously with Privacy Portal"', 'privacy-portal-sso' ),
			array( $this, 'enroll_settings_description' ),
			$this->options_page_name,
			array(
				'before_section' => '<br/>',
				'after_section' => $this->settings->enable_enroll ? $this->format_section_footer( 'enroll_settings' ) : '',
			)
		);

		if ( $this->settings->enable_login || $this->settings->enable_enroll ) {
			add_settings_section(
				'log_settings',
				__( 'Log Settings', 'privacy-portal-sso' ),
				array( $this, 'log_settings_description' ),
				$this->options_page_name,
				array(
					'before_section' => '<br/>',
				)
			);
		}

		// Preprocess fields and add them to the page.
		foreach ( $this->settings_fields as $key => $field ) {
			// Make sure each key exists in the settings array.
			if ( ! isset( $this->settings->{ $key } ) ) {
				$this->settings->{ $key } = null;
			}

			// Determine appropriate output callback.
			switch ( $field['type'] ) {
				case 'checkbox':
					$callback = 'do_checkbox';
					break;

				case 'select':
					$callback = 'do_select';
					break;

				case 'text':
				default:
					$callback = 'do_text_field';
					break;
			}

			// Add the field.
			add_settings_field(
				$key,
				$field['title'],
				array( $this, $callback ),
				$this->options_page_name,
				$field['section'],
				$field
			);
		}
	}

	/**
	 * Get the array of wordpress pages
	 *
	 * @return array
	 */
	private function get_page_options() {
		$pages = get_pages();
		$page_map = array(
			'' => 'Default',
		);

		foreach ( $pages as $page ) {
			// Use the page ID as the key and the page title as the value.
			$page_map[ $page->ID ] = $page->post_title;
		}

		return $page_map;
	}

	/**
	 * Get the array of newsletter lists
	 *
	 * @return array
	 */
	private function get_newsletter_lists() {
		$lists = array(
			PP_SSO_Ext_MailPoet_Plugin::FULL_NAME   => PP_SSO_Ext_MailPoet_Plugin::get_newsletter_lists(),
			PP_SSO_Ext_MC4WP_Plugin::FULL_NAME      => PP_SSO_Ext_MC4WP_Plugin::get_newsletter_lists(),
			PP_SSO_Ext_Newsletter_Plugin::FULL_NAME => PP_SSO_Ext_Newsletter_Plugin::get_newsletter_lists(),
			PP_SSO_Ext_ConvertKit_Plugin::FULL_NAME => PP_SSO_Ext_ConvertKit_Plugin::get_newsletter_lists(),
		);
		return $lists;
	}

	/**
	 * Get the plugin settings fields definition.
	 *
	 * @return array
	 */
	private function get_settings_footers() {
		return array(
			'login_settings' => array(
				array(
					'title' => __( 'Important Note:', 'privacy-portal-sso' ),
					'description' => __( 'This plugin defines new WordPress Permalinks needed for the redirection back to your site. The first time you enable "Sign In with Privacy Portal", you will need to manually go to the Permalinks page under Settings and click on "Save Changes".', 'privacy-portal-sso' ),
					'properties' => array(),
				),
				array(
					'title' => __( 'To use Sign In With Privacy Portal please make sure your OAUTH app has the following configuration:', 'privacy-portal-sso' ),
					'properties' => array(
						'redirect_uri' => site_url( '/pp-sso-authorize' ),
					),
				),
				array(
					'title' => __( 'Wordpress Shortcodes:', 'privacy-portal-sso' ),
					'properties' => array(
						'Login Button' => '[' . PP_SSO_Login_Form::LOGIN_BUTTON_SHORTCODE . ']',
						'Login URL' => '[' . PP_SSO_Login_Form::LOGIN_URL_SHORTCODE . ']',
					),
				),
				array(
					'title' => __( 'Email Configuration:', 'privacy-portal-sso' ),
					'description' => __( 'When using "Sign In With Privacy Portal", users can only receive emails from domains you own. In order to send email to users, you must verify your domain in the OAUTH app configuration and you must make sure your email has the correct security settings (SPF, DKIM, and DMARC). Usually this simply means that you also need to follow the domain verification steps required by your email provider.', 'privacy-portal-sso' ),
					'properties' => array(),
				),
			),
			'enroll_settings' => array(
				array(
					'title' => __( 'Important Note:', 'privacy-portal-sso' ),
					'description' => __( 'This plugin defines new WordPress Permalinks needed for the redirection back to your site. The first time you enable "Subscribe Anonymously with Privacy Portal", you will need to manually go to the Permalinks page under Settings and click on "Save Changes".', 'privacy-portal-sso' ),
					'properties' => array(),
				),
				array(
					'title' => __( 'To use Subscribe Anonymously with Privacy Portal please make sure your OAUTH app has the following configuration:', 'privacy-portal-sso' ),
					'properties' => array(
						'redirect_uri' => site_url( '/pp-sso-subscribe' ),
					),
				),
				array(
					'title' => __( 'Wordpress Shortcodes:', 'privacy-portal-sso' ),
					'properties' => array(
						'Subscribe Button' => '[' . PP_SSO_Subscribe_Form::SUBSCRIBE_ANONYMOUSLY_SHORTCODE . ']',
						'Subscribe Button + Styling' => '[' . PP_SSO_Subscribe_Form::SUBSCRIBE_ANONYMOUSLY_SHORTCODE . ' subtitle="" style_background_color="#cccccc" style_color="black" style_font_size="14px" style_font_weight="strong" style_font_family="Arial, Helvetica, sans-serif" style_padding="10px" style_margin="0px" style_border_radius="6px"]',
						'Subscribe URL' => '[' . PP_SSO_Login_Form::LOGIN_URL_SHORTCODE . ' type="enroll"]',
					),
				),
				array(
					'title' => __( 'Wordpress Shortcode Alternative:', 'privacy-portal-sso' ),
					'description' => __( 'Instead of using shortcodes, with plugins such as MailPoet, you can integrate the "Subscribe Anonymously" button directly into the MailPoet form by using the following html elements that you can style as you please.', 'privacy-portal-sso' ),
					'properties' => array(
						'html' => esc_html( '<div><a href="{pp_sso_subscribe_anonymously_url}">Subscribe Anonymously</a><p class="hide-when-empty">{pp_sso_subscribe_anonymously_message}</p></div>' ),
						'css' => esc_html( '.hide-when-empty:empty { display: none; }' ),
					),
				),
				array(
					'title' => __( 'Email Configuration:', 'privacy-portal-sso' ),
					'description' => __( 'When using "Subscribe Anonymously With Privacy Portal", users can only receive emails from domains you own. In order to send email to users, you must verify your domain in the OAUTH app configuration and you must make sure your email has the correct security settings (SPF, DKIM, and DMARC). Usually this simply means that you also need to follow the domain verification steps required by your email provider.', 'privacy-portal-sso' ),
					'properties' => array(),
				),
			),
		);
	}

	/**
	 * Get the plugin settings fields definition.
	 *
	 * @return array
	 */
	private function get_settings_fields() {

		/**
		 * Simple settings fields have:
		 *
		 * - title
		 * - description
		 * - type ( checkbox | text | select )
		 * - section - settings/option page section ( common_settings | login_settings )
		 * - example (optional example will appear beneath description and be wrapped in <code>)
		 */
		$fields = array(
			'client_id'         => array(
				'title'       => __( 'Client ID', 'privacy-portal-sso' ),
				'description' => __( 'The Client ID is generated automatically when you create an OAUTH app on Privacy Portal.', 'privacy-portal-sso' ),
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_CLIENT_ID' ),
				'section'     => 'common_settings',
			),
			'client_secret'     => array(
				'title'       => __( 'Client Secret Key', 'privacy-portal-sso' ),
				'description' => __( 'The Client Secret can be generated in your OAUTH app\'s authentication settings on Privacy Portal.', 'privacy-portal-sso' ),
				'type'        => 'password',
				'disabled'    => defined( 'PP_SSO_CLIENT_SECRET' ),
				'section'     => 'common_settings',
			),
			'enable_login'     => array(
				'title'       => __( 'Enable SSO', 'privacy-portal-sso' ),
				'description' => __( 'Enable authentication using "Sign In With Privacy Portal".', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'section'     => 'login_settings',
			),
			'enable_enroll'     => array(
				'title'       => __( 'Enable Subscriptions', 'privacy-portal-sso' ),
				'description' => __( 'Enable Anonymous Subscriptions with Privacy Portal.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'section'     => 'enroll_settings',
			),
			'login_type'        => array(
				'title'       => __( 'Login Type', 'privacy-portal-sso' ),
				'description' => __( 'Select how the client (login form) should provide login options.', 'privacy-portal-sso' ),
				'type'        => 'select',
				'options'     => array(
					'button' => __( 'Sign-In Button on login form', 'privacy-portal-sso' ),
					'auto'   => __( 'Auto Login - SSO', 'privacy-portal-sso' ),
				),
				'disabled'    => defined( 'PP_SSO_LOGIN_TYPE' ),
				'depends_on'  => 'enable_login',
				'section'     => 'login_settings',
			),
			'scope'             => array(
				'title'       => __( 'OpenID Scope', 'privacy-portal-sso' ),
				'description' => __( 'Space separated list of scopes this client should access during Login.', 'privacy-portal-sso' ),
				'example'     => 'openid name email',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_CLIENT_SCOPE' ),
				'section'     => 'login_settings',
			),
			'scope_enroll'      => array(
				'title'       => __( 'OpenID Scope (Newsletter)', 'privacy-portal-sso' ),
				'description' => __( 'Space separated list of scopes this client should access during Anonymous Subscriptions.', 'privacy-portal-sso' ),
				'example'     => 'openid email',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_CLIENT_SCOPE_ENROLL' ),
				'section'     => 'enroll_settings',
			),
			'endpoint_login'    => array(
				'title'       => __( 'Login Endpoint URL', 'privacy-portal-sso' ),
				'description' => __( 'Identify provider authorization endpoint.', 'privacy-portal-sso' ),
				'example'     => 'https://app.privacyportal.org/oauth/authorize',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_ENDPOINT_LOGIN_URL' ),
				'section'     => 'common_settings',
			),
			'endpoint_userinfo' => array(
				'title'       => __( 'Userinfo Endpoint URL', 'privacy-portal-sso' ),
				'description' => __( 'Identify provider User information endpoint.', 'privacy-portal-sso' ),
				'example'     => 'https://api.privacyportal.org/oauth/userinfo',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_ENDPOINT_USERINFO_URL' ),
				'section'     => 'common_settings',
			),
			'endpoint_token'    => array(
				'title'       => __( 'Token Endpoint URL', 'privacy-portal-sso' ),
				'description' => __( 'Identify provider token endpoint.', 'privacy-portal-sso' ),
				'example'     => 'https://api.privacyportal.org/oauth/token',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_ENDPOINT_TOKEN_URL' ),
				'section'     => 'common_settings',
			),
			'endpoint_end_session'    => array(
				'title'       => __( 'End Session Endpoint URL', 'privacy-portal-sso' ),
				'description' => __( 'Identify provider logout endpoint.', 'privacy-portal-sso' ),
				'example'     => 'https://example.com/oauth2/logout',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_ENDPOINT_LOGOUT_URL' ),
				'section'     => 'common_settings',
			),
			'acr_values'    => array(
				'title'       => __( 'ACR values', 'privacy-portal-sso' ),
				'description' => __( 'Use a specific defined authentication contract from the IDP - optional.', 'privacy-portal-sso' ),
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_ACR_VALUES' ),
				'section'     => 'common_settings',
			),
			'identity_key'     => array(
				'title'       => __( 'Identity Key', 'privacy-portal-sso' ),
				'description' => __( 'Where in the user claim array to find the user\'s identification data. Possible standard values: preferred_username, name, or sub. If you\'re having trouble, use "sub".', 'privacy-portal-sso' ),
				'example'     => 'preferred_username',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_IDENTITY_KEY' ),
				'section'     => 'common_settings',
			),
			'no_sslverify'      => array(
				'title'       => __( 'Disable SSL Verify', 'privacy-portal-sso' ),
				// translators: %1$s HTML tags for layout/styles, %2$s closing HTML tag for styles.
				'description' => sprintf( __( 'Do not require SSL verification during authorization. The OAuth extension uses curl to make the request. By default CURL will generally verify the SSL certificate to see if its valid an issued by an accepted CA. This setting disabled that verification.%1$sNot recommended for production sites.%2$s', 'privacy-portal-sso' ), '<br><strong>', '</strong>' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_NO_SSL_VERIFY' ),
				'section'     => 'common_settings',
			),
			'http_request_timeout'      => array(
				'title'       => __( 'HTTP Request Timeout', 'privacy-portal-sso' ),
				'description' => __( 'Set the timeout for requests made to the IDP. Default value is 5.', 'privacy-portal-sso' ),
				'example'     => 30,
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_HTTP_REQUEST_TIMEOUT' ),
				'section'     => 'common_settings',
			),
			'enforce_privacy'   => array(
				'title'       => __( 'Enforce Privacy', 'privacy-portal-sso' ),
				'description' => __( 'Require users be logged in to see the site.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_ENFORCE_PRIVACY' ),
				'section'     => 'login_settings',
				'depends_on'  => 'enable_login',
			),
			'alternate_redirect_uri'   => array(
				'title'       => __( 'Alternate Redirect URI', 'privacy-portal-sso' ),
				'description' => __( 'Provide an alternative redirect route. Useful if your server is causing issues with the default admin-ajax method. You must flush rewrite rules after changing this setting. This can be done by saving the Permalinks settings page.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_ALTERNATE_REDIRECT_URI' ),
				'section'     => 'login_settings',
				'depends_on'  => 'enable_login',
			),
			'nickname_key'     => array(
				'title'       => __( 'Nickname Key', 'privacy-portal-sso' ),
				'description' => __( 'Where in the user claim array to find the user\'s nickname. Possible standard values: preferred_username, name, or sub.', 'privacy-portal-sso' ),
				'example'     => 'preferred_username',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_NICKNAME_KEY' ),
				'section'     => 'common_settings',
				'depends_on'  => 'enable_login',
			),
			'email_format'     => array(
				'title'       => __( 'Email Formatting', 'privacy-portal-sso' ),
				'description' => __( 'String from which the user\'s email address is built. Specify "{email}" as long as the user claim contains an email claim.', 'privacy-portal-sso' ),
				'example'     => '{email}',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_EMAIL_FORMAT' ),
				'section'     => 'common_settings',
				'depends_on'  => 'enable_login',
			),
			'displayname_format'     => array(
				'title'       => __( 'Display Name Formatting', 'privacy-portal-sso' ),
				'description' => __( 'String from which the user\'s display name is built.', 'privacy-portal-sso' ),
				'example'     => '{given_name} {family_name}',
				'type'        => 'text',
				'disabled'    => defined( 'PP_SSO_DISPLAY_NAME_FORMAT' ),
				'section'     => 'common_settings',
				'depends_on'  => 'enable_login',
			),
			'identify_with_username'     => array(
				'title'       => __( 'Identify with User Name', 'privacy-portal-sso' ),
				'description' => __( 'If checked, the user\'s identity will be determined by the user name instead of the email address.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_IDENTIFY_WITH_USERNAME' ),
				'section'     => 'common_settings',
				'depends_on'  => 'enable_login',
			),
			'state_time_limit'     => array(
				'title'       => __( 'State time limit', 'privacy-portal-sso' ),
				'description' => __( 'State valid time in seconds. Defaults to 180', 'privacy-portal-sso' ),
				'type'        => 'number',
				'disabled'    => defined( 'PP_SSO_STATE_TIME_LIMIT' ),
				'section'     => 'common_settings',
				'depends_on'  => 'enable_login',
			),
			'token_refresh_enable'   => array(
				'title'       => __( 'Enable Refresh Token', 'privacy-portal-sso' ),
				'description' => __( 'If checked, support refresh tokens used to obtain access tokens from supported IDPs.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_TOKEN_REFRESH_ENABLE' ),
				'section'     => 'common_settings',
				'depends_on'  => 'enable_login',
			),
			'link_existing_users'   => array(
				'title'       => __( 'Link Existing Users', 'privacy-portal-sso' ),
				'description' => __( 'If a WordPress account already exists with the same identity as a newly-authenticated user over OpenID Connect, login as that user instead of generating an error.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_LINK_EXISTING_USERS' ),
				'section'     => 'login_settings',
				'depends_on'  => 'enable_login',
			),
			'create_if_does_not_exist'   => array(
				'title'       => __( 'Create user if does not exist', 'privacy-portal-sso' ),
				'description' => __( 'If the user identity is not linked to an existing WordPress user, it is created. If this setting is not enabled, and if the user authenticates with an account which is not linked to an existing WordPress user, then the authentication will fail.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_CREATE_IF_DOES_NOT_EXIST' ),
				'section'     => 'login_settings',
				'depends_on'  => 'enable_login',
			),
			'redirect_user_back'   => array(
				'title'       => __( 'Redirect Back to Origin Page', 'privacy-portal-sso' ),
				'description' => __( 'After a successful OpenID Connect authentication, this will redirect the user back to the page on which they clicked the OpenID Connect login button. This will cause the login process to proceed in a traditional WordPress fashion. For example, users logging in through the default wp-login.php page would end up on the WordPress Dashboard and users logging in through the WooCommerce "My Account" page would end up on their account page.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_REDIRECT_USER_BACK' ),
				'section'     => 'login_settings',
				'depends_on'  => 'enable_login',
			),
			'redirect_on_logout'   => array(
				'title'       => __( 'Redirect to the login screen when session is expired', 'privacy-portal-sso' ),
				'description' => __( 'When enabled, this will automatically redirect the user back to the WordPress login page if their access token has expired.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_REDIRECT_ON_LOGOUT' ),
				'section'     => 'login_settings',
				'depends_on'  => 'enable_login',
			),
			'newsletter_lists'     => array(
				'title'       => __( 'Newsletter Lists', 'privacy-portal-sso' ),
				'description' => __( 'Select one or more lists to subscribe users to when using "Subscribe Anonymously". You must have one of the supported Newsletter plugins installed and configured.', 'privacy-portal-sso' ),
				'type'        => 'select',
				'options'     => $this->get_newsletter_lists(),
				'multiple'    => true,
				'grouped'     => true,
				'section'     => 'enroll_settings',
				'depends_on'  => 'enable_enroll',
			),
			'newsletter_double_opt_in' => array(
				'title'       => __( 'Try Enable Double Opt-In', 'privacy-portal-sso' ),
				'description' => __( 'When possible, enables confirmation emails. You might need to also enable Double opt-in directly with your newsletter provider.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'section'     => 'enroll_settings',
				'depends_on'  => 'enable_enroll',
			),
			'newsletter_on_success' => array(
				'title'       => __( 'On Subscription Success', 'privacy-portal-sso' ),
				'description' => __( 'Go to page', 'privacy-portal-sso' ),
				'type'        => 'select',
				'options'     => $this->get_page_options(),
				'section'     => 'enroll_settings',
				'depends_on'  => 'enable_enroll',
			),
			'newsletter_on_failure' => array(
				'title'       => __( 'On Subscription Failure', 'privacy-portal-sso' ),
				'description' => __( 'Go to page', 'privacy-portal-sso' ),
				'type'        => 'select',
				'options'     => $this->get_page_options(),
				'section'     => 'enroll_settings',
				'depends_on'  => 'enable_enroll',
			),
			'enable_logging'    => array(
				'title'       => __( 'Enable Logging', 'privacy-portal-sso' ),
				'description' => __( 'Very simple log messages for debugging purposes.', 'privacy-portal-sso' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'PP_SSO_ENABLE_LOGGING' ),
				'section'     => 'log_settings',
			),
			'log_limit'         => array(
				'title'       => __( 'Log Limit', 'privacy-portal-sso' ),
				'description' => __( 'Number of items to keep in the log. These logs are stored as an option in the database, so space is limited.', 'privacy-portal-sso' ),
				'type'        => 'number',
				'disabled'    => defined( 'PP_SSO_LOG_LIMIT' ),
				'section'     => 'log_settings',
			),
		);

		return apply_filters( 'pp-sso-settings-fields', $fields );
	}

	/**
	 * Sanitization callback for settings/option page.
	 *
	 * @param array $input The submitted settings values.
	 *
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$options = array();

		// Loop through settings fields to control what we're saving.
		foreach ( $this->settings_fields as $key => $field ) {
			if ( isset( $input[ $key ] ) ) {
				if ( is_array( $input[ $key ] ) && boolval( $this->settings_fields[ $key ]['multiple'] ) ) {
					// handle multiple items.
					$multi_value = array();
					foreach ( $input[ $key ] as $item ) {
						array_push( $multi_value, sanitize_text_field( trim( $item ) ) );
					}
					$options[ $key ] = $multi_value;
				} else {
					$options[ $key ] = sanitize_text_field( trim( $input[ $key ] ) );
				}
			} else {
				$options[ $key ] = '';
			}
		}

		return $options;
	}

	/**
	 * Output the options/settings page.
	 *
	 * @return void
	 */
	public function settings_page() {
		wp_enqueue_style( 'pp-sso-admin', plugin_dir_url( __DIR__ ) . 'css/styles-admin.css', array(), Privacy_Portal_SSO::VERSION, 'all' );

		$redirect_uri = admin_url( 'admin-ajax.php?action=pp-sso-authorize' );

		if ( $this->settings->alternate_redirect_uri ) {
			$redirect_uri = site_url( '/pp-sso-authorize' );
		}
		?>
		<div class="wrap">
			<h2><?php print esc_html( get_admin_page_title() ); ?></h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->settings_field_group );
				do_settings_sections( $this->options_page_name );
				submit_button();

				// Simple debug to view settings array.
				if ( isset( $_GET['debug'] ) ) {
					var_dump( $this->settings->get_values() );
				}
				?>
			</form>

			<?php if ( $this->settings->enable_logging ) { ?>
				<h2><?php esc_html_e( 'Logs', 'privacy-portal-sso' ); ?></h2>
				<div id="logger-table-wrapper">
					<?php print wp_kses_post( $this->logger->get_logs_table() ); ?>
				</div>

			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Output a standard text field.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_text_field( $field ) {
		?>
		<input type="<?php print esc_attr( $field['type'] ); ?>"
			id="<?php print esc_attr( $field['key'] ); ?>"
			class="large-text<?php echo ( ! empty( $field['disabled'] ) && boolval( $field['disabled'] ) === true ) ? ' disabled' : ''; ?>"
			name="<?php print esc_attr( $field['name'] ); ?>"
			<?php echo ( ! empty( $field['disabled'] ) && boolval( $field['disabled'] ) === true ) ? ' disabled' : ''; ?>
			value="<?php print esc_attr( $this->settings->{ $field['key'] } ); ?>">
		<?php
		$this->do_field_description( $field );
	}

	/**
	 * Output a checkbox for a boolean setting.
	 *  - hidden field is default value so we don't have to check isset() on save.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_checkbox( $field ) {
		$hidden_value = 0;
		if ( ! empty( $field['disabled'] ) && boolval( $field['disabled'] ) === true ) {
			$hidden_value = intval( $this->settings->{ $field['key'] } );
		}
		?>
		<input type="hidden" name="<?php print esc_attr( $field['name'] ); ?>" value="<?php print esc_attr( strval( $hidden_value ) ); ?>">
		<input type="checkbox"
			   id="<?php print esc_attr( $field['key'] ); ?>"
				 name="<?php print esc_attr( $field['name'] ); ?>"
				 <?php echo ( ! empty( $field['disabled'] ) && boolval( $field['disabled'] ) === true ) ? ' disabled="disabled"' : ''; ?>
			   value="1"
			<?php checked( $this->settings->{ $field['key'] }, 1 ); ?>>
		<?php
		$this->do_field_description( $field );
	}

	/**
	 * Output a select control.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_select( $field ) {
		$current_value = isset( $this->settings->{ $field['key'] } ) ? $this->settings->{ $field['key'] } : '';
		?>
		<select
			id="<?php print esc_attr( $field['key'] ); ?>"
			name="<?php print ( ! empty( $field['multiple'] ) && boolval( $field['multiple'] ) === true ) ? esc_attr( $field['name'] ) . '[]' : esc_attr( $field['name'] ); ?>"
			<?php print ( ! empty( $field['disabled'] ) && boolval( $field['disabled'] ) === true ) ? ' disabled' : ''; ?>
			<?php print ( ! empty( $field['multiple'] ) && boolval( $field['multiple'] ) === true ) ? ' multiple' : ''; ?>
			>
			<?php if ( ! empty( $field['grouped'] ) && boolval( $field['grouped'] ) === true ) : ?>
				<?php foreach ( $field['options'] as $group_name => $options ) : ?>
					<optgroup label="<?php print esc_attr( $group_name ); ?>">
					<?php foreach ( $field['options'][ $group_name ] as $value => $text ) : ?>
						<option value="<?php print esc_attr( $value ); ?>" <?php ( ! empty( $field['multiple'] ) && boolval( $field['multiple'] ) === true ) ? print( is_array( $current_value ) && in_array( $value, $current_value ) ? ' selected' : '' ) : selected( $value, $current_value ); ?>><?php print esc_html( $text ); ?></option>
					<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			<?php else : ?>
				<?php foreach ( $field['options'] as $value => $text ) : ?>
					<option value="<?php print esc_attr( $value ); ?>" <?php ( ! empty( $field['multiple'] ) && boolval( $field['multiple'] ) === true ) ? print( is_array( $current_value ) && in_array( $value, $current_value ) ? ' selected' : '' ) : selected( $value, $current_value ); ?>><?php print esc_html( $text ); ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<?php
		$this->do_field_description( $field );
	}

	/**
	 * Output the field description, and example if present.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_field_description( $field ) {
		?>
		<p class="description">
			<?php print wp_kses_post( $field['description'] ); ?>
			<?php if ( isset( $field['example'] ) ) : ?>
				<br/><strong><?php esc_html_e( 'Example', 'privacy-portal-sso' ); ?>: </strong>
				<code><?php print esc_html( $field['example'] ); ?></code>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Output the 'Client Settings' plugin setting section description.
	 *
	 * @return void
	 */
	public function common_settings_description() {
		esc_html_e( 'Enter your OAUTH app credentials provided to you by Privacy Portal. If you haven\'t yet registered your Wordpress site as an OAUTH app on Privacy Portal, go to https://app.privacyportal.org/settings/developers and create a "New Application".', 'privacy-portal-sso' );
	}

	/**
	 * Output the 'WordPress User Settings' plugin setting section description.
	 *
	 * @return void
	 */
	public function login_settings_description() {
		esc_html_e( 'Configure OAUTH authentication to your Wordpress website.', 'privacy-portal-sso' );
	}

	/**
	 * Output the 'Newsletter Anonymous Subscription Settings' plugin setting section description.
	 *
	 * @return void
	 */
	public function enroll_settings_description() {
		esc_html_e( 'Set up OAUTH-based anonymous subscriptions to your newsletter.', 'privacy-portal-sso' );
	}

	/**
	 * Output the 'Log Settings' plugin setting section description.
	 *
	 * @return void
	 */
	public function log_settings_description() {
		esc_html_e( 'Log information about OAUTH login attempts.', 'privacy-portal-sso' );
	}
}
