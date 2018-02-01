<?php
/*
Plugin Name: MF Archive
Plugin URI: 
Description: 
Version: 2.3.6
Licence: GPLv2 or later
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_archive
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: 
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_action('widgets_init', 'widgets_archive');

if(is_admin())
{
	add_action('admin_init', 'settings_archive');
}

else
{
	//add_filter('get_the_archive_title', 'archive_title_archive');
	//add_filter('wp_list_pages_excludes', '');
	//add_filter('wp_list_pages', '');
}

load_plugin_textdomain('lang_archive', false, dirname(plugin_basename(__FILE__)).'/lang/');