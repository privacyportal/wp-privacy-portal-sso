<?php
/**
 * Class PP_SSO_Test (Forked from OpenID Connect Generic)
 *
 * @package   Privacy_Portal_SSO
 */

/**
 * Plugin initialization functionality class test case.
 */
class PP_SSO_Test extends WP_UnitTestCase {

	/**
	 * @var Privacy_Portal_SSO $oidc_plugin
	 */
	private $oidc_plugin = null;

	/**
	 * @var PP_SSO_Option_Settings $oidc_plugin_settings
	 */
	private $oidc_plugin_settings = null;

	/**
	 * @var int
	 */
	private $state_time_limit_default = 9999;

	/**
	 * Test case setup method.
	 *
	 * @return void
	 */
	public function setUp(): void {

		parent::setUp();
		$this->oidc_plugin = Privacy_Portal_SSO::instance();
		$this->oidc_plugin_settings = new PP_SSO_Option_Settings(
			// Default settings values.
			array(
				'state_time_limit' => $this->state_time_limit_default,
			)
		);

	}

	/**
	 * Test case cleanup method.
	 *
	 * @return void
	 */
	public function tearDown(): void {

		unset( $this->oidc_plugin );
		parent::tearDown();

	}

	/**
	 * Test plugin has an Privacy_Portal_SSO $_instance property.
	 *
	 * @group PluginTests
	 */
	public function test_plugin_has_instance_propery() {

		$this->assertObjectHasProperty( '_instance', $this->oidc_plugin, 'Privacy_Portal_SSO has a $_instance property.' );

	}

	/**
	 * Test plugin has an PP_SSO_Option_Settings $settings property.
	 *
	 * @group PluginTests
	 */
	public function test_plugin_has_settings_propery() {

		$this->assertObjectHasProperty( 'settings', $this->oidc_plugin, 'Privacy_Portal_SSO has a $settings property.' );

	}

	/**
	 * Test plugin has an PP_SSO_Option_Logger $logger property.
	 *
	 * @group PluginTests
	 */
	public function test_plugin_has_logger_propery() {

		$this->assertObjectHasProperty( 'logger', $this->oidc_plugin, 'Privacy_Portal_SSO has a $logger property.' );

	}

	/**
	 * Test plugin has an PP_SSO_Client $client property.
	 *
	 * @group PluginTests
	 */
	public function test_plugin_has_client_propery() {

		$this->assertObjectHasProperty( 'client', $this->oidc_plugin, 'Privacy_Portal_SSO has a $client property.' );

	}

	/**
	 * Test plugin has an PP_SSO_Client_Wrapper $client_wrapper property.
	 *
	 * @group PluginTests
	 */
	public function test_plugin_has_client_wrapper_propery() {

		$this->assertObjectHasProperty( 'settings', $this->oidc_plugin, 'Privacy_Portal_SSO has a $settings property.' );
		$this->assertInstanceOf( 'PP_SSO_Client_Wrapper', $this->oidc_plugin->client_wrapper, 'Plugin $client_wrapper property is an PP_SSO_Client_Wrapper instance.' );

	}

	/**
	 * Test plugin get_redirect_uri() method.
	 *
	 * @group PluginTests
	 */
	public function test_plugin_redirect_uri() {

		$this->assertInstanceOf( 'Privacy_Portal_SSO', $this->oidc_plugin, 'Instance is of type Privacy_Portal_SSO.' );

	}

	/**
	 * Test plugin instance() method.
	 *
	 * @group PluginTests
	 */
	public function test_plugin_returns_valid_instance() {

		$this->assertInstanceOf( 'Privacy_Portal_SSO', $this->oidc_plugin, 'Instance is of type Privacy_Portal_SSO.' );

	}

	/**
	 * Test plugin settings `state_time_limit` has a default.
	 *
	 * @group PluginTests
	 */
	public function test_plugin_settings_has_state_time_limit_default() {

		$this->assertIsInt( $this->oidc_plugin_settings->state_time_limit, "The PP_SSO_Option_Settings `state_time_limit` value '{$this->oidc_plugin_settings->state_time_limit}' is not and integer or is not set." );
		if ( defined( 'OIDC_STATE_TIME_LIMIT' ) ) {
			$expected = intval( OIDC_STATE_TIME_LIMIT );
			$this->assertEquals( $expected, $this->oidc_plugin_settings->state_time_limit, "The PP_SSO_Option_Settings `state_time_limit` default value '{$this->oidc_plugin_settings->state_time_limit}' is not overridden to '{$expected}'." );
		} else {
			$this->assertEquals( $this->state_time_limit_default, $this->oidc_plugin_settings->state_time_limit, "The PP_SSO_Option_Settings `state_time_limit` default value '{$this->oidc_plugin_settings->state_time_limit}' is not '{$this->state_time_limit_default}'." );
		}

	}

}
