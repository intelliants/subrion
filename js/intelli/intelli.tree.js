function IntelliTree(params) {
    params = $.extend({}, params);

    this.url = params.url || window.location.href + 'read.json';
    this.selector = params.selector || '#js-tree';

    this.$tree = null;
    this.$value = params.value ? $(params.value) : $('#input-tree');
    this.$search = $('input', '#js-tree-search');


    var $row = $(this.selector).closest('.js-tree-control');

    var $label = $row.find('.js-category-label'),
        $toggler = $row.find('.js-tree-toggler');

    var self = this;

    this.init = function() {
        self.$tree = $(self.selector).jstree({
            core: {
                data: {
                    data: function (n) {
                        var params = {};
                        if (n.id !== '#') {
                            params.id = n.id;
                        }

                        return params;
                    },
                    url: self.url
                },
                multiple: false
            },
            plugins: ['search'],
            search: {show_only_matches: true}
        });

        self.$tree.on('load_node.jstree after_open.jstree', _cascadeOpen);
        self.$tree.on('changed.jstree', this.onchange);
        if ('function' === typeof params.onchange) {
            self.$tree.on('click.jstree', params.onchange);
        }

        $toggler.on('click', function(e) {
            e.preventDefault();

            self.$tree.toggle();
            self.$search.parent().toggle();
        });

        if (params.search) {
            var timeout = false;

            self.$search.keyup(function () {
                if (timeout) clearTimeout(timeout);
                timeout = setTimeout(function () {
                    self.$tree.jstree(true).search(self.$search.val());
                }, 250);
            });
        }
    };

    this.onchange = function (e, data) {
        var nodeId = data.instance.get_node(data.selected).id,
            path = data.instance.get_path(nodeId);

        self.$value.val(nodeId);
        if (path)
            $label.val(path.join(' / '));
    };

    var _cascadeOpen = function(e, o) {
        if (!params.nodeOpened) return;

        var nodes = o.node.children,
            tree = self.$tree.jstree(true);

        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i] == params.nodeSelected) {
                tree.select_node(nodes[i]);
                return;
            }
            else if ($.inArray(parseInt(nodes[i]), params.nodeOpened) !== -1) {
                if (tree.get_node(nodes[i])) {
                    tree.open_node(nodes[i]);
                    continue;
                }

                tree.load_node(nodes[i], function(n) {
                    tree.open_node(n.id);
                })
            }
        }
    };

    this.init();
}