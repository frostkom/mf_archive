<?php

class widget_archive extends WP_Widget
{
	public function __construct()
	{
		$widget_ops = array(
			'classname' => 'widget_post_type_archive',
			'description' => __("Show archive for a chosen post type and/or category", 'lang_archive'),
		);

		$this->arr_default = array(
			'title' => '',
			'replace_page_title' => '',
			'post_type' => 'post',
			'categories' => array(),
			'always_show_years' => 'no',
			'show_all' => 'no',
			'year_order' => 'DESC',
		);

		parent::__construct('post_type_archives', __("Post Type Archive", 'lang_archive'), $widget_ops);

		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_archive', $plugin_include_url."style.css", $plugin_version);
		mf_enqueue_script('script_archive', $plugin_include_url."script.js", $plugin_version);
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

		if(count($this->instance['categories']) > 0)
		{
			foreach($this->arr_cat as $category)
			{
				if(in_array($category, $this->instance['categories']))
				{
					$this->display = true;
				}
			}
		}

		else if(isset($post->post_type) && $post->post_type == $this->instance['post_type'])
		{
			$this->display = true;
		}
	}

	function get_post_years()
	{
		global $wpdb;

		$arr_data = array();
		$arr_data[''] = "-- ".get_option_or_default('setting_archive_choose_here_text', __("Choose year here", 'lang_archive'))." --";

		$result = $wpdb->get_results($wpdb->prepare("SELECT SUBSTRING(post_date, 1, 4) AS post_year FROM ".$wpdb->posts." INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy ." USING (term_taxonomy_id) WHERE post_type = %s AND post_status = 'publish'".$this->query_where." GROUP BY post_year ORDER BY post_year ".$this->instance['year_order'], $this->instance['post_type'])); //, COUNT(ID) AS year_amount

		foreach($result as $r)
		{
			$arr_data[$r->post_year] = $r->post_year; //." (".$r->year_amount.")"
		}

		return $arr_data;
	}

	function get_categories_for_select($data = array())
	{
		if(!isset($data['hierarchical'])){		$data['hierarchical'] = true;}

		$arr_data = array();

		$arr_categories = get_categories(array(
			'hierarchical' => $data['hierarchical'],
			'hide_empty' => 1,
		));

		foreach($arr_categories as $category)
		{
			$arr_data[$category->cat_ID] = ($data['hierarchical'] && $category->parent > 0 ? "&nbsp;&nbsp;&nbsp;" : "").$category->name;
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

		//echo var_export($arr_categories, true);

		foreach($arr_categories as $category)
		{
			if(!($category->parent > 0))
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy ." USING (term_taxonomy_id) WHERE post_type = %s AND post_status = 'publish' AND post_date LIKE %s AND term_id = '%d'".$this->query_where, $this->instance['post_type'], $this->year."%", $category->cat_ID));

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
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy ." USING (term_taxonomy_id) WHERE post_type = %s AND post_status = 'publish' AND post_date LIKE %s AND term_id = '%d'".$this->query_where, $this->instance['post_type'], $this->year."%", $category->cat_ID));

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

					$arr_data_years = $this->get_post_years();

					if($instance['always_show_years'] == 'yes' || count($arr_data_years) > 1)
					{
						echo "<form action='".get_site_url()."' method='get' class='mf_form'>"
							.show_select(array('data' => $arr_data_years, 'name' => 'year', 'value' => $this->year, 'xtra' => " rel='submit_change' disabled"))
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

				echo $args['after_widget'];
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
			.show_textfield(array('name' => $this->get_field_name('title'), 'text' => __("Title", 'lang_archive'), 'value' => $instance['title']))
			.show_textfield(array('name' => $this->get_field_name('replace_page_title'), 'text' => __("Replace Page Title", 'lang_archive'), 'value' => $instance['replace_page_title'], 'placeholder' => "[category] [year]"))
			.show_select(array('data' => $arr_data_post_types, 'name' => $this->get_field_name('post_type'), 'text' => __("Post Type", 'lang_archive'), 'value' => $instance['post_type']));

			if($instance['post_type'] == 'post')
			{
				echo show_select(array('data' => $this->get_categories_for_select(), 'name' => $this->get_field_name('categories')."[]", 'text' => __("Categories", 'lang_archive'), 'value' => $instance['categories']));
			}

			echo "<div class='flex_flow'>"
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('always_show_years'), 'text' => __("Always Show Years", 'lang_archive'), 'value' => $instance['always_show_years']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('show_all'), 'text' => __("Show 'All'", 'lang_archive'), 'value' => $instance['show_all']))
				.show_select(array('data' => $this->get_order_for_select(), 'name' => $this->get_field_name('year_order'), 'text' => __("Year Order", 'lang_archive'), 'value' => $instance['year_order']))
			."</div>
		</div>";
	}
}