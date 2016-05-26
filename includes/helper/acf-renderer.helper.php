<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// a helper class to help us access the ACF api, and stub it when not available
class LOU_ACF_Renderer extends LOU_ACF_Singleton {
	// method to grab the instance of this singleton
	public static function instance( $options=false ) { return self::_instance( __CLASS__, $options ); }
	protected function __construct() { parent::__construct(); }

	// container for the acf core api functions to use
	protected $funcs = array();

	// container for the api helper
	protected $api = null;

	// generic function designed to call acf core functions, based on what is available
	public function __call( $name, $args ) {
		return isset( $this->funcs[ $name ] ) ? call_user_func_array( $this->funcs[ $name ], $args ) : '';
	}

	// during the first creation of this object register some hooks
	protected function initialize() {
		$this->api = LOU_ACF_API::instance();
		// once all plugins are loaded, figure out if we need to stub any functions
		add_action( 'plugins_loaded', array( &$this, 'initialize_functions' ) );

		// add a special option to each field, that decides if the label should be displayed on the frontend or not
		add_action( 'acf/create_field_options', array( &$this, 'add_field_display_label' ) );
		add_action( 'acf/render_field_settings', array( &$this, 'add_field_display_label_pro' ) );
	}

	// determind which functions to use, based on what is available
	public function initialize_functions() {
	}

	// NON-PRO ONLY: add a field to the admin interface, that decides whether this field's label gets displayed on the frontend or not
	public function add_field_display_label( $field ) {
		?>
			<tr class="field_display_label">
				<td class="label"><label><?php _e( 'Display Label Frontend', 'lou-acf-wc' ); ?></label>
				<p class="description"><?php _e( 'Whether the Label of this field is rendered on the frontend, using the Loushou: ACF for WooCommerce locations.', 'lou-acf-wc' ); ?></p></td>
				<td>
					<?php
						do_action( 'acf/create_field', array(
							'type' => 'radio',
							'name' => 'fields[' . $field['name'] . '][display_label]',
							'value' => isset( $field['display_label'] ) ? $field['display_label'] : 1,
							'choices' => array(
								1 => __( 'Yes', 'acf' ),
								0 => __( 'No', 'acf' ),
							),
							'layout' => 'horizontal',
						) );
					?>
				</td>
			</tr>
		<?php
	}

	// PRO ONLY: add a field to the admin interface, that decides whether this field's label gets displayed on the frontend or not
	public function add_field_display_label_pro( $field ) {
			// required
			acf_render_field_wrap(array(
				'label' => __( 'Display Label Frontend', 'lou-acf-wc' ),
				'instructions' => __( 'Whether the Label of this field is rendered on the frontend, using the Loushou: ACF for WooCommerce locations.', 'lou-acf-wc' ),
				'type' => 'radio',
				'name' => 'display_label',
				'prefix' => $field['prefix'],
				'value' => isset( $field['display_label'] ) ? $field['display_label'] : 1,
				'choices' => array(
					1 => __( 'Yes', 'acf' ),
					0 => __( 'No', 'acf' ),
				),
				'layout' => 'horizontal',
				'class' => 'field-display_label'
			), 'tr');
	}

	// help out rendering each type of field on the frontend
	public function frontend_fields( $fields, $object_id, $object=null ) {
		// if there is no fields or object id, bail
		if ( empty( $fields ) || empty( $object_id ) )
			return;

		// before rendering the fields, send a message out saying we are about to
		do_action( 'lou/acf/render_frontend_fields/before', $fields, $object_id, $object );

		// cycle through all the fields, grab the value, and render the field
		foreach ( $fields as $field ) {
			// get the value
			$value = get_field( $field['key'], $object_id );

			// normalize the field to include our 'display_label' option, defaulted to yes
			$field = wp_parse_args( $field, array( 'display_label' => 1 ) );

			$output = '';
			// create different output depending on the type of the field
			switch ( $field['type'] ) {
				// do nothing for these fields by default
				case 'password':
				case 'color_picker':
				case 'file':
				case 'page_link':
				case 'post_object':
				case 'relationship':
				case 'tab':
				case 'taxonomy':
				case 'true_false':
				case 'user':
				break;

				// maps are special
				case 'google_map':
					$output = $this->_map_field( $value, $field, $object_id, $object, $fields );
				break;

				// dates are special too
				case 'date_picker':
					$output = $this->_date_field( $value, $field, $object_id, $object, $fields );
				break;

				// for many of the fields, default to a labeled output, where a label exists above the output of the field information
				default:
				case 'date_picker':
				case 'checkbox':
				case 'image':
				case 'email':
				case 'message':
				case 'wysiwyg':
				case 'textarea':
				case 'text':
				case 'number':
				case 'select':
				case 'radio':
					$output = $this->_labeled_field( $value, $field, $object_id, $object, $fields );
				break;
			}

			// allow editing of this output by external sources
			$output = apply_filters( 'lou/acf/render_frontend_field/type=' . $field['type'], $output, $value, $field, $fields, $object_id, $object );

			// if there is a value to display, display it
			if ( $output )
				echo $output;
		}

		// after rendering the fields, send a message out saying we finished
		do_action( 'lou/acf/render_frontend_fields/after', $fields, $object_id, $object );
	}

	// render a map field
	protected function _map_field( $value, $field, $object_id, $object, $fields ) {
		// queue the needed js
		wp_enqueue_script( 'lou-acf-load-maps' );

		// figure out the map height
		$height = $field['height'];
		$height = $height == intval( $height ) ? $height . 'px' : $height;

		$markers = array();
		// create a pin for each marker
		$markers[] = sprintf(
			'<div class="lou-marker" data-lat="%s" data-lng="%s">%s</div>',
			esc_attr( $value['lat'] ),
			esc_attr( $value['lng'] ),
			''
		);

		// allow modification of the pins
		$markers = apply_filters( 'lou/acf/render_map_field/type=markers', $markers, $value, $field, $fields, $object_id, $object );

		// return the actual map container with the pins
		return ! $markers ? '' : sprintf(
			'<div class="%s">%s<div class="lou-acf-google-map" data-zoom="%s" style="height:%s">%s</div></div>',
			'lou-acf lou-acf-' . $field['type'],
			! $field['display_label'] || ! isset( $field['label'] ) || empty( $field['label'] ) ? '' : sprintf(
				'<label class="%s">%s</label>',
				'lou-acf-label',
				apply_filters( 'the_title', $field['label'] )
			),
			$field['zoom'],
			$height,
			is_array( $markers ) ? implode( '', $markers ) : $markers
		);
	}

	// render a date field
	protected function _date_field( $value, $field, $object_id, $object ) {
		// figure out the timestamp of the date
		$date = strtotime( $value );

		return $date
				// if we figured out a timestamp, then format it
				? $this->_labeled_field( date( $this->api->translate_date_format( $field['display_format'] ), $date ), $field, $object_id, $object )
				// otherwise, pass through
				: $this->_labeled_field( $value, $field, $object_id, $object );
	}

	// render a text/wysiwyg field
	protected function _labeled_field( $value, $field, $object_id, $object ) {
		return sprintf(
			'<div class="%s">%s<div class="%s">%s</div></div>',
			'lou-acf lou-acf-' . $field['type'],
			! $field['display_label'] || ! isset( $field['label'] ) || empty( $field['label'] ) ? '' : sprintf(
				'<label class="%s">%s</label>',
				'lou-acf-label',
				apply_filters( 'the_title', $field['label'] )
			),
			'lou-acf-value',
			! is_array( $value ) ? force_balance_tags( $value ) : implode( ', ', array_map( 'force_balance_tags', $value ) )
		);
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_Renderer::instance();
