<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// handle added locations that would be useful for a WooCommerce website
class LOU_ACF_WooCommerce extends LOU_ACF_Location_Group {
	// method to grab the instance of this singleton
	public static function instance( $options=false ) { return self::_instance( __CLASS__, $options ); }
	protected function __construct() { parent::__construct(); }

	// initialize this group
	protected function initialize() {
		$this->slug = 'woocommerce';
		$this->name = __( 'WooCommerce', 'lou-acf-wc' );

		// finish normal initialization
		parent::initialize();
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_WooCommerce::instance();
