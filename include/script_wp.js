jQuery(function($)
{
	$("select[name='_status']").append("<option value='archive'>" + script_archive.archive_text + "</option>");

	$(".editinline").on('click', function()
	{
		var row = $(this).closest("tr"),
			option = $(".inline-edit-row").find("select[name='_status'] option[value='archive']"),
			is_archived = row.hasClass('status-archive');

		option.prop('selected', is_archived);
	});
});