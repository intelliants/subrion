$(function() {
	var $panelMenu = $('#panel-left'),
		$panelSubMenu = $('#panel-center');

	enquire.register("screen and (max-width:768px)", {
		match : function() {
			$('#panel-left, #panel-center').wrapAll('<div class="m-wrp"/>');
		},
		unmatch : function() {
			$('#panel-left, #panel-center').unwrap();
		}
	});

	$('.m-header__toggle').click(function(e) {
		e.preventDefault();

		var $wrp = $('.m-wrp');

		$wrp.slideToggle('fast', function() {
			$wrp.toggleClass('is-opened');
		});
	})
});