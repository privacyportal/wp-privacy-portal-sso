<?php
/**
 * Login form and login button handling class.
 *
 * @package   Privacy_Portal_SSO
 * @category  Login
 * @author    Privacy Portal SSO (Forked from Jonathan Daggerhart <jonathan@daggerhart.com>)
 * @copyright 2015-2020 daggerhart, 2024 Privacy Portal
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * PP_SSO_Subscribe_Form class.
 *
 * Anonymous Subscribe form and button handling.
 *
 * @package Privacy_Portal_SSO
 * @category  Login
 */
class PP_SSO_Subscribe_Form {

	const SUBSCRIBE_ANONYMOUSLY_SHORTCODE = 'pp_sso_subscribe_anonymously_button';

	/**
	 * Plugin settings object.
	 *
	 * @var PP_SSO_Option_Settings
	 */
	private $settings;

	/**
	 * Plugin client wrapper instance.
	 *
	 * @var PP_SSO_Client_Wrapper
	 */
	private $client_wrapper;

	/**
	 * The class constructor.
	 *
	 * @param PP_SSO_Option_Settings $settings       A plugin settings object instance.
	 * @param PP_SSO_Client_Wrapper  $client_wrapper A plugin client wrapper object instance.
	 */
	public function __construct( $settings, $client_wrapper ) {
		$this->settings = $settings;
		$this->client_wrapper = $client_wrapper;
	}

	/**
	 * Create an instance of the PP_SSO_Subscribe_Form class.
	 *
	 * @param PP_SSO_Option_Settings $settings       A plugin settings object instance.
	 * @param PP_SSO_Client_Wrapper  $client_wrapper A plugin client wrapper object instance.
	 *
	 * @return void
	 */
	public static function register( $settings, $client_wrapper ) {
		$subscribe_form = new self( $settings, $client_wrapper );

		// Alter the login form as dictated by settings.
		add_filter( 'pp-sso-subscription-message', array( $subscribe_form, 'handle_subscribe_page' ), 99 );

		// Add a shortcode for the login button.
		add_shortcode( self::SUBSCRIBE_ANONYMOUSLY_SHORTCODE, array( $subscribe_form, 'make_subscribe_button' ) );

		// Update Subscribe Anonymously button within MailPoet forms.
		add_filter( 'mailpoet_form_widget_post_process', array( $subscribe_form, 'ext_plugin_form_subscribe_btn_override' ) );

		// Update Subscrive Anonymously button within MC4WP forms.
		add_filter( 'mc4wp_form_content', array( $subscribe_form, 'ext_plugin_form_subscribe_btn_override' ) );
	}

	/**
	 * Implements filter subscribe_message.
	 *
	 * @param string $message The text message to display on the anonymous subscribe page.
	 *
	 * @return string
	 */
	public function handle_subscribe_page( $message ) {

		if ( isset( $_GET['pp-enroll-error'] ) && ! empty( $_GET['pp-enroll-error'] ) ) {
			$error_message = isset( $_GET['pp-message'] ) && ! empty( $_GET['pp-message'] ) ? sanitize_text_field( wp_unslash( $_GET['pp-message'] ) ) : 'Unknown error.';
			$message .= $this->make_error_output( sanitize_text_field( wp_unslash( $_GET['pp-enroll-error'] ) ), $error_message );
		}

		return $message;
	}

	/**
	 * Display an error message to the user.
	 *
	 * @param string $error_code    The error code.
	 * @param string $error_message The error message test.
	 *
	 * @return string
	 */
	public function make_error_output( $error_code, $error_message ) {

		ob_start();
		?>
		<div id="login_error"><?php // translators: %1$s is the error code from the IDP. ?>
			<strong><?php printf( esc_html__( 'ERROR (%1$s)', 'privacy-portal-sso' ), esc_html( $error_code ) ); ?>: </strong>
			<?php print esc_html( $error_message ); ?>
		</div>
		<?php
		return wp_kses_post( ob_get_clean() );
	}

	/**
	 * Create a subscribe button (link).
	 *
	 * @param array $atts Array of optional attributes to override subscribe buton
	 * functionality when used by shortcode.
	 *
	 * @return string
	 */
	public function make_subscribe_button( $atts = array() ) {

		if ( ! $this->settings->enable_enroll ) {
			return '';
		}

		$subscription_message = apply_filters( 'pp-sso-subscription-message', '' );

		$atts = shortcode_atts(
			array(
				'style' => null,
				'style_padding' => '10px',
				'style_margin' => '0px',
				'style_border_radius' => '0px',
				'style_background_color' => 'var(--wp--preset--color--black)',
				'style_color' => 'var(--wp--preset--color--white)',
				'style_font_size' => 'var(--wp--preset--font-size--small)',
				'style_font_weight' => 'normal',
				'style_font_family' => 'var(--wp--preset--font-family--body)',
				'title' => __( 'Subscribe Anonymously', 'privacy-portal-sso' ),
				'subtitle' => __( 'with Privacy Portal', 'privacy-portal-sso' ),
			),
			$atts,
			self::SUBSCRIBE_ANONYMOUSLY_SHORTCODE
		);

		$title = apply_filters( 'pp-sso-subscribe-button-title', $atts['title'] );
		$title = esc_html( $title );

		$subtitle = apply_filters( 'pp-sso-subscribe-button-subtitle', $atts['subtitle'] );
		$subtitle = esc_html( $subtitle );

		$href = $this->client_wrapper->get_authentication_url(
			array(
				'type' => 'enroll',
			)
		);
		$href = esc_url_raw( $href );

		$font_size = esc_attr( $atts['style_font_size'] );

		if ( null !== $atts['style'] ) {
			$style = esc_attr( $atts['style'] );
			$subtitle_style = esc_attr( '' );
		} else {
			$background_color = esc_attr( $atts['style_background_color'] );
			$color = esc_attr( $atts['style_color'] );
			$font_family = esc_attr( $atts['style_font_family'] );
			$font_weight = esc_attr( $atts['style_font_weight'] );
			$padding = esc_attr( $atts['style_padding'] );
			$margin = esc_attr( $atts['style_margin'] );
			$border_radius = esc_attr( $atts['style_border_radius'] );

			$style = esc_attr(
				implode(
					' ',
					array(
						'display: flex;',
						'flex-direction: column;',
						'width: 100%;',
						'height: auto;',
						'box-sizing: border-box;',
						"background-color: {$background_color};",
						"color: {$color};",
						'text-decoration: none;',
						'text-align: center;',
						'border-style: solid;',
						"border-radius: {$border_radius};",
						'border-width: 0px;',
						'border-color: transparent;',
						"padding: {$padding};",
						"margin: {$margin};",
						"font-family: {$font_family};",
						'font-size: var(--pp-font-size);',
						"font-weight: {$font_weight};",
					)
				)
			);
			$subtitle_style = esc_attr( 'font-size: calc(var(--pp-font-size) * 0.7); color: inherit;' );
		}

		$subscribe_button = "
<div class=\"pp-subscribe-button-container\" style=\"--pp-font-size: {$font_size};\">
<a class=\"pp-subscribe-button\" href=\"{$href}\" style=\"{$style}\"><span class=\"pp-title\" style=\"font-size: inherit; color: inherit;\">{$title}</span> <span class=\"pp-subtitle\" style=\"{$subtitle_style}\">{$subtitle}</span></a>
<p class=\"pp-subscribe-button-message\" style=\"font-size: calc(var(--pp-font-size) * 0.9)\">{$subscription_message}</p>
</div>
";

		return $subscribe_button;
	}

	/**
	 * Injects the Subscribe Anonymously url into external newsletter forms that support filters
	 *
	 * @param string $content raw content of the form as rendered by newletter plugins.
	 *
	 * @return string updated content with anonymous subscribe url injected
	 */
	public function ext_plugin_form_subscribe_btn_override( $content ) {
		$href = $this->client_wrapper->get_authentication_url(
			array(
				'type' => 'enroll',
			)
		);
		$href = esc_url_raw( $href );

		$html = str_replace( 'href="{pp_sso_subscribe_anonymously_url}"', "href=\"{$href}\" data-pp-type=\"button\"", $content );

		if ( isset( $_GET['pp-enroll-error'] ) && ! empty( $_GET['pp-enroll-error'] ) ) {
			$error_code = sanitize_text_field( wp_unslash( $_GET['pp-enroll-error'] ) );
			$error_message = isset( $_GET['pp-message'] ) && ! empty( $_GET['pp-message'] ) ? sanitize_text_field( wp_unslash( $_GET['pp-message'] ) ) : 'Unknown error.';
			return str_replace( '{pp_sso_subscribe_anonymously_message}', "<strong>ERROR ({$error_code}):</strong> {$error_message}", $html );
		}
		return str_replace( '{pp_sso_subscribe_anonymously_message}', '', $html );
	}
}
