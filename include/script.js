jQuery(function($)
{
	var dom_obj = $('#replace_page_title');

	if(dom_obj.length > 0)
	{
		var dom_val = dom_obj.val();

		if(dom_val != '')
		{
			var i = 0;

			$('body.archive h1').each(function()
			{
				if(i == 0)
				{
					$(this).text(dom_val).addClass('archive_title');
				}

				else
				{
					$(this).remove();
				}

				i++;
			});
		}
	}
});