/**
 * Class for creating plans section.
 * @class This is the Plans class.  
 *
 * @param {Array} conf
 *
 * @param {String} conf.id The id div of plans element
 * @param {Function} conf.callback The callback function on click event
 *
 */
intelli.plans = function(conf)
{
	var id = conf.id;
	var callback = (typeof conf.callback == 'function') ? conf.callback : function(){};
	var idPlan = (typeof conf.idPlan == 'undefined') ? false : conf.idPlan;

	this.init = function()
	{
		this.getPlans();

		setPlan();

		$("#" + id + " input[type=radio]").click(callback);
	};

	/**
	 * Return id current plan
	 *
	 * @return {Integer}
	 */
	this.getPlanCost = function()
	{
		if($('#plans').length > 0)
		{
			var id_plan = $("#plans input[type='radio']:checked").val();
			var plan_cost = $("#planCost_" + id_plan).val();
			
			return plan_cost;
		}

		return false;
	};

	this.getPlans = function()
	{
		$('#' + id).empty();

		$.ajaxSetup({async: false});

		var idCategory = $("#category_id").val();

		// Getting listings fields by AJAX
		$.getJSON('get-plans.php', {action: 'getplans', idcategory: idCategory}, function(plans)
		{
			if(plans)
			{
				for(var i = 0; i < plans.length; i++)
				{
					createPlan(plans[i]);
				}
			}
			else
			{
				$('#' + id).parents("fieldset").css("display", "none");
			}
		});
		
		$.ajaxSetup({async: true});

		setPlan();
	};

	var setPlan = function()
	{
		if(idPlan)
		{
			$("#" + id + "> p > input[type=radio]").each(function()
			{
				if($(this).val() == idPlan)
				{
					$(this).prop('checked', true);
				}
			});
		}
		else
		{
			$("#" + id + " input[type=radio]:first").prop('checked', true);
		}
	};

	var createPlan = function(plan)
	{
		var html = '';

		html = '<p class="field">';
		html += '<input type="radio" name="plan" value="' + plan.id + '" id="p' + plan.id + '" />';
		html += '<input type="hidden" id="planCrossedMax_' + plan.id + '" value="' + plan.multicross + '" />';
		html += '<input type="hidden" id="planDeepLinks_' + plan.id + '" value="' + plan.deep_links + '" />';
		html += '<input type="hidden" id="planCost_' + plan.id + '" value="' + plan.cost + '" />';
		html += '<label for="p' + plan.id + '"><strong>' + plan.title + ' - ' + intelli.config.currency_symbol + plan.cost + '</strong></label><br />';
		html += '<span style="font-size: 9px; color: #666; padding: 0 10px 5px 30px;">' + plan.description + '</span>';
		html += '</p>';

		$('#' + id).append(html);
	};
};
