<?php

function widgets_archive()
{
	register_widget('widget_archive');
}

function settings_archive()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	$arr_settings['setting_archive_choose_here_text'] = __("Replace text", 'lang_archive');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
}

function settings_archive_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Archive", 'lang_archive'));
}

function setting_archive_choose_here_text_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Choose year here", 'lang_archive')));
}

/*function archive_title_archive($title)
{
	if(is_category())
	{
		$title = "Category"; //single_cat_title('Test - ', false);
	}

	if(is_year())
	{
		$title = "Year";
	}

	return $title." 2";
}*/