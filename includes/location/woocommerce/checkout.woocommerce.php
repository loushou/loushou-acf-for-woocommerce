<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// handles all extra checkout fields
class LOU_ACF_WC_Checkout extends LOU_ACF_Location {
	// method to grab the instance of this singleton
	public static function instance( $options=false ) { return self::_instance( __CLASS__, $options ); }
	protected function __construct() { parent::__construct(); }

	// initialize this location
	protected function initialize() {
		$this->slug = 'wc-checkout';
		$this->name = __( 'Checkout', 'lou-acf-wc' );
		$this->group_slug = 'woocommerce';
		$this->priority = 1;

		// finish normali initialization
		parent::initialize();

		// add our fields to the WC checkout form
		// REFERENCE: woocommerce/templates/checkout/form-checkout.php @ 42
		add_action( 'woocommerce_checkout_billing', array( &$this, 'add_fields_to_billing_form' ), 100 );

		// pickup the extra checkout fields, and save them when appropriate
		// REFERENCE: woocommerce/includes/class-wc-checkout.php @ 634
		add_action( 'woocommerce_checkout_order_processed', array( &$this, 'process_checkout_fields' ), 100, 2 );
	}

	// determine when this group needs to load the acf_form_head function. should be overriden by child class for it's logic to run
	protected function _needs_form_head() {
		return is_checkout();
	}

	// on the checkout, load our checkout js
	protected function _enqueue_assets() {
		// reused vars
		$launcher = LOU_ACF_WC_Launcher::instance();
		$uri = $launcher->plugin_url() . 'assets/';
		$version = $launcher->version();

		// queue up the checkout specific js, that handles the acf form validation
		wp_enqueue_script( 'lou-acf-checkout', $uri . 'js/frontend/checkout.js', array( 'jquery' ), $version );
	}

	// add the fields we need to the billing information form on the checkout
	public function add_fields_to_billing_form() {
		$api = LOU_ACF_API::instance();
		// fetch the list of groups that belong on the checkout
		$field_groups = $api->get_field_groups( array(
			'woocommerce' => $this->slug,
		) );

		// if there are no field groups to show, then bail
		if ( ! is_array( $field_groups ) || empty( $field_groups ) )
			return;

		// get the group keys from the array of fields
		$group_keys = wp_list_pluck( $field_groups, 'ID' );

		// fetch the appropriate order id to use
		$order_id = absint( WC()->session->order_awaiting_payment );

		$api = LOU_ACF_API::instance();
		// start styling the fields for a woocommerce form
		$api->wc_fields_start();

		$form_id = 'acf_billing_form';
		// otherwise render the groups
		$this->acf_form( apply_filters( 'lou-acf-my-account-acf_form-params', array(
			// name the form in the DOM
			'id' => $form_id,
			// assign a new unique id for where to associate the field groups. checkout_[order_number]
			'post_id' => 'checkout_' . $order_id,
			// draw the wc-checkout groups
			'field_groups' => $group_keys,
			// do not wrap the groups in a form, because we are already inside a form
			'form' => false,
			// kill the updated post message
			'updated_message' => '',
		), 'checkout_' . $order_id, wc_get_order( $order_id ) ) );

		// add the javascript we need in order to make this work via ajax
		$api->acf_js_form_register( '#' . $form_id );

		// stop styling the fields for a woocommerce form
		$api->wc_fields_stop();
	}

	// detect and process the checkout fields, once we have an order number and a user to work with
	public function process_checkout_fields( $order_id, $posted ) {
		$this->_handle_checkout_fields( $order_id );
	}

	// handle the submitted fields
	protected function _handle_checkout_fields( $order_id ) {
		// get the current user if any
		$user = wp_get_current_user();

		// if the acf fields validate, then save them
		if ( $this->_form_submitted() ) {
			// if the function exists (because acf pro is active), then set the form data
			if ( function_exists( 'acf_set_form_data' ) )
				acf_set_form_data( array( 'post_id' => $order_id ) );

			// allow acf to save the fields to the order itself
			do_action( 'acf/save_post', $order_id );

			// then tell the world of our success
			do_action( 'lou/acf/save_post/type=checkout', $order_id, $user );
		}
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_WC_Checkout::instance();
