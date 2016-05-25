<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// handles displaying all ACFs assigned to an archive product page
class LOU_ACF_Archive_Product_Display extends LOU_ACF_Location {
	// method to grab the instance of this singleton
	public static function instance( $options=false ) { return self::_instance( __CLASS__, $options ); }
	protected function __construct() { parent::__construct(); }

	// initialize this location
	protected function initialize() {
		$this->slug = 'archive-product-top';
		$this->name = __( 'Archive Product Top', 'lou-acf-wc' );
		$this->group_slug = 'woocommerce-display';
		$this->priority = 1;

		// finish normali initialization
		parent::initialize();

		// add the output to the top of the WooCommerce Archive Product page
		// REFERENCE: woocommerce/templates/archive-product.php @ 47
		add_action( 'woocommerce_archive_description', array( &$this, 'render_displayed_fields' ), 9 );
	}

	// determine when this group needs to load the acf_form_head function. should be overriden by child class for it's logic to run
	protected function _needs_form_head() { return false; }

	// on the checkout, load our checkout js
	protected function _enqueue_assets() { }

	// render the fields in the appropriate location
	public function render_displayed_fields() {
		// get the object that was queried with wp_query
		$object = get_queried_object();

		// if the object is not a product_cat, then bail
		if ( ! isset( $object, $object->term_id, $object->taxonomy ) || 'product_cat' !== $object->taxonomy )
			return;

		// load the api helper, to help us find all the field groups used in this location
		$api = LOU_ACF_API::instance();

		// fetch the list of groups that belong on the checkout
		$field_groups = $api->get_field_groups( array(
			$this->group_slug => $this->slug,
		) );

		die(var_dump( $field_groups ));
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_Archive_Product_Display::instance();
