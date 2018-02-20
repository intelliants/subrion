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
                $(ui.item).css({width: ''}).removeClass('dragging');
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
            hiddenClass = (1 == $this.attr('vm-hidden')) ? ' box-visual--hidden' : '';

        $this.addClass('box-visual ' + hiddenClass).append(
            '<div class="box-visual__actions">' +
            '<a class="js-config-open box-visual__actions__item box-visual__actions__item--settings" data-type="blocks" data-name="' + id + '" href="#"><span class="v-icon v-icon--settings"></span></a>' + moveBtn +
            '</div>');
    });

    var vmBar = new $.slidebars({siteClose: false});

    $('.js-config-open').on('click', function (e) {
        e.preventDefault();

        var $this = $(this);

        if (!vmBar.slidebars.active('left')) {
            openSlideBar(vmBar, $this.data('type'), $this.data('name'));
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
    }
    else {
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
    }
    else {
        $this.removeClass('vm-checkbox--checked');
        $icon
            .addClass('v-icon--square')
            .removeClass('v-icon--check-square');

        $checkbox.prop('checked', false);
    }
}