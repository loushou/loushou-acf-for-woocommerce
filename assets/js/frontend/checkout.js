( function( $ ) {
	$.fn.prependOn = function( ev, f ) {
		return this.each( function() {
			var elem = this,
					ele = $( this ),
					events = $._data( this, 'events' ),
					revts = ev.split( /\s+/ );

			// cycle through all the requested events, and handle each one separately
			$.each( revts, function( rind, revt ) {
				// split the requested event into event and namespace
				var revt = revt.split( /\./ ),
						type = revt.shift(),
						namespaces = revt,
						data = null;

				// if there are no events yet, then assume this is the first. no special logic here
				if ( ! events ) {
					ele.on( ev, f );
				// otherwise we need some help
				} else {
					var eventHandle, special, handlers;
					// copied from jquery core
					if ( !( eventHandle = elemData.handle ) ) {
						eventHandle = elemData.handle = function( e ) {
							// Discard the second event of a jQuery.event.trigger() and
							// when an event is called after a page has unloaded
							return typeof jQuery !== "undefined" && jQuery.event.triggered !== e.type ?  jQuery.event.dispatch.apply( elem, arguments ) : undefined;
						};
					}

					// figure out the special event handler
					special = jQuery.event.special[ type ] || {};

					// again, copied from jquery core
					// Init the event handler queue if we're the first
					if ( !( handlers = events[ type ] ) ) {
						handlers = events[ type ] = [];
						handlers.delegateCount = 0;

						// Only use addEventListener if the special events handler returns false
						if ( !special.setup || special.setup.call( elem, data, namespaces, eventHandle ) === false ) {
							if ( elem.addEventListener ) {
								elem.addEventListener( type, eventHandle );
							}
						}
					}
					console.log( 'inside', events );

					// prepend the envent handler
					handlers.unshift( f );

					// reintegrate the event list
					$._data( elem, 'events', events );
				}
			} );
		} );
	};

	// copy of form submission check from advanced-custom-fields/js/input.js, but specifically for woocommerce forms
	// removed delegation, so that it can run earlier in jquery bubble stack
	// prepending it to the jquery event list, so that it beats out any WooCommerce core js that might already be attached (since load order of js can vary from site to site)
	$( function() {
		$( '.woocommerce form' ).prependOn( 'submit', function( e ) {
			// If disabled, bail early on the validation check
			if ( acf.validation.disabled )
				return true;
			
			// do validation
			acf.validation.run();
				
				
			if ( ! acf.validation.status ) {
				// store the form for later access
				var $form = $(this);
				
				// show message
				$form.siblings( '#message' ).remove();
				$form.before( '<div id="message" class="error"><p>' + acf.l10n.validation.error + '</p></div>' );

				// REMOVED: the submitdiv manipulation, cause it is irrelevant
				
				// prevent submission!
				e.stopImmediatePropagation();
				return false;
			}
			
			// remove hidden postboxes
			// + this will stop them from being posted to save
			$( '.acf_postbox.acf-hidden' ).remove();

			// submit the form
			return true;
		} );
	} );
} )( jQuery );
