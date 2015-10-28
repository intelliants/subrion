$(function() {

	// toggle tooltips
	$('[data-toggle="tooltip"]').tooltip({
		container: 'body'
	});
	
	// back to top button
	$('.js-back-to-top').on('click', function(e){
		e.preventDefault();

		$('html, body').animate({
			scrollTop: 0
		}, 'fast');
	})
});