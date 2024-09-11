<?php
/**
 * Class PP_SSO_Option_Settings_Test (Forked from OpenID Connect Generic)
 *
 * @package   Privacy_Portal_SSO
 */

/**
 * Plugin WordPress options handling class test case.
 */
class PP_SSO_Option_Settings_Test extends WP_UnitTestCase {

	/**
	 * Test case setup method.
	 *
	 * @return void
	 */
	public function setUp(): void {

		parent::setUp();

	}

	/**
	 * Test case cleanup method.
	 *
	 * @return void
	 */
	public function tearDown(): void {

		parent::tearDown();

	}

	/**
	 * Test plugin option settings get_option_name() method.
	 *
	 * @group ClientTests
	 */
	public function test_plugin_option_settings_get_option_name() {

		$this->assertTrue( true, 'Needs Unit Tests.' );

	}

}
