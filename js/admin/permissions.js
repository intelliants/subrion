intelli.permissions =
    {
        button: null,
        url: intelli.config.admin_url + '/permissions/edit.json' + window.location.search,

        save: function ($toggler, access) {
            var self = this,
                data = $toggler.data(),
                defaults = ('undefined' == typeof access),
                params = {
                    object: data.pageType,
                    action: data.action,
                    id: ('undefined' === typeof data.object) ? 0 : data.object
                };

            defaults || (params.access = access);

            intelli.post(this.url, params, function (response) {
                if (response.result) {
                    self.toggle($toggler, defaults ? null : params.access);
                    if ('admin_access' === params.object) {
                        self.toggleDashboardActions(defaults ? $toggler.data('default-access') : params.access);
                    }
                }

                intelli.notifFloatBox({
                    msg: response.message,
                    type: response.result ? 'success' : 'error',
                    autohide: true,
                    pause: 1200
                });
            }, 'json');
        },

        batchSave: function ($ctl) {
            var access = $ctl.data('access'),
                defaults = ('undefined' == typeof access),
                self = this,
                $togglers, data, params;

            $togglers = $ctl.closest('.p-table').find('.js-toggler');
            data = $togglers.eq(0).data();
            params = {object: data.pageType, id: data.object};

            if (!defaults) {
                var actions = [];
                $togglers.each(function () {
                    actions.push($(this).data('action'));
                });

                params.access = access;
                params.action = actions;
            }

            intelli.post(self.url, params, function (response) {
                if (response.result) {
                    self.button = $ctl.get(0);
                    $togglers.each(function () {
                        self.toggle($(this), defaults ? null : access);
                    });
                }

                intelli.notifFloatBox({
                    msg: response.message,
                    type: response.result ? 'success' : 'error',
                    autohide: true,
                    pause: 1200
                });
            }, 'json');
        },

        toggle: function ($toggler, access) {
            var $togglers = $('span', $toggler),
                cssClass = 'label label-',
                $parentRow = $toggler.parent(),
                $resetCtl = $toggler.next().find('a:first');

            if (null === access) {
                access = $toggler.data('default-access');
                $parentRow.removeClass('p-table__row--modified');
                $resetCtl.fadeOut();
            }
            else {
                $parentRow.addClass('p-table__row--modified');
                $resetCtl.fadeIn();
            }

            $togglers.eq(0).attr('class', cssClass + (access ? 'success' : 'default'));
            $togglers.eq(1).attr('class', cssClass + (!access ? 'danger' : 'default'));
        },

        toggleDashboardActions: function (access) {
            var $actionRows = $('.js-dashboard-action');
            (0 == access) ? $actionRows.hide() : $actionRows.show();
        }
    };

$(function () {
    $('td.js-toggler span.label').on('click', function () {
        if ($(this).hasClass('label-default')) {
            intelli.permissions.save($(this).parent(), $(this).data('access'));
        }
    });

    $('.js-togglers-group a').on('click', function (e) {
        e.preventDefault();

        var $this = $(this);
        switch ($this.attr('rel')) {
            case 'reset':
                intelli.permissions.save($this.parent().prev());
                break;
            case 'allow-all':
            case 'deny-all':
            case 'reset-all':
                if ($this.get(0) != intelli.permissions.button) {
                    intelli.permissions.batchSave($this);
                }
        }
    });
});