$(function () {
    var category_id = $("#category_id").val();

    $("input[name='confirm_answer']").each(function () {
        $(this).click(function () {
            if ('back' == $(this).attr("id")) {
                history.back(1);
            }
            else {
                intelli.cookie.write('confirm_' + category_id, '1');

                window.location = window.location.href;
            }
        });
    });
});