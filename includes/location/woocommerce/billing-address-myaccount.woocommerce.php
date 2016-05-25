<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// handles all extra my account fields, on the edit billing address screen
class LOU_ACF_WC_Billing_Address_My_Account extends LOU_ACF_Location {
	// method to grab the instance of this singleton
	public static function instance( $options=false ) { return self::_instance( __CLASS__, $options ); }
	protected function __construct() { parent::__construct(); }

	// initialize this location
	protected function initialize() {
		$this->slug = 'wc-my-account-billing-address';
		$this->name = __( 'My Account - Edit Billing Address', 'lou-acf-wc' );
		$this->group_slug = 'woocommerce';
		$this->priority = 20;

		// finish normali initialization
		parent::initialize();

		// add our fields to the WC my account page
		// REFERENCE: woocommerce/templates/myaccount/form-edit-address.php @ 45
		add_action( 'woocommerce_after_edit_address_form_billing', array( &$this, 'add_fields' ), 100 );

		// handle the saving of the address
		// REFERENCE: woocommerce/includes/class-wc-form-handler.php @ 131
		add_action( 'woocommerce_customer_save_address', array( &$this, 'save_address' ), 100, 2 );
	}

	// register this location with a specific location group
	public function register_with_group( $group ) {
		// register the extra edit billing fields location
		$group->register_location( array(
			'slug' => $this->slug,
			'name' => $this->name,
			'object' => &$this,
		) );
	}

	// determine when this group needs to load the acf_form_head function. should be overriden by child class for it's logic to run
	protected function _needs_form_head() {
		return is_account_page();
	}

	// on the my account page, load our my account assets
	protected function _enqueue_assets() {
		// reused vars
		$launcher = LOU_ACF_WC_Launcher::instance();
		$uri = $launcher->plugin_url() . 'assets/';
		$version = $launcher->version();
	}

	// add the fields we need to a new form at the bottom of the my account page
	public function add_fields() {
		$api = LOU_ACF_API::instance();
		// fetch the list of groups that belong on the top of the my account page
		$field_groups = $api->get_field_groups( array(
			'woocommerce' => $this->slug,
		) );

		// if there are no field groups to show, then bail
		if ( ! is_array( $field_groups ) || empty( $field_groups ) )
			return;

		// get the group keys from the array of fields
		$group_keys = wp_list_pluck( $field_groups, 'ID' );

		$api = LOU_ACF_API::instance();
		// start styling the fields for a woocommerce form
		$api->wc_fields_start();

		// get the information about the current user
		$user = wp_get_current_user();

		$form_id = 'acf_' . $this->slug . '_form';
		// otherwise render the groups
		$this->acf_form( apply_filters( 'lou-acf-' . $this->slug .  '-acf_form-params', array(
			// name the form in the DOM
			'id' => $form_id,
			// assign a new unique id for where to associate the field groups. myaccount_[$user_id]
			'post_id' => 'user_' . $user->ID,
			// draw the wc-my-account-top groups
			'field_groups' => $group_keys,
			// do not wrap the groups in a form, because we are already inside a form
			'form' => false,
			// kill the message, because the form already has one
			'updated_message' => false,
			// kill the button. we dont need it
			'submit_value' => false,
		), 'myaccount_' . $user->ID, $user ) );

		// add the javascript we need in order to make this work via ajax
		$api->acf_js_form_register( '#' . $form_id );

		// stop styling the fields for a woocommerce form
		$api->wc_fields_stop();
	}

	// handle the address save at the appropriate time
	public function save_address( $user_id, $address_type ) {
		// if this is not the billing fields save, then bail
		if ( 'billing' !== $address_type )
			return;

		// otherwise, save this shiz
		$this->_process_save( $user_id );
	}

	// handle the submitted fields
	protected function _process_save( $user_id=null ) {
		// get the current user if any
		$user = ! $user_id ? wp_get_current_user() : get_user_by( 'id', $user_id );

		// if the acf fields validate, then save them
		if ( $user instanceof WP_User && $user->ID > 0 && $this->_form_submitted() ) {
			$post_id = 'user_' . $user->ID;
			// if the function exists (because acf pro is active), then set the form data
			if ( function_exists( 'acf_set_form_data' ) )
				acf_set_form_data( array( 'post_id' => $post_id ) );

			// allow acf to save the fields to the order itself
			do_action( 'acf/save_post', $post_id );

			// then tell the world of our success
			do_action( 'lou/acf/save_post/type='. $this->slug, $post_id, $user );
		}
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_WC_Billing_Address_My_Account::instance();
