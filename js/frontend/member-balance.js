$(function()
{
	$('#js-add-funds').click(function()
	{
		var amount = prompt(_f('howmuch_funds'));
		if (amount)
		{
			$.getJSON(intelli.config.ia_url + 'profile/balance.json', {amount: amount} , function(response)
			{
				if (typeof response.error == 'undefined')
				{
					return;
				}
				if (response.error === false)
				{
					window.location = response.url;
				}
				else
				{
					intelli.notifBox(
					{
						id: 'notification',
						msg: response.error,
						type: 'error'
					});
				}
			});
		}
	});
});