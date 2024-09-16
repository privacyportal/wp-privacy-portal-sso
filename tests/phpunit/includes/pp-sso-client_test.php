<?php
/**
 * Class PP_SSO_Client_Test (Forked from OpenID Connect Generic)
 *
 * @package   Privacy_Portal_SSO
 */

/**
 * Plugin OIDC/OAUTH client class test case.
 */
class PP_SSO_Client_Test extends WP_UnitTestCase {

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
	 * Test plugin get_redirect_uri() method.
	 *
	 * @group ClientTests
	 */
	public function test_plugin_client_get_redirect_uri() {

		$this->assertTrue( true, 'Needs Unit Tests.' );

	}

}
