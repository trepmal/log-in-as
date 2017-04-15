<?php
/**
 * Plugin Name: Log In As
 * Plugin URI: trepmal.com
 * Description: Log in as any user. Handy for local dev where databases come and go and you can never remember the dang credentials.
 * Version: 0.0.3
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * TextDomain: log-in-as
 * DomainPath:
 * Network:
 *
 * @package trepmal/log-in-as
 */

$log_in_as = new Log_In_As();

/**
 * Log In As
 */
class Log_In_As {

	/**
	 * Get hooked in
	 *
	 * @return void
	 */
	function __construct() {
		add_action( 'init',                      array( $this, 'admin_init' ), -100 );
		add_action( 'login_head',                array( $this, 'assets' ) );
		add_action( 'login_form',                array( $this, 'login_form' ) );

		if ( isset( $_REQUEST['user_id'] ) ) {
			remove_all_filters( 'authenticate' );
			add_action( 'authenticate',              array( $this, 'authenticate' ) );
		}

		add_action( 'wp_ajax_log_in_as',         array( $this, 'already_logged_in' ) );
		add_action( 'wp_ajax_nopriv_log_in_as',  array( $this, 'log_in_as' ) );
		add_action( 'wp_ajax_log_out_and_in_as', array( $this, 'log_out_and_in_as' ) );
		add_action( 'wp_ajax_switch_back',       array( $this, 'switch_back' ) );

		// hook on admin* so switching works.
		add_action( 'admin_enqueue_scripts',     array( $this, 'assets' ) );
		add_action( 'admin_notices',             array( $this, 'admin_notices' ) );
		add_filter( 'user_row_actions',          array( $this, 'user_row_actions' ), 10, 2 );
	}

	/**
	 * Turn on output buffering early
	 *
	 * In local dev, you may have notices and junk. Catch them in output
	 * buffering so they don't break the ajaxy-login
	 */
	function admin_init() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		ob_start();
	}

	/**
	 * Enqueue our assets
	 *
	 * @return void
	 */
	function assets() {
		wp_enqueue_script( 'log-in-as', plugins_url( 'log-in-as.js', __FILE__ ), array( 'wp-util', 'jquery' ), '0.0.3', true );
		wp_enqueue_style( 'log-in-as', plugins_url( 'log-in-as.css', __FILE__ ), array(), '0.0.3' );
	}

	/**
	 * Output our custom interface
	 *
	 * @return void
	 */
	function login_form() {

		echo '<div id="log-in-as">';
		echo '<button class="button button-small alignright">' . esc_html__( 'Standard login', 'log-in-as' ) . '</button>';

		echo '<h2>' . esc_html__( 'Log in as&hellip;', 'log-in-as' ) . '</h2>';

		$hide  = false;

		if ( is_multisite() ) {
			$links = array();
			foreach ( get_super_admins() as $username ) {
				$user = get_user_by( 'login', $username );
				$links[] = '<a href="#" data-user-id="' . esc_attr( $user->ID ) . '" class="log-in-as-user">' . esc_html( $user->user_login ) . '</a>';
			}
			if ( ! empty( $links ) ) {
				echo '<h4>';
				printf( esc_html__( 'Super Admin %s', 'log-in-as' ), "<span class='dashicons dashicons-arrow-down-alt2'></span>" );
				echo '</h4>';

				$class = $hide ? ' hidden' : '';
				$hide = true;
				echo '<div class="log-in-as-group ' . esc_attr( $class ) . '">' . implode( ' ' , $links ) . '</div>';
			}
		}

		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/user.php' );
		}

		// default args for users query.
		$get_user_args = apply_filters( 'log_in_as_user_args', array( 'number' => 2 ) );

		foreach ( get_editable_roles() as $role => $details ) {
			$links = array();

			// filter for per-role query args.
			$args = apply_filters( 'log_in_as_user_args_for_' . $role, $get_user_args );
			// make sure we don't alter the role.
			$args = array_merge( $args, array( 'role' => $role ) );

			foreach ( get_users( $args ) as $user ) {
				$links[] = '<a href="#" data-user-id="' . esc_attr( $user->ID ) . '" class="log-in-as-user">' . esc_html( $user->user_login ) . '</a>';
			}
			if ( ! empty( $links ) ) {
				echo '<h4>' . esc_html( $details['name'] ) . '<span class="dashicons dashicons-arrow-down-alt2"></span></h4>';
				$class = $hide ? ' hidden' : '';
				$hide = true;
				echo '<div class="log-in-as-group ' . esc_attr( $class ) . '">' . implode( ' ' , $links ) . '</div>';
			}
		}

		echo '</div>';

	}

	/**
	 * Authenticate by looking up user by ID
	 *
	 * @param null|WP_User|WP_Error $user     WP_User if the user is authenticated.
	 *                                        WP_Error or null otherwise.
	 * @return WP_User|false User or Error
	 */
	function authenticate( $user ) {
		$uid = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;
		return get_user_by( 'id', $uid );
	}

	/**
	 * Ajax Callback for logged out users
	 * Authenticate given user ID
	 *
	 * @return void
	 */
	function log_in_as() {
		sleep( 1 );

		// attempt to sign in.
		$user = wp_signon();

		// flush the buffer (it's fun to say).
		ob_end_clean();

		// if successful, send Dashboard url to JS for redirecting.
		if ( ! is_wp_error( $user ) ) {
			$redirect = admin_url();

			$interim = isset( $_POST['interim'] ) ? intval( $_POST['interim'] ) : 0;
			$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

			if ( 1 === $interim ) {
				$redirect = add_query_arg( array(
					'action'          => 'login',
					'user_id'         => $user_id,
					'customize-login' => '1',
					'interim-login'   => '1',
				), wp_login_url() );
			}
			wp_send_json_success( $redirect );

		} else { // otherwise, send error message.
			wp_send_json_error( $user->get_error_message() );
		}
	}

	/**
	 * Ajax Callback for logged in users that are switching
	 * Authenticate given user ID
	 */
	function log_out_and_in_as() {

		// save original user for switching back, yes, this can be faked.
		setcookie( 'switch', get_current_user_id(), time() + WEEK_IN_SECONDS );

		wp_logout();
		$this->log_in_as();
	}

	/**
	 * Ajax Callback for logged in users that are switching back
	 * Authenticate given user ID
	 */
	function switch_back() {

		// remove cookie.
		setcookie( 'switch', '', time() - ( 86400 * 7 ) );

		wp_logout();
		$this->log_in_as();
	}

	/**
	 * Ajax Callback for logged in users
	 * If logged in, tell them and give options
	 */
	function already_logged_in() {

		$actions = array(
			'<a href="' . esc_url( admin_url() ) . '">' . esc_html__( 'Dashboard', 'log-in-as' ) . '</a>',
			'<a href="' . esc_url( wp_logout_url() ) . '">' . esc_html__( 'Log out', 'log-in-as' ) . '</a>',
		);
		$action_links = implode( ' | ', $actions );

		// clean it like you mean it.
		ob_end_clean();

		wp_send_json_error(
			sprintf( __( 'You are already logged in as %s.', 'log-in-as' ), wp_get_current_user()->user_login ) .
			"<p>$action_links</p>"
		);
	}

	/**
	 * Display 'switch back' for switched users
	 */
	function admin_notices() {
		if ( ! isset( $_COOKIE['switch'] ) ) {
			return;
		}
		$user = absint( $_COOKIE['switch'] );
		if ( ! ( $original_user = get_user_by( 'id', $user ) ) ) {
			return;
		}

		$text = sprintf( __( 'Switch back to %s', 'log-in-as' ), $original_user->user_login );
		echo '<div class="notice"><p><a href="#" data-user-id="' . esc_attr( $user ) . '" class="switch-back">' . esc_html( $text ) . '</a></p></div>';
	}

	/**
	 * Add switch links to users table
	 *
	 * @param array  $actions Actions.
	 * @param object $user User object.
	 * @return array Actions
	 */
	function user_row_actions( $actions, $user ) {
		if ( get_current_user_id() === $user->ID ) {
			return $actions;
		}
		$text = esc_html__( 'Switch', 'log-in-as' );
		$actions['switch'] = '<a href="#" data-user-id="' . esc_attr( $user->ID ) . '" class="log-out-and-in-as-user">' . esc_html( $text ) . '</a>';
		return $actions;
	}

}
