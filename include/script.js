function on_load_archive()
{
	/* Adjust Page Title */
	var dom_obj = jQuery('#replace_page_title');

	if(dom_obj.length > 0)
	{
		var dom_val = dom_obj.val();

		if(dom_val != '')
		{
			var i = 0;

			jQuery('body.archive h1').each(function()
			{
				if(i == 0)
				{
					jQuery(this).text(dom_val).addClass('archive_title');
				}

				else
				{
					jQuery(this).remove();
				}

				i++;
			});
		}
	}
}

jQuery(function($)
{
	on_load_archive();

	if(typeof collect_on_load == 'function')
	{
		collect_on_load('on_load_archive');
	}
});