<?php
/*
 * Plugin Name: Simple System Status
 * Version: 2.1.1
 * Plugin URI: http://leogopal.com/
 * Description: View Information about your WordPress Configurationa and Server Information that is useful for debugging.
 * Author: Leo Gopal
 * Author URI: http://leogopal.com/
 * Requires at least: 5.1
 * Tested up to: 5.7
 *
 * Text Domain: sss
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Leo Gopal
 * @since 1.0.0
 */

class Simple_System_Status {

	/**
	 * Load hooks
	 *
	 * @since  0.9
	 * @action plugins_loaded
	 *
	 * @return void
	 */
	static function setup() {
		define( 'SSS_DIR', plugin_dir_path( __FILE__ ) );
		define( 'SSS_INC_DIR', SSS_DIR . 'includes/' );
		define( 'SSS_VIEWS_DIR', SSS_DIR . 'views/' );

		require_once SSS_INC_DIR . 'viewer.php';
		require_once SSS_INC_DIR . 'browser.php';

		register_activation_hook( __FILE__, array( __CLASS__, 'generate_url' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_submenu_page' ) );
		add_action( 'wp_ajax_regenerate_url', array( __CLASS__, 'generate_url' ) );
		add_action( 'wp_ajax_download_simple_system_status', array( __CLASS__, 'download_info' ) );
		add_action( 'template_redirect', array( 'Simple_System_Status_Viewer', 'remote_view' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'action_link' ) );
	}

	/**
	 * Print direct link to Simple System Status page from Plugins Page
	 *
	 * @since  1.0
	 * @filter plugin_action_links_
	 *
	 * @param  array  Array of links
	 * @return array  Updated Array of links
	 */
	static function action_link( $links ) {
		$links[] = '<a href="' . admin_url( 'tools.php?page=simple-system-status.php' ) . '">' . __( 'View System Status', 'simple-system-status' ) . '</a>';
		return $links;
	}

	/**
	 * Enqueue Javascript
	 *
	 * @since  1.0
	 * @action admin_print_scripts-
	 *
	 * @return void
	 */
	static function enqueue_js() {
		wp_register_script( 'sss-script', plugins_url( '/ui/simple-system-status.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'sss-script', 'systemInfoAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'sss-script' );
	}

	/**
	 * Enqueue CSS
	 *
	 * @since  1.0
	 * @action admin_print_styles-
	 *
	 * @return void
	 */
	static function enqueue_css() {
		wp_enqueue_style( 'sss-style', plugins_url( '/ui/simple-system-status.css', __FILE__ ) );
	}

	/**
	 * Register submenu page and enqueue styles and scripts.
	 * Only viewable by Administrators
	 *
	 * @since  1.0
	 * @action admin_menu
	 *
	 * @return void
	 */
	static function register_submenu_page() {
		$page = add_submenu_page(
			'tools.php',
			__( 'Simple System Status', 'simple-system-status' ),
			__( 'Simple System Status', 'simple-system-status' ),
			'manage_options',
			'simple-system-status',
			array( __CLASS__, 'render_info' )
		);

		// Enqueue scripts and styles on the Plugin Settings page only
		add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'enqueue_css' ) );
		add_action( 'admin_print_scripts-' . $page, array( __CLASS__, 'enqueue_js' ) );
	}

	/**
	 * Render plugin page title, information and info textarea
	 *
	 * @since  1.0
	 *
	 * @return void
	 */
	static function render_info() {
		include( SSS_VIEWS_DIR . 'simple-system-status.php' );
	}

	/**
	 * Generate Text file download
	 *
	 * @since  1.0
	 *
	 * @return void
	 */
	static function download_info() {
		if ( ! isset( $_POST['simple-system-status-textarea'] ) || empty( $_POST['simple-system-status-textarea'] ) ) {
			return;
		}

		header( 'Content-type: text/plain' );

		//Text file name marked with Unix timestamp
		header( 'Content-Disposition: attachment; filename=simple_system_status_' . time() . '.txt' );

		echo $_POST['simple-system-status-textarea'];
		die();
	}

	/**
	 * Gather data, then generate System Status
	 *
	 * Based on System Status submenu page in Easy Digital Downloads
	 * by Pippin Williamson
	 *
	 * @since  1.0
	 *
	 * @return void
	 */
	static function display() {
		$browser = new Browser();
		if ( get_bloginfo( 'version' ) < '3.4' ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
		} else {
			$theme_data = wp_get_theme();
			$theme      = $theme_data->Name . ' ' . $theme_data->Version;
		}

		// Try to identify the hosting provider
		$host = false;
		if ( defined( 'WPE_APIKEY' ) ) {
			$host = 'WP Engine';
		} elseif ( defined( 'PAGELYBIN' ) ) {
			$host = 'Pagely';
		}

		$request['cmd'] = '_notify-validate';

		$params = array(
			'sslverify' => false,
			'timeout'   => 60,
			'body'      => $request,
		);

		$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$WP_REMOTE_POST = 'wp_remote_post() works' . "\n";
		} else {
			$WP_REMOTE_POST = 'wp_remote_post() does not work' . "\n";
		}

		return self::display_output( $browser, $theme, $host, $WP_REMOTE_POST );
	}

	/**
	 * Render System Status
	 *
	 * Based on System Status submenu page in Easy Digital Downloads
	 * by Pippin Williamson
	 *
	 * @since  1.0
	 *
	 * @param   string  Browser information
	 * @param   string  Theme Data
	 * @param   string  Theme name
	 * @param   string  Host
	 * @param   string  WP Remote Host
	 * @return  string  Output of System Status display
	 */
	//Render Info Display
	static function display_output( $browser, $theme, $host, $WP_REMOTE_POST ) {
		global $wpdb;
		ob_start();
		include( SSS_VIEWS_DIR . 'output.php' );
		return ob_get_clean();
	}

	/**
	 * Size Conversions
	 *
	 * @author Chris Christoff
	 * @since 1.0
	 *
	 * @param  unknown    $v
	 * @return int|string
	 */
	static function let_to_num( $v ) {
		$l   = substr( $v, -1 );
		$ret = substr( $v, 0, -1 );

		switch ( strtoupper( $l ) ) {
			case 'P': // fall-through
			case 'T': // fall-through
			case 'G': // fall-through
			case 'M': // fall-through
			case 'K': // fall-through
				$ret *= 1024;
				break;
			default:
				break;
		}

		return $ret;
	}

	/**
	 * Generate Random URL for the remote view.
	 * Saves result to options.  If it's an ajax request
	 * the new query value is sent back to the js script.
	 *
	 * @since  1.0
	 * @action wp_ajax_regenerate_url
	 *
	 * @return void
	 */
	static function generate_url() {
		$alphabet    = 'abcdefghijklmnopqrstuwxyz-ABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
		$value       = array();
		$alphaLength = strlen( $alphabet ) - 1;
		for ( $i = 0; $i < 16; $i++ ) {
			$n     = rand( 0, $alphaLength );
			$value[] = $alphabet[$n];
		}
		$value = implode( $value );
		update_option( 'simple_system_status_remote_url', $value );
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$output = home_url() . '/?simple_system_status=' . $value;
			wp_send_json( $output );
		}
	}

	/**
	 * Delete URL option on uninstall.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	static function uninstall() {
		delete_option( 'simple_system_status_remote_url' );
	}

}
//Load Plugin on 'plugins_loaded'
add_action( 'plugins_loaded', array( 'Simple_System_Status', 'setup' ) );