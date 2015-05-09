(function($){

	var wp = window.wp,
		$loginform = $('#loginform');

	buttonClick = function( event ) {
		event.preventDefault();

		// move the 'remember me' checkbox and submit button elements
		// above our user selection div so they can toggle together
		$forgetmenot = $('p.forgetmenot').detach()
		$('#log-in-as').before( $forgetmenot );
		$submit = $('p.submit').detach()
		$('#log-in-as').before( $submit );

		// add class padding class for compat with standard login form
		$('#log-in-as').toggleClass('pad');

		// toggle the standard form
		$loginform.find('p').slideToggle();
	}

	userClick = function( event ) {
		event.preventDefault();

		var $a = $(this);

		// dim form so user know *something* is happening
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
		// all good, go to Dashboard
		window.location = data;
	}

	userClickError = function ( data ) {
		// restore opacity to indicate we tried
		$loginform.css('opacity', '1');

		// output error, creating container if needed
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

	$('button.button').on('click', buttonClick );
	$('#log-in-as h4').on('click', roleHeadingClick );
	$('.log-in-as-user').on( 'click', userClick );

})( jQuery );
