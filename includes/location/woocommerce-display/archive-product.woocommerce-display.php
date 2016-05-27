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
		$this->slug2 = 'archive-product-bottom';
		$this->name2 = __( 'Archive Product Bottom', 'lou-acf-wc' );
		$this->group_slug = 'woocommerce-display';
		$this->priority = 200;

		// finish normali initialization
		parent::initialize();

		// add the output to the top of the WooCommerce Archive Product page
		// REFERENCE: woocommerce/templates/archive-product.php @ 47
		add_action( 'woocommerce_archive_description', array( &$this, 'render_displayed_fields_top' ), 9 );

		// add the output to the bottom of the WooCommerce Archive Product page
		// REFERENCE: woocommerce/templates/archive-product.php @ 80
		add_action( 'woocommerce_after_shop_loop', array( &$this, 'render_displayed_fields_bottom' ), 9 );
	}

	// determine when this group needs to load the acf_form_head function. should be overriden by child class for it's logic to run
	protected function _needs_form_head() { return false; }

	// register this location with a specific location group
	public function register_with_group( $group ) {
		// register two new locations
		// one for the top of the archive product page
		$group->register_location( array(
			'slug' => $this->slug,
			'name' => $this->name,
			'object' => &$this,
		) );

		// oen for the bottom
		$group->register_location( array(
			'slug' => $this->slug2,
			'name' => $this->name2,
			'object' => &$this,
		) );
	}

	// stub
	protected function _enqueue_assets() { }

	// render the fields for the top of the page
	public function render_displayed_fields_top() {
		$this->_render_displayed_fields( $this->slug );
	}

	// render the fields for the bottom of the page
	public function render_displayed_fields_bottom() {
		$this->_render_displayed_fields( $this->slug2 );
	}

	// render the fields in the appropriate location
	protected function _render_displayed_fields( $slug ) {
		// get the object that was queried with wp_query
		$object = get_queried_object();

		// if the object is not a product_cat, then bail
		if ( ! isset( $object, $object->term_id, $object->taxonomy ) || 'product_cat' !== $object->taxonomy )
			return;

		// load the api helper, to help us find all the field groups used in this location
		$api = LOU_ACF_API::instance();

		// fetch the list of groups that belong to this group
		$field_groups = $api->get_field_groups( array(
			$this->group_slug => $slug,
		) );

		$fields = array();
		// get a list of all the fields to display here
		foreach ( $field_groups as $group ) {
			// get all the fields for this group
			$group_fields = $api->api_get_field_group_fields( $group );

			// add each group to the list of fields to display. make sure to index by key (key instead of id, because key is present in both pro and non-pro, but id is only present in pro), so we dont get dupes
			foreach ( $group_fields as $field )
				$fields[ $field['key'] ] = $field;
		}

		// if there are no fields to display, then bail
		if ( empty( $fields ) )
			return;

		// otherwise, sort the fields to display
		uasort( $fields, array( &$api, 'sort_by_menu_order' ) );
		$fields = apply_filters( 'lou/acf/field_display_order/location=' . $slug . '/term_id=' . $object->term_id, $fields, $object );

		$renderer = LOU_ACF_Renderer::instance();
		// render the fields
		$renderer->frontend_fields( $fields, $object->taxonomy . '_' . $object->term_id, $object );
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_Archive_Product_Display::instance();
