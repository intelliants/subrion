$(function () {
    $('.tree_name').each(function (index, treeName) {
        var parents = intelli[$(treeName).val() + '_category']['parents'];
        parents = parents.split(',').reverse();
//		$.ajaxSetup({async:false});

        $('#' + $(treeName).val()).jstree(
            {
                data: {
                    data: function (n) {
                        var params = {};
                        if (n.id != '#') {
                            params.id = n.id;
                        }

                        return params;
                    },
                    url: $('#' + $(treeName).val() + '_json_url').val()
                },
                multiple: false
                /*			core: {
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
                 plugins: ['json_data', 'ui', 'types']*/
            });
        /*		.undelegate('a', 'click');
         $.ajaxSetup({async: true});

         $("li[id='"+intelli[$(treeName).val() + '_category']['selected']+"']", '#' + $(treeName).val()).addClass('active');*/
    });
});