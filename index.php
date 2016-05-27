<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );
/**
 * Plugin Name: Loushou: ACF for WooCommerce
 * Plugin URI:  http://looseshoe.com/
 * Description: A plugin to integrate ACF with WooCommerce
 * Version:     1.1.0
 * Author:      Loushou
 * Author URI:  http://looseshoe.com/
 * License: GNU General Public License, version 3 (GPL-3.0)
 * License URI: http://www.gnu.org/copyleft/gpl.html
 * Text Domain: lou-acf-wc
 * Domain Path: /langs
 */

// bootstrap class to control our plugin
class LOU_ACF_WC_Launcher {
	// container for our singleton
	protected static $_instance = null;

	// public access static method to grab the singleton instance
	public static function instance() {
		return self::$_instance instanceof self ? self::$_instance : ( self::$_instance = new self );
	}

	// container for some rather static plugin data that we should only calculate once
	protected $_name = null;
	protected $_version = '1.1.0';
	protected $_me = null;
	protected $_plugin_dir = null;
	protected $_plugin_url = null;

	// container for a list of the missed prereqs if versions are not met
	protected $prereq_errors = array();

	// protect the constructor so that we can actually have a singleton
	protected function __construct() {
		$is_admin = is_admin();
		// calculate the plugin data once
		$this->_me = plugin_basename( __FILE__ );
		$this->_plugin_dir = trailingslashit( plugin_dir_path( __FILE__ ) );
		$this->_plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );

		// load the plugin text domain AFTER all plugins load. do it this way so that it is compatible with qtranslateX. do it HERE, because we may need to pop a translated error about version minimums not being met
		add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ), 4 );

		// load some strings we need after we load the textdomain
		add_action( 'plugins_loaded', array( &$this, 'load_strings' ), 5 );

		$can_load = true;
		// PREREQUISITE: wc active
		if ( ! $this->_plugin_active( 'woocommerce', 'woocommerce.php' ) ) {
			$can_load = false;
			$this->prereq_errors[] = array( 'active', 'WooCommerce' );
		}
		// PREREQUISITE: wc version
		// check if woocommerce is the minimum required version
		if ( ! $this->_at_least_version( 'WooCommerce', '2.5.5' ) ) {
			$can_load = false;
			$this->prereq_errors[] = array( 'version', 'WooCommerce', '2.5.5' );
		}

		// PREREQUISITE: php version
		// verify that php is at the minimum required version for the plugin
		if ( ! $this->_at_least_version( 'PHP', '5.2.4' ) ) {
			$can_load = false;
			$this->prereq_errors[] = array( 'version', 'PHP', '5.2.4' );
		}

		// if the system passed all the prerequisits, then 
		if ( $can_load ) {
			// register our activation function
			register_activation_hook( __FILE__, array( &$this, 'on_activation' ) );

			// if this is an admin request, update our recorded version so that other plugins know it
			if ( $is_admin && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) )
				$this->_maybe_update_version();

			// load our guts
			$this->_load_files();
		// otherwise, one of our prereq version checks failed, so we need to pop a dashboard error
		} else if ( $is_admin ) {
			add_action( 'admin_notices', array( &$this, 'prereq_dashboard_error' ), 10 );
		}
	}

	// public getters for plugin data
	public function version() { return $this->_version; }
	public function name( $ele=false ) { return ! $ele ? $this->_name : $this->_wrap_str( $this->_name, $ele ); }
	public function me() { return $this->_me; }
	public function plugin_dir() { return $this->_plugin_dir; }
	public function plugin_url() { return $this->_plugin_url; }

	// load the plugin's textdomain. load any custom defined translations first, so that users can make their own translations locally without modifying the plugin
	public function load_textdomain() {
		$domain = 'lou-acf-wc';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		// first load any custom language file defined in the site languages path
		load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $domain . '/custom-' . $locale . '.mo' );

		// load the translation after all plugins have been loaded. fixes the multilingual issues
		load_plugin_textdomain( $domain, false, $this->_me . 'langs/' );
	}

	// load some needed strings, after we load the translation files
	public function load_strings() {
		$this->_name = __( 'Loushou: ACF for WooCommerce', 'lou-acf-wc' );
	}

	// display any prereq errors we may have encountered during loading
	public function prereq_dashboard_error() {
		// if there are no fail prereq checks, then bail now
		if ( empty( $this->prereq_errors ) )
			return;

		// add a message for each fail prereq
		foreach ( $this->prereq_errors as $error ) {
			@list( $type, $prereq, $param ) = $error;
			// do something different depending on type of fail prereq
			switch ( $type ) {
				// if a version check failed, make a message indicating that
				case 'version':
					$test_version = $this->_get_test_version( $prereq );
					$this->_admin_error( sprintf(
						__( 'Could not load %s because %s was not at least version %s. You are currently using version %s. Please update before using %s.', 'lou-acf-wc' ),
						$this->name( 'u' ),
						$this->_wrap_str( $prereq, 'u' ),
						$param,
						$test_version,
						$this->name( 'u' )
					) );
				break;

				// if the active plugin check failed, make a message indicating that
				case 'active':
					$this->_admin_error( sprintf(
						__( 'Could not load %s because the %s plugin is not active. Please activate %s first.', 'lou-acf-wc' ),
						$this->name( 'u' ),
						$this->_wrap_str( $prereq, 'u' ),
						$this->_wrap_str( $prereq, 'u' )
					) );
				break;
			}
		}
	}

	// render an admin error message
	protected function _admin_error( $msg ) {
		?><div class="error"><?php echo apply_filters( 'the_content', $msg ) ?></div><?php
	}

	// wrap a string in the given element
	protected function _wrap_str( $string, $ele='span' ) {
		// if the supplied element is not a string, then bail
		if ( ! is_string( $ele ) || '' == $ele )
			return $string;

		// otherwise wrap it
		$base = explode( ' ', $ele );
		$base = current( $base );
		return '<' . $ele . '>' . $string . '</' . $base . '>';
	}

	// check that a prerequisite for version number
	protected function _at_least_version( $prereq, $version ) {
		// fetch the version to test against
		$test_version = $this->_get_test_version( $prereq );

		// figure out if the prereq is met
		return version_compare( $version, $test_version ) <= 0;
	}

	// determine if a plugin is active or not
	protected function _plugin_active( $basedir, $filename ) {
		// load the list of currently active plugins
		$active = self::_find_active_plugins();

		// check if the regular plugin is active. now DIRECTORY_SEPARATOR here, because wp translates it to '/' for the active plugin arrays
		$is_active = in_array( $basedir . '/' . $filename, $active );

		// if the regular plugin is not acitve, check for known direcotry formats for github zip downloads
		if ( ! $is_active ) {
			foreach ( $active as $active_plugin ) {
				if ( preg_match( '#^' . preg_quote( $basedir, '#' ) . '-(master|[\d\.]+(-(alpha|beta|RC\d+)(-\d+)?)?)[\/\\\\]' . preg_quote( $filename, '#' ). '$#', $active_plugin ) ) {
					$is_active = true;
					break;
				}
			}
		}

		return $is_active;
	}

	// fill a static var with the list of all active plugins
	protected static function _find_active_plugins() {
		static $active = false;

		// if we have not yet loaded the list of active plugins, do so now
		if ( false === $active ) {
			// aggregate a complete list of active plugins, including those that could be active on the network level
			$active = get_option( 'active_plugins', array() );
			$network = defined( 'MULTISITE' ) && MULTISITE ? get_site_option( 'active_sitewide_plugins' ) : array();
			$active = is_array( $active ) ? $active : array();
			$network = is_array( $network ) ? $network : array();
			$active = array_merge( array_keys( $network ), $active );
		}

		return $active;
	}

	// get the version to test against when doing min version checks
	protected function _get_test_version( $prereq ) {
		$test_version = false;
		$prereq = strtolower( $prereq );
		// figure out the version number to test
		switch ( $prereq ) {
			// test the php version
			case 'php':
				$test_version = PHP_VERSION;
			break;

			// test any other software version, defaulting to checking the DB for a version indication, but allowing a filter to override.. which means that the plugin would have to be loaded before this plugin, unless we move the check to later, like plugins_loaded
			default:
				$test_version = apply_filters( 'lou-nyp-prereq-version', get_option( $prereq . '_version', '' ) );
			break;
		}

		return $test_version;
	}

	// possibly update the version of our plugin that is recorded in the database to match the current plugin version, if it is not already up to date
	protected function _maybe_update_version() {
		$recorded = get_option( 'lou_acf_wc_version', '0.0.0' );
		if ( $recorded !== $this->_version )
			update_option( 'lou_acf_wc_version', $this->_version );
	}

	// load the files we need to start the plugin logic
	public function _load_files() {
		require_once $this->_plugin_dir . 'includes/core.class.php';
	}

	// function that runs on plugin activation, and sets up anything we need to setup for the plugin to work on future page loads
	public function on_activation() {
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_WC_Launcher::instance();
