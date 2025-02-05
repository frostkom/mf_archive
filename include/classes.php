<?php

class mf_archive
{
	var $post_status = 'archive';

	function __construct(){}

	function is_excluded_post_type($post_type)
	{
		$excluded = (array) apply_filters('archive_excluded_post_types', array('attachment'));

		return in_array($post_type, $excluded);
	}

	function init()
	{
		load_plugin_textdomain('lang_archive', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		$args = array(
			'label' => __("Archived", 'lang_archive'),
			'public' => false,
			'private' => true,
			'exclude_from_search' => true,
			'show_in_admin_all_list' => false,
			'show_in_admin_status_list' => true,
			'label_count' => _n_noop(__("Archived", 'lang_archive')." <span class='count'>(%s)</span>", __("Archived", 'lang_archive')." <span class='count'>(%s)</span>"),
		);

		register_post_status($this->post_status, $args);
	}

	function settings_archive()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();
		$arr_settings['setting_archive_choose_here_text'] = __("Replace text", 'lang_archive');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
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

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Choose Year Here", 'lang_archive')));
	}

	function admin_init()
	{
		global $pagenow;

		if($pagenow == 'edit.php' && $this->is_excluded_post_type(check_var('post_type')) == false)
		{
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_script('script_archive', $plugin_include_url."script_wp.js", array('archive_text' => __("Do Archive", 'lang_archive')));
		}
	}

	function pre_get_posts($wp_query)
	{
		global $post_type, $pagenow;

		if($pagenow == 'edit.php' && $this->is_excluded_post_type($post_type) == false)
		{
			if($wp_query->query_vars['post_status'] == '')
			{
				$wp_query->set('post_status', array('draft', 'private', 'publish'));
			}
		}
	}

	function wp_loaded()
	{
		global $wpdb;

		if(isset($_REQUEST['btnPostArchive']) && IS_EDITOR)
		{
			$post_id = check_var('post_id');

			if($post_id > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = %s WHERE ID = '%d' AND post_status = %s", $this->post_status, $post_id, 'publish'));

				if($wpdb->rows_affected > 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = %s WHERE post_parent = '%d' AND post_status = %s", $this->post_status, $post_id, 'publish'));

					//mf_redirect(admin_url("edit.php?post_type=".get_post_type($post_id)."&s=".get_post_title($post_id)));
				}

				else
				{
					wp_die(__("I could not archive the post for you", 'lang_archive'));
				}
			}
		}
	}

	function row_actions($actions, $post)
	{
		if(IS_EDITOR && $post->post_status == 'publish')
		{
			$actions['archive'] = "<a href='".admin_url("edit.php?post_type=".$post->post_type."&btnPostArchive&post_id=".$post->ID)."'>".__("Do Archive", 'lang_archive')."</a>";
		}

		return $actions;
	}

	function widgets_init()
	{
		register_widget('widget_archive');
	}

	/*function get_the_archive_title($title)
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

	function wp_head()
	{
		if(!is_plugin_active("mf_widget_logic_select/index.php") || apply_filters('get_widget_search', 'post_type_archives') > 0)
		{
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_style('style_archive', $plugin_include_url."style.css");
			mf_enqueue_script('script_archive', $plugin_include_url."script.js");
		}
	}
}

class widget_archive extends WP_Widget
{
	var $widget_ops;
	var $arr_default = array(
		'title' => '',
		'replace_page_title' => '',
		'post_type' => 'post',
		'categories' => array(),
		'always_show_years' => 'no',
		'show_all' => 'no',
		'year_order' => 'DESC',
	);

	public function __construct()
	{
		$this->widget_ops = array(
			'classname' => 'widget_post_type_archive',
			'description' => __("Show archive for a chosen post type and/or category", 'lang_archive'),
		);

		parent::__construct('post_type_archives', __("Post Type Archive", 'lang_archive'), $this->widget_ops);
	}

	function fetch_request()
	{
		global $post;

		$this->year = check_var('year', 'int', true, get_query_var('year'));
		$this->cat = check_var('cat', 'char', true, get_query_var('cat'));

		if(!($this->year > DEFAULT_DATE))
		{
			$this->year = ''; //date("Y");
		}

		if($this->cat == '')
		{
			if(isset($post->post_type) && $post->post_type == $this->instance['post_type'])
			{
				foreach(get_the_category($post->ID) as $arr_category)
				{
					$this->cat .= ($this->cat != '' ? "," : "").$arr_category->term_id;
				}
			}
		}

		$this->arr_cat = explode(",", $this->cat);

		$this->query_where = $this->cat_all = "";

		if(isset($this->instance['categories']) && count($this->instance['categories']) > 0)
		{
			$this->query_where .= " AND term_id IN('".implode("','", $this->instance['categories'])."')";

			$this->cat_all = implode(",", $this->instance['categories']);
		}

		$this->display = false;
		//$log_message = "";

		if(count($this->instance['categories']) > 0)
		{
			foreach($this->arr_cat as $category)
			{
				if(in_array($category, $this->instance['categories']))
				{
					//$log_message .= "Category (".$category." IN ".var_export($this->instance['categories'], true).")";

					$this->display = true;
				}
			}
		}

		else if(strpos(get_page_template(), 'template_posts.php') !== false)
		{
			//$log_message .= "Template Posts (".get_page_template().")";

			$this->display = true;
		}

		else if(isset($post->post_type) && $post->post_type == $this->instance['post_type'])
		{
			//$log_message .= "Post Type (".$post->post_type.")";

			$this->display = true;
		}

		/*if($log_message != '')
		{
			$post_id = $post->ID;

			if($post_id > 0)
			{
				do_log("widget_archive()->fetch_request(): ".$post_id." (".$log_message.")");
			}
		}*/
	}

	function get_post_years()
	{
		global $wpdb;

		$arr_data = array(
			'' => "-- ".get_option_or_default('setting_archive_choose_here_text', __("Choose year here", 'lang_archive'))." --"
		);

		$result = $wpdb->get_results($wpdb->prepare("SELECT SUBSTRING(post_date, 1, 4) AS post_year FROM ".$wpdb->posts." INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy ." USING (term_taxonomy_id) WHERE post_type = %s AND post_status = %s".$this->query_where." GROUP BY post_year ORDER BY post_year ".$this->instance['year_order'], $this->instance['post_type'], 'publish'));

		foreach($result as $r)
		{
			$arr_data[$r->post_year] = $r->post_year; //." (".$r->year_amount.")"
		}

		return $arr_data;
	}

	function get_order_for_select()
	{
		return array(
			'ASC' => __("Ascending", 'lang_archive'),
			'DESC' => __("Descending", 'lang_archive'),
		);
	}

	function get_categories()
	{
		global $wpdb;

		$this->arr_categories = array();

		$arr_categories = get_categories(array('hierarchical' => 1, 'hide_empty' => 1, 'include' => implode(",", $this->instance['categories']))); //, 'orderby' => 'name', 'order' => 'ASC'

		foreach($arr_categories as $category)
		{
			if(!($category->parent > 0))
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy ." USING (term_taxonomy_id) WHERE post_type = %s AND post_status = %s AND post_date LIKE %s AND term_id = '%d'".$this->query_where, $this->instance['post_type'], 'publish', $this->year."%", $category->cat_ID));

				$count_temp = $wpdb->num_rows;

				if($count_temp > 0)
				{
					$this->arr_categories[$category->cat_ID] = array('name' => $category->name, 'count' => $count_temp, 'children' => array());
				}
			}
		}

		foreach($arr_categories as $category)
		{
			if($category->parent > 0)
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy." USING (term_taxonomy_id) WHERE post_type = %s AND post_status = %s AND post_date LIKE %s AND term_id = '%d'".$this->query_where, $this->instance['post_type'], 'publish', $this->year."%", $category->cat_ID));

				$count_temp = $wpdb->num_rows;

				if($count_temp > 0)
				{
					$this->arr_categories[$category->parent]['children'][$category->cat_ID] = array('name' => $category->name, 'count' => $count_temp);
				}
			}
		}
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance = wp_parse_args((array) $instance, $this->arr_default);

		if($instance['post_type'] != '')
		{
			$this->instance = $instance;
			$this->fetch_request();

			if($this->display == true)
			{
				echo $args['before_widget'];

					if($instance['title'] != '')
					{
						echo $args['before_title']
							.$instance['title']
						.$args['after_title'];
					}

					echo "<div class='section'>";

						$arr_data_years = $this->get_post_years();

						if($instance['always_show_years'] == 'yes' || count($arr_data_years) > 2)
						{
							echo "<form action='".get_site_url()."' method='get' class='mf_form'>"
								.show_select(array('data' => $arr_data_years, 'name' => 'year', 'value' => $this->year, 'xtra' => " rel='submit_change' class='is_disabled' disabled"))
								.input_hidden(array('name' => 'cat', 'value' => $this->cat))
							."</form>";
						}

						if($instance['post_type'] == 'post')
						{
							$this->get_categories();

							if(count($this->arr_categories) > 1 || count($this->instance['categories']) > 1)
							{
								$site_url = get_site_url();

								$url_base = $site_url."?year=".$this->year."&cat=";

								$out_temp = "";

								foreach($this->arr_categories as $key => $arr_category)
								{
									$out_temp .= "<li class='is_parent".(in_array($key, $this->arr_cat) ? " active" : "")."'>
										<a href='".$url_base.$key."'>".$arr_category['name']." (".$arr_category['count'].")</a>
									</li>";

									foreach($this->arr_categories[$key]['children'] as $key => $arr_category)
									{
										$out_temp .= "<li class='is_child".(in_array($key, $this->arr_cat) ? " active" : "")."'>
											<a href='".$url_base.$key."'>".$arr_category['name']." (".$arr_category['count'].")</a>
										</li>";
									}
								}

								echo "<ul>";

									if($instance['show_all'] == 'yes')
									{
										echo "<li class='is_all".($this->cat_all == $this->cat && strlen($this->cat_all) == strlen($this->cat) ? " active" : "")."'>
											<a href='".$url_base.$this->cat_all."'>".__("All", 'lang_archive')."</a>
										</li>";
									}

									echo $out_temp
								."</ul>";
							}
						}

						if($instance['replace_page_title'] != '')
						{
							$str_categories = "";

							if($this->cat != '')
							{
								foreach($this->arr_cat as $category)
								{
									if($category > 0)
									{
										$str_categories .= ($str_categories != '' ? ", " : "").get_category($category)->name;
									}
								}
							}

							$instance['replace_page_title'] = str_replace("[category]", $str_categories, $instance['replace_page_title']);
							$instance['replace_page_title'] = str_replace("[year]", ($this->year > 0 ? $this->year : __("All", 'lang_archive')), $instance['replace_page_title']);

							echo input_hidden(array('name' => 'replace_page_title', 'value' => $instance['replace_page_title'], 'xtra' => "id='replace_page_title'"));
						}

					echo "</div>"
				.$args['after_widget'];
			}
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array) $new_instance, $this->arr_default);

		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['replace_page_title'] = sanitize_text_field($new_instance['replace_page_title']);
		$instance['post_type'] = sanitize_text_field($new_instance['post_type']);
		$instance['categories'] = is_array($new_instance['categories']) ? $new_instance['categories'] : array();
		$instance['always_show_years'] = sanitize_text_field($new_instance['always_show_years']);
		$instance['show_all'] = sanitize_text_field($new_instance['show_all']);
		$instance['year_order'] = sanitize_text_field($new_instance['year_order']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array) $instance, $this->arr_default);

		$arr_data_post_types = array();

		foreach(get_post_types(array('public' => true), 'objects') as $post_type)
		{
			if(!in_array($post_type->name, array('attachment')))
			{
				$arr_data_post_types[$post_type->name] = $post_type->label;
			}
		}

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('title'), 'text' => __("Title", 'lang_archive'), 'value' => $instance['title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_textfield(array('name' => $this->get_field_name('replace_page_title'), 'text' => __("Replace Page Title", 'lang_archive'), 'value' => $instance['replace_page_title'], 'placeholder' => "[category] [year]"))
			.show_select(array('data' => $arr_data_post_types, 'name' => $this->get_field_name('post_type'), 'text' => __("Post Type", 'lang_archive'), 'value' => $instance['post_type']));

			if($instance['post_type'] == 'post')
			{
				echo show_select(array('data' => get_categories_for_select(), 'name' => $this->get_field_name('categories')."[]", 'text' => __("Categories", 'lang_archive'), 'value' => $instance['categories']));
			}

			echo "<div class='flex_flow'>"
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('always_show_years'), 'text' => __("Always Show Years", 'lang_archive'), 'value' => $instance['always_show_years']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('show_all'), 'text' => __("Show All", 'lang_archive'), 'value' => $instance['show_all']))
				.show_select(array('data' => $this->get_order_for_select(), 'name' => $this->get_field_name('year_order'), 'text' => __("Year Order", 'lang_archive'), 'value' => $instance['year_order']))
			."</div>
		</div>";
	}
}