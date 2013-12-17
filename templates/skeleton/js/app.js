$(document).ready(function($)
{
	$('.nav-toggle').on('click', function(e)
	{
		e.preventDefault();
	});

	$('.m_login .dropdown-menu, .m_login .dropdown-menu input, .m_login .dropdown-menu label, .m_login .dropdown-menu a').click(function(e)
	{
		e.stopPropagation();
	});
});