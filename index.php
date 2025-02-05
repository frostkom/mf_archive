<?php
/*
Plugin Name: MF Archive
Plugin URI: https://github.com/frostkom/mf_archive
Description:
Version: 2.5.26
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_archive
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_archive
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_archive = new mf_archive();

	add_action('init', array($obj_archive, 'init'));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_archive');

		add_action('admin_init', array($obj_archive, 'settings_archive'));
		add_action('admin_init', array($obj_archive, 'admin_init'), 0);

		add_action('pre_get_posts', array($obj_archive, 'pre_get_posts'));

		add_action('wp_loaded', array($obj_archive, 'wp_loaded'));
		add_filter('post_row_actions', array($obj_archive, 'row_actions'), 10, 2);
		add_filter('page_row_actions', array($obj_archive, 'row_actions'), 10, 2);
	}

	else
	{
		add_action('wp_head', array($obj_archive, 'wp_head'), 0);

		//add_filter('get_the_archive_title', array($obj_archive, 'get_the_archive_title'));
		//add_filter('wp_list_pages_excludes', '');
		//add_filter('wp_list_pages', '');
	}

	if(wp_is_block_theme() == false)
	{
		add_action('widgets_init', array($obj_archive, 'widgets_init'));
	}

	function uninstall_archive()
	{
		global $wpdb;

		$wpdb->query("UPDATE ".$wpdb->posts." SET post_status = 'draft' WHERE post_status = 'archive'");
	}
}