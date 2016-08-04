$(function() {

	// toggle tooltips
	$('[data-toggle="tooltip"]').tooltip({
		container: 'body'
	});

	// sticky navbar
	if ($('.navbar-sticky').length) {
		var stickyToggle = function(sticky, stickyWrapper, scrollElement) {
			var stickyHeight = sticky.outerHeight();
			var stickyTop = stickyWrapper.offset().top;

			if (scrollElement.scrollTop() >= stickyTop) {
				stickyWrapper.height(stickyHeight);
				sticky.addClass("is-sticky");
			}
			else {
				sticky.removeClass("is-sticky");
				stickyWrapper.height('auto');
			}
		};

		// Find all data-toggle="sticky-onscroll" elements
		$('.navbar-sticky').each(function() {
			var sticky = $(this);
			var stickyWrapper = $('<div>').addClass('sticky-wrapper'); // insert hidden element to maintain actual top offset on page
			sticky.before(stickyWrapper);

			// Scroll & resize events
			$(window).on('scroll.sticky-onscroll resize.sticky-onscroll', function() {
				stickyToggle(sticky, stickyWrapper, $(this));
			});

			// On page load
			stickyToggle(sticky, stickyWrapper, $(window));
		});
	}

	// search toggle
	$('.js-search-navbar-toggle').click(function(e) {
		e.preventDefault();

		var $this = $(this);

		$('body, .navbar').append('<div class="search-navbar-backdrop"></div>');

		$this.next().addClass('is-visible');

		setTimeout(function() {
			$this.next().find('input').focus();
		}, 200);
	});

	$('body').on('click', '.search-navbar-backdrop', function(e) {
		$('.search-navbar .input-group').removeClass('is-visible');
		$('.search-navbar-backdrop').remove();
	});

	// back to top button
	var $backToTopBtn = $('.js-back-to-top');

	$(window).scroll(function() {
		if ($(this).scrollTop() > 200) {
			$backToTopBtn.addClass('on');
		} else {
			$backToTopBtn.removeClass('on');
		}
	});

	$backToTopBtn.on('click', function(e){
		$('html, body').animate({
			scrollTop: 0
		}, 'fast');
	})
});