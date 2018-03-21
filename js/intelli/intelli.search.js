intelli.search = (function () {
    var paramsMapping = {page: '__p', sortingField: '__s', sortingOrder: '__so'},

        decodeUri = function (uriComponent) {
            return (decodeURIComponent(uriComponent) + '').replace(/\+/g, ' ');
        },

        events = {},
        params = {},

        $form = $('#js-item-filters-form'),

        composeParams = function (formValues) {
            return (formValues != '' ? formValues + '&' : '') + $.param(params);
        },

        fireEvent = function (name) {
            if ('function' === typeof events[name]) {
                events[name]();
            }
        },

        parseHash = function () {
            var result = false,
                hash = window.location.hash.substring(1);

            if (hash != '') {
                result = {};
                hash = hash.split('&');

                for (var i = 0; i < hash.length; i++) {
                    var key, value;
                    [key, value] = hash[i].split('=');

                    key = decodeUri(key);
                    value = decodeUri(value);

                    typeof result[key] === 'undefined'
                        ? (result[key] = [value])
                        : result[key].push(value);
                }
            }

            return result;
        };

    return {
        setParam: function (name, value) {
            if (undefined !== paramsMapping[name]) {
                params[paramsMapping[name]] = value;
            }
        },

        bindEvents: function (fnStart, fnFinish) {
            if ('function' === typeof fnStart) events.start = fnStart;
            if ('function' === typeof fnStart) events.finish = fnFinish;
        },

        run: function (pageNum) {
            fireEvent('start');

            this.setParam('page', pageNum);

            $.ajax({
                data: composeParams($form.serialize()),
                url: $form.attr('action'),
                success: function (response) {
                    if (response.url !== undefined) {
                        window.location = response.url;
                        return;
                    }

                    window.location.hash = response.hash;

                    if (response.html !== undefined) {
                        $('#js-search-results-container').html(response.html);
                        $('#js-search-results-num').html(response.total);
                        $('#js-search-results-pagination').html(response.pagination);
                    }

                    fireEvent('finish');
                },
                error: function () {
                    fireEvent('finish');
                }
            });
        },

        initFilters: function () {
            var data = parseHash();

            if (!data) {
                return;
            }

            var systemParams = {};
            for (var key in paramsMapping) {
                systemParams[paramsMapping[key]] = key;
            }

            for (key in data) {
                for (var i = 0; i < data[key].length; i++) {
                    var value = data[key][i];

                    if (!value) {
                        continue;
                    }

                    if (typeof systemParams[key] === 'string') {
                        this.setParam(systemParams[key], value);
                        continue;
                    }

                    var $ctl = $('[name="' + key + '"]', $form);

                    if (!$ctl.length) {
                        continue;
                    }

                    switch ($ctl[0].nodeName.toLowerCase()) {
                        case 'input':
                            switch ($ctl.attr('type')) {
                                case 'checkbox':
                                case 'radio':
                                    $ctl.filter('[value="' + value + '"]').prop('checked', true);
                                    break;
                                default:
                                    $ctl.val(value);
                            }

                            break;
                        case 'select':
                            var $option = $('option[value="' + value + '"]', $ctl);

                            if ($option.length)
                                $option.prop('selected', true).trigger('change');
                            else $ctl.data('value', value);
                    }
                }
            }

            this.run(params[paramsMapping.page]);
        }
    };
})();