$(function()
{
	$('input[name="items[]"]').on('click', function() {
		toggle_fields($(this).val());
	});

	$('input:checked[name="items[]"]').each(function() {
		toggle_fields($(this).val());
	});
});

// show/hide additional items fields
function toggle_fields(aItem)
{
	var fieldset = $('#' + aItem + '_fields');
	var parent = fieldset.closest('.search-pane');
	if(fieldset.is(':hidden'))
	{
		fieldset.show();
		parent.show();
	}
	else
	{
		fieldset.hide();
		if(parent.find('.search-pane-fieldset:visible').length == 0)
		{
			parent.hide();
		}
	}
}