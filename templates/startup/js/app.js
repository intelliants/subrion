$(function()
{
	// Sticky navbar

	// if($('.sticky-navbar').length) {
	// 	var $window = $(window),
	// 		$navbar = $('.section--navigation'),
	// 		$inventory = $('.section--inventory'),
	// 		navbarHeight = $navbar.outerHeight(),
	// 		navbarPos = $navbar.position();

	// 	$window.scroll(function () {
	// 		if($window.scrollTop() >= (navbarPos.top)) {
	// 			$navbar.addClass('navigation-sticky');
	// 			$inventory.css('margin-bottom', navbarHeight);
	// 		} else {
	// 			$navbar.removeClass('navigation-sticky');
	// 			$inventory.removeAttr('style');
	// 		}
	// 	});	
	// }



	// Menu button on mobile devices

	$('.nav-toggle').on('click', function(e)
	{
		e.preventDefault();
	});



	// Back to top button.

	// var $backToTopBtn = $('<a href="#" id="backToTop"><i class="icon-chevron-up"></i></a>'),
	// 	scroll_timer,
	// 	displayed     = false,
	// 	$window       = $(window),
	// 	top           = $(document.body).children(0).position().top;
	
	// $('body').append($backToTopBtn);

	// $window.scroll(function () {
	// 	window.clearTimeout(scroll_timer);
		
	// 	scroll_timer = window.setTimeout(function () {
	// 		if($window.scrollTop() <= top)
	// 		{
	// 			displayed = false;
	// 			$backToTopBtn.fadeOut(500);
	// 		}
	// 		else if(displayed == false)
	// 		{
	// 			displayed = true;
	// 			$backToTopBtn.stop(true, true).show().click(function () { $backToTopBtn.fadeOut(500); });
	// 		}
	// 	}, 100);
	// });

	// $('body').on('click', '#backToTop', function (e) {
	// 	e.preventDefault();

	// 	$('html, body').animate({
	// 		scrollTop: 0
	// 	}, 'fast');
	// });
});