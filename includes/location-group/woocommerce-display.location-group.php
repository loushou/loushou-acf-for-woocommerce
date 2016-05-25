<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// handle added locations that would be useful for a WooCommerce website
class LOU_ACF_WooCommerce_Display extends LOU_ACF_Location_Group {
	// method to grab the instance of this singleton
	public static function instance( $options=false ) { return self::_instance( __CLASS__, $options ); }
	protected function __construct() { parent::__construct(); }

	// initialize this group
	protected function initialize() {
		$this->slug = 'woocommerce-display';
		$this->name = __( 'WooCommerce Display', 'lou-acf-wc' );
		$this->primary_group_key = __( 'Page', 'acf' );

		// finish normal initialization
		parent::initialize();
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_WooCommerce_Display::instance();
