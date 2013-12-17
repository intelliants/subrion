/**
* Class for adding text counter
*
* @el id text form (textarea)
* @counter id counter form
* @max max length of characters
*/

// TODO
// Counter form
intelli.textcounter = function(conf)
{
	this.element = document.getElementById(conf.textarea_el);
	this.count = document.getElementById(conf.counter_el);
	
	this.maxLength = conf.max;
	this.minLength = conf.min;

	this.conf = conf;

	this.init = function()
	{
		var obj = this;

		// init counter form
		this.count.readOnly = true;
		this.count.size = '3';
		this.count.maxLength = '3';

		if((this.minLength >= 0 && this.maxLength >= 0) || (isNaN(this.minLength) && this.maxLength >= 0))
		{
			this.count.value = this.maxLength;

			this.counting(true);

			// atach event for text form
			this.element.onkeydown = function()
			{
				obj.counting(true);
			}

			this.element.onkeyup = function()
			{
				obj.counting(true);
			}
		}

		if((this.minLength >= 0 && isNaN(this.maxLength)))
		{
			this.count.value = this.minLength;

			this.counting(false);

			// atach event for text form
			this.element.onkeydown = function()
			{
				obj.counting(false);
			}

			this.element.onkeyup = function()
			{
				obj.counting(false);
			}
		}
	};
	
	this.counting = function(decrease)
	{
		if(decrease)
		{
			if(this.element.value.length > this.maxLength)
			{
				this.element.value = this.element.value.substring(0, this.maxLength);
			}
			else
			{
				this.count.value = this.maxLength - this.element.value.length;
			}
		}
		else
		{
			this.count.value = this.element.value.length;
		}
	};
};
