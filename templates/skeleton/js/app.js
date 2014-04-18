$(document).ready(function($)
{
	if($('.sticky-navbar').length) {
		var $window = $(window),
			$navbar = $('.navigation'),
			$inventory = $('.inventory'),
			navbarHeight = $navbar.outerHeight(),
			navbarPos = $navbar.position();

		$window.scroll(function () {
			if($window.scrollTop() >= (navbarPos.top + navbarHeight)) {
				$navbar.addClass('navigation-sticky');
				$inventory.css('margin-bottom', navbarHeight);
			} else {
				$navbar.removeClass('navigation-sticky');
				$inventory.removeAttr('style');
			}
		});	
	}

	$('.nav-toggle').on('click', function(e)
	{
		e.preventDefault();
	});

	$('.m_login .dropdown-menu, .m_login .dropdown-menu input, .m_login .dropdown-menu label, .m_login .dropdown-menu a').click(function(e)
	{
		e.stopPropagation();
	});
});