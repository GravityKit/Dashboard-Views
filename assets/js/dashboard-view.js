( function () {
	const dashboardViews = {
		pressedKeys: [],
	};

	document.addEventListener( 'keydown', function ( event ) {
		dashboardViews.pressedKeys.push( event.keyCode );

		if ( dashboardViews.pressedKeys.length > 4 ) {
			dashboardViews.pressedKeys.shift();
		}

		switch ( dashboardViews.pressedKeys.join( ',' ) ) {
			case '18,16,86,69': // Alt(18) + Shift(16) + V(86) + E(69)
				const postIdMatch = window.location.href.match( /page=([a-zA-Z0-9_-]+)-(\d+)/ );

				dashboardViews.pressedKeys = [];

				if ( postIdMatch?.[ 2 ] ) {
					window.open( `post.php?post=${ postIdMatch[ 2 ] }&action=edit`, '_blank' );
				}

				break;
		}
	} );
} )();
