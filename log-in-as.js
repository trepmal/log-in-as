(function($){

	var wp = window.wp,
		$loginform = $('#loginform');

	buttonClick = function( event ) {
		event.preventDefault();

		var $logInAs = $('#log-in-as');

		// move the 'remember me' checkbox and submit button elements
		// above our user selection div so they can toggle together
		$forgetmenot = $('p.forgetmenot').detach()
		$logInAs.before( $forgetmenot );

		$submit      = $('p.submit').detach()
		$logInAs.before( $submit );

		// add class padding class for compat with standard login form
		$logInAs.toggleClass('pad');

		// toggle the standard form
		$loginform.find('p').slideToggle();
	}

	userClick = function( event ) {
		event.preventDefault();

		var $a = $(this);

		// dim form so user know *something* is happening
		$('body').css('opacity', '0.3');

		wp.ajax.send( 'log_in_as', {
			data: {
				user_id : $a.attr('data-user-id'),
				interim : $('input[name="interim-login"]').length
			},
			success: userClickSuccess,
			error: userClickError
		} );
	}

	userClick2 = function( event ) {
		event.preventDefault();

		var $a = $(this);

		$('body').css('opacity', '0.3');

		wp.ajax.send( 'log_out_and_in_as', {
			data: {
				user_id : $a.attr('data-user-id')
			},
			success: userClickSuccess,
			error: userClickError
		} );
	}

	userClick3 = function( event ) {
		event.preventDefault();

		var $a = $(this);

		$('body').css('opacity', '0.3');

		wp.ajax.send( 'switch_back', {
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
		$('body').css('opacity', '1');

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

	$('#log-in-as button.button').on('click', buttonClick );
	$('#log-in-as h4').on('click', roleHeadingClick );
	$('.log-in-as-user').on( 'click', userClick );
	$('.log-out-and-in-as-user').on( 'click', userClick2 );
	$('.switch-back').on( 'click', userClick3 );

})( jQuery );
