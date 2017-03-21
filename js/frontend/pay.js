$(function () {
    $('input[name="source"]').click(function () {
        if ('external' == this.value) {
            $('#gw_wrap').show();
        }
        else {
            $('#gw_wrap').hide();
        }
    }).click();

    $('input[name="source"]:checked').click();
});