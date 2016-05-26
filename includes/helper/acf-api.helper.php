<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// a helper class to help us access the ACF api, and stub it when not available
class LOU_ACF_API extends LOU_ACF_Singleton {
	// method to grab the instance of this singleton
	public static function instance( $options=false ) { return self::_instance( __CLASS__, $options ); }
	protected function __construct() { parent::__construct(); }

	// container for the acf core api functions to use
	protected $funcs = array();

	// generic function designed to call acf core functions, based on what is available
	public function __call( $name, $args ) {
		return isset( $this->funcs[ $name ] ) ? call_user_func_array( $this->funcs[ $name ], $args ) : '';
	}

	// during the first creation of this object register some hooks
	protected function initialize() {
		// once all plugins are loaded, figure out if we need to stub any functions
		add_action( 'plugins_loaded', array( &$this, 'initialize_functions' ) );
	}

	// determind which functions to use, based on what is available
	public function initialize_functions() {
		// are we pro?
		$pro = function_exists( 'acf_get_fields' );

		// setup some functions
		$this->funcs['get_field_groups'] = $pro ? 'acf_get_field_groups' : array( &$this, 'api_get_field_groups' );
		$this->funcs['get_field_group_fields'] = $pro ? 'acf_get_fields' : array( &$this, 'filter_acf_get_fields' );
		$this->funcs['sort_by_menu_order'] = $pro ? array( &$this, 'api_sort_by_menu_order' ) : array( &$this, 'api_sort_by_order_no' );
		$this->funcs['translate_date_format'] = $pro ? array( &$this, 'no_translate_format' ) : array( &$this, 'translate_format' );
	}

	// start overriding the styling of the fields, so that the fields fit better into woocommerce forms
	public function wc_fields_start() {
		add_filter( 'acf/get_fields', array( &$this, 'wc_modify_fields' ), 1000, 2 );
	}

	// stop making the fields blend into wc form
	public function wc_fields_stop() {
		remove_filter( 'acf/get_fields', array( &$this, 'wc_modify_fields' ), 1000 );
	}

	// apply the field modifications to each field in the list
	public function wc_modify_fields( $fields ) {
		// cycle through the list of fields, and apply the modifications
		foreach ( $fields as $index => $field )
			$fields[ $index ] = $this->wc_modify_field_data( $field );

		return $fields;
	}

	// funciton that actually performs the field modifications to make them fit into the wc forms better
	public function wc_modify_field_data( $field ) {
		// add the appropriate field wrapper classes to make the field fit better in wc forms
		if ( isset( $field['wrapper'], $field['wrapper']['class'] ) )
			$field['wrapper']['class'] .= ' form-row';

		return $field;
	}

	// add the js to the bottom of a rendered form, that allows the form to be recognized by the frontend acf js, and thus the fields be initialized and required fields be enforced
	public function acf_js_form_register( $jq_selector ) {
		?><script type="text/javascript">if ( jQuery ) jQuery( function( $ ) { if ( acf && 'function' == typeof acf.do_action ) acf.do_action( 'append', $( '<?php esc_attr( $jq_selector ) ?>' ) ); } );</script><?php
	}

	// stub function to grab the field groups
	// this only runs if ACF non-pro is in use. ACF non-pro stores fields ang groups differently. this function loads those groups from that format
	public function api_get_field_groups( $args=false ) {
		// load all the acf groups
		$field_groups = apply_filters( 'acf/get_field_groups', array() );
		// and add their location information
		foreach ( $field_groups as $index => $group )
			$field_groups[ $index ]['location'] = apply_filters( 'acf/field_group/get_location', array(), $group['id'] );

		// filter the list of groups by our args
		return $this->_filter_groups( $field_groups, $args );
	}

	// get the fields that are part of the given field group
	public function api_get_field_group_fields( $field_group ) {
		$fields = array();
		// get the field_group_id
		$group_id = $this->_get_field_group_id( $field_group );

		// if there is no group_id, then bail
		if ( empty( $group_id ) )
			return $fields;

		return $this->get_field_group_fields( $group_id );
	}

	// polyfill for the missing acf_get_fields function on non-pro
	public function filter_acf_get_fields( $group_id ) {
		return apply_filters( 'acf/field_group/get_fields', array(), $group_id );
	}

	// sort the array by the array key 'menu_order' (PRO ONLY)
	public function api_sort_by_menu_order( $a, $b ) {
		return isset( $a['menu_order'], $b['menu_order'] ) ? $a['menu_order'] - $b['menu_order'] : 0;
	}

	// sort the array by the array key 'order_no' (NON-PRO ONLY)
	public function api_sort_by_order_no( $a, $b ) {
		return isset( $a['order_no'], $b['order_no'] ) ? $a['order_no'] - $b['order_no'] : 0;
	}

	// function to determine the field_group group_id based on some supplied information
	protected function _get_field_group_id( $field_group='' ) {
		// get the field group field_group_id from the supplied info
		$group_id = 0;

		// get the post_id from the field group array
		if ( is_array( $field_group ) && isset( $field_group['ID'] ) ) {
			$group_id = absint( $field_group['ID'] );
		// if the value supplied is a number, assume it is the field group post_id
		} else if ( is_numeric( $field_group ) ) {
			$group_id = absint( $field_group );
		// if it is a string, assume it is the unique id of the group. use that to look up the group info, then extract the id
		} else if ( is_string( $field_group ) ) {
			// if we are using PRO then this matters, otherwise it doesnt
			if ( function_exists( 'acf_is_field_group_key' ) ) {
				if ( acf_is_field_group_key( $field_group ) ) {
					$field_group = acf_get_field_group( $field_group );
					$group_id = $field_group['ID'];
				}
			}
		}

		return $group_id;
	}

	// filter the list of groups, by the args supplied
	protected function _filter_groups( $field_groups, $args ) {
		// if we do not have any args or field groups, then bail
		if ( empty( $field_groups ) || empty( $args ) )
			return $field_groups;

		$out_groups = array();
		// cycle through the groups and find all that match the args
		if ( is_array( $field_groups ) ) while ( $group = array_shift( $field_groups ) ) {
			if ( $this->_group_matches( $group, $args ) ) {
				$group['ID'] = $group['id'];
				$out_groups[] = $group;
			}
		}

		return $out_groups;
	}

	// figure out if a discreet group matches the supplied args
	protected function _group_matches( $group, $args ) {
		// cycle throguh
		// vars
		$args = wp_parse_args( $args, array(
			// pro
			'post_id' => 0,
			'post_type' => 0,
			'page_template' => 0,
			'page_parent' => 0,
			'page_type' => 0,
			'post_status' => 0,
			'post_format' => 0,
			'post_taxonomy' => null,
			'taxonomy' => 0,
			'user_id' => 0,
			'user_role' => 0,
			'user_form' => 0,
			'attachment' => 0,
			'comment' => 0,
			'widget' => 0,
			'lang' => defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '',
			'ajax' => false,
			
			// non-pro extras
			'post_category' => array(),
			'ef_taxonomy' => 0,
			'ef_user' => 0,
			'ef_media' => 0,
		) );
		// filter for 3rd party customization
		$args = apply_filters( 'acf/location/screen', $args, $group );

		// if the group is not active, bail
		if ( isset( $group['active'] ) && ! $group['active'] )
			return false;
	
		$show = false;
		// cycle through the location rules, and figure out if this group matches the args
		foreach ( $group['location'] as $rules_id => $rules ) {
			// figure out if any rules pass
			$passed = true;

			if ( is_array( $rules ) ) foreach ( $rules as $rule ) {
				// figure out if this rule matches
				$match = apply_filters( 'acf/location/rule_match/' . $rule['param'] , false, $rule, $args );

				// if the rule does not match, bail now
				if ( ! $match ) {
					$passed = false;
					break;
				}
			}

			// if all rules for any location passed, then this group should be shown
			if ( $passed ) {
				$show = true;
				break;
			}
		}

		return $show;
	}

	// no translation needed (PRO ONLY)
	public function no_translate_format( $format ) { return $format; }

	// translate a jquery datepicker format into a php date format. (NON-PRO ONLY)
	public function translate_format( $format ) {
		// this is a simplified, reverse engineer of the below function
		// first remove all escaped chars
		$format = preg_replace( '#(\'[^\']\')#', '', $format );

		// symbol map. ignore any that do not change between the formats
		$SYMBOLS = array(
			// two special cases, we need to temp replace first
			'dd' => '?',
			'mm' => '!',
			// then the normal one to ones (so to speak)
			'DD' => 'l',
			'MM' => 'F',
			'yy' => 'Y',
			'd' => 'j',
			'o' => 'z',
			'm' => 'n',
			// extra special, because of the way that str_replace works
			'?' => 'd',
			'!' => 'm',
		);
		// then do a symbol replace
		$format = str_replace( array_keys( $SYMBOLS ), array_values( $SYMBOLS ), $format );

		return $format;
	}

	// convert php to jquery date format
	// REFERENCE: http://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
	protected function _translate_format_to_jquery( $format ) {
		$SYMBOLS_MATCHING = array(
			// Day
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week
			'W' => '',
			// Month
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// Year
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// Time
			'a' => '',
			'A' => '',
			'B' => '',
			'g' => '',
			'G' => '',
			'h' => '',
			'H' => '',
			'i' => '',
			's' => '',
			'u' => ''
		);
		$jqueryui_format = "";
		$escaping = false;
		for( $i = 0; $i < strlen( $php_format ); $i++ ) {
			$char = $php_format[ $i ];
			// PHP date format escaping character
			if ($char === '\\') {
				$i++;
				if ( $escaping )
					$jqueryui_format .= $php_format[$i];
				else
					$jqueryui_format .= '\'' . $php_format[$i];
				$escaping = true;
			} else {
				if ( $escaping ) {
					$jqueryui_format .= "'";
					$escaping = false;
				}
				if ( isset( $SYMBOLS_MATCHING[ $char ] ) )
					$jqueryui_format .= $SYMBOLS_MATCHING[ $char ];
				else
					$jqueryui_format .= $char;
			}
		}
		return $jqueryui_format;

	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_API::instance();
