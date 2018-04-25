<?php
/*
Plugin Name: MF Archive
Plugin URI: https://github.com/frostkom/mf_archive
Description: 
Version: 2.4.0
Licence: GPLv2 or later
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_archive
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_archive
*/

include_once("include/classes.php");

$obj_archive = new mf_archive();

add_action('widgets_init', array($obj_archive, 'widgets_init'));

if(is_admin())
{
	add_action('admin_init', array($obj_archive, 'settings_archive'));
}

else
{
	add_action('wp_head', array($obj_archive, 'wp_head'), 0);

	//add_filter('get_the_archive_title', array($obj_archive, 'get_the_archive_title'));
	//add_filter('wp_list_pages_excludes', '');
	//add_filter('wp_list_pages', '');
}

load_plugin_textdomain('lang_archive', false, dirname(plugin_basename(__FILE__)).'/lang/');