$(function(){
	$('.tree_name').each(function(index, tree_name){
		var parents = intelli[$(tree_name).val() + '_category']['parents'];
		parents = parents.split(',').reverse();
		$.ajaxSetup({async:false});
		$('#' + $(tree_name).val()).jstree({
			core: {
				to_open: 0,
				initially_open: parents
			},
			types: {
				types: {
					'parent': {
						icon: {
							image : intelli.config.ia_url + 'js/jquery/plugins/jstree/themes/default/d.png',
							position: '-56px -36px'
						},
						valid_children: 'all'
					},
					'default': {
						valid_children: 'all'
					}
				}
			},
			json_data: {
				ajax: {
					url: $('#' + $(tree_name).val() + '_json_url').val(),
					data : function (n) {
						var result = {'id': intelli[$(tree_name).val() + '_category']['id']};
						if (n.attr)
						{
							result['id'] = n.attr('id');
						}
						return result;
					}
				}
			},
			ui: {
				select_limit: 1,
				selected_parent_close: true,
				disable_selecting_children: false,
			},
			plugins: ['json_data', 'ui', 'types']
		})
		.undelegate('a', 'click');
		$.ajaxSetup({async: true});

		$("li[id='"+intelli[$(tree_name).val() + '_category']['selected']+"']", '#' + $(tree_name).val()).addClass('active');
	});	
});