<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// generic location class. designed to handle many of the basic location needs
abstract class LOU_ACF_Location extends LOU_ACF_Singleton {
	// counter to help with unique naming as a fallback
	protected static $inc = 0;

	// information about this location
	protected $slug = '';
	protected $name = '';
	protected $group_slug = '';
	protected $priority = 10;

	// protect the constructor so that we can actually have a singleton
	protected function __construct() {
		// setup the fallback name of this location
		$this->name = sprintf( __( 'Location %d', 'lou-acf-wc' ), self::$inc );
		$this->slug = 'location-' . ( self::$inc++ );
	}

	// initialize this location
	protected function initialize() {
		// if there is a defined location group, then add an action to register this location with that group
		if ( $this->group_slug )
			add_action( 'lou-acf-register-locations/' . $this->group_slug, array( &$this, 'register_with_group' ), $this->priority );

		// load the acf_form_head stuff on the template redirect action when on the appropriate page
		// REFERENCE: wp-includes/template-loader.php @ 12
		add_action( 'template_redirect', array( &$this, 'load_acf_form_head' ), 10 );
	}

	// register this location with a specific location group
	public function register_with_group( $group ) {
		$group->register_location( array(
			'slug' => $this->slug,
			'name' => $this->name,
			'object' => &$this,
		) );
	}

	// draw the acf form
	public function acf_form( $args='' ) {
		$func = apply_filters( 'lou-acf-render-acf_form', 'acf_form', $args );
		call_user_func_array( $func, func_get_args() );
	}

	// determine if an acf form was submitted
	protected function _form_submitted() {
		$pro = function_exists( 'acf_validate_save_post' );
		// if this is acf pro, then validate the form, and pass
		if ( $pro && isset( $_POST['acf'] ) && acf_validate_save_post() )
			return true;
		// if not pro, and the fields we submitted, pass
		else if ( ! $pro && isset( $_POST['fields'], $_POST['acf_settings'] ) )
			return true;

		return false;
	}

	// determine when this group needs to load the acf_form_head function. should be overriden by child class for it's logic to run
	protected function _needs_form_head() {
		return false;
	}

	// stub for enqueuing the assets needed by this locaiton
	protected function _enqueue_assets() {}

	// stub for handling a form submission
	protected function _process_submitted_form() {}

	// when viewing a page, load the acf_form_head logic before page render
	public function load_acf_form_head() {
		// if this is not the appropriate woocommerce page, then bail now
		if ( ! $this->_needs_form_head() )
			return;

		// process the form handler if we still need to
		if ( $this->_form_submitted() )
			$this->_process_submitted_form();

		// load any script and style for this location specifically
		$this->_enqueue_assets();

		// otherwise load the acf logic
		acf_form_head();

		// if we are pro, add the scripts too
		if ( function_exists( 'acf_enqueue_scripts' ) )
			acf_enqueue_scripts();
	}
}
