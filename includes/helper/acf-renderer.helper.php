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

		$ts = microtime( true );
		// get the output for all the fields
		$output = $this->_all_fields( $fields, $object_id, $object );
		echo $output;
		echo '<!-- render-time:' . round( 1000 * ( microtime( true ) - $ts ), 3 ) . 'ms -->';

		// after rendering the fields, send a message out saying we finished
		do_action( 'lou/acf/render_frontend_fields/after', $fields, $object_id, $object );
	}

	// get the field value, possibly based on passed in value data
	protected function _get_field( $field, $object_id, $value=null ) {
		// if the value field was not passed in, just grab the field from acf
		if ( null == $value || ! is_array( $value ) )
			return get_field( $field['key'], $object_id );

		// otherwise, try to grab the info from the value passed in
		return isset( $value[ $field['name'] ] ) ? $value[ $field['name'] ] : '';
	}

	// handle all field types
	protected function _all_fields( $fields, $object_id, $object=null, $values=null ) {
		$final = '';
		// cycle through all the fields, grab the value, and render the field
		foreach ( $fields as $field ) {
			// get the value
			$value = $this->_get_field( $field, $object_id, $values );

			// normalize the field to include our 'display_label' option, defaulted to yes
			$field = wp_parse_args( $field, array( 'display_label' => 1 ) );

			$output = '';
			// create different output depending on the type of the field
			switch ( $field['type'] ) {
				// do nothing for these fields by default
				default:
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

				// flexible content blocks are special
				case 'flexible_content':
					$output = $this->_flexible_content_field( $value, $field, $object_id, $object, $fields, $values );
				break;

				// repeaters are special
				case 'repeater':
					$output = $this->_repeater_field( $value, $field, $object_id, $object, $fields, $values );
				break;

				// maps are special
				case 'google_map':
					$output = $this->_map_field( $value, $field, $object_id, $object, $fields, $values );
				break;

				// dates are special too
				case 'date_picker':
					$output = $this->_date_field( $value, $field, $object_id, $object, $fields, $values );
				break;

				// for many of the fields, default to a labeled output, where a label exists above the output of the field information
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
					$output = $this->_labeled_field( $value, $field, $object_id, $object, $fields, $values );
				break;
			}

			// allow editing of this output by external sources
			$output = apply_filters( 'lou/acf/render_frontend_field/type=' . $field['type'], $output, $value, $field, $fields, $object_id, $object, $values );

			// add this field's output to the total output
			$final .= $output;
		}

		 return $final;
	}

	// render a map field
	protected function _map_field( $value, $field, $object_id, $object, $fields, $values ) {
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
		$markers = apply_filters( 'lou/acf/render_map_field/type=markers', $markers, $value, $field, $fields, $object_id, $object, $values );

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
	protected function _date_field( $value, $field, $object_id, $object, $fields, $values ) {
		// figure out the timestamp of the date
		$date = strtotime( $value );

		return $date
				// if we figured out a timestamp, then format it
				? $this->_labeled_field( date( $this->api->translate_date_format( $field['display_format'] ), $date ), $field, $object_id, $object, $fields, $values )
				// otherwise, pass through
				: $this->_labeled_field( $value, $field, $object_id, $object );
	}

	// render a text/wysiwyg field
	protected function _labeled_field( $value, $field, $object_id, $object, $fields, $values ) {
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

	// handle a flexible content field
	protected function _flexible_content_field( $value, $field, $object_id, $object, $fields, $values ) {
		$final = '';
		// cycle through each stored value
		if ( is_array( $value ) ) foreach ( $value as $item ) {
			// figure out the block type for this content block
			$layout = $this->_get_block_layout( $item, $field );

			// if there is no layout, then skip
			if ( ! $layout || ! is_array( $layout ) )
				continue;

			// load the field data for that type of flexible content block
			$block_fields = $layout['sub_fields'];

			// get the output for this one block
			$block_output = $this->_all_fields( $block_fields, $object_id, $object, $item );

			// alloe modification of the block
			$block_output = apply_filters( 'lou/acf/render_frontend_field/type=flexible_content/layout=' . $layout['name'], $block_output, $item, $layout, $value, $field, $object_id, $object, $fields, $values );

			// wrap this one block's output in an outer html block, and add it to the final output of this field
			$final .= $block_output ? '<div class="lou-acf lou-acf-flexible-content lou-acf-flexible-content-' . sanitize_title_with_dashes( $layout['name'] ) . '">' . $block_output . '</div>' : '';
		}

		return $final;
	}

	// handle a repeater field
	protected function _repeater_field( $value, $field, $object_id, $object, $fields, $values ) {
		$final = '';
		// get the list of fields for this repeater
		$repeated_fields = isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ? $field['sub_fields'] : false;

		// cycle through the repeats, and create some html for each one
		if ( is_array( $repeated_fields ) && is_array( $value ) ) foreach ( $value as $item ) {
			// get this block's html output
			$block_output = $this->_all_fields( $repeated_fields, $object_id, $object, $item );

			// alloe modification of the block
			$block_output = apply_filters( 'lou/acf/render_frontend_field/type=repeater/block', $block_output, $item, $value, $field, $object_id, $object, $fields, $values );

			// add this blocks output to the final output for this field, wrapped in an outer container marking it as a repeater
			$final .= '<div class="lou-acf lou-acf-repeater">' . $block_output . '</div>';
		}

		return $final;
	}

	// get the layout array for the type of content block we are displaying
	protected function _get_block_layout( $item, $field ) {
		// get teh string name of the layout
		$layout_type = isset( $item['acf_fc_layout'] ) ? $item['acf_fc_layout'] : '';

		// if there is no layout name, then bail
		if ( ! $layout_type || ! is_string( $layout_type ) )
			return false;

		$found = false;
		// cycle through all the layouts, and find the one that matches our name
		foreach ( $field['layouts'] as $layout ) {
			if ( isset( $layout['name'] ) && $layout_type == $layout['name'] ) {
				$found = $layout;
				break;
			}
		}

		return $layout;
	}
}

// security
if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) )
	LOU_ACF_Renderer::instance();
