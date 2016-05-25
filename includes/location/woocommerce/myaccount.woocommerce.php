<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// handles all extra my account fields
class LOU_ACF_WC_My_Account extends LOU_ACF_Location {
	// method to grab the instance of this singleton
	public static function instance( $options=false ) { return self::_instance( __CLASS__, $options ); }
	protected function __construct() { parent::__construct(); }

	// initialize this location
	protected function initialize() {
		$this->slug = 'wc-my-account';
		$this->name = __( 'My Account - Top', 'lou-acf-wc' );
		$this->slug2 = 'wc-my-account-bottom';
		$this->name2 = __( 'My Account', 'lou-acf-wc' );
		$this->group_slug = 'woocommerce';
		$this->priority = 10;

		// finish normali initialization
		parent::initialize();

		// add our fields to the WC my account page
		// @NOTE#1:cannot have two forms on one page, because 'required validation' is handled on the page level by acf, not the form level. maybe in the future we can add this in there. for now, just the bottom field group area is available
		// REFERENCE: woocommerce/templates/myaccount/my-account.php @ 38
		//add_action( 'woocommerce_before_my_account', array( &$this, 'add_fields_to_my_account_top' ), 100 );
		// REFERENCE: woocommerce/templates/myaccount/my-account.php @ 46
		add_action( 'woocommerce_after_my_account', array( &$this, 'add_fields_to_my_account_bottom' ), 100 );
	}

	// register this location with a specific location group
	public function register_with_group( $group ) {
		// register two new locations
		// one for the top of the my account page
		// commented out. see @NOTE#1
		/*
		$group->register_location( array(
			'slug' => $this->slug,
			'name' => $this->name,
			'object' => &$this,
		) );
		*/

		// oen for the top of the my account page
		$group->register_location( array(
			'slug' => $this->slug2,
			'name' => $this->name2,
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

	// add the fields we need to a new form at the top of the my account page
	// commented out. see @NOTE#1
	/*
	public function add_fields_to_my_account_top() {
		$api = LOU_ACF_API::instance();
		// fetch the list of groups that belong on the top of the my account page
		$field_groups = $api->get_field_groups( array(
			'woocommerce' => $this->slug,
		) );

		// if there are no field groups to show, then bail
		if ( ! is_array( $field_groups ) || empty( $field_groups ) )
			return;

		// draw the form for these groups
		$this->_draw_field_groups_from( $field_groups, $this->slug );
	}
	*/

	// add the fields we need to a new form at the bottom of the my account page
	public function add_fields_to_my_account_bottom() {
		$api = LOU_ACF_API::instance();
		// fetch the list of groups that belong on the top of the my account page
		$field_groups = $api->get_field_groups( array(
			'woocommerce' => $this->slug2,
		) );

		// if there are no field groups to show, then bail
		if ( ! is_array( $field_groups ) || empty( $field_groups ) )
			return;

		// draw the form for these groups
		$this->_draw_field_groups_from( $field_groups, $this->slug2 );
	}

	// draw a form with the specified field groups
	protected function _draw_field_groups_from( $field_groups, $slug ) {
		// get the group keys from the array of fields
		$group_keys = wp_list_pluck( $field_groups, 'ID' );

		$api = LOU_ACF_API::instance();
		// start styling the fields for a woocommerce form
		$api->wc_fields_start();

		// get the information about the current user
		$user = wp_get_current_user();

		$form_id = 'acf_' . $slug . '_form';
		// otherwise render the groups
		$this->acf_form( apply_filters( 'lou-acf-' . $slug .  '-acf_form-params', array(
			// name the form in the DOM
			'id' => $form_id,
			// assign a new unique id for where to associate the field groups. myaccount_[$user_id]
			'post_id' => 'user_' . $user->ID,
			// draw the wc-my-account-top groups
			'field_groups' => $group_keys,
			// do not wrap the groups in a form, because we are already inside a form
			'form' => true,
			// add a meaningful message upon save success
			'updated_message' => __( 'Your account information has been saved', 'lou-acf-wc' ),
			// the text for the save button
			'submit_value' => __( 'Save', 'lou-acf-wc' ),
		), 'myaccount_' . $user->ID, $user ) );

		// add the javascript we need in order to make this work via ajax
		$api->acf_js_form_register( '#' . $form_id );

		// stop styling the fields for a woocommerce form
		$api->wc_fields_stop();
	}

	// handle the submitted fields
	protected function _process_submitted_form() {
		// get the current user if any
		$user = wp_get_current_user();

		// if the acf fields validate, then save them
		if ( $user instanceof WP_User && $user->ID > 0 && $this->_form_submitted() ) {
			$post_id = 'user_' . $user->ID;
			// if the function exists (because acf pro is active), then set the form data
			if ( function_exists( 'acf_set_form_data' ) )
				acf_set_form_data( array( 'post_id' => $post_id ) );

			// allow acf to save the fields to the order itself
			do_action( 'acf/save_post', $post_id );

			// then tell the world of our success
			do_action( 'lou/acf/save_post/type='. $this->slug2, $post_id, $user );
		}
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_WC_My_Account::instance();
