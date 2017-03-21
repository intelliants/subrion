$(function () {
    var $obj = $('#disable_fields');

    $obj.on('click', function () {
        if ($(this).is(':checked')) {
            $('#pass_fieldset').hide();
            $('#pass1, #pass2').prop('disabled', true);
        }
        else {
            $('#pass_fieldset').show();
            $('#pass1, #pass2').prop('disabled', false);
        }
    });

    if ($obj.is(':checked')) {
        $('#pass_fieldset').hide();
    }
});