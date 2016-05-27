window.LOU = window.LOU || {};
if ( jQuery ) ( function( $ ) {
	var LOU = window.LOU,
			def = {
				zoom: 14,
				lat: false,
				lng: false
			},
			def_map_args = {
				zoom: 14,
				center: false,
				mapTypeId: false
			};

	// utility functions
	function toF( v ) { var n = parseFloat( v ); return ! isNaN( n ) ? n : 0.0; }
	function toI( v ) { var n = parseInt( v ); return ! isNaN( n ) ? n : 0; }

	// export all these functions
	$.extend( LOU, {
		render_maps: function( els ) {
			$( els ).each( function() {
				// fetch all the map data from the element itself
				var $me = $( this ),
						// get all of the markers inside the map container
						$markers = $me.find( '.lou-marker' ),
						// get the basic map args off the map element
						map_args = $me.data(),
						// create the google map element
						map = LOU.create_map( $me, map_args );

				// create a container for the map markers
				map.markers = [];

				// find all the markers, create a pin
				$markers.each( function() {
					var $marker = $( this ),
							data = $marker.data();

					// normalize the data with some defaults
					data = $.extend( {}, def, data );

					// if there is no lat or lng, bail
					if ( false === data.lat || false === data.lng )
						return;

					// normalize the data
					data.lat = toF( data.lat );
					data.lng = toF( data.lng );
					data.zoom = toI( data.zoom );

					// add the pin based on the loaded data
					LOU.add_pin( map, data, $marker, $me );
				} );

				// center the map around the supplied points
				LOU.center_map( map );
			} );
		},

		// create the google map element
		create_map: function( $el, map_args ) {
			// get the nake element
			var el = $el.get( 0 ),
					map_args = $.extend( {}, def_map_args, map_args ),
					map;

			// normalize the args
			map_args.zoom = Math.min( 20, Math.max( 1, map_args.zoom ) );
			map_args.center = false !== map_args.center ? new google.maps.LatLng( map_args.center.lat, map_args.center.lng ) : new google.maps.LatLng( 0, 0 );
			map_args.mapTypeId = false !== map_args.mapTypeId ? map_args.mapTypeId : google.maps.MapTypeId.ROADMAP;

			// create the map object
			map = new google.maps.Map( el, map_args );
			map.orig_args = map_args;

			return map;
		},

		// add a pin to a given map
		add_pin: function( map, pin_data, $marker, $map_el ) {
			// create the LatLng object for the point
			var latlng = new google.maps.LatLng( pin_data.lat, pin_data.lng ),
					// add the pin to the map
					marker = new google.maps.Marker( { position:latlng, map:map } );

			// add this marker to the list of map markers for the map
			map.markers.push( marker );

			// get the marker html, and make the info window for it, if there is html
			var html = $.trim( $marker.html() );
			if ( html ) {
				// create the info window
				var info_window = new google.maps.InfoWindow( { content:html } );

				// when the pin is clicked, open the info window
				google.maps.event.addListener( marker, 'click', function() { info_window.open( map, marker ); } );
			}
		},

		// center the map around the array of map markers we have
		center_map: function( map ) {
			// if there are no markers, then bail
			if ( ! map.markers || ! map.markers.length )
				return;

			// create a bounding box
			var bound = new google.maps.LatLngBounds();

			// cycle through all the pins, and expand the box appropriately
			$.each( map.markers, function( idx, marker ) {
				bound.extend( marker.position );
			} );

			// if there was only one marker, then set the bound of that marker, with an appropriate zoom
			if ( 1 == map.markers.length ) {
				map.setCenter( bound.getCenter() );
				map.setZoom( map.orig_args.zoom );
			// otherwise calc the appropriate bouding box
			} else {
				map.fitBounds( bound );
			}
		}
	} );

	// on page load, render all lou-google_map areas
	$( function() {
		LOU.render_maps( $( '.lou-acf-google-map' ) );
	} );
} )( jQuery );
