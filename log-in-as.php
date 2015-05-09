<?php
/*
 * Plugin Name: Log In As
 * Plugin URI: trepmal.com
 * Description: Log in as any* user. Handy for local dev where databases come and go and you can never remember the dang credentials.
 * Version: 0.0.1
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * TextDomain: log-in-as
 * DomainPath:
 * Network:
 */

$log_in_as = new Log_In_As();

class Log_In_As {

	/**
	 * Get hooked in
	 *
	 * @return void
	 */
	function __construct() {
		add_action( 'login_head',               array( $this, 'login_head' ) );
		add_action( 'login_form',               array( $this, 'login_form' ) );
		add_action( 'wp_ajax_log_in_as',        array( $this, 'already_logged_in' ) );
		add_action( 'wp_ajax_nopriv_log_in_as', array( $this, 'log_in_as' ) );
	}

	/**
	 * Enqueue our assets
	 *
	 * @return void
	 */
	function login_head() {
		wp_enqueue_script( 'log-in-as', plugins_url( 'log-in-as.js', __FILE__ ), array('wp-util', 'jquery'), '0.0.1', true );
		wp_enqueue_style( 'log-in-as', plugins_url( 'log-in-as.css', __FILE__ ), array(), '0.0.1' );
	}

	/**
	 * Output our custom interface
	 *
	 * @return void
	 */
	function login_form() {

		echo '<div id="log-in-as">';

		echo '<h2>Log in as&hellip;</h2>';

		$hide = false;
		$class = '';

		if ( is_multisite() ) {
			$heading = "<h4>Super Admin<span class='dashicons dashicons-arrow-down-alt2'></span></h4>";
			$links = array();
			foreach ( get_super_admins() as $username ) {
				$user = get_user_by( 'login', $username );
				$url = esc_url( admin_url( "admin-ajax.php?action=log_in_as&user_id={$user->ID}" ) );
				$links[] = "<a href='#' data-user-id='{$user->ID}' class='log-in-as-user'>({$user->ID}) {$user->user_login} : {$user->user_email}</a><br />";
			}
			if ( ! empty( $links ) ) {
				echo $heading;
				if ( $hide ) {
					$class = ' hidden';
				}
				$hide = true;
				echo '<div class="log-in-as-group ' . $class . '">' . implode( ' ' , $links ) . '</div>';
			}
		}
		// sorry, at the moment MS support is limited to super admins
		else {

			foreach ( get_editable_roles() as $role => $details ) {
				$heading = "<h4>{$details['name']}<span class='dashicons dashicons-arrow-down-alt2'></span></h4>";
				$links = array();
				foreach ( get_users( array( 'role' => $role ) ) as $user ) {
					$url = esc_url( admin_url( "admin-ajax.php?action=log_in_as&user_id={$user->ID}" ) );
					$links[] = "<a href='#' data-user-id='{$user->ID}' class='log-in-as-user'>{$user->user_login}</a>";
				}
				if ( ! empty( $links ) ) {
					echo $heading;
					if ( $hide ) {
						$class = ' hidden';
					}
					$hide = true;
					echo '<div class="log-in-as-group ' . $class . '">' . implode( ' ' , $links ) . '</div>';
				}
			}

		}

		echo '</div>';

	}

	/**
	 * Authenticate by looking up user by ID
	 *
	 * @return WP_User|WP_Error User or Error
	 */
	function authenticate() {
		return get_user_by( 'id', absint( $_POST['user_id'] ) );
	}

	/**
	 * Ajax Callback for logged out users
	 * Authenticate given user ID
	 *
	 * @return void
	 */
	function log_in_as() {
		sleep(1);

		// replace any authentication with our own
		remove_all_filters( 'authenticate' );
		add_filter( 'authenticate', array( $this, 'authenticate' ) );

		// attempt to sign in
		$user = wp_signon();

		// if successful, send Dashboard url to JS for redirecting
		if ( ! is_wp_error( $user ) ) {
			wp_send_json_success( esc_url( admin_url() ) );

		// otherwise, send error message
		} else {
			wp_send_json_error( $user->get_error_message() );
		}
	}

	/**
	 * Ajax Callback for logged in users
	 * If logged in, tell them and give options
	 *
	 * @return void
	 */
	function already_logged_in() {

		$actions = array(
			'<a href="' . esc_url( admin_url() ) . '">' . __( 'Dashboard', 'log-in-as' ) .'</a>',
			'<a href="' . esc_url( wp_logout_url() ) . '">' . __( 'Log out', 'log-in-as' ) . '</a>',
		);
		$action_links = implode( ' | ', $actions );

		wp_send_json_error(
			sprintf( __( 'You are already logged in as %s.', 'log-in-as' ), wp_get_current_user()->user_login ) .
			"<p>$action_links</p>"
		);
	}

}