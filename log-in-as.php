<?php
/*
 * Plugin Name: Log In As
 * Plugin URI: trepmal.com
 * Description: Log in as any user. Handy for local dev where databases come and go and you can never remember the dang credentials.
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
		add_action( 'init',                     array( $this, 'admin_init' ), -100 );
		add_action( 'login_head',               array( $this, 'login_head' ) );
		add_action( 'login_form',               array( $this, 'login_form' ) );
		add_action( 'wp_ajax_log_in_as',        array( $this, 'already_logged_in' ) );
		add_action( 'wp_ajax_nopriv_log_in_as', array( $this, 'log_in_as' ) );
	}

	/**
	 * Turn on output buffering early
	 * In local dev, you may have notices and junk. Catch them in output
	 * buffering so they don't break the ajaxy-login
	 */
	function admin_init() {
		if ( ! defined( 'DOING_AJAX' ) ) return;
		ob_start();
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
		echo '<button class="button button-small alignright">'. esc_html__( 'Standard login', 'log-in-as' ) . '</button>';

		echo '<h2>'. esc_html__( 'Log in as&hellip;', 'log-in-as' ) . '</h2>';

		$hide = false;
		$class = '';

		if ( is_multisite() ) {
			$heading = '<h4>' .
				sprintf( esc_html__( 'Super Admin %s', 'log-in-as' ), "<span class='dashicons dashicons-arrow-down-alt2'></span>" ) .
				'</h4>';
			$links = array();
			foreach ( get_super_admins() as $username ) {
				$user = get_user_by( 'login', $username );
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

		if ( ! function_exists('get_editable_roles' ) ) {
			require_once(ABSPATH . 'wp-admin/includes/user.php');
		}

		// default args for users query
		$get_user_args = apply_filters( 'log_in_as_user_args', array( 'number' => 2 ) );

		foreach ( get_editable_roles() as $role => $details ) {
			$role_name = esc_html( $details['name'] );
			$heading = "<h4>{$role_name}<span class='dashicons dashicons-arrow-down-alt2'></span></h4>";
			$links = array();

			// filter for per-role query args
			$args = apply_filters( 'log_in_as_user_args_for_' . $role, $get_user_args );
			// make sure we don't alter the role
			$args = array_merge( $args, array( 'role' => $role ) );

			foreach ( get_users( $args ) as $user ) {
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

		// flush the buffer (it's fun to say)
		ob_end_clean();

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

		// clean it like you mean it
		ob_end_clean();

		wp_send_json_error(
			sprintf( __( 'You are already logged in as %s.', 'log-in-as' ), wp_get_current_user()->user_login ) .
			"<p>$action_links</p>"
		);
	}

}
