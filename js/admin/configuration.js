$(function () {
    $('input[name^="v"], select[name^="v"]').on('change', function () {
        var id = $(this).attr('id');

        $("[data-id^='js-" + id + "']").hide();
        $('[data-id="js-' + id + '-' + $(this).val() + '"]').show();
    });

    $('.set-default, .set-custom').on('click', function () {
        $(this).closest('.row')
            .toggleClass('common custom')
            .find('.chck').val($(this).data('value'));
    });

    $('.item-val').dblclick(function () {
        $(this).closest('.row')
            .toggleClass('common custom')
            .find('.chck').val('1');
    });


    // STYLE CHOOSER
    //---------------------------

    if ($('#sap_style').length > 0) {
        $('body').addClass('sap-style-transition');

        var $o = $('#sap_style');
        var $parent = $o.parent();
        var currentStyle = $o.val();
        var $currentStyleCSS = $('link[href*="bootstrap-' + currentStyle + '.css"]');
        var currentStyleLink = $currentStyleCSS.attr('href');

        $currentStyleCSS.attr('id', 'defaultStyle');

        var styles = {
            colors: {
                calmy: 'background: #a2dadb; border: 8px solid #3d4c4f;',
                // darkness: 'background: #777; border: 8px solid #333;',
                default: 'background: #47c1a8; border: 8px solid #25272a;',
                'gebeus-waterfall': 'background: #38b7ea; border: 8px solid #1d1c24;',
                'radiant-orchid': 'background: #B163A3; border: 8px solid #3d4049;',
                roseus: 'background: #e45b9b; border: 8px solid #3d4049;',
            },
            css: 'height: 34px; width: 34px; margin-right: 10px; display: inline-block;'
        };

        $.each(styles.colors, function (key, value) {
            $parent.append('<div class="sap-style-color ' + (currentStyle == key ? ' active' : '') + '" data-color="' + key + '" style="' + value + styles.css + '"></div>');

            var css = currentStyleLink.replace('bootstrap-' + currentStyle, 'bootstrap-' + key);

            if (currentStyle != key) {
                $currentStyleCSS.before('<link rel="stylesheet" type="text/css" href="' + css + '" data-style="' + key + '">');
            }
        });

        $('.sap-style-color', $parent).on('click', function () {
            if (!$(this).hasClass('active')) {
                $(this).addClass('active').siblings().removeClass('active');
                $o.val($(this).data('color'));

                // set new sap style
                $('#defaultStyle').attr('href', $('link[data-style="' + $(this).data('color') + '"]').attr('href'));

                // save sap style new configuration
                $.ajax(
                    {
                        data: {name: 'sap_style', value: $(this).data('color')},
                        dataType: 'json',
                        failure: function () {
                            Ext.MessageBox.alert(_t('error'));
                        },
                        type: 'POST',
                        url: intelli.config.admin_url + '/configuration/read.json?action=update',
                        success: function (response) {
                            if ('boolean' == typeof response.result && response.result) {
                                intelli.notifFloatBox({
                                    msg: response.message,
                                    type: response.result ? 'success' : 'error',
                                    autohide: true
                                });
                            }
                        }
                    });
            }
        });

        $('.sap-style-color', $parent).hover(function () {
            $('link[data-style="' + $(this).data('color') + '"]').clone().insertAfter('#defaultStyle').attr('id', 'currentStyle');
        }, function () {
            $('#currentStyle').remove();
        });
    }
});