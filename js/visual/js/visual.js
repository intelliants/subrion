$(function () {
    intelli.visualModeUrl = intelli.config.url + $('#js-config-admin-page').val() + '/visual-mode.json';

    $('body').addClass('visual-mode').css('overflow-x', 'visible');
    $('body > *:not(.sb-left, .vm-bar, .sb-slide, #debug-toggle, #debug)').wrapAll('<div id="sb-site"></div>');

    // name each position
    $('.groupWrapper').each(function () {
        var $this = $(this),
            id = $this.attr('id').split('Blocks');

        $this.prepend(
            '<div class="groupWrapper__header">' +
            '<div class="groupWrapper__header__title">' + id[0] + '</div>' +
            '<a class="js-config-open" data-type="positions" data-name="' + id[0] + '" href="#"><span class="v-icon v-icon--settings"></span></a>' +
            '</div>');
    }).sortable(
        {
            items: '> .box',
            connectWith: '.groupWrapper',
            handle: '.box-visual__actions__item--move',
            placeholder: 'box-visual__placeholder',
            revert: 300,
            delay: 100,
            opacity: 0.8,
            forcePlaceholderSize: true,
            start: function (e, ui) {
                $(ui.helper).addClass('dragging');
            },
            stop: function (e, ui) {
                $(ui.item).css({ width: '' }).removeClass('dragging');
                $('.groupWrapper').sortable('enable');

                var blocksPos = [];
                $('.groupWrapper > .box').each(function () {
                    var parentId = $(this).closest('.groupWrapper').attr('id');
                    blocksPos.push(parentId + '[]=' + $(this).attr('id'));
                });

                $.get(intelli.visualModeUrl + '?' + blocksPos.join('&'), function (response) {
                    intelli.notifFloatBox({
                        msg: response.message,
                        type: response.result ? 'success' : 'error',
                        autohide: true,
                        pause: 1500
                    });
                });
            }
        });

    // attach config boxes for blocks
    $('.groupWrapper > .box').each(function () {
        var $this = $(this),
            id = $this.attr('id').substring(6),
            moveBtn = ($this.parent('.groupWrapper').hasClass('groupWrapper--movable')) ? '<div class="box-visual__actions__item box-visual__actions__item--move"><span class="v-icon v-icon--arrows"></span></div>' : '',
            editBtn = (-1 !== ['html', 'plain'].indexOf($this.data('type'))) ? '<a class="js-block-inline-edit box-visual__actions__item box-visual__actions__item--edit" data-type="blocks" data-name="' + id + '" data-id="' + $this.data('block_id') + '" data-is_html="' + ($this.data('type') === 'html') + '" href="#" data-action="edit"><span class="v-icon v-icon--pencil"></span></a>' : '',
            hiddenClass = (1 == $this.attr('vm-hidden')) ? ' box-visual--hidden' : '';

        $this.addClass('box-visual ' + hiddenClass).append(
            '<div class="box-visual__actions">' +
            '<a class="js-config-open box-visual__actions__item box-visual__actions__item--settings" data-type="blocks" data-name="' + id + '" href="#"><span class="v-icon v-icon--settings"></span></a>' +
            moveBtn + editBtn +
            '</div>');
    });

    var vmBar = new $.slidebars({ siteClose: false });

    $('.js-config-open').on('click', function (e) {
        e.preventDefault();

        var $this = $(this);

        if (!vmBar.slidebars.active('left')) {
            openSlideBar(vmBar, $this.data('type'), $this.data('name'));
        }
    });

    $('.js-block-inline-edit').on('click', function (e) {
        e.preventDefault();

        var $this = $(this),
            $block = $this.closest('.box-visual__actions').prev(),
            blockId = $this.data('id'),
            isHtml = $this.data('is_html');

        switch ($this.data('action')) {
            case 'edit':
                $this.data('action', 'save')
                $this.find('.v-icon').removeClass('v-icon--pencil').addClass('v-icon--check-circle-o')
                $block.attr('contenteditable', true)

                var config = { startupFocus: true, floatSpaceDockedOffsetY: 35 };

                if (!isHtml) {
                    config['removeButtons'] = 'PasteFromWord,Paste,Bold,Italic,Underline,Strike,TextColor,Format,BGColor,Link,Image,Iframe,Unlink,Youtube,Embed'
                }

                CKEDITOR.once('instanceCreated', function (event) {
                    $block.data('ckeditor_instance_name', event.editor.name)
                })

                CKEDITOR.inline($block.get(0), config)

                break;

            case 'save':
                $this.data('action', 'edit')
                $this.find('.v-icon').removeClass('v-icon--check-circle-o').addClass('v-icon--pencil')
                var editor = CKEDITOR['instances'][$block.data('ckeditor_instance_name')]
                var data = isHtml ? editor.getData() : editor.element.getText()

                saveInlineEdit('block', blockId, data);

                $block.removeAttr('contenteditable')
                editor.destroy()
                break;
        }
    });

    $('.js-page-inline-edit').on('click', function (e) {
        e.preventDefault();

        var $this = $(this),
            pageName = $this.data('name'),
            pageId = 'page-content__' + pageName,
            $page = $('#' + pageId)

        switch ($this.data('action')) {
            case 'edit':
                $this.data('action', 'save')
                $this.find('.v-icon').removeClass('v-icon--pencil').addClass('v-icon--check-circle-o')
                $page.attr('contenteditable', true)

                var config = { startupFocus: true };

                CKEDITOR.inline(pageId, config)
                break;

            case 'save':
                $this.data('action', 'edit')
                $this.find('.v-icon').removeClass('v-icon--check-circle-o').addClass('v-icon--pencil')
                var editor = CKEDITOR['instances'][pageId]

                saveInlineEdit('page', pageName, editor.getData());

                $page.removeAttr('contenteditable')
                editor.destroy()
                break;
        }
    });

    $('.js-phrase-inline-edit').on('click', function (e) {
        e.preventDefault();

        var $this = $(this),
            $phrase = $this.closest('.box-visual').find('.box-visual__content'),
            key = $this.data('key'),
            phraseElementId = $phrase.attr('id'),
            isHtml = $this.data('is_html');

        switch ($this.data('action')) {
            case 'edit':
                $this.data('action', 'save')
                $this.find('.v-icon').removeClass('v-icon--pencil').addClass('v-icon--check-circle-o')
                $phrase.attr('contenteditable', true)

                var config = { startupFocus: true };

                if (!isHtml) {
                    config['removeButtons'] = 'PasteFromWord,Paste,Bold,Italic,Underline,Strike,TextColor,Format,BGColor,Link,Image,Iframe,Unlink,Youtube,Embed'
                }
                CKEDITOR.dtd.$editable.span = 1

                CKEDITOR.inline(phraseElementId, config)
                break;

            case 'save':
                $this.data('action', 'edit')
                $this.find('.v-icon').removeClass('v-icon--check-circle-o').addClass('v-icon--pencil')
                var editor = CKEDITOR['instances'][phraseElementId]

                var data = isHtml ? editor.getData() : editor.element.getText()

                saveInlineEdit('phrase', key, data);

                $phrase.removeAttr('contenteditable')
                editor.destroy()
                break;
        }
    });

    $('.js-config-save').on('click', function (e) {
        e.preventDefault();

        closeSlideBar(vmBar, $(this).data('type'), this);
    });

    $('.js-config-close').on('click', function (e) {
        e.preventDefault();

        closeSlideBar(vmBar);
    });

    // custom checkboxes
    $('.vm-checkbox').on('click', function () {
        var checked = ($(this).find('input[type="checkbox"]').prop('checked')) ? false : true;

        applyCheckboxState(this, checked);
    });
});

function saveInlineEdit(type, id, data) {
    intelli.post(intelli.visualModeUrl, {
        action: 'save-inline',
        type: type,
        id: id,
        data: data
    }, function (res) {
        intelli.notifFloatBox({ msg: res.message, type: res.result ? 'success' : 'error', autohide: true });
    });
}

function openSlideBar(bar, type, name) {
    $('.js-config-save').prop('disabled', false).text('Save');

    $.get(intelli.visualModeUrl + '?get=access&type=' + type + '&object=' + name + '&page=' + intelli.pageName, function (response) {
        $('#js-object').val(name);

        if ('positions' == type) {
            var $posConfigBlock = $('.vm-config__item--position');
            $posConfigBlock.find('.vm-config__item__title b').text(name);

            // set values
            applyCheckboxState($('#pos-visible-on-page').parent(), response.result.page);
            $('#pos-visible-everywhere').val(response.result.global);

            setTimeout(function () {
                $('.vm-spinner').hide();
                $posConfigBlock.removeClass('vm-config__item--hidden');
            }, 500);
        }

        if ('blocks' == type) {
            var $blockConfigBlock = $('.vm-config__item--block');
            $blockConfigBlock.find('.vm-config__item__title b').text(name);

            // set values
            applyCheckboxState($('#block-visible-on-page').parent(), response.result.page);
            $('#block-visible-everywhere').val(response.result.global);

            setTimeout(function () {
                $('.vm-spinner').hide();
                $blockConfigBlock.removeClass('vm-config__item--hidden');
            }, 500);
        }
    });

    bar.slidebars.open('left');
}

function closeSlideBar(bar, type, btn) {
    $(btn).text(intelli.lang.saving).prop('disabled', true);
    var name = $('#js-object').val();

    if ('positions' == type) {
        var globalVisibility = $('#pos-visible-everywhere').val();
        var pageVisibility = +$('#pos-visible-on-page').prop('checked');

        pageVisibility ? $('#' + name + 'Blocks').removeClass('groupWrapper--hidden') : $('#' + name + 'Blocks').addClass('groupWrapper--hidden');
    } else {
        var globalVisibility = $('#block-visible-everywhere').val();
        var pageVisibility = +$('#block-visible-on-page').prop('checked');

        pageVisibility ? $('#block_' + name).removeClass('box-visual--hidden') : $('#block_' + name).addClass('box-visual--hidden');
    }

    intelli.post(intelli.visualModeUrl, {
            action: 'save',
            type: type,
            global: globalVisibility,
            page: pageVisibility,
            name: name,
            pagename: intelli.pageName
        },
        function (data) {
            // TODO: reserved for notifications
            if ('boolean' === typeof data.error && !data.error) {
            }
        }
    );

    setTimeout(function () {
        $('.vm-config__item').addClass('vm-config__item--hidden');
        bar.slidebars.close();
        $('.vm-spinner').show();
    }, 500);
}

function applyCheckboxState(elem, checked) {
    $this = $(elem);
    $checkbox = $this.find('input[type="checkbox"]');
    $icon = $this.find('i');

    if (1 == checked) {
        $this.addClass('vm-checkbox--checked');
        $icon
            .removeClass('v-icon--square')
            .addClass('v-icon--check-square');

        $checkbox.prop('checked', true);
    } else {
        $this.removeClass('vm-checkbox--checked');
        $icon
            .addClass('v-icon--square')
            .removeClass('v-icon--check-square');

        $checkbox.prop('checked', false);
    }
}