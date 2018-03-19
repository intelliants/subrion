$(function () {
    setTimeout(function () {
        $('#js-ajax-loader').fadeOut();
        intelli.cookie.write('loader', 'loaded');
    }, 2000);

    // panel toggle
    $('.panel-toggle').on('click', function (e) {
        e.preventDefault();

        var $o = $('#panel-center'),
            $this = $(this);

        window.dispatchEvent(new Event('resize'));

        if (!$o.hasClass('is-hidden')) {
            $o.addClass('is-hidden');
            $this.find('i').removeClass('fa-angle-left').addClass('fa-angle-right');
            intelli.cookie.write('panelHidden', '1');
        }
        else {
            $o.removeClass('is-hidden');
            $this.find('i').removeClass('fa-angle-right').addClass('fa-angle-left');
            intelli.cookie.write('panelHidden', '0');
        }
    });

    $('#user-logout').on('click', function () {
        intelli.cookie.write('loader', 'notloaded');
    });

    // main nav
    $('.nav-main > li > a').on('click', function (e) {
        if (!$(this).hasClass('dashboard')) {
            e.preventDefault();

            var toggler = $(this).data('toggle'),
                $panel = $('#panel-center');

            $(this).parent().addClass('active').siblings().removeClass('active');
            $('#' + toggler).addClass('active').siblings().removeClass('active');

            if ($panel.hasClass('is-hidden')) {
                $panel.removeClass('is-hidden');
            }

            if ($(window).scrollTop() > 0) {
                $('html, body').animate({scrollTop: 0}, 'fast');
            }
        }
    });

    // minmax
    var widgetsState = JSON.parse(intelli.cookie.read('widgetsState'));
    if (typeof widgetsState == 'undefined' || widgetsState == null) {
        widgetsState = {};
    }

    $('.widget').each(function () {
        if ('collapsed' == widgetsState[$(this).attr('id')]) {
            $(this).addClass('collapsed');
        }
    });

    $('.widget-toggle').on('click', function (e) {
        e.preventDefault();

        var $obj = $(this).closest('.widget');
        var objContent = $obj.find('.widget-content');
        var objId = $obj.attr('id');

        if (!$obj.hasClass('collapsed')) {
            objContent.slideUp('fast', function () {
                $obj.addClass('collapsed');
            });

            widgetsState[objId] = 'collapsed';
        }
        else {
            objContent.slideDown('fast', function () {
                $obj.removeClass('collapsed');
                if (objContent.hasClass('mCustomScrollbar')) {
                    objContent.mCustomScrollbar('update');
                }
            });

            widgetsState[objId] = '';
        }

        intelli.cookie.write('widgetsState', JSON.stringify(widgetsState));
    });

    // Tree toggle
    $('.js-categories-toggle').on('click', function (e) {
        e.preventDefault();

        var toggleWhat = $(this).data('toggle');

        $(toggleWhat).toggle();
    });

    if ('function' == typeof $.fn.numeric) {
        $('.js-filter-numeric').numeric();
    }

    $('textarea.js-code-editor').each(function () {
        editAreaLoader.init(
            {
                id: $(this).attr('id'),
                display: 'later',
                min_height: 200,
                syntax: 'php',
                start_highlight: true,
                toolbar: 'undo, redo'
            });
    });

    $('textarea.js-wysiwyg').each(function () {
        intelli.ckeditor($(this).attr('id'), {height: '200px'});
    });

    $('.js-edit-lang-group').on('click', function () {
        var $parent = $($(this).data('group')),
            $group = $parent.find('.translate-group__langs');

        $parent.toggleClass('is-opened').hasClass('is-opened') ? $group.slideDown('fast') : $group.slideUp('fast');
    });

    $('.js-copy-lang-group').on('click', function () {
        var $this = $(this);

        if ($this.data('wysiwygEnabled')) {
            var name = $this.data('name');
                value = CKEDITOR.instances[name].getData();

            for (var iso in intelli.languages) {
                if (iso !== intelli.config.lang) {
                    var ckInstance = CKEDITOR.instances[name + '-' + iso];

                    ckInstance.setData(value);
                    ckInstance.updateElement();
                }
            }
        } else {
            var $parent = $($this.data('group')),
                value = $parent.find('input:first, textarea:first').val();
            $parent.find('.translate-group__langs input, .translate-group__langs textarea').val(value);
        }
    });

    // switching
    $('.js-input-switch').on('switch-change', function (e, data) {
        $('input', this).val(data.value == true ? 1 : 0);
    });


    // file upload
    var fileUpload = function (elem) {
        var $parent = $(elem).closest('.file-upload');

        $('input[type="file"]', $parent)
            .trigger('click')
            .on('change', function () {
                var filename = $(this).val();
                var lastIndex = filename.lastIndexOf("\\");

                if (lastIndex >= 0) {
                    filename = filename.substring(lastIndex + 1);
                }

                $('input[type="text"]:not(.file-title)', $parent).val(filename);
            });
    };

    var addFileUploadField = function (elem) {
        var clone = $(elem).closest('.file-upload').clone();
        var counterObj = $('#' + $('input[type="file"]', clone).attr('name').replace('[]', ''));
        var counterNum = parseInt(counterObj.val());

        if (1 < counterNum) {
            $('input[type="file"], input[type="text"]', clone).val('');
            $(elem).closest('.file-upload').after(clone);
            counterObj.val(counterNum - 1);
        }
        else {
            intelli.notifFloatBox({msg: intelli.admin.lang.no_more_files, type: 'error', autohide: true, pause: 2500});
        }
    };

    var removeFileUploadField = function (elem) {
        var $parent = $(elem).closest('.file-upload');
        var counterObj = $('#' + $('input[type="file"]', $parent).attr('name').replace('[]', ''));
        var counterNum = parseInt(counterObj.val());

        if ($parent.prev().hasClass('file-upload') || $parent.next().hasClass('file-upload')) {
            $parent.remove();
            counterObj.val(counterNum + 1);
        }
    };

    // activating buttons
    $('.upload-group')
        .on('click', '.js-file-browse', function (e) {
            e.preventDefault();
            fileUpload(this);
        })
        .on('click', '.js-file-add', function (e) {
            e.preventDefault();
            addFileUploadField(this);
        })
        .on('click', '.js-file-remove', function (e) {
            e.preventDefault();
            removeFileUploadField(this);
        });

    // tooltips
    $('.js-tooltip').tooltip({container: 'body'}).on('click', function (e) {
        e.preventDefault();
    });

    $('#api_token').passField(
        {
            showWarn: false,
            showTip: false,
            pattern: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ab' // 64 symbols
        });

    // add password generator
    $('.js-input-password').passField({showWarn: false, showTip: false});

    if ($('.right-column .box').length > 1) {
        var items = [];
        var name, rand;
        $('.right-column .box').each(function () {
            rand = 'box' + Math.random();
            if ($(this).attr('id')) {
                name = $(this).attr('id');
                $(this).attr('id', rand);
            }
            else {
                name = rand;
            }
            $(this).find('.box-content').attr('id', name).show();
            items.push(
                {
                    contentEl: name,
                    title: '<div class="tab-caption">' + $(this).find('.box-caption').text() + '</div>'
                });
        }).hide();


        $('.right-column .box:first').before('<div id="ext_tabs"></div>');
        $('.x-tab-panel > div').removeClass('x-tab-panel-header');
    }

    if ($().datetimepicker) {
        $('.js-datepicker').datetimepicker(
            {
                format: 'YYYY-MM-DD HH:mm:ss',
                locale: intelli.getLocale(),
                icons: {
                    time: 'i-clock',
                    date: 'i-calendar',
                    up: 'i-chevron-up',
                    down: 'i-chevron-down',
                    previous: 'i-chevron-left',
                    next: 'i-chevron-right',
                    today: 'i-checkmark',
                    clear: 'i-remove',
                    close: 'i-remove-sign'
                }
            });

        $('.js-datepicker-toggle').on('click', function (e) {
            e.preventDefault();

            $(this).prev().datetimepicker('show');
        });
    }

    $('.js-iconpicker').iconpicker();

    /* header-menu show/hide START */
    if ($('#alert').length) {
        $('#alert').show();
    }
    if ($('#success')) {
        var text = [];
        $('#success .inner li').each(function () {
            text.push($(this).html());
        });
        $('#success').html('');
        if (text.length > 0) {
            intelli.admin.notifBox({msg: text, type: 'notification', autohide: true});
        }
    }
    if ($('#notification').length) {
        $('#notification').show();
    }

    /* feedback form START */
    var $feedbackForm = $('form', '#feedback-modal');
    $('select[name="subject"]', $feedbackForm).on('change', function () {
        var $option = $('option:selected', this);
        if ($option.val() != '') {
            $('#feedback_subject_label').html('<i class="i-' + $option.data('icon') + '"></i> ' + _t('subject'));
        }
    });

    $('input[name="fullname"], input[name="email"]', $feedbackForm).focus(function () {
        var $this = $(this);
        if ($this.data('def') == $this.val()) {
            $this.val('');
        }
    }).blur(function () {
        var $this = $(this);
        if ($this.val() == '') {
            $this.val($this.data('def'));
        }
    });

    $feedbackForm.on('submit', function () {
        var $subject = $('[name="subject"]', this);
        if ('' != $('[name="body"]', this).val() && '' != $('option:selected', $subject).val()) {
            $.ajax(
                {
                    data: $(this).serialize(),
                    success: function (response) {
                        $('#feedback-modal').modal('hide');
                        intelli.notifFloatBox({
                            msg: response.message,
                            type: response.result ? 'success' : 'error',
                            autohide: true
                        });
                    },
                    type: 'POST',
                    url: intelli.config.admin_url + '.json'
                });
        }
        else {
            intelli.notifFloatBox({msg: _t('body_incorrect'), type: 'error', autohide: true});
        }

        return false;
    });

    $('#clearFeedback').on('click', function () {
        $('[name="body"]').val('');
    });
    /* feedback form END */

    $('div.minmax').each(function () {
        $(this).on('click', function () {
            if ($(this).next('.box-content').css('display') == 'block') {
                $(this).next('.box-content').slideUp();
                Ext.util.Cookies.set(this.id, 0);
            }
            else {
                $(this).next('.box-content').slideDown();
                Ext.util.Cookies.set(this.id, 1);
            }
            $(this).toggleClass('white-close white-open');
        });
    });

    function getMousePosition(e) {
        return {x: e.clientX + document.documentElement.scrollLeft, y: e.clientY + document.documentElement.scrollTop};
    }

    // get substring count with limit
    $.fn.substrCount = function (needle) {
        var p;
        var h = this.text();
        var times = 0;
        while ((p = h.indexOf(needle)) != -1) {
            h = h.substr(p + needle.length);
            times++;
        }

        return times;
    };

    function stopPropagation(ev) {
        ev = ev || event;
        /* get IE event ( not passed ) */
        ev.stopPropagation ? ev.stopPropagation() : ev.cancelBubble = true;
    }

    textareaResizer = function () {
        $('textarea.resizable').each(function () {
            var obj = $(this);

            cl = obj.attr('class');
            if (cl && -1 != cl.indexOf('noresize')) {
                return false;
            }

            var content = obj.text();
            var Height = 75;
            if (content.length) {
                // IE - doesnt find \n I gave up I don't know why it is so ..
                // Firefox works just as it must work as well as Opera
                var times = obj.substrCount(navigator.userAgent.match(/msie/i) ? "\r" : "\n");
                if (times > 20) {
                    Height = 200;
                }
                else {
                    Height = 70 + 10 * times;
                }
            }

            obj.height(Height);

            var offset = null;

            $(this).wrap('<div class="resizable-textarea"></div>').after($('<div class="resizable-textarea2"></div>').bind("mousedown", dragBegins));


            var image = $('div.resizable-textarea2', $(this).parent())[0];
            image.style.marginRight = (image.offsetWidth - $(this)[0].offsetWidth) + 'px';

            function dragBegins(e) {
                offset = obj.height() - getMousePosition(e).y;
                if ($.browser.opera) {
                    offset -= 6;
                }
                $(document)
                    .bind('mousemove', doDrag)
                    .bind('mouseup', dragEnds);
                stopPropagation(e);
            }

            function doDrag(e) {
                obj.height(Math.max(15, offset + getMousePosition(e).y) + 'px');
                stopPropagation(e);
            }

            function dragEnds(e) {
                $(document).unbind();
            }
        });
    }();

    /*
     * Help tooltips
     */
    $('.tip-header').each(function () {
        var id = $(this).attr('id').replace('tip-header-', '');

        if ($('#tip-content-' + id).length > 0) {
            $(this).append('<span class="question" id="tip_' + id + '"><img src="' + intelli.config.admin_url + '/templates/' + intelli.config.admin_tmpl + '/img/icons/sp.gif" alt="" width="16" height="17" /></span>').find("span.question").each(function () {
                new Ext.ToolTip({target: this, dismissDelay: 0, contentEl: 'tip-content-' + id});
            });
        }
    });

    /*
     * Init AJAX notification box
     */
    $('.collapsed[rel]').on('click', function () {
        $($(this).attr('rel')).toggle();
    });

    /*
     * Resolving issues
     */

    if ($('.navbar-nav__notifications__alerts').length > 0) {
        // remove installer
        var $installerAlert = $('.alert-danger:contains("module.install.php")');
        if ($installerAlert.length > 0) {
            $installerAlert.on('click', '.b-resolve__btn', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var $this = $(this);

                if (!$this.hasClass('disabled')) {
                    $this.animate(
                        {
                            left: '10px',
                            opacity: 0
                        }, 150, function () {
                            $this.hide().prev().show(function () {
                                intelli.post(intelli.config.admin_url + '/actions/read.json', {action: 'remove-installer'}, function (response) {
                                    if (!response.error) {
                                        $this.prev().animate(
                                            {
                                                left: '10px',
                                                opacity: 0
                                            }, 150, function () {
                                                $this.prev().hide().prev().show();
                                            });

                                        $installerAlert
                                            .removeClass('alert-danger')
                                            .addClass('alert-info');

                                        setTimeout(function () {
                                            clearNotification($installerAlert);
                                        }, 2000);
                                    }
                                });
                            });
                        });
                }
            });

            var resolveBtnHtml = '<div class="b-resolve__wrapper">' +
                '<span href="#" class="b-resolve__btn b-resolve__btn--result" title="' + _t('notification_resolve--resolved') + '"><i class="i-checkmark"></i> ' + _t('notification_resolve--resolved') + '</span>' +
                '<span href="#" class="b-resolve__btn b-resolve__btn--progress" title="' + _t('notification_resolve--working') + '"><i class="i-spinner"></i> ' + _t('notification_resolve--working') + '</span>' +
                '<a href="#" class="b-resolve__btn" title="' + _t('notification_resolve--resolve') + '"><i class="i-wrench"></i> ' + _t('notification_resolve--resolve') + '</a>' +
                '</div>';
            $installerAlert.addClass('b-resolve').append(resolveBtnHtml);
        }
    }

    // moving upload blocks up and down

    disableMoveButtons();

    $('.js-upload-moveup').click(function (e) {
        e.preventDefault();

        var $this = $(this),
            $parent = $this.closest('.uploads-list-item');

        $parent.insertBefore($parent.prev());

        disableMoveButtons();
    });

    $('.js-upload-movedown').click(function (e) {
        e.preventDefault();

        var $this = $(this),
            $parent = $this.closest('.uploads-list-item');

        $parent.insertAfter($parent.next());

        disableMoveButtons();
    });

    if (typeof Dropzone !== 'undefined') {
        Dropzone.autoDiscover = false;
    }

    $('.js-dropzone').each(function () {
        var $dropzone = $(this);
        var fieldName = $dropzone.data('field');
        var itemName = $dropzone.data('item');
        var itemId = $dropzone.data('item_id');
        var submitButtonText = $dropzone.data('submit_btn_text');
        var values = $dropzone.data('values');

        var dropZone = new Dropzone('#' + $dropzone.attr('id'),
            {
                url: intelli.config.admin_url + '/actions/read.json',
                addRemoveLinks: true,
                acceptedFiles: 'image/*',
                parallelUploads: 20,
                maxFiles: $dropzone.data('max_num'),
                dictRemoveFile: '',
                dictMaxFilesExceeded: _t('no_more_files'),
                dictDefaultMessage: _t('drop_files_here'),
                dictInvalidFileType: _t('field_tooltip_members_avatar'),
                dictCancelUpload: '',
                dictCancelUploadConfirmation: _t('cancel_upload_confirmation'),
                init: function () {
                    var dropZone = this,
                        error = false,
                        errorMessage = '';

                    this.on('success', function (file, response) {
                        if (response.error) {
                            return false;
                        }

                        var $preview = $(file.previewElement),
                            htmlInputs =
                                '<input type="hidden" name="' + fieldName + '_dropzone_paths[]" value="' + response.path + '">'
                                + '<input type="hidden" name="' + fieldName + '_dropzone_files[]" value="' + response.file + '">'
                                + '<input type="hidden" name="' + fieldName + '_dropzone_sizes[]" value="' + response.size + '">',
                            htmlFancybox = '<a class="dz-zoom" rel="ia_lightbox" href="' + intelli.config.clear_url + 'uploads/' + response.path + response.imagetype + '/' + response.file + '"><span class="fa fa-search-plus"></span></a>';

                        $preview.append(htmlInputs).find('.dz-details').append(htmlFancybox);
                        $('[data-dz-name]', $preview).text(response.file);
                    });

                    var $submit = $('.js-btn-submit');
                    this.on('sending', function (file, xhr, formData) {
                        $submit
                            .attr('disabled', true)
                            .html('<span class="fa fa-refresh fa-spin fa-fw"></span><span class="sr-only">' + _t('uploading_please_wait') + '</span> ' + _t('uploading_please_wait'));
                        formData.append(intelli.securityTokenKey, intelli.securityToken);
                        formData.append('action', 'dropzone-upload-file');
                        formData.append('field', fieldName);
                        formData.append('item', itemName);
                    });

                    this.on('error', function (file) {
                        if ('canceled' !== file.status) {
                            error = true;
                            errorMessage = 'error';
                        }
                    });

                    this.on('maxfilesexceeded', function (file) {
                        error = true;
                        errorMessage = 'no_more_files';

                        $(file.previewElement).remove();
                    });

                    this.on('removedfile', function (file) {
                        if ('undefined' === typeof file.status || 'success' === file.status) {
                            var params = {
                                action: 'dropzone-delete-file',
                                item: itemName,
                                field: fieldName,
                                path: $('input[type="hidden"]:first', file.previewElement).val(),
                                file: $('[data-dz-name]', file.previewElement).text()
                            };

                            if (typeof existingFiles !== 'undefined' && -1 !== existingFiles.indexOf(file.name)) {
                                dropZone.options.maxFiles = dropZone.options.maxFiles + 1;

                                params.action = 'delete-file';
                                params.itemid = itemId;
                            }

                            intelli.post(intelli.config.admin_url + '/actions/read.json', params).done(function(response) {
                                intelli.notifFloatBox({
                                    msg: response.message,
                                    type: response.error ? 'error' : 'success',
                                    autohide: true
                                });
                            });
                        }
                    });

                    this.on('queuecomplete', function() {
                        if (error) {
                            message = errorMessage;
                            error = false;
                            errorMessage = '';
                            intelli.notifFloatBox({msg: _t(message), type: 'error', autohide: true});
                        }
                        $submit.attr('disabled', false).html(_t(submitButtonText));
                    });
                }
            });

        if ('object' === typeof values && values) {
            var imageTypes = {
                primary: $dropzone.data('imagetype-primary'),
                thumbnail: $dropzone.data('imagetype-thumbnail')
            };
            var existingFiles = [], mock;

            values.forEach(function(entry) {
                mock = {name: entry.file, size: entry.size};

                dropZone.emit('addedfile', mock);
                dropZone.createThumbnailFromUrl(mock, intelli.config.clear_url + 'uploads/' + entry.path + imageTypes.thumbnail + '/' + entry.file);

                var htmlInputs =
                        '<input type="hidden" name="' + fieldName + '_dropzone_paths[]" value="' + entry.path + '">'
                        + '<input type="hidden" name="' + fieldName + '_dropzone_files[]" value="' + entry.file + '">'
                        + '<input type="hidden" name="' + fieldName + '_dropzone_sizes[]" value="' + entry.size + '">',
                    htmlFancybox = '<a class="dz-zoom" rel="ia_lightbox" href="' + intelli.config.clear_url + 'uploads/' + entry.path + imageTypes.primary + '/' + entry.file + '"><span class="fa fa-search-plus"></span></a>';

                $(mock.previewElement).append(htmlInputs).find('.dz-details').append(htmlFancybox);

                dropZone.emit('complete', mock);
                existingFiles.push(entry.file);
            });

            dropZone.options.maxFiles = dropZone.options.maxFiles - existingFiles.length;
        }

        intelli.sortable($dropzone.attr('id'), {handle: '.dz-image-preview'});
    });

    $('.js-cmd-delete-file').on('click', function (e) {
        e.preventDefault();
        var data = $(this).data();
        intelli.admin.removeFile(data.file, this, data.item, data.field, data.id);
    });
});

function clearNotification(el) {
    var $nLabel = $('.navbar-nav__notifications > a .label-info'),
        $nLabelCount = parseInt($nLabel.text()),
        $nBlock = $('.navbar-nav__notifications__alerts');

    if (0 != $nLabelCount) {
        $nLabel.text($nLabelCount - 1);
    }

    if ($('.alert', $nBlock).length >= 2) {
        el.animate(
            {
                top: '-10px',
                opacity: 0
            }, 150, function () {
                el.hide();
            })
    }
    else {
        el.closest('.dropdown').find('.dropdown-toggle').dropdown('toggle');
    }
}

function disableMoveButtons() {
    $('.uploads-list-item')
        .find('.js-upload-moveup, .js-upload-movedown')
        .prop('disabled', false);

    $('.uploads-list-item:first-child')
        .find('.js-upload-moveup')
        .prop('disabled', true);

    $('.uploads-list-item:last-child')
        .find('.js-upload-movedown')
        .prop('disabled', true);
}