(function($){

	var wp = window.wp,
		$loginform = $('#loginform');

	userClick = function( event ) {
		event.preventDefault();

		var $a = $(this);

		$loginform.css('opacity', '0.3');

		wp.ajax.send( 'log_in_as', {
			data: {
				user_id : $a.attr('data-user-id')
			},
			success: userClickSuccess,
			error: userClickError
		} );
	}

	userClickSuccess = function( data ) {
		window.location = data;
	}

	userClickError = function ( data ) {
		console.log( data );
		$loginform.css('opacity', '1');
		$error = $('#login_error')
		if ( $error.length < 1 ) {
			$error_wrap = $('<div id="login_error"/>');
			$loginform.before( $error_wrap );
			$error = $('#login_error');
		}
		$error.html( data );
	}

	roleHeadingClick = function() {
		$(this).next('.log-in-as-group').slideToggle('hidden');
	}

	$('.log-in-as-user').on( 'click', userClick );
	$('h4').on('click', roleHeadingClick );

})( jQuery );
