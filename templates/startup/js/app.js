$(function()
{
	// Sticky navbar

	if($('.navigation--sticky').length) {
		var $window = $(window),
			$navbar = $('.navigation--sticky'),
			$inventory = $('.inventory'),
			navbarHeight = $navbar.outerHeight(),
			navbarPos = $navbar.position();

		$window.scroll(function () {
			if($window.scrollTop() >= (navbarPos.top)) {
				$navbar.addClass('is-sticky');
				$inventory.css('margin-bottom', navbarHeight);
			} else {
				$navbar.removeClass('is-sticky');
				$inventory.removeAttr('style');
			}
		});	
	}



	// Menu button on mobile devices

	$('.nav-toggle').on('click', function (e) {
		e.preventDefault();
	});



	// Back to top button.

	var $backToTopBtn = $('<a href="#" id="backToTop"><i class="icon-chevron-up"></i></a>'),
		whereYouWantYourButtonToAppear = 200,
		$window = $(window);

	$('body').append($backToTopBtn);

	$window.scroll(function () {
		var position = $window.scrollTop();
		
		if(position > whereYouWantYourButtonToAppear) {
			$backToTopBtn.stop(true, true).fadeIn();
		} else {
			$backToTopBtn.stop(true, true).fadeOut();
		}
	});

	$('body').on('click', '#backToTop', function (e) {
		e.preventDefault();

		$('html, body').animate({
			scrollTop: 0
		}, 'fast');

		$(this).stop(true, true).fadeOut();
	});
});