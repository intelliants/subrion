Ext.onReady(function () {
    if (Ext.get('js-grid-placeholder')) {
        var grid = new IntelliGrid(
            {
                columns: [
                    'selection',
                    {name: 'id', title: _t('invoice_id'), width: 110},
                    {name: 'date_created', title: _t('date'), width: 170},
                    {name: 'fullname', title: _t('username'), width: 1},
                    {name: 'plan', title: _t('plan'), width: 1, sortable: false},
                    {name: 'gateway', title: _t('gateway'), width: 90},
                    {name: 'amount', title: _t('total'), width: 100},
                    'status',
                    {
                        name: 'pdf',
                        title: _t('view'),
                        icon: 'file',
                        href: intelli.config.admin_url + '/invoices/view/{id}/'
                    },
                    'update',
                    'delete'
                ],
                sorters: [{property: 'date_created', direction: 'DESC'}],
                statuses: ['pending', 'passed', 'failed', 'refunded']
            }, false);

        grid.toolbar = Ext.create('Ext.Toolbar', {
            items: [
                {
                    xtype: 'textfield',
                    name: 'fullname',
                    emptyText: _t('username'),
                    listeners: intelli.gridHelper.listener.specialKey,
                    width: 125
                }, {
                    emptyText: _t('gateway'),
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/transactions/gateways.json'),
                    displayField: 'title',
                    name: 'gateway',
                    valueField: 'value',
                    width: 100
                }, {
                    emptyText: _t('status'),
                    name: 'status',
                    id: 'fltStatus',
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: grid.stores.statuses,
                    displayField: 'title',
                    valueField: 'value',
                    width: 80
                }, {
                    handler: function () {
                        intelli.gridHelper.search(grid)
                    },
                    id: 'fltBtn',
                    text: '<i class="i-search"></i> ' + _t('search')
                }, {
                    handler: function () {
                        intelli.gridHelper.search(grid, true)
                    },
                    text: '<i class="i-close"></i> ' + _t('reset')
                }]
        });

        grid.init();
    }
});

$(function () {
    if ($('#js-grid-placeholder').length > 0) {
        return;
    }

    var $itemsTable = $('#js-items-table');

    intelli.resetLinesCounter = function () {
        $('tr', $itemsTable).not(':first').each(function (i) {
            $('td:first > span', this).text(i + 1);
        });
    };

    intelli.calculateItems = function ($row) {
        var price = parseFloat($('td:nth-child(3) > input', $row).val()),
            qty = parseInt($('td:nth-child(4) > input', $row).val()),
            taxPercent = parseFloat($('td:nth-child(6) input', $row).val()) || 0;

        if (isNaN(price) || isNaN(qty)) {
            $('td > span', $row).text('');
            return;
        }

        var subtotal = price * qty;
        $('td:nth-child(5) > span', $row).text(subtotal);

        var tax = subtotal / 100 * taxPercent;
        $('td:nth-child(7) > span', $row).text(tax);

        $('td:nth-child(8) > span', $row).text(subtotal + tax);
    };

    $('.js-field-tax, .js-field-quantity, .js-field-price').numeric();
    $itemsTable.on('change', '.js-field-tax, .js-field-quantity, .js-field-price', function () {
        intelli.calculateItems($(this).closest('tr'));
    });

    $('#js-cmd-add-line').on('click', function () {
        var $clone = $('tr:nth-child(2)', $itemsTable).clone();

        $('input', $clone).val('');
        $('span:not(.input-group-addon)', $clone).text('');
        $('td:nth-child(4) > input', $clone).val('1');
        $('td:nth-child(6) input', $clone).val('0');

        $itemsTable.append($clone);

        intelli.resetLinesCounter();
    });

    $itemsTable
        .on('click', '.js-cmd-remove-line', function () {
            if ($('tr', $itemsTable).length > 2) {
                $(this).closest('tr').remove();
                intelli.resetLinesCounter();
            }
        });

    intelli.resetLinesCounter();
    $('tr', $itemsTable).each(function () {
        intelli.calculateItems($(this));
    });

    $('input[name="fullname"]').typeahead({
            source: function (query, process) {
                $.ajax({
                    url: intelli.config.url + 'actions.json',
                    type: 'get',
                    dataType: 'json',
                    data: {q: query, action: 'assign-owner'},
                    success: function (response) {
                        objects = items = [];
                        $.each(response, function (i, object) {
                            items[object.fullname] = object;
                            objects.push(object.fullname);
                        });

                        return process(objects);
                    }
                });
            },
            updater: function (item) {
                $('#member-id').val(items[item].id);
                return item;
            },
            matcher: function () {
                return true;
            }
        });
});