(function($){

	var wp = window.wp,
		$loginform = $('#loginform');

	buttonClick = function( event ) {
		event.preventDefault();

		// $('#log-in-as').slideToggle();
		$forgetmenot = $('p.forgetmenot').detach()
		$('#log-in-as').before( $forgetmenot );
		$submit = $('p.submit').detach()
		$('#log-in-as').before( $submit );
		$('#log-in-as').toggleClass('pad');
		$loginform.find('p').slideToggle();
	}

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

	$('button.button').on('click', buttonClick );
	$('#log-in-as h4').on('click', roleHeadingClick );
	$('.log-in-as-user').on( 'click', userClick );

})( jQuery );
