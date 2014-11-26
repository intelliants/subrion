function IntelliTree(params)
{
	params = $.extend({}, params);

	this.url = params.url || window.location.href + 'read.json';
	this.selector = params.selector || '#js-tree';

	this.$tree = null;
	this.$label = params.label ? $(params.label) : $('#js-category-label');
	this.$value = params.value ? $(params.value) : $('#input-category');
	this.$toggler = params.toggler ? $(params.toggler) : $('#js-tree-toggler');

	var self = this;

	this.init = function()
	{
		self.$tree = $(self.selector).jstree({core:
		{
			data: {
				data: function(n)
				{
					var params = {};
					if(n.id != '#')
					{
						params.id = n.id;
					}

					return params;
				},
				url: self.url
			},
			multiple: false
		}});

		self.$tree.on('loaded.jstree', _openCascade);
		self.$tree.on('changed.jstree', this.onchange);
		if (typeof params.onchange == 'function')
		{
			self.$tree.on('click.jstree', params.onchange);
		}

		self.$toggler.on('click', function(e)
		{
			e.preventDefault();
			self.$tree.toggle();
		})
	};

	this.onchange = function(e, data)
	{
		var nodeId = data.instance.get_node(data.selected).id,
			path = data.instance.get_path(nodeId);

		self.$value.val(nodeId);
		if(path)
			self.$label.val(path.join(' / '));
	};

	var _openCascade = function()
	{
		if (params.nodeOpened)
		{
			self.$tree.jstree(true).open_node(params.nodeOpened, function()
			{
				self.$tree.jstree(true).select_node(params.nodeSelected);
			});
		}
	};

	this.init();
}