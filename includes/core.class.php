<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

require_once LOU_ACF_WC_Launcher::instance()->plugin_dir() . 'includes/singleton.class.php';

// handles the guts of our plugin
class LOU_ACF_WC_Core extends LOU_ACF_Singleton {
	// load the singleton instance
	public static function instance() { return self::_instance( __CLASS__ ); }
	protected function __construct() { parent::__construct(); }

	// initialize the class
	protected function initialize() {
		$this->_load_fields_and_locations();

		// fix the acf input javascript so that it loads the color picker before itself also. this is a cor ACF bug
		add_action( 'init', array( &$this, 'fix_acf_js_loading_bug' ), PHP_INT_MAX - 1000 );
	}

	// fixes a bug in the js registration for acf, where all the rquired js for input.js is not specified
	public function fix_acf_js_loading_bug() {
		global $wp_scripts;
		// get the entry for the script that needs more definition
		$entry = isset( $wp_scripts->registered['acf-input'] ) ? $wp_scripts->registered['acf-input'] : null;

		// if we found an entry, then fix it
		if ( $entry && isset( $entry->deps ) ) {
			$entry->deps[] = 'wp-color-picker';

			// register the wp-color-picker, since this does not get registered on the frontend
			if ( ! is_admin() || ! isset( $wp_scripts->registered['wp-color-picker'] ) ) {
				$suffix = SCRIPT_DEBUG ? '' : '.min';
				// copied from: wp-includes/script-loader.php @ 619
				// modified to not load in footer
				$wp_scripts->add( 'iris', '/wp-admin/js/iris.min.js', array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), '1.0.7' );
				$wp_scripts->add( 'wp-color-picker', "/wp-admin/js/color-picker$suffix.js", array( 'iris' ), false );
				did_action( 'init' ) && $wp_scripts->localize( 'wp-color-picker', 'wpColorPickerL10n', array(
					'clear' => __( 'Clear' ),
					'defaultString' => __( 'Default' ),
					'pick' => __( 'Select Color' ),
					'current' => __( 'Current Color' ),
				) );
			// or move the color picker to the header, so that the input.js additional javascript logic happens after the script is attached to the page
			} else {
				$wp_scripts->registered['wp-color-picker']->args = null;
			}
		}
	}

	// functions to load the fields and locations that our plugin provides
	// fields = the new types of ACF fields we provide
	// location-groups = the groups of new locations we provide
	// locations = an individual location (part of a location group) that represents a specific area of the site
	protected function _load_fields_and_locations() {
		// reused path
		$dir = LOU_ACF_WC_Launcher::instance()->plugin_dir() . 'includes/';

		// the helper class for accessing the ACF api
		require_once $dir . 'helper/acf-api.helper.php';

		// load the base location group and base location
		require_once $dir . 'location-group/_base-location-group.php';
		require_once $dir . 'location/_base-location.php';

		// load the individual location groups
		require_once $dir . 'location-group/woocommerce.location-group.php';
	}

	// load files based on the supplied params
	protected function _load_files( $regex, $sub_dir='' ) {
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_WC_Core::instance();
