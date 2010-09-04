<?php
/*                                                                                                                                                                                                                                                             
Plugin Name: ThreeWP Upcoming Posts
Plugin URI: http://mindreantre.se/threewp-upcoming-posts/
Description: Display upcoming posts in a widget. 
Version: 1.0
Author: Edward Hevlund
Author URI: http://www.mindreantre.se
Author Email: edward@mindreantre.se
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

class ThreeWP_Upcoming_Posts_Widget extends WP_Widget
{
	private $options = array(
		'title', 'category', 'amount', 'display_when_empty', 'title_link', 'format',
	);

	function ThreeWP_Upcoming_Posts_Widget()
	{
		$widget_ops = array('classname' => 'ThreeWP_Upcoming_Posts_Widget', 'description' => 'Shows schedules / upcoming posts.' );
		$this->WP_Widget('ThreeWP_Upcoming_Posts_Widget', 'Upcoming Posts (3WP)', $widget_ops);
		
		add_filter( 'the_posts', array($this, 'enable_future_posts') );
	}
	
	function enable_future_posts($posts)
	{
		if (get_option('threewp_upcoming_posts_display_future_posts') != true)
			return;
			
		global $wp_query, $wpdb;
		
		if( is_single() && $wp_query->post_count == 0 )
			$posts = $wpdb->get_results( $wp_query->request );
		
		return $posts;
	}
	
	function widget($args, $instance)
	{
		$queryArgs = array(
			'showposts'			=> $instance['amount'],
			'what_to_show'		=> 'posts',
			'nopaging'			=> 0,
			'post_status'		=> 'future',
			'caller_get_posts'	=> 1,
			'order'				=> 'ASC',
			'cat'				=> $instance['category'],
		);
		
		$posts = new WP_Query($queryArgs);
		if (!$posts->have_posts() && !$instance['display_when_empty'])
			return;
			
		$format = $instance['format'];
			
		$futurePosts = '';
		while ($posts->have_posts())
		{
			$posts->the_post();
			$post = $format;
			$post = str_replace('%POST_TITLE%', get_the_title(), $post);
			if (get_option('threewp_upcoming_posts_display_future_posts') == true)
				$post = str_replace('%POST_LINK%', get_permalink(), $post);
			$post = str_replace('%POST_DATE%', get_the_date(), $post);
			$post = str_replace('%POST_TIME%', get_the_time(), $post);
			$futurePosts[] = $post;
		}
		if (count($futurePosts) > 0)
			$futurePosts = '<ul>'.implode($futurePosts).'</ul>';
			
		// Fix up the title
		$title = apply_filters('widget_title', $instance['title']);
		// Make the title a link?
		if ($instance['title_link'])
			$title = '<a href="' . get_category_link($instance['category']) . '">' . $title . '</a>';			

		echo '
			'.$args['before_widget'].'
			'.$args['before_title'].$title.$args['after_title'].'
			'.$futurePosts.'
			'.$args['after_widget'].'
		';
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		foreach($this->options as $key)
			$instance[$key] = stripslashes($new_instance[$key]);
			
		if (intval($instance['amount']) < 1)
			$instance['amount'] = 1;

		if ($instance['format'] == '')
			$instance['format'] = htmlspecialchars('<li><a href="%POST_LINK%">%POST_TITLE%</a><br />%POST_DATE%<br />%POST_TIME%</li>');
			
		if (isset($new_instance['display_future_posts']))
			update_option('threewp_upcoming_posts_display_future_posts', true );
		else
			delete_option('threewp_upcoming_posts_display_future_posts');
			
		return $instance;
	}

	/** @see WP_Widget::form */
	function form($instance)
	{
		$options = array();
		foreach($this->options as $key)
			$options[$key] = esc_attr( $instance[$key] );
			
		$options['display_future_posts'] = get_option('threewp_upcoming_posts_display_future_posts');
		
        echo '
			<p>
				<label for="'.$this->get_field_id('title').'">Widget title</label>
				<input
					class="widefat"
					id="'.$this->get_field_id('title').'"
					name="'.$this->get_field_name('title').'"
					type="text"
					value="'.$options['title'].'"
				/>
			</p>
			
			<p>
				<label for="'.$this->get_field_id('category').'">Category</label>
				
				'. wp_dropdown_categories(array(
				'name' => $this->get_field_name('category'),
				'id' => $this->get_field_id('category'),
        		'selected' => $options['category'],
				'hide_empty' => false,
		        'echo' => false,
				)) .'
			</p>

			<p>
				<label for="'.$this->get_field_id('amount').'">How many posts to display</label>
				<input
					id="'.$this->get_field_id('amount').'"
					name="'.$this->get_field_name('amount').'"
					type="text"
					maxlength="2"
					size="2"
					value="'.$options['amount'].'"
				/>
			</p>

			<p>
				<input
					class="checkbox"
					type="checkbox"
					id="'.$this->get_field_id('display_future_posts').'"
					name="'.$this->get_field_name('display_future_posts').'"
					' . ( $options['display_future_posts'] == true ? 'checked="checked"' : '' ) . '
				/>
				<label for="'.$this->get_field_id('display_future_posts').'">
					Enable linking and user viewing of future posts
				</label>
			</p>
			
			<p>
				<input
					class="checkbox"
					type="checkbox"
					id="'.$this->get_field_id('title_link').'"
					name="'.$this->get_field_name('title_link').'"
					' . ( $options['title_link'] == true ? 'checked="checked"' : '' ) . '
				/>
				<label for="'.$this->get_field_id('title_link').'">Make the widget title link to the category</label>
			</p>
			
			<p>
				<input
					class="checkbox"
					type="checkbox"
					id="'.$this->get_field_id('display_when_empty').'"
					name="'.$this->get_field_name('display_when_empty').'"
					' . ( $options['display_when_empty'] == true ? 'checked="checked"' : '' ) . '
				/>
				<label for="'.$this->get_field_id('display_when_empty').'">Display widget even if there are no posts to display</label>
			</p>

			<p>
				<label for="'.$this->get_field_id('format').'">Post display format</label>
				<textarea
					class="widefat",
					id="'.$this->get_field_id('format').'"
					name="'.$this->get_field_name('format').'"
				>'.addslashes($options['format']).'</textarea>
			</p>
			
			';
	}
}

add_action('widgets_init', create_function('', 'return register_widget("ThreeWP_Upcoming_Posts_Widget");'));

?>