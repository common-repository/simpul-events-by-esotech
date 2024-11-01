<?php
/**
 * @package Simpul
 */
/*
Plugin Name: Simpul Events by Esotech
Plugin URI: http://www.esotech.org/plugins/simpul/simpul-events/
Description: This plugin is designed to create a post type "Events" and Widget that will display a Calendar and/or list of Events.
Version: 1.8.5
Author: Alexander Conroy
Author URI: http://www.esotech.org/people/alexander-conroy/
License: GPLv2+
*/

$simpulEvents = new SimpulEvents;

//Function for Template
function get_simpul_events( $args ) {
	global $simpulEvents, $wpdb;
	
	if(!$args['order']) $args['order'] == "DESC";
	
	if(!$args['older']): 
		$buildAnd .= "AND meta_value > NOW()";
		unset($args['older']);
	endif;

	$events = $wpdb->get_results( "SELECT meta_value FROM  " . $wpdb->prefix . "postmeta 
										WHERE meta_key =  '" . $simpulEvents->prefix_date . "'  
										 " . $buildAnd . " 
										ORDER BY meta_value " . $args['order'] . " ");
	
	foreach($events as $event):
		$event_ids[] = $event->ID;
	endforeach;
	
	$events = implode(',', (array)$event_ids);
	
	$args['include'] = $events;
	
	$args['post_type'] = 'events';
	
	return get_posts( $args );
}

class SimpulEventsWidget extends WP_Widget {
	
	public function __construct()
	{
		$widget_ops = array('classname' => 'simpul-events', 
							'description' => 'A Simpul Event Calendar Widget' );
							
		parent::__construct('simpul_events', // Base ID
							'Event Calendar', // Name
							$widget_ops // Args  
							);
							
		//Same as Section
		$this->fields = array( 		'date_begin'	=> 'datetime',
									'date_end' 		=> 'datetime', 
									'link' 			=> 'text', 
									'location'   	=> 'map');
									
		$this->post_type = "events"; 
		
		$this->post_type_singular = "event"; 
		
		$this->meta_prefix = "simpul_events_";
		//End of Same as Section
		
		//Register Scripts and Styles
		add_action( 'wp_print_styles', array( &$this, 'registerScripts' ) );
		add_action( 'wp_print_scripts' , array( &$this, 'registerStyles' ) );
		
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Begin Ajax
		 */
		 
		// embed the javascript file that makes the AJAX request
		wp_enqueue_script( 'simpul-ajax-request', plugin_dir_url( __FILE__ ) . 'js/simpulevents-ajax.js', array( 'jquery' ) );
		 
		$ajaxCall = 'ajaxWidget';
		
		// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
		wp_localize_script( 'simpul-ajax-request', 'SimpulAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'ajaxcall' => $ajaxCall ) );
		
		add_action('wp_ajax_' . $ajaxCall , array(&$this, $ajaxCall) );
		add_action('wp_ajax_nopriv_' . $ajaxCall, array(&$this, $ajaxCall) );

	}

	public function ajaxWidget() {
		
		$widgetID = $_POST['widgetID'];
		unset($_POST['widgetID'], $_POST['action']);
		
		$widget_array = get_option('widget_simpul_events');
		$instance = $widget_array[$widgetID];
		self::getCalendar(null, $instance);
		
		unset($_POST);
		die();
	}

	/**
	 * End Ajax
	 */
	 
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function widget( $args, $instance )
	{
		
		extract($args, EXTR_SKIP);
		$terms = unserialize( $instance['terms'] );
		
		$query['post_type']		= array('events');
		
		if( $terms ):
				$query['tax_query'][] = array('taxonomy' => 'calendar',
												'field' => 'name',
												'terms' => $terms);
		endif;
		
		$posts = new WP_Query( $query );
		
		echo $before_widget;
		
		if($instance['title_element']):
			$before_title = '<' . $instance['title_element'] . ' class="widgettitle">';
			$after_title = '</' . $instance['title_element'] . '>';
		else:
			$before_title = '<h3 class="widgettitle">';
			$after_title = '</h3>';
		endif;
		
		if ( !empty( $instance['title']) ) { echo $before_title . $instance['title']. $after_title; };
		
		//Show the Calendar
		if($instance['calendar']):
			echo "<div class='calendar-anchor'>";
			$calendar = self::getCalendar($data, $instance);
			echo "</div>";
			//echo $calendar;
		endif;
		//Show the List
		if($instance['list']):
			if($instance['class']):
				$ul_class = ' class="' . $instance['class'] . '"';
			endif;
		
			echo '<ul' . $ul_class . '>';
			
			$post_number = 0;
			
			while($posts->have_posts()): $posts->the_post();
				echo "<li>";
				if($instance['post_title']):
					if($instance['post_title_element']):
						$post_title_element = $instance['post_title_element'];
					else:
						$post_title_element = "h4";
					endif;
				
					if($instance['post_title_link']):
						$the_link = get_permalink();
						echo '<a href="' . $the_link  . '">' . the_title('<' . $post_title_element . '>','</' . $post_title_element . '>',FALSE) . '</a>';
					else:
						echo the_title('<' . $post_title_element . '>','</' . $post_title_element . '>',FALSE);
					endif;
				endif;
				if($instance['date']):
					if($instance['date_format']):
						$date_format = $instance['date_format'];
					else:
						$date_format = "Y-m-d H:i:s";
					endif;
					echo "<div class='date'>" . date( $date_format, strtotime( get_post_meta( get_the_ID(), '__events_date', TRUE ) ) )  . "</div>";
				endif;
				if($instance['snippet']):
			//Decide Excerpt or Content
				if($post->post_excerpt):
						$content = get_the_excerpt();
					else:
						$content = get_the_content();
					endif;
					//Strip Tags
					$content = strip_tags($content);
					//The Length
					if($instance['snippet_length'] > 0):
						if($instance['snippet_truncation']):
							$truncate = strpos($content,' ', $instance['snippet_length']);
						else:
							$truncate = $instance['snippet_length'];
						endif;
						$content = substr($content, 0, $truncate); 
					endif;
					
					echo "<div class='snippet'><div>" . $content . "</p></div>";
				endif;
				echo "</li>";
				$post_number++;
				if($post_number == $instance['number']) break;
			endwhile;		
			echo "</ul>";
		endif;
		
		echo '</ul>';
		
		echo $after_widget;
		
	}	
	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title'] 							= strip_tags($new_instance['title']);
		$instance['title_element'] 					= strip_tags($new_instance['title_element']);
		$instance['class'] 							= strip_tags($new_instance['class']);
		$instance['title_element'] 					= strip_tags($new_instance['title_element']);
		$instance['use_css'] 						= strip_tags($new_instance['use_css']);
		
		$instance['calendar'] 						= strip_tags($new_instance['calendar']);
		$instance['calendar_head'] 					= strip_tags($new_instance['calendar_head']);
		$instance['calendar_labels'] 				= strip_tags($new_instance['calendar_labels']);
		$instance['calendar_labels_format'] 		= strip_tags($new_instance['calendar_labels_format']);
		$instance['calendar_link_date'] 			= strip_tags($new_instance['calendar_link_date']);
		$instance['calendar_rollover'] 				= strip_tags($new_instance['calendar_rollover']);
		$instance['calendar_link_title'] 			= strip_tags($new_instance['calendar_link_title']);
		$instance['calendar_link'] 					= strip_tags($new_instance['calendar_link']);
		$instance['calendar_location'] 				= strip_tags($new_instance['calendar_location']);
		$instance['calendar_map'] 					= strip_tags($new_instance['calendar_map']);
		$instance['calendar_map_width']				= strip_tags($new_instance['calendar_map_width']);
		$instance['calendar_map_height']			= strip_tags($new_instance['calendar_map_height']);
		$instance['list'] 							= strip_tags($new_instance['list']);
		$instance['number'] 						= strip_tags($new_instance['number']);
		$instance['date']							= strip_tags($new_instance['date']);
		$instance['date_format']					= strip_tags($new_instance['date_format']);
		$instance['post_title'] 					= strip_tags($new_instance['post_title']);
		$instance['post_title_link']				= strip_tags($new_instance['post_title_link']);
		$instance['post_title_element']				= strip_tags($new_instance['post_title_element']);	
		$instance['snippet']						= strip_tags($new_instance['snippet']);
		$instance['snippet_length']					= strip_tags($new_instance['snippet_length']);
		$instance['snippet_truncation']				= strip_tags($new_instance['snippet_truncation']);
		
		$instance['terms']							= serialize( $new_instance['terms']);
		
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$instance 			= wp_parse_args( (array) $instance );
		
		$title 							= strip_tags($instance['title']);
		$title_element					= strip_tags($instance['title_element']);
		$class 							= strip_tags($instance['class']);
		$use_css						= strip_tags($instance['use_css']);
		
		$calendar						= strip_tags($instance['calendar']);
		$calendar_head					= strip_tags($instance['calendar_head']);
		$calendar_labels				= strip_tags($instance['calendar_labels']);
		$calendar_labels_format			= strip_tags($instance['calendar_labels_format']);
		$calendar_link_date				= strip_tags($instance['calendar_link_date']);
		$calendar_rollover				= strip_tags($instance['calendar_rollover']);
		$calendar_link_title			= strip_tags($instance['calendar_link_title']);
		$calendar_link					= strip_tags($instance['calendar_link']);
		$calendar_location				= strip_tags($instance['calendar_location']);
		$calendar_map					= strip_tags($instance['calendar_map']);
		$calendar_map_width				= strip_tags($instance['calendar_map_width']);
		$calendar_map_height			= strip_tags($instance['calendar_map_height']);
		
		$list							= strip_tags($instance['list']);
		$number							= strip_tags($instance['number']);
		$date 							= strip_tags($instance['date']);
		$date_format					= strip_tags($instance['date_format']);
		$post_title						= strip_tags($instance['post_title']);
		$post_title_link				= strip_tags($instance['post_title_link']);
		$post_title_element				= strip_tags($instance['post_title_element']);
		$snippet 						= strip_tags($instance['snippet']);
		$snippet_length					= strip_tags($instance['snippet_length']);
		$snippet_truncation				= strip_tags($instance['snippet_truncation']);
		
		
		$terms				= unserialize($instance['terms']);	
		
		echo self::formatField($this->get_field_name('title'), $this->get_field_id('title'),  $title, "Title");
		echo self::formatField($this->get_field_name('title_element'), $this->get_field_id('title_element'),  $title_element, "Title Element (Default h3)");
		echo self::formatField($this->get_field_name('date_format'), $this->get_field_id('date_format'), $date_format, 'Date Format, see <a href="http://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">Codex</a>' );
		echo self::formatField($this->get_field_name('use_css'), $this->get_field_id('use_css'), $use_css, 'Use Built in CSS', 'checkbox' );
		echo "<h3>Calendar Settings</h3>";
		echo self::formatField($this->get_field_name('calendar'), $this->get_field_id('calendar'),  $calendar, "Show Calendar", 'checkbox');
		echo self::formatField($this->get_field_name('calendar_head'), $this->get_field_id('calendar_head'),  $calendar_head, "Show Calendar Head", 'checkbox');
		echo self::formatField($this->get_field_name('calendar_labels'), $this->get_field_id('calendar_labels'),  $calendar_labels, "Show Calendar Labels", 'checkbox');
		echo self::formatField($this->get_field_name('calendar_labels_format'), $this->get_field_id('calendar_labels_format'),  $calendar_labels_format, "Calendar Labels Format", 'radio', '' , array('short', 'long') );
		echo self::formatField($this->get_field_name('calendar_link_date'), $this->get_field_id('calendar_link_date'),  $calendar_link_date, "Link Calendar Dates with Events", 'checkbox');
		echo "<h3>Calendar Rollover Settings</h3>";
		echo self::formatField($this->get_field_name('calendar_rollover'), $this->get_field_id('calendar_rollover'),  $calendar_rollover, "Enable Calendar Event Rollover", 'checkbox');
		echo self::formatField($this->get_field_name('calendar_link_title'), $this->get_field_id('calendar_link_title'),  $calendar_link_title, "Link Calendar Event Title to Event", 'checkbox');

		echo self::formatField($this->get_field_name('calendar_link'), $this->get_field_id('calendar_link'),  $calendar_link, "Show Event Link", 'checkbox');
		echo self::formatField($this->get_field_name('calendar_location'), $this->get_field_id('calendar_location'),  $calendar_location, "Show Event Location", 'checkbox');

		echo self::formatField($this->get_field_name('calendar_map'), $this->get_field_id('calendar_map'),  $calendar_map, "Show Event Location Map", 'checkbox');
		echo self::formatField($this->get_field_name('calendar_map_width'), $this->get_field_id('calendar_map_width'),  $calendar_map_width, "Map Width", 'text');
		echo self::formatField($this->get_field_name('calendar_map_height'), $this->get_field_id('calendar_map_height'),  $calendar_map_height, "Map Height", 'text');
		echo "<h3>Event List Settings</h3>";
		echo self::formatField($this->get_field_name('list'), $this->get_field_id('list'),  $list, "Show Event List", 'checkbox');
		echo self::formatField($this->get_field_name('class'), $this->get_field_id('class'),  $class, "UL Class");
		echo self::formatField($this->get_field_name('number'), $this->get_field_id('number'), $number, "Number of events to be displayed");
		echo self::formatField($this->get_field_name('date'), $this->get_field_id('date'), $date, "Show Event Date and Time", "checkbox");
		echo self::formatField($this->get_field_name('post_title'), $this->get_field_id('post_title'), $post_title, "Show Post Title", "checkbox");
		echo self::formatField($this->get_field_name('post_title_link'), $this->get_field_id('post_title_link'), $post_title_link, "Link the Post Title", "checkbox");
		echo self::formatField($this->get_field_name('post_title_element'), $this->get_field_id('post_title_element'), $post_title_element, "Post Title Element (Default h4)");
		echo self::formatField($this->get_field_name('snippet'), $this->get_field_id('snippet'), $snippet, "Show Snippet", "checkbox");
		echo self::formatField($this->get_field_name('snippet_length'), $this->get_field_id('snippet_length'), $snippet_length, "Snippet character length");
		echo self::formatField($this->get_field_name('snippet_truncation'), $this->get_field_id('snippet_truncation'), $snippet_truncation, "Truncate at nearest word", "checkbox");
		
		echo self::formatField($this->get_field_name('terms'), $this->get_field_id('terms'), $terms, "Calendars", 'term');
		
	}

	# -----------------------------------------------------------------------------#
	# End Standard Wordpress Widget Section
	# -----------------------------------------------------------------------------#
	public function getCalendar($data, $instance)
	{
		
		$weekrows = 5;
		$max_days = $weekrows * 7;
		$weekdays = array(0, 1, 2, 3, 4, 5, 6);
		
		if(isset($_POST['year'])):
			foreach($_POST as $key => $value):
				$data[$key] = $value;
			endforeach;
		endif;
		
		if(empty($data)):
			$now 		= current_time('timestamp');
		else:
			$now		= strtotime(date('Y-m-d H:i:s', strtotime(implode('-', $data))));
		endif;
		
		$now_mysql 	= date('Y-m-d H:i:s', $now);
		$month 		= date("n", $now);
		$day 		= date("j", $now);
		$year   	= date("Y", $now);
		
		$weekday_start = date("w", strtotime( $year . "-" .  $month . "-" . "1"));
		
		$current_month_end = date("t", $now );
		$current_month_name = date("F", $now);
		
		$last_month_date = date("Y-m-d", strtotime( $now_mysql . " - 1 Month"));
		$last_month_end  = date("t", strtotime( $last_month_date ) );
		$last_year  	 = date("Y", strtotime( $now_mysql . " - 1 Month"));
		$last_month   	 = date("n", strtotime( $now_mysql . " - 1 Month"));
		
		$next_year  	 = date("Y", strtotime( $now_mysql . " + 1 Month"));
		$next_month   	 = date("n", strtotime( $now_mysql . " + 1 Month"));
		
		
		
		//Get the Previous Month if Any
		for($i = $weekday_start; $i >= 0; $i--):
			$dates[] = array('day' => $last_month_end - $i,
							 'date' => $last_year . "-" . $last_month . "-" . $i,
							 'class' => 'simpul-calendar-month-previous' );
		endfor;
		
		//Get the Current Month
		for($i = 1; $i <= $current_month_end; $i++):
			if( $day == $i ):
				$dates[] = array('day' => $i,
							 	 'date' => $year . "-" . $month . "-" . $i,
								 'class' => 'simpul-calendar-today simpul-calendar-month-current');
			
			else:
				$dates[] = array('day' => $i,
							 	 'date' => $year . "-" . $month . "-" . $i,
								 'class' => 'simpul-calendar-month-current');
			endif;
		endfor;
		
		$i = 1;
		//Get the First few days of the next month
		while( count( $dates ) <= $max_days ):
			$dates[] = array('day' => $i,
							 	'date' => $next_year . "-" . $next_month . "-" .  $i,
								'class' => 'simpul-calendar-month-next');
			$i++;
		endwhile;
				
		//Get Events and their Infoz from Now to 30 Days out.
		
		$args = array('post_type' => 'events',
						'post_status' => 'published',
						'order_by' => 'meta_value',
						'meta_key' => $this->meta_prefix . "date_begin",
						'order' => 'ASC',
						'meta_query' => 
							array(
								'key' 		=> $this->meta_prefix . "date_begin",
								'value' 	=> array( $now_mysql , $next_year . "-" . $next_month . "-" . key( array_slice( $dates, -1 ,1, TRUE ) ) . " 23:59:59"),
								'type' 		=> 'DATETIME',
								'compare' 	=> 'BETWEEN')
														);
		
		//Terms if Any (Calendars)
		$terms = unserialize( $instance['terms'] );
		 
		if( $terms ):
				$args['tax_query'][] = array('taxonomy' => 'calendar',
												'field' => 'name',
												'terms' => $terms);
		endif;
		
		$posts = new WP_Query( $args );
		//Get Events if Any
		while( $posts->have_posts() ): $posts->the_post();
			$event_id = get_the_ID();
			$query_dates[$event_id]['the_content'] 		= get_the_content();
			$query_dates[$event_id]['the_title'] 		= get_the_title();
			$query_dates[$event_id]['the_permalink'] 	= get_permalink();
			$query_dates[$event_id]['date'] =  date("Y-n-j", strtotime( get_post_meta( $event_id, $this->meta_prefix . 'date_begin', TRUE ) ) );
			foreach($this->fields as $field => $format):
				$key = $this->meta_prefix . $field;
				$query_dates[$event_id][$key] =  get_post_meta( $event_id, $key, TRUE );
			endforeach;
		endwhile;
		//Setup the Headers
		echo '<table class="simpul-calendar" border="0" cellspacing="0" cellpadding="0" year="' . $year . '" month="' . $month . '">';
		if($instance['calendar_head']):
			echo "<tr class='simpul-calendar-header-row simpul-calendar-row'>
					<th class='simpul-calendar-header-left'></th>
					<th class='simpul-calendar-header-title' colspan='5'>" . $current_month_name . " " . $year . "</th>
					<th class='simpul-calendar-header-right'></th>
				  </tr>";
		endif;
		//Setup the Labels
		if($instance['calendar_labels']):
			$days_of_week['short'] = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thur', 'Fri', 'Sat' );
			$days_of_week['long'] = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
			if($instance['calendar_labels_format']):
				$format = $instance['calendar_labels_format'];
				$days_of_week_formatted = $days_of_week[$format];
			else:
				$days_of_week_formatted = $days_of_week['short'];
			endif;
			echo '<tr class="simpul-calendar-labels-row simpul-calendar-row">';
			for($i = 0; $i < 7; $i++):
				echo '<th class="simpul-calendar-labels-day">' . $days_of_week_formatted[$i] . '</th>';
			endfor;
			echo '</tr>';
		endif;
		//Echo out the Calendar
		$n = 1;
		for($i = 0; $i < $weekrows; $i++):
			echo "<tr class='simpul-calendar-week'>";
			$n++;
			foreach( $weekdays as $weekday ):
				$date = next($dates);
				if( $query_dates ):
					$has_event = false;
					//Check for Events on the Date. 
					foreach($query_dates as $event_id => $query_date):
						if( $date['date'] == $query_date['date'] ):
							$has_event[$event_id] = $query_date;
						endif;
					endforeach;
					//Do something if we have the Event.
					if($has_event):
						if($instance['calendar_link_date']):
							$link_begin = '<a href="' . $query_dates[$event_id]['the_permalink'] . '" class="simpul-calendar-link" >';
							$link_end = "</a>";
						endif;
						if($instance['calendar_rollover']):
							echo "<td class='" . $date['class'] . " simpul-calendar-day'><div class='simpul-calendar-clickable simpul-calendar-number'>" 
							. $link_begin . "<strong>" . $date['day'] . $link_end . "</strong>";
							echo self::getEventCell( $has_event, $instance );
							echo "</div></td>";
						else:
							echo "<td class='" . $date['class'] . " simpul-calendar-day'><div class='simpul-calendar-clickable simpul-calendar-number'>" 
							. $link_begin . "<strong>" . $date['day'] . $link_end . "</strong></div></td>";
						endif;
					else:
						echo "<td class='" . $date['class'] . " simpul-calendar-day'><div class='simpul-calendar-number'>" . $date['day'] . "</div></td>";
					endif;
				else:
					echo "<td class='" . $date['class'] . " simpul-calendar-day'><div class='simpul-calendar-number'>" . $date['day'] . "</div></td>";
				endif;
	
			endforeach;
			echo "</tr>";
		endfor;
		echo "</table>";
		
	}
	public function getEventCell($events, $instance)
	{
		$eventCell = '<div class="simpul-calendar-cell" style="display: none;">';
		
		foreach($events as $index => $event):
			
			$eventCell .= '<div class="simpul-calendar-event" number="' . (1 + $i++) . '" ref="' . $index . '">';
		
			$eventCell .= '<div class="simpul-calendar-event-title">';
			
			//Calendar Link
			if($instance['calendar_link_title']):
				$eventCell .= '<a href="' . $event['the_permalink'] . '">' . $event['the_title'] . '</a>';
			else:
				$eventCell .= $event['the_title'];
			endif;
			
			$eventCell .= '</div>';
			
			//Date Format and Date Begin and End
			if($instance['date_format']) $date_format = $instance['date_format']; else $date_format = "l, F j, Y g:i a";
			$eventCell .= '<div class="simpul-calendar-event-date-begin simpul-calendar-event-date">Begin: ' . date( $date_format, strtotime( $event[$this->meta_prefix . "date_begin"] ) ) . '</div>';
			$eventCell .= '<div class="simpul-calendar-event-date-end simpul-calendar-event-date">End: ' . date( $date_format, strtotime( $event[$this->meta_prefix . "date_end"] ) ) . '</div>';
			
			if( $instance['calendar_link'] && $event[$this->meta_prefix . 'link'] ) 
				$eventCell .= '<div class="simpul-calendar-event-link"> Link: <a href="' . $event[$this->meta_prefix . "link"] . '">' . $event[$this->meta_prefix . "link"] .'</a></div>';
			
			if( $instance['calendar_location'] && $event[$this->meta_prefix . 'location']) 
				$eventCell .= '<div class="simpul-calendar-event-location"> Location: ' . $event[$this->meta_prefix . "location"] . '</div>';
			
			if( $instance['calendar_map'] && $event[$this->meta_prefix . 'location']):
				$map_string = str_replace(" ", "+", $event[$this->meta_prefix . 'location'] );
				if($instance['calendar_map_width']) $calendar_map_width = $instance['calendar_map_width']; else $calendar_map_width = "150"; 
				if($instance['calendar_map_height']) $calendar_map_height = $instance['calendar_map_height']; else $calendar_map_height  = "100"; 
				 $eventCell .= '<iframe class="simpul-calendar-event-map" width="' . $calendar_map_width . '" height="' . $calendar_map_width . '" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com/maps?q=' . $map_string . '&output=embed"></iframe><br />';
			endif;
			
			$eventCell .= '</div>';
			
		endforeach;
		
		$eventCell .= '</div>';
		return $eventCell;
	}
	public function formatField($field, $id, $value, $description, $type = "text", $args = array(), $options = array() )	{
		if($type == "text"):
			return '<p>
					<label for="' . $id . '">
						' . $description . ': 
						<input class="widefat" id="' . $id . '" name="' . $field. '" type="text" value="' . attribute_escape($value) . '" />
					</label>
					</p>';
		elseif($type == "checkbox"):
			if( $value ) $checked = "checked";
			return '<p>
					<label for="' . $field . '">
						
						<input id="' . $field. '" name="' . $field . '" type="checkbox" value="1" ' . $checked . ' /> ' . $description . ' 
					</label>
					</p>';
		elseif($type == "radio"):
			$radio = '<p>
					<label for="' . $field . '">' . $description . '<br />';
					foreach($options as $option):
						if( $value == $option ): $checked = "checked"; else: $checked = ""; endif;						
						$radio .= '<input id="' . $field. '" name="' . $field . '" type="radio" value="' . $option . '" ' . $checked . ' /> ' . SimpulEvents::getLabel($option) . '<br />';
					endforeach; 
			$radio .= '</label>
					</p>';
			return $radio;
		elseif($type == "term"):
			
			$taxonomies = array('calendar');
			
			$terms = get_terms($taxonomies, array( 'hide_empty' => 0 ));
		
			$tax_terms .= '
					
					' . $description . ':
					<div class="widefat" style="height: 100px; overflow-y: scroll; margin-bottom: 10px;"> '; 
			foreach( $terms as $term ):
			
				if( in_array($term->name, (array)$value  ) ) $term_checked = "checked"; else $term_checked = "";
			
				$tax_terms .= '<input type="checkbox" name="' . $field . '[]" value="' . $term->name . '" ' . $term_checked . ' /> ' . $term->name . '<br />';
			
			endforeach;
			$tax_terms .= '</div>
						';
			
			return $tax_terms;
		endif;
	}
	public function registerScripts()
	{
		if( !wp_script_is( 'jquery') ):	
			wp_enqueue_script( 'jquery' );	//Sometimes wasn't loading first.
		endif;
	
		wp_deregister_script( 'simpulevents-front' );
		wp_enqueue_script( 'simpulevents-front', plugins_url( '/js/simpulevents-front.js', __FILE__ ), array('jquery') );
	}
	public function registerStyles()
	{
		wp_register_style( 'simpulevents-css', plugins_url( '/css/simpulevents.css', __FILE__ ) );
		wp_enqueue_style( 'simpulevents-css' );
	}
}
//Register the Widget
function simpul_events_widget() {
	register_widget( 'SimpulEventsWidget' );
}
//Add Widget to wordpress
add_action( 'widgets_init', 'simpul_events_widget' );	


//The Post Type and Taxonomy Class
class SimpulEvents {
	var $meta_fields = array();
	var $meta_box_fields = array();
	
	public function __construct() {
		/*Custom Meta Boxes*/
		$this->fields = array( 		'date_begin'	=> 'datetime',
									'date_end' 		=> 'datetime', 
									'link' 			=> 'text', 
									'location'   	=> 'map');
									
		$this->post_type = "events"; 
		
		$this->post_type_singular = "event"; 
		
		$this->meta_prefix = "simpul_events_";
		
		foreach($this->fields as $key => $value):
			$this->meta_fields[$this->meta_prefix . $key] = $value;
		endforeach;
		
		//Iterate and Create Label.
		foreach($this->meta_fields as $field => $format):
			$this->meta_box_fields[$field] = SimpulEvents::getLabel(str_replace($this->meta_prefix, "", $field));
			$this->meta_box_fields_formats[$field] = $format;
		endforeach;
		add_action( 'init', array(&$this, 'run') );
		
		self::addColumns();
		self::addMetaBoxes();
	}
	//Add Post Types and Taxonomies
	public function run() {
		self::createPostType();
		self::createTaxonomies();
	}
	//Add or Remove Columns for Post Type
	public function addColumns()
	{
		add_filter( 'manage_edit-' . $this->post_type . '_columns', array(&$this, 'editColumns') ) ; // Add or Remove a Column
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array(&$this, 'manageColumns') ); //Show and Modify Column Data
		add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array(&$this, 'sortableColumns') ); // Flags sortable Columns
		add_action( 'load-edit.php', array(&$this, 'loadSortColumns') );
	}
	//Add Meta Boxes
	public function addMetaBoxes()
	{
		add_action( 'add_meta_boxes', array(&$this, 'addCustomBox') ); // Add Meta Box
		add_action( 'save_post', array(&$this, 'savePostData') ); // Save Meta Box Info
	}
	public function createPostType() {
		//Programs	
		register_post_type( $this->post_type,
			array(
				'labels' => array(
					'name' => __( SimpulEvents::getLabel($this->post_type) ),
				),
			'hierarchical' => false,
			'public' => true,
			'has_archive' => true,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'page-attributes', 'revisions', 'custom-fields', 'excerpt' )
			)
		);
	}
	public function createTaxonomies() {
		register_taxonomy(
			'calendar',
			'events',
			array(
				'label' => __( 'Calendars' ),
				'sort' => true,
				'hierarchical' => true,
				'args' => array( 'orderby' => 'term_order' ),
			)
		);
	}
	//Add the Custom Meta Box
	public function addCustomBox() {
	    add_meta_box( 
	        'events_information',
	        __( 'Event Information', 'events_information' ),
	        array(&$this, 'innerCustomBox'),
	        'events',
	        'side', 
	        'high'
	    );
		add_action( 'admin_print_scripts', array( &$this, 'registerScripts' ) );
		add_action( 'admin_print_styles' , array( &$this, 'registerStyles' ) );
		
	}
	//Add the Inner Custom Meta Box and the custom Meta Fields
	public function innerCustomBox( $post ) {
		global $post;
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'myplugin_noncename' );
		echo '<table class="widefat" cellpadding="0" cellspacing="0" border="0">';
		// The actual fields for data entry
		//Iterate through the array. Will expand or contract as fields are added in the database (through scraper).
		foreach($this->meta_box_fields as $field => $label):
			echo self::formatFields($post, $field, $label, 'yachts', $this->meta_box_fields_formats[$field] );
		endforeach;
		echo "</table>";
	  
	}
	//Saves the Meta Box Values
	public function savePostData( $post_id ) {
	  global $post;
	  // verify if this is an auto save routine. 
	  // If it is our form has not been submitted, so we dont want to do anything
	  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	      return;
	
	  // verify this came from the our screen and with proper authorization,
	  // because save_post can be triggered at other times
	  
	  // Check permissions
	 
	  if ( !current_user_can( 'edit_post', $post->ID) )
	      return;
	
	  // OK, we're authenticated: we need to find and save the data
		foreach($this->meta_box_fields_formats as $field => $format):
			if(isset($_POST[$field])):
				switch( $format ):
				case "datetime":
					update_post_meta($post->ID, $field, date("Y-m-d H:i:s", strtotime( $_POST[$field] ) ) );
					break;
				case "date":
					update_post_meta($post->ID, $field, date("Y-m-d", strtotime( $_POST[$field] ) ) );
					break;
				default:
					update_post_meta($post->ID, $field, $_POST[$field]);
					break;
				endswitch;
			endif;
		endforeach;;
	  // Do something with $mydata 
	  // probably using add_post_meta(), update_post_meta(), or 
	  // a custom table (see Further Reading section below)
	}
	//Show Columns in the Post Type List
	public function editColumns( $columns ) {
		
		unset($columns['author']);
		unset($columns['date']);
		$columns['calendar'] = __( 'Calendar' );
		foreach($this->meta_box_fields as $field => $label):
				$columns[$field] = __( $label );
		endforeach;
	    return $columns;
	}
	//Show the Column Data in the Post Type List
	public function manageColumns( $column ){
		global $post;
	    
		switch( $column ):
			case "event_date":
				echo __( get_post_meta($post->ID, $this->meta_prefix . 'date', true) );
				if ( empty( $date ) ):
				echo __( 'Unknown' );

			/* If there is a duration, append 'minutes' to the text string. */
				else:
					printf( $date );
				endif;
				break;
			case "calendar":
				the_taxonomies(array('post' => $post->ID, 'template' => '% %l'));
				break;
			default:
				echo __( get_post_meta($post->ID, $column, true) );
				break;
		endswitch;
	}
	//Define which Columns are sortable
	public function sortableColumns( $columns )
	{
		foreach($this->meta_box_fields as $field => $label):
		$columns[$field] = $field;
		endforeach;	
		return $columns;
	}
	//Load / Filter out the Sortable Columns
	public function loadSortColumns()
	{
		add_filter( 'request', array( &$this, 'sortColumns' ) );
	}
	//Actually sort the columns, needed to be able to sort by Meta Data
	public function sortColumns( $vars )
	{
		/* Check if we're viewing the 'movie' post type. */
		if ( isset( $vars['post_type'] ) && $this->post_type == $vars['post_type'] ) {
	
			/* Check if 'orderby' is set to 'duration'. */
			foreach($this->meta_box_fields as $field => $label):
			
			if ( isset( $vars['orderby'] ) && $field == $vars['orderby'] ) {
	
				/* Merge the query vars with our custom variables. */
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => $field,
						'orderby' => 'meta_value'
					)
				);
			}
			
			endforeach;
		}
	
		return $vars;
	}
	///////////////////////////
	//////HELPER FUNCTIONS/////
	///////////////////////////
	
	//Formats Meta Fields
	public function formatFields($post, $field_name, $label, $box, $format )
	{
	  echo "<tr>";
	  echo '<th><label for="' . $field_name . '">';
	       _e($label, $box );
	  echo '</label> </th>';	
	  switch($format):
		 case "datetime":
			echo '<td><input type="text" id="' . $field_name . '" name="' . $field_name . '" class="hasDateTimePicker" value="' . get_post_meta($post->ID, $field_name ,true) . '"  /></td>';
			break;
		case "date":
			echo '<td><input type="text" id="' . $field_name . '" name="' . $field_name . '" class="hasDatePicker" value="' . get_post_meta($post->ID, $field_name ,true) . '"  /></td>';
			break;
		case "map":
			echo '<td><input type="text" id="' . $field_name . '" name="' . $field_name . '" class="hasMap" value="' . get_post_meta($post->ID, $field_name ,true) . '"  /></td>';
			echo '</tr>';
			echo '<tr>
					<td colspan="2">';
			$map_string = str_replace(" ", "+", get_post_meta($post->ID, $field_name ,true) );
			echo '<iframe class="' . $field_name .'" width="240" height="150" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com/maps?q=' . $map_string . '&output=embed"></iframe><br />';
			echo '</td>';
			break;
		default:
			echo '<td><input type="text" id="' . $field_name . '" name="' . $field_name . '" value="' . get_post_meta($post->ID, $field_name ,true) . '" " /></td>';
		  
			break;
	  endswitch;
	  echo "</tr>";
	}
	//Gets Meta Field Labels
	public static function getLabel($key)
	{
		$glued = array();
		if( strpos( $key, "_" ) ) $pieces = explode( "_", $key );
		elseif( strpos( $key, "-" ) ) $pieces = explode( "-", $key );
		else $pieces = explode(" ", $key);
		foreach($pieces as $piece):
			if($piece == "id"):
				$glued[] = strtoupper($piece);
			else:
				$glued[] = ucfirst($piece);
			endif;
		endforeach;
			
		return implode(" ", (array) $glued);
	}
	
	///////////////////////////////
	//////REGISTRATION SCRIPTS/////
	///////////////////////////////
	
	public function registerScripts()
	{
		if( !wp_script_is('jquery') ):
			wp_enqueue_script( 'jquery' ); // Make sure jQuery is Enqueued
		endif;
					
		if( !wp_script_is('jquery-ui') ):
			wp_deregister_script( 'jquery-ui' );
			wp_enqueue_script('jquery-ui', FALSE, array('jquery') );
		endif;
		
		if( !wp_script_is('jquery-timepicker') ):
			wp_deregister_script( 'jquery-timepicker' );
			wp_enqueue_script('jquery-timepicker', plugins_url( '/js/jquery-ui-timepicker-addon.js', __FILE__ ), array('jquery', 'jquery-ui') );
		endif;
		
		wp_deregister_script( 'simpulevents' );
		wp_enqueue_script('simpulevents', plugins_url( '/js/simpulevents.js', __FILE__ ), array('jquery', 'jquery-ui', 'jquery-timepicker') );
	}
	public function registerStyles()
	{
		wp_register_style('jquery-ui-custom-css', plugins_url( '/css/jquery-ui-1.8.16.custom.css', __FILE__ ) );
		wp_enqueue_style('jquery-ui-custom-css');
	}
	
}
