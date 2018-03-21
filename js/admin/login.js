$(function () {
    // special-effects for login page
    if ($('body').width() >= 768) {
        setTimeout(function () {
            $('.login-block').animate({'margin-top': '-260px', opacity: 1, specialEasing: 'ease-in'});
        }, 500);
    }

    // Forgot password functionality
    $('#js-forgot-dialog').on('click', function (e) {
        e.preventDefault();

        $('.login-body').slideUp('fast', function () {
            $('.js-login-body-forgot-password').slideDown('fast');
        });
    });

    $('#js-forgot-dialog-close').on('click', function (e) {
        e.preventDefault();

        $('.js-login-body-forgot-password').slideUp('fast', function () {
            $('.login-body').slideDown('fast');
        });
    });

    // Email validation
    $('#js-forgot-submit').on('click', function (e) {
        e.preventDefault();

        var form = $(this).parent();
        var alertBox = form.find('.alert');

        if (intelli.is_email($('#email').val())) {
            alertBox.fadeOut();
            $.get(intelli.config.url + 'registration.json', form.serialize(), function (response) {
                if ('boolean' === typeof response.result && response.result) {
                    alertBox.fadeOut().removeClass('alert-danger').addClass('alert-success').text(response.message);
                    alertBox.fadeIn();
                }
                else {
                    alertBox.fadeOut().removeClass('alert-success').addClass('alert-danger').text(response.message);
                    alertBox.fadeIn();
                }
            });
        }
        else {
            alertBox.addClass('alert-danger').fadeIn();
        }
    });
});