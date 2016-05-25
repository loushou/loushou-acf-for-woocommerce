<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) die( header( 'Location: /') );

// a multiton that handles all our singletons
abstract class LOU_ACF_Singleton {
	// container for all the singleton instances
	protected static $_instances = array();

	// internal method to handle grabing of a given instance
	protected static function _instance( $class, $options='' ) {
		// if the instance does not exist, or is not of the right type, then initialize the instance
		if ( ! isset( self::$_instances[ $class ] ) || ! ( self::$_instances[ $class ] instanceof $class ) ) {
			self::$_instances[ $class ] = new $class;
			self::$_instances[ $class ]->initialize();
		}

		// if any options were passed in, set those up now
		if ( $options )
			self::$_instances[ $class ]->set_options( $options );

		return self::$_instances[ $class ];
	}
	protected function __construct() {}

	// require an initialize function
	abstract protected function initialize();

	// stub out the deinit and set_options functions
	protected function deinitialize() {}
	public function set_options( $options=false ) {}
}
