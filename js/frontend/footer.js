$(function () {
    $('#js-modal-searches').on('click', '.js-delete-search', function (e) {
        e.preventDefault();

        var $this = $(this),
            data = $this.data(),
            id = data.id;

        intelli.post(intelli.config.url + 'search.json', {action: 'delete', id: id}, function (data) {
            if (Boolean(data.result)) {
                var $wrap = $this.closest('.modal-body'),
                    itemsCount = $('tr', $wrap).length;
                $this.closest('tr').remove();
                if (itemsCount <= 1) {
                    $('table', $wrap).remove();
                    $wrap.append('<p>' + _t('no_items') + '</p>');
                }

                intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});
            }
            else {
                intelli.notifFloatBox({msg: data.message, type: 'error', autohide: true});
            }
        });
    });

    if ($('#error').length > 0) {
        $('html, body').animate({scrollTop: $('.page-header').offset().top});
    }

    $('.js-print-page').on('click', function (e) {
        e.preventDefault();

        window.print();
    });

    $('.dropdown-menu a', '#js-currencies-list').on('click', function(e) {
        e.preventDefault();

        intelli.post(intelli.config.url + 'actions.json', {action: 'set-currency', code: $(this).data('code')}, function(response) {
            if (!response.error) {
                window.location.reload(true);
            }
        })
    })

    $('body').on('click', '.js-favorites', function (e) {
        e.preventDefault();

        var $this = $(this);
        var id = $this.data('id'),
            item = $this.data('item'),
            action = $this.data('action'),
            guests = $this.data('guests'),
            textAdd = $this.data('text-add'),
            textDelete = $this.data('text-delete');

        $.ajax(
            {
                url: intelli.config.baseurl + 'favorites/read.json',
                type: 'get',
                data: {item: item, item_id: id, action: action},
                success: function (data) {
                    if (!data.error) {
                        intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});
                        if ('add' == action) {
                            $this.html(textDelete);
                            $this.data('action', 'delete');
                        }
                        else {
                            $this.html(textAdd);
                            $this.data('action', 'add');
                        }
                    }
                    else {
                        intelli.notifFloatBox({msg: data.message, type: 'error', autohide: true});
                        window.location.href = intelli.config.url + 'login/';
                    }
                }
            });
    });

    if ('object' == typeof $.tabs) {
        $('#ia-tab-container').tabs();
    }

    // hide tab if content is empty
    $('.tab-pane').each(function () {
        if ($.trim($(this).html()) == '') {
            var tabId = '#';
            tabId += $(this).attr('id');
            $(this).remove();
            $('a[href=' + tabId + ']').parent('li').remove();
        }
    });

    $('.tabbable').each(function () {
        var $tabs = $(this).children('.nav-tabs');
        if (!$tabs.children('li.active').length) {
            $tabs.children('li').length
                ? $('a[href*="#"]:first', $tabs).tab('show')
                : $(this).remove();
        }
    });

    $('input[placeholder]').each(function () {
        inputPlaceholder(this);
    });

    $('.search-text').focus(function () {
        $(this).parent().addClass('focused');
    }).focusout(function () {
        $(this).parent().removeClass('focused');
    });

    if ('function' === typeof $.fn.numeric) {
        $('.js-filter-numeric').numeric();
    }

    if ($().datetimepicker) {
        $('.js-datepicker').datetimepicker(
            {
                format: 'YYYY-MM-DD HH:mm:ss',
                locale: intelli.getLocale(),
                icons: {
                    time: 'fa fa-clock-o',
                    date: 'fa fa-calendar',
                    up: 'fa fa-chevron-up',
                    down: 'fa fa-chevron-down',
                    previous: 'fa fa-chevron-left',
                    next: 'fa fa-chevron-right',
                    today: 'fa fa-checkmark',
                    clear: 'fa fa-remove',
                    close: 'fa fa-remove-sign'
                }
            });

        $('.js-datepicker-toggle').on('click', function (e) {
            e.preventDefault();

            $(this).prev().datetimepicker('show');
        });
    }

    // update picture titles
    if ($.fn.editable) {
        var $pictureTitles = $('.js-edit-picture-title');
        $.fn.editableform.buttons =
            '<button type="submit" class="btn btn-primary btn-sm editable-submit"><span class="fa fa-check"></span></button>' +
            '<button type="button" class="btn btn-default btn-sm editable-cancel"><span class="fa fa-times"></span></button>';

        if ($pictureTitles.length) {
            $pictureTitles.editable({
                url: intelli.config.url + 'actions.json',
                type: 'text',
                params: function (params) {
                    var $self = $(this);

                    params.action = 'edit-picture-title';
                    params.field = $self.data('field');
                    params.item = $self.data('item');
                    params.itemid = $self.data('item-id');
                    params.path = $self.data('picture-path');

                    return params;
                },
                success: function (response, newValue) {
                    var $self = $(this),
                        success = ('boolean' == typeof response.error && !response.error);

                    intelli.notifFloatBox({
                        msg: success ? _t('saved') : response.message,
                        type: success ? 'success' : 'error',
                        autohide: true
                    });

                    if (success) {
                        $self.closest('.gallery').find('input[name*="title"]').val(newValue)
                    }
                }
            });
        }
    }

    // delete picture
    $('.js-delete-file').on('click', function (e) {
        e.preventDefault();

        var $this = $(this);

        var file = $this.data('file');
        var id = $this.data('item-id');
        var item = $this.data('item');
        var field = $this.data('field');

        intelli.confirm(_t('sure_rm_file'), '', function (result) {
            if (result) {
                intelli.post(intelli.config.url + 'actions/read.json', {
                    action: 'delete-file',
                    item: item,
                    field: field,
                    file: file,
                    itemid: id
                }, function (data) {
                    intelli.notifFloatBox({msg: data.message, type: data.error ? 'error' : 'success', autohide: true});

                    if (!data.error) {
                        $this.closest('.fieldzone').find('.js-file-name').val('');
                        $this.closest('.thumbnail').remove();
                        $this.closest('.upload-items__item').remove();

                        var counter = $('#' + field);
                        if (counter.val() == 0) {
                            $('#wrap_' + field).show();
                        }
                    }
                });
            }
        });
    });

    $('.upload-items').each(function () {
        intelli.sortable($(this).attr('id'), {handle: '.drag-handle'});
    });

    // add/delete pictures fields
    function detectFilename() {
        $('.js-files :file').on('change', function () {
            var $input = $(this),
                $parent = $input.closest('.js-files');
            label = $input.val().replace(/\\/g, '/').replace(/.*\//, '');

            $parent.find('.js-file-name').val(label);
        });
    }

    detectFilename();

    var addImgItem = function (btn) {
        var thisParent = $(btn).closest('.upload-list__item');
        var clone = thisParent.clone(true);
        var name = $('input[type="file"]', thisParent).attr('name').replace('[]', '');
        var num = parseInt($('#' + name).val());

        if (num > 1) {
            $('input', clone).val('');
            thisParent.after(clone);
            $('#' + name).val(num - 1);
        }
        else {
            intelli.notifFloatBox({msg: intelli.lang.no_more_files, autohide: true, pause: 2500});
        }

        detectFilename();
    };

    var removeImgItem = function (btn) {
        var thisParent = $(btn).closest('.upload-list__item');
        var name = $('input[type="file"]', thisParent).attr('name').replace('[]', '');
        var num = parseInt($('#' + name).val());

        if (thisParent.prev().hasClass('upload-list__item') || thisParent.next().hasClass('upload-list__item')) {
            thisParent.remove();
            $('#' + name).val(num + 1);
        }
    };

    $('.js-add-img').on('click', function (e) {
        e.preventDefault();
        addImgItem(this);
    });

    $('.js-remove-img').on('click', function (e) {
        e.preventDefault();
        removeImgItem(this);
    });
});

function inputPlaceholder(input, color) {
    if (!input) return null;

    // Do nothing if placeholder supported by browser (Webkit, Firefox 3.7)
    if (input.placeholder && 'placeholder' in document.createElement(input.tagName)) return input;

    color = color || '#AAA';
    var default_color = input.style.color;
    var default_type = input.type;
    var placeholder = input.getAttribute('placeholder');

    if (input.value === '' || input.value == placeholder) {
        input.value = placeholder;
        input.style.color = color;
        if (default_type == 'password') input.type = 'text';
    }

    var add_event = /*@cc_on'attachEvent'||@*/'addEventListener';

    input[add_event](/*@cc_on'on'+@*/'focus', function () {
        input.style.color = default_color;
        if (input.value == placeholder) {
            input.value = '';
            if (default_type == 'password') input.type = 'password';
        }
    }, false);

    input[add_event](/*@cc_on'on'+@*/'blur', function () {
        if (input.value === '') {
            input.value = placeholder;
            input.style.color = color;
            if (default_type == 'password') input.type = 'text';
        } else {
            input.style.color = default_color;
        }
    }, false);

    input.form && input.form[add_event](/*@cc_on'on'+@*/'submit', function () {
        if (input.value == placeholder) {
            input.value = '';
        }
    }, false);

    return input;
}