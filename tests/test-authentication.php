<?php
/**
 * Class Log_In_As_Authentication_Test
 *
 * @package Log_In_As
 */

/**
 * Authentication test case.
 */
class Log_In_As_Authentication_Test extends WP_UnitTestCase {

	/**
	 * Test authentication
	 */
	function test_authentication() {
		$Log_In_As = new Log_In_As;
		$_REQUEST['user_id'] = 1;
		$user = $Log_In_As->authenticate( null );
		$this->assertTrue( is_a( $user, 'WP_User' ) );
	}

	/**
	 * Test authentication failure
	 */
	function test_authentication_failure() {
		$Log_In_As = new Log_In_As;
		$_REQUEST['user_id'] = 999;
		$user = $Log_In_As->authenticate( null );
		$this->assertFalse( $user );
	}
}
