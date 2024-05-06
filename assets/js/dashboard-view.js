document.addEventListener( 'keydown', function ( event ) {
	if ( event.key === 'v' || event.key === 'V' ) {
		let vPressed = true;

		document.addEventListener( 'keydown', function ( event ) {
			if ( ( event.key === 'e' || event.key === 'E' ) && vPressed ) {
				const postIdMatch = window.location.href.match( /page=[a-zA-Z0-9_-]+-(\d+)/ );

				if ( !postIdMatch || !postIdMatch[ 1 ] ) {
					return;
				}

				window.open( `post.php?post=${ postIdMatch[ 1 ] }&action=edit`, '_blank' );
			}

			vPressed = false;
		} );
	}
} );
