<?php
/*
Plugin Name: Weekly Planner
Description: Weekly Planner is a highly customizable plug-in that makes it easy to keep track of weekly events.
Author: Michael Reid
Version: 1.0
Author URI: http://mike.frad.org
*/


// define the SQL tables to be used
define('WP_LPLANNER_TABLE', $table_prefix . 'lplanner');
define('WP_LPLANNER_CONFIG_TABLE', $table_prefix . 'lplanner_config');

// define other constants
define('WP_LPLANNER_VER', '1.0');
define('ALL_DAYS', '-1');
define('SUN', '0');
define('MON', '1');
define('TUES', '2');
define('WED', '3');
define('THURS', '4');
define('FRI', '5');
define('SAT', '6');
define('PADDING_CELLS', '3'); // number of padding cells to be used in display
define('CELL_HEIGHT', '10'); // height of one 15-minute block, in pixels 
define('BLOCK_TIME', '15');  // the time period 1 block represents.  default is 15 minutes
define('TIME_DISPLAY_INCREMENT', '60'); // display times ever XX minutes

// function to add information to the admin menu
add_action('admin_menu','planner_menu');

// insert into pages via {PLANNER}
add_filter('the_content','planner_insert');

// this adds the CSS to the header
add_action('wp_head', 'planner_wp_head');
add_action('admin_head', 'planner_wp_head');

// header info for Javascript
add_action('admin_print_scripts', 'planner_js_admin_header' );


function planner_js_admin_header ()
{
 	 // use JavaScript SACK library for Ajax
 	 // but we're not using ajax.  not now anyway
  	// wp_print_scripts( array( 'sack' ));
	
  	// begin javascript here
  	?>
 	<script type="text/javascript">

	// changes the color of a cell to a new value, in the config menu
	function change_color (number, value)
	{
		document.getElementById("color_cell" + number).style.backgroundColor = '#' + value;
	}

	// adds a space for a new color in the config menu
	function add_color ()
	{
		document.getElementById("num_colors").value = parseInt(document.getElementById("num_colors").value) + 1;
		var new_color_num = document.getElementById("num_colors").value;
		
		var new_color_cell_left_text = "Hex code: <input style=\"width: 6em\" type=\"text\" maxlength=\"6\"" +
			"name=\"color_value" + new_color_num + "\" id=\"color_value" + new_color_num +
			"\" onblur=\"change_color( " + new_color_num + " , this.value)\"" +
			"value=\"000000\" />";
		var new_color_row = document.createElement("tr");
		var new_color_cell_left = document.createElement("td");
		var new_color_cell_right = document.createElement("td");
		new_color_cell_left.style.padding = "1px 1px 1px 3px";
		new_color_cell_left.style.width = "auto";
		new_color_cell_left.innerHTML = new_color_cell_left_text;
		new_color_cell_right.setAttribute("id", "color_cell" + new_color_num);
		new_color_cell_right.style.padding = "3px";
		new_color_cell_right.style.backgroundColor = "#000000";
		new_color_cell_right.style.width = "50px";
		
		var colortable = document.getElementById("colortable");

		new_color_row.appendChild(new_color_cell_left);
		new_color_row.appendChild(new_color_cell_right);
		colortable.appendChild(new_color_row);
		
	}

	// script to clear up a new lesson for entry when clicking on a time slot
	function jsAddNewLesson (hour, min, day)
	{
		if (document.getElementById("action"))
		{
			document.getElementById("action").value = 'add';
		}
		if (document.getElementById("modifyappointment"))
		{
			document.getElementById("modifyappointment").value = 'Add Appointment';
		}
		if (document.getElementById("is_unavailable"))
		{
			document.getElementById("is_unavailable").checked = false;
		}
		if (document.getElementById("lesson_id"))
		{
			document.getElementById("lesson_id").value = false;
		}
		if (document.getElementById("day"))
		{
			document.getElementById("day").value = day;
		}		
		if (document.getElementById("student_name"))
		{
			document.getElementById('student_name').disabled = false;
			document.getElementById("student_name").value = '';
		}		
		if (document.getElementById("student_mail"))
		{
			document.getElementById('student_mail').disabled = false;
			document.getElementById("student_mail").value = '';
		}

		if (hour >= 12)
		{
			ampm = 'pm';
		}
		else
		{
			ampm = 'am';
		}
		if (hour > 12)
		{
			hour = hour - 12;
		}
		
		document.getElementById("start_time_hour").value = (hour);
		document.getElementById("start_time_ampm").value = ampm;
		document.getElementById("end_time_hour").value = (hour);
		document.getElementById("end_time_ampm").value = ampm;

		if (min < 10)
		{
			min = min.substr(1, 1);
		}
		document.getElementById("start_time_minute").value = min;
		document.getElementById("end_time_minute").value = min;
	}
	
	// script to load lesson data into the edit form
	function jsEditLesson (lesson_id, day, start_h, start_m, end_h, end_m, name, mail)
	{
		if (document.getElementById("action"))
		{
			document.getElementById("action").value = 'edit';
		}
		if (document.getElementById("modifyappointment"))
		{
			document.getElementById("modifyappointment").value = 'Edit Appointment';
		}
		if (document.getElementById("is_unavailable"))
		{
			document.getElementById("is_unavailable").checked = false;
		}
		if (document.getElementById("lesson_id"))
		{
			document.getElementById("lesson_id").value = lesson_id;
		}
		if (document.getElementById("day"))
		{
			document.getElementById("day").value = day;
		}		
		if (document.getElementById("student_name"))
		{
			if (name == 'PLANNER_UNAVAILABLE')
			{
				document.getElementById('student_mail').value = '';
				document.getElementById('student_name').value = '';
				document.getElementById('student_name').disabled = true;
				document.getElementById('student_mail').disabled = true;
				document.getElementById('is_unavailable').checked = true;
			}
			else
			{
				document.getElementById('student_name').disabled = false;
				document.getElementById('student_mail').disabled = false;
				document.getElementById("student_name").value = name;
				if (document.getElementById("student_mail"))
				{
					document.getElementById("student_mail").value = mail;
				}
			}
		}		

		if (document.getElementById("start_time_hour"))
		{
			if (document.getElementById("start_time_ampm"))
			{
				if (start_h > 12)
				{
					document.getElementById("start_time_hour").value = (start_h - 12);
					document.getElementById("start_time_ampm").value = 'pm';
				}
				else if (start_h >= 10)
				{
					document.getElementById("start_time_hour").value = (start_h);
					document.getElementById("start_time_ampm").value = 'am';
				}
				else
				{
					document.getElementById("start_time_hour").value = (start_h.substr(1, 1));
					document.getElementById("start_time_ampm").value = 'am';
				}
			}
		}
		if (document.getElementById("start_time_minute"))
		{
			// we already have the padding zeroes... confusing!
			if (start_m < 10)
			{
				start_m = start_m.substr(1, 1);
				document.getElementById("start_time_minute").value = start_m;
			}
			else
			{
				document.getElementById("start_time_minute").value = start_m;
			}
		}

		if (document.getElementById("end_time_hour"))
		{
			if (document.getElementById("end_time_ampm"))
			{
				if (end_h > 12)
				{
					document.getElementById("end_time_hour").value = (end_h - 12);
					document.getElementById("end_time_ampm").value = 'pm';
				}
				else if (end_h >= 10)
				{
					document.getElementById("end_time_hour").value = (end_h);
					document.getElementById("end_time_ampm").value = 'am';
				}
				else
				{
					document.getElementById("end_time_hour").value = (end_h.substr(1,1));
					document.getElementById("end_time_ampm").value = 'am';
				}				
			}
		}
		if (document.getElementById("end_time_minute"))
		{
			if (end_m < 10)
			{
				end_m = end_m.substr(1, 1);
				document.getElementById("end_time_minute").value = end_m;
			}
			else
			{
				document.getElementById("end_time_minute").value = end_m;
			}
		}
		
	}

	function jsUnavailable ()
	{
		if (document.getElementById("is_unavailable").checked)
		{
			document.getElementById("student_name").value = '';
			document.getElementById("student_mail").value = '';
			document.getElementById("student_name").disabled = true;
			document.getElementById("student_mail").disabled = true;
		}
		else
		{
			document.getElementById("student_name").disabled = false;
			document.getElementById("student_mail").disabled = false;
		}
	}
	
 	</script>
 
 	<?php  	
}

	
	/**
	 * Insert a new lesson into the database.
	 * @param $student_name Name of the student (30 characters max)
	 * @param $student_mail Student's e-mail address, home address, etc (60 characters max)
	 * @param $start_time   Lesson start time
	 * @param $end_time     Lesson end time
	 * @param $day          Day of the week (0 = Sunday; 6 = Saturday)
	 * @return integer      Error code:
	 *      1 = OK
	 *      2 = invalid start time
	 *      3 = invalid end time
	 *      4 = end time is earlier than the start time
	 *      5 = invalid day (?? how did this happen ??)
	 *      6 = Other invalid input
	 */
	function newLesson ($student_name, $student_mail, $start_time, $end_time, $day)
	{
		global $wpdb;
		
		// Make sure the name/mail are readable by the database
		$student_name = mysql_escape_string($student_name);
		$student_mail = mysql_escape_string($student_mail);
		
		if (!validate_time($start_time)) // check if start time is valid
		{
			return 2;
		}
		if (!validate_time($end_time)) // check if end time is valid
		{
			return 3;
		}
		if (!is_after($end_time, $start_time)) // check if start time is after end time (typo protection)
		{
			return 4;
		}
		if (($day > 6) || ($day < 0)) // check if day is valid
		{
			return 5;
		}
		if (($conflict = check_conflicts($start_time, $end_time, $day)) !== false)
		{
			return $conflict;
		}
		$lesson_sql = "
			INSERT INTO ".WP_LPLANNER_TABLE." (
			`lesson_ID` ,
			`day` ,
			`start_time` ,
			`end_time` ,
			`student_name` ,
			`student_mail`
			)
			VALUES
			( NULL, '$day', '$start_time', '$end_time', '$student_name', '$student_mail' 
			)";
		$wpdb->get_results($lesson_sql);
		
		return 1;
	}		

function planner_wp_head ()
{
	lp_check();
	
	// pull the CSS from the database
	echo '<style type="text/css">
<!--';
	echo get_config('css');
	echo '//-->
</style>';
	
	
}
	
/**
 * Gets a group of lessons; either all of them (no parameter passed) or those on a given day
 * @param $day    The day to get lessons (-1 = all days, by default)
 * @return Object ($return->start_time, etc.)
 */
function getLessons ($day = ALL_DAYS)
{
	global $wpdb;
	$wpdb->show_errors(true);
	
	$sql = 'SELECT * FROM '.WP_LPLANNER_TABLE;
	if ($day != ALL_DAYS)
	{
		$sql .= ' WHERE day = '.$day;
	}
	$lessons = $wpdb->get_results($sql);
	return $lessons;
}

/**
 * Validates a time for insertion into the SQL database.
 * @param $time The time to be validated
 * @return boolean
 */
function validate_time($time)
{
	$exp = explode(":", $time);
	if (($exp[0] > 23) || ($exp[0] < 0))
			return false;
	if (($exp[1] > 59) || ($exp[1] < 0))
			return false;
	return true;
}

/**
 * Checks to see if a given start/end time on a given day conflicts with a previously
 * scheduled lesson.
 * 
 * @param $start   Lesson start time
 * @param $end     Lesson end time
 * @param $day     Day of the week
 * @param $id      Lesson id, for editing mode
 * @return Returns "false" if no conflict is found, otherwise returns an array of student
 * name, start/end times, and day
 */
function check_conflicts ($new_start, $new_end, $day, $id = -1)
{
	$lessons = getLessons($day);
	
	foreach ($lessons as $lesson)
	{
		if ($lesson->lesson_ID != $id)
		{
			if (
			(is_after($new_end, $lesson->start_time) && is_after($lesson->end_time, $new_end)) ||
			(is_after($new_start, $lesson->start_time) && is_after($lesson->end_time, $new_start)) ||
			(is_after($lesson->start_time, $new_start) && is_after($new_end, $lesson->end_time)) ||
			($new_start == substr($lesson->start_time, 0, 5)) ||
			($new_end == substr($lesson->end_time, 0, 5)))
			{
				return $lesson;
			}
		}
	}
	// no conflicts found
	return false;
}

/**
 * Checks to see if the first time is after the second time
 * @param $t1 Time 1
 * @param $t2 Time 2
 * @return boolean True if $t1 is after $t2
 */
function is_after ($t1, $t2)
{
	$t1e = explode(":", $t1);
	$t2e = explode(":", $t2);
	if (($t1e[0] > $t2e[0]) || (($t1e[0] == $t2e[0]) && ($t1e[1] > $t2e[1])))
	{
		return true;
	}
	else
	{
		return false;
	}
}


/**
 * Return the value of a configuration switch (in WP_LPLANNER_CONFIG_TABLE)
 * @param $item The config option to check
 * @return $value Value of the configuration option
 */
function get_config ($item)
{
	global $wpdb;
	
	$value = $wpdb->get_var('SELECT config_value FROM ' . WP_LPLANNER_CONFIG_TABLE . 
	                        ' WHERE config_item = \'' . $item . '\'' );
	
	return $value;
}

function set_config ($item, $value)
{
	global $wpdb;
	
	$wpdb->get_results("UPDATE " . WP_LPLANNER_CONFIG_TABLE . " SET config_value= '" . $value . "' WHERE config_item='" . $item . "'");
}

/**
 * Checks to see if this is the first time LP has been activated;
 * creates tables, etc if so
 * @return
 */
function lp_check ()
{
	global $wpdb;
	
	$new_install = false;
	$lp_base_table = false;
	$lp_config_table = false;
	$current_version = false;
	
	// Search for the base table and config table in the SQL database.
	$tables = $wpdb->get_results("show tables;");
	foreach ($tables as $table)
	{
		foreach ($table as $value)
		{
			if ( $value == WP_LPLANNER_TABLE )
			{
				$lp_base_table = true;
			}
			if ( $value == WP_LPLANNER_CONFIG_TABLE)
			{
				$lp_config_table = true;
				
				// check to see if we're running the current version
				$ver_number = get_config("version");
				if (($ver_number == WP_LPLANNER_VER))
				{
					$current_version = true;
				}
			}
		}
	}
	
	if ( $lp_base_table == false && $lp_config_table == false )
	{
		// no base table and no config table means it's a fresh install
		$new_install = true;
	}
	elseif ( ( $lp_base_table == false && $lp_config_table == true) ||
	         ( $lp_base_table == true  && $lp_config_table == false) )
	         {
	         	// corrupted installation!
	         }
	elseif ( $lp_base_table == true && $lp_config_table == true && $current_version)
	{
		// we are good to go
	}
	
	// New installation
	if ( $new_install == true )
	{
		// set up our (default) style sheet
		$stylesheet_default = 
'@CHARSET "ISO-8859-1";

.planner_table {
border: none;
border-collapse: collapse;
margin-left: 1%;
margin-right: 1%;
width: 98%;
padding: 0;
}

.planner_header_cell {
	width: 91%;
	padding: 0;
}

.planner_header {
width: 100%;
font-weight: bold;
text-align: center;
	}

.planner_time_header {
	width: 9%;
}

.planner_padder {
	height: 10px;
}

.planner_time_cell {
	border-bottom: 1px solid transparent;
	text-align: right;
	height: 10px;
	padding: 0;
	padding-right: 1px;
	vertical-align: top;
	overflow: visible;
}
.planner_time_text {
	position: relative;
	height: 100%;
	top: -1em;
	font-size: 0.75em;
	float: right;
}

.planner_time_column {
	text-align: right;
	font-size: 0.8em;
}

.planner_day_header {
   text-align: center;
   width:13%;
   height:25px;
   font-size:0.8em;
}

.planner_outer {
	width: auto;
	border: 1px solid #000000;
	padding: 0;
	vertical-align: top;
}
.planner_time_outer {
	width: auto;
	margin-top: 1px;
	padding: 0;
}

.planner_day_table {
	width: 100%;
	padding-left: 0;
	padding-right: 0;
}


.planner_empty_spot {
	height: 10px;
	font-size: 0.0em;
	border-top: 1px solid #BBBBBB;
	padding: 0;
	margin: 0;
}

.planner_unavailable {
	height: 10px;
	padding: 0;
}

.planner_cell_top {
	border-top: 1px solid #999999;
}

.planner_cell_mid {
	border-top: 1px solid #999999;
	border-bottom: 1px solid #999999;
}

.planner_other_unavailable_cell {
	border-top: 1px solid #999999;	
}

.planner_dayname {
font-weight: bold;
}';
		
		
		// Create the lessons table
		$lp_create_query = "CREATE TABLE " . WP_LPLANNER_TABLE . " ( 
		lesson_ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		day INT NOT NULL,
		start_time TIME NOT NULL,
		end_time TIME NOT NULL,
		student_name VARCHAR(30) NOT NULL,
		student_mail VARCHAR(60),
		PRIMARY KEY (lesson_ID) )";
		
		$wpdb->get_results($lp_create_query);
		
		// Create the configuration table
		$lp_create_query = "CREATE TABLE " . WP_LPLANNER_CONFIG_TABLE . " ( 
		config_item VARCHAR(30) NOT NULL, 
		config_value TEXT NOT NULL,
		config_desc TEXT NOT NULL,
		PRIMARY KEY (config_item)
		) ";
		
		$wpdb->get_results($lp_create_query);
		
		// Insert the configuration options
		// version/meta config settings
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='version', config_value='1.0',
		config_desc='Version number'";
		$wpdb->get_results($lp_insert_query);
		
		// privacy settings
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='show_student_names', config_value='0',
		config_desc='Option to show student names, 0 for no, 1 for yes'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='show_student_mail', config_value='0',
		config_desc='Option to show student e-mails, 0 for no, 1 for yes'";
		$wpdb->get_results($lp_insert_query);
		
		// display settings
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='display_start_time', config_value='auto',
		config_desc='Schedule display start time'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='display_end_time', config_value='auto',
		config_desc='Schedule display end time'";
		$wpdb->get_results($lp_insert_query);				
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='colors', 
		config_value='#F8C8D0,#F8F080,#99FFD8,#98F880,#C8E8C0',
		config_desc='Colors to be used for the planner display'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='padding_cells', config_value='1',
		config_desc='Number of padding cells before the first time'";
		$wpdb->get_results($lp_insert_query);	
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='cell_height', config_value='10',
		config_desc='Height of each cell, in pixels'";
		$wpdb->get_results($lp_insert_query);	
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='block_time', config_value='15',
		config_desc='Value of each cell (ie 15 minutes)'";
		$wpdb->get_results($lp_insert_query);	
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='time_display_increment', config_value='60',
		config_desc='How often to display a time on the side, in minutes'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='time_format', config_value='1',
		config_desc='Time format, 0 for 24hour, 1 for ampm'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='unavailable_color', config_value='#DFDFDF',
		config_desc='Color for unavailable times'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='bh_bgcolor', config_value='#E4EBE3',
		config_desc='Background color of the big planner header'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='bh_border', config_value='#D6DED5',
		config_desc='Border color of the big planner header'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='day_bgcolor', config_value='#EBF2EA',
		config_desc='Background color of each day cell'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='day_border', config_value='#DFE6DE',
		config_desc='Border color of the each day cell'";
		$wpdb->get_results($lp_insert_query);
		
		
		// jargon config
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='lesson', config_value='appointment',
		config_desc='What to call lessons'";
		$wpdb->get_results($lp_insert_query);
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='student', config_value='appointment',
		config_desc='What to call students'";
		$wpdb->get_results($lp_insert_query);
				
		// the big one, the stylesheet
		$lp_insert_query = "INSERT INTO ".WP_LPLANNER_CONFIG_TABLE." SET config_item='css', config_value='$stylesheet_default',
		config_desc='Stylesheet'";
		$wpdb->get_results($lp_insert_query);
		
		
	}
	
}

/**
 * Returns the colloquial term for a 'student'
 * @param $cap   Capitalize first letter? (defaults to false)
 * @return String with term for 'student'
 */
function student($cap = false)
{
	global $jargon_student;
	if (!isset($jargon_student))
	{
		$jargon_student = get_config('student');
	}
	if ($cap)
	{
		return ucfirst($jargon_student);
	}
	else
	{
		return $jargon_student;
	}
}

/**
 * Returns the colloquial term for a 'lesson'
 * @param $cap   Capitalize first letter? (defaults to false)
 * @return String with term for 'lesson'
 */
function lesson($cap = false)
{
	global $jargon_lesson;
	if (!isset($jargon_lesson))
	{
		$jargon_lesson = get_config('lesson');
	}
	if ($cap)
	{
		return ucfirst($jargon_lesson);
	}
	else
	{
		return $jargon_lesson;
	}
}




// Planner Administrative Options
function planner_menu()
{
	global $wpdb;
	
	// check for LP installation
	lp_check();
	
	// only allow admins to mess with the schedule
	$allowed_group = 'manage_options';
	
  	if (function_exists('add_menu_page')) 
     {
       add_menu_page('Planner', 'Planner', $allowed_group, 'lessons', 'edit_lessons');
     }
    // add submenus
   if (function_exists('add_submenu_page')) 
     {
       add_submenu_page('lessons', 'Manage Planner', 'Manage Planner', $allowed_group, 'lessons', 'edit_lessons');
       //add_action( "admin_head", 'calendar_add_javascript' );
       // Note only admin can change calendar options
       add_submenu_page('lessons', 'Planner Options', 'Planner Options', 'manage_options', 'planner-config', 'edit_lessons_config');
     }
	
}

/**
 * For the configuration/input forms, displays a time field for users to pick a time. 
 * 
 * @param $name           Name of the input (for PHP $_POST)
 * @param $selected_time  Currently selected time
 * @return 				  HTML code that outputs an input box with the time
 */
function time_form_display ($name, $selected_time)
{
	$pm = false;
	if ($selected_time == 'auto')
	{
		$cur_time = NULL;
	}
	else
	{
		$cur_time = explode(":", $selected_time);
		if ($cur_time[0] > 12)
		{
			$cur_time[0] -= 12;
			$pm = true;
		}
	}
	
	// hour selector
	$body = '<select name="'.$name.'_hour" id="'.$name.'_hour">';
	for ($i = 1; $i <= 12; $i++)
	{
		$body .= '<option value="'.$i.'"';
		if ($i == $cur_time[0])
			$body .= ' selected="selected"';
		$body .= '>'.$i.'</option>';
	}
	$body .= '</select>';
	
	// minute selector
	$body .= '<select name="'.$name.'_minute" id="'.$name.'_minute">';
	for ($i = 0; $i < 60; $i += 5)
	{
		$body .= '<option value="'.$i.'"';
		if ($i == $cur_time[1] || (($i == 0) && ($cur_time[1] == '00')))
			$body .= ' selected="selected"';
		$body .= '>';
		if ($i < 10)
			$body .= '0'.$i;
		else
			$body .= $i;
		$body .= '</option>';
	}
	$body .= '</select>';
	
	// am/pm switch
	$body .= '<select name="'.$name.'_ampm" id="'.$name.'_ampm">
	<option value="am">AM</option>
	<option value="pm"';
	if ($pm)
	{
		$body .= ' selected="selected"';
	}
	$body .= '>PM</option>';
	
	return $body;
}

/**
 * Converts a user-submitted time to 24-hour HH:MM format used internally
 * @param $h			Hour (1-12)
 * @param $m			Minute (0, 15, 30, 45, 60, for now)
 * @param $ampm			AMPM ('am' or 'pm')
 * @return unknown_type
 */
function assemble_time($h, $m, $ampm)
{
	if ($m < 10)
		$m = '0'.$m;
	if (($h == 12) && ($ampm == 'am')) // something scheduled for midnight? really?
	{
		$h = '00';
	}
	elseif (($h < 10) && ($ampm == 'am')) // add leading zero to times earlier than 10am
	{
		$h = '0'.$h;
	}
	elseif (($ampm == 'pm') && ($h != 12)) // add 12 hours to pm times other than noon
	{
		$h += 12;
	}
	
	return $h.':'.$m;
}

/**
 * Function that displays the configuration for the Lesson Planner.
 * 
 */
function edit_lessons_config ()
{
	global $wpdb;
	
	// check/install, etc
	lp_check();
	
	if (isset($_POST['new_tdi'])) // if this is set the form has been submitted
	{
		$new_tdi = $_POST['new_tdi'];			
		$new_padding = (int)$_POST['new_padding'];
		
		// assemble the new colors
		
		
		if (isset($_POST['start_time_auto'])) // start time set to automatic
		{
			set_config('display_start_time', 'auto');
		}
		else // assemble start time then add to database
		{
			$new_start = assemble_time($_POST['new_display_start_time_hour'], $_POST['new_display_start_time_minute'], $_POST['new_display_start_time_ampm']);
			set_config('display_start_time', $new_start);
		}
		if (isset($_POST['end_time_auto'])) // end time set to automatic
		{
			set_config('display_end_time', 'auto');
		}
		else // assemble start time then add to database
		{
			$new_end = assemble_time($_POST['new_display_end_time_hour'], $_POST['new_display_end_time_minute'], $_POST['new_display_end_time_ampm']);
			if ((get_config('display_start_time') == 'auto') || is_after($new_end, get_config('display_start_time'))) // make sure the end time is after the current start time
			{
				set_config('display_end_time', $new_end);
			}
			else
			{
				echo '<font style="color: red; font-size=larger">Error: end time is before start time</font>';
			}
		}
		$new_colors = '';
		for ($i = 0; $i <= $_POST['num_colors']; $i++)
		{
			if (isset($_POST['color_value'.$i]) && (strlen($_POST['color_value'.$i]) == 6))
			{
				$new_colors .= '#'.$_POST['color_value'.$i].',';
			}	
		}
		if (strlen($new_colors) > 0) // check to make sure we still have colors
		{
			// get rid of trailing comma
		
			set_config('colors', substr($new_colors, 0, strlen($new_colors) - 1));
		}
		
		set_config('time_display_increment', $new_tdi);
		set_config('padding_cells', $new_padding);
		set_config('show_student_names', $_POST['new_show_student_names']);
		set_config('show_student_mail', $_POST['new_show_student_mail']);
		set_config('block_time', $_POST['new_block_time']);
		set_config('time_format', $_POST['new_time_format']);
		if (strlen($_POST['color_valueu']) != 6) // problem with the unavailable slot color
		{
			set_config('unavailable_color', '#BBBBBB');
		}
		else
		{
			set_config('unavailable_color', '#'.$_POST['color_valueu']);
		}
		if (strlen($_POST['color_valuebhbg']) != 6)
		{
			set_config('bh_bgcolor', '#E4EBE3');
		}
		else
		{
			set_config('bh_bgcolor', '#'.$_POST['color_valuebhbg']);
		}
		if (strlen($_POST['color_valuebhborder']) != 6)
		{
			set_config('bh_border', '#D6DED5');
		}
		else
		{
			set_config('bh_border', '#'.$_POST['color_valuebhborder']);
		}
		if (strlen($_POST['color_valuedaybg']) != 6)
		{
			set_config('day_bgcolor', '#EBF2EA');
		}
		else
		{
			set_config('day_bgcolor', '#'.$_POST['color_valuedaybg']);
		}	
		if (strlen($_POST['color_valuedayborder']) != 6)
		{
			set_config('day_border', '#DFE6DE');
		}
		else
		{
			set_config('day_border', '#'.$_POST['color_valuedayborder']);
		}	
		
		
		
		set_config('student', $_POST['new_jargon_student']);
		set_config('lesson', $_POST['new_jargon_lesson']);
	}
	
	// grab configurable values from the database
	$current_tdi = get_config('time_display_increment');
	$current_padding_cells = get_config('padding_cells');
	$current_display_start_time = get_config('display_start_time');
	$current_display_end_time = get_config('display_end_time');
	$current_show_student_names = get_config('show_student_names');
	$current_show_student_mail = get_config('show_student_mail');
	$current_colorlist = explode(",", get_config('colors'));
	$current_unavailable_color = get_config('unavailable_color');
	$current_time_format = get_config('time_format');
	$current_block_time = get_config('block_time');
	$current_jargon_student = get_config('student');
	$current_jargon_lesson = get_config('lesson');
	$current_bh_bgcolor = get_config('bh_bgcolor');
	$current_bh_border = get_config('bh_border');
	$current_day_bgcolor = get_config('day_bgcolor');
	$current_day_border= get_config('day_border');
	
	
	// for values with set options, set up their values here
	$allowable_tdi_values = array(15, 30, 60);
	$allowable_block_time_values = array(5, 10, 15, 30, 60);
	
	?>
	
	<h2>Planner Options</h2>
	<form name="planner_option_form" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=planner-config">
		<table style="border-collapse: collapse; border: 1px solid #000000">
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				What to call appointment/events<br>
				<font style="font-size: 0.8em">e.g. "class", "lesson", "meeting"</font>
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<input type="text" name="new_jargon_lesson" value="<?php echo $current_jargon_lesson ?>" />
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				What to call people involved with events<br>
				<font style="font-size: 0.8em">e.g. "professor", "student", "employee"</font>
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<input type="text" name="new_jargon_student" value="<?php echo $current_jargon_student ?>" />
				</td>
			</tr>
		
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Show <?php echo student() ?> names publically<br>
				<font style="font-size: 0.8em">Names will always display on admin pages</font>
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<select name="new_show_student_names">
				<option value="0" <?php if ($current_show_student_names == 0) echo 'selected="selected"'; ?>>No</option>
				<option value="1" <?php if ($current_show_student_names == 1) echo 'selected="selected"'; ?>>Yes</option>
				</select>
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Show <?php echo student() ?> contact publically<br>
				<font style="font-size: 0.8em">Contact info will always display on admin pages</font>
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<select name="new_show_student_mail">
				<option value="0" <?php if ($current_show_student_mail == 0) echo 'selected="selected"'; ?>>No</option>
				<option value="1" <?php if ($current_show_student_mail == 1) echo 'selected="selected"'; ?>>Yes</option>
				</select>
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Time format
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<select name="new_time_format">
				<option value="0" <?php if ($current_time_format == 0) echo 'selected="selected"'; ?>>24-Hour</option>
				<option value="1" <?php if ($current_time_format == 1) echo 'selected="selected"'; ?>>AM/PM</option>
				</select>
				</td>
			</tr>			
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				How often to display time on the side
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<select name="new_tdi">
				<?php
				for ($i = 0; $i < count($allowable_tdi_values); $i++)
				{
					echo '<option value="'.$allowable_tdi_values[$i].'"';
					if ($allowable_tdi_values[$i] == $current_tdi)
					{
						echo ' selected="selected"';
					}
					echo '>'.$allowable_tdi_values[$i].'</option>';	
				}
				?>
				</select>
				minutes
				</td>			
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Length of each time slot
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<select name="new_block_time">
				<?php
				for ($i = 0; $i < count($allowable_block_time_values); $i++)
				{
					echo '<option value="'.$allowable_block_time_values[$i].'"';
					if ($allowable_block_time_values[$i] == $current_block_time)
					{
						echo ' selected="selected"';
					}
					echo '>'.$allowable_block_time_values[$i].'</option>';	
				}
				?>
				</select>
				minutes
				</td>			
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Number of padding cells
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<input name="new_padding" style="width: 4em" type="text" maxlength="2" value="<?php echo $current_padding_cells ?>" />
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Starting time for schedule display
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<input type="checkbox" name="start_time_auto" <?php if ($current_display_start_time == 'auto') echo 'checked="checked"' ?> />Automatic<br/>
				<?php echo time_form_display("new_display_start_time", $current_display_start_time) ?>
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Ending time for schedule display
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<input type="checkbox" name="end_time_auto" <?php if ($current_display_end_time == 'auto') echo 'checked="checked"' ?> />Automatic<br/>
				<?php echo time_form_display("new_display_end_time", $current_display_end_time) ?>
				</td>
			</tr>			
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Colors used for <?php echo lesson()?>s
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
					<table style="border-collapse: collapse; border: 1px solid #666666; width=100%">
						<tbody id="colortable">
					<?php
					for ($i = 0; $i < count($current_colorlist); $i++)
					{
						?>
						<tr>
							<td style="padding: 1px 1px 1px 3px; width: auto">
							Hex code: <input style="width: 6em" type="text" maxlength="6"
							name="color_value<?php echo $i ?>" id="color_value<?php echo $i?>"
							onblur="change_color( <?php echo $i ?> , this.value)"
							value="<?php echo substr($current_colorlist[$i], 1, 6)?>" />
							</td>
							<td style="padding: 3px; background-color: <?php echo $current_colorlist[$i] ?>; width: 50px"
							id="color_cell<?php echo $i ?>">
							</td>
						</tr>
						<?php
					}
					?>
						</tbody>
					</table>
					<input type="hidden" name="num_colors" id="num_colors" value="<?php echo (count($current_colorlist) - 1)?>" />
					<br>
					<button type="button" onclick="add_color()">Add a new color</button><br/>
					<font style="font-size: 0.8em">Blank values will be ignored</font>
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Color for an unavailable time slot
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
					<table style="border-collapse: collapse; border: 1px solid #666666; width=100%">
						<tr>
							<td style="padding: 1px 1px 1px 3px; width: auto">
							Hex code: <input style="width: 6em" type="text" maxlength="6"
							name="color_valueu" id="color_valueu"
							onblur="change_color( 'u' , this.value)"
							value="<?php echo substr($current_unavailable_color, 1, 6) ?>" />
							</td>
							<td style="padding: 3px; background-color: <?php echo $current_unavailable_color ?>; width: 50px"
							id="color_cellu">
							</td>
						</tr>
					</table>
				</td>
			</tr>		
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Colors for the main planner header
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
					<table style="border-collapse: collapse; border: 1px solid #666666; width=100%">
						<tr>
							<td style="padding: 1px 1px 1px 3px; width: auto">
							Background color hex code: <input style="width: 6em" type="text" maxlength="6"
							name="color_valuebhbg" id="color_valuebhbg"
							onblur="change_color( 'bhbg' , this.value)"
							value="<?php echo substr($current_bh_bgcolor, 1, 6) ?>" />
							</td>
							<td style="padding: 3px; background-color: <?php echo $current_bh_bgcolor ?>; width: 50px"
							id="color_cellbhbg">
							</td>
						</tr>
						<tr>
							<td style="padding: 1px 1px 1px 3px; width: auto">
							Border color hex code: <input style="width: 6em" type="text" maxlength="6"
							name="color_valuebhborder" id="color_valuebhborder"
							onblur="change_color( 'bhborder' , this.value)"
							value="<?php echo substr($current_bh_border, 1, 6) ?>" />
							</td>
							<td style="padding: 3px; background-color: <?php echo $current_bh_border ?>; width: 50px"
							id="color_cellbhborder">
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Colors for each day header
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
					<table style="border-collapse: collapse; border: 1px solid #666666; width=100%">
						<tr>
							<td style="padding: 1px 1px 1px 3px; width: auto">
							Background color hex code: <input style="width: 6em" type="text" maxlength="6"
							name="color_valuedaybg" id="color_valuedaybg"
							onblur="change_color( 'daybg' , this.value)"
							value="<?php echo substr($current_day_bgcolor, 1, 6) ?>" />
							</td>
							<td style="padding: 3px; background-color: <?php echo $current_day_bgcolor ?>; width: 50px"
							id="color_celldaybg">
							</td>
						</tr>
						<tr>
							<td style="padding: 1px 1px 1px 3px; width: auto">
							Border color hex code: <input style="width: 6em" type="text" maxlength="6"
							name="color_valuedayborder" id="color_valuedayborder"
							onblur="change_color( 'dayborder' , this.value)"
							value="<?php echo substr($current_day_border, 1, 6) ?>" />
							</td>
							<td style="padding: 3px; background-color: <?php echo $current_day_border ?>; width: 50px"
							id="color_celldayborder">
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td style="text-align: center; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;" colspan="2">
				<input type="submit" value="Save Changes" />
				</td>
			</tr>
		</table>
	</form>
	
	<?php 
	
	
	
}


// The page rendered to change lessons scheduled.
function edit_lessons ()
{
	 global $current_user, $wpdb, $users_entries;
	 
	 // if this is first run, create the database
	 
	 lp_check();
	 
	 // What are we doing here? Let's find out.  Get action/lesson id
	 $action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
	 $lesson_id = !empty($_REQUEST['lesson_id']) ? $_REQUEST['lesson_id'] : '';
	 
	 
	 
	 // Add a lesson to the calendar
	 if ($action == 'add')
	 {
	 	if (isset($_REQUEST['delete_lesson']))
	 	{
	 		echo "<font style=\"font-size: larger; color: red\">Error: no ".lesson()." selected for deletion</font>";
	 	}
	 	else
	 	{
		 	// assemble the start and end times
		 	$start_time = assemble_time($_POST['start_time_hour'], $_POST['start_time_minute'], $_POST['start_time_ampm']);
		 	$end_time = assemble_time($_POST['end_time_hour'], $_POST['end_time_minute'], $_POST['end_time_ampm']);
		 	
	 		if (isset($_POST['is_unavailable']))
		 	{
		 		$result = newLesson('PLANNER_UNAVAILABLE', '', $start_time, $end_time, $_REQUEST['day']);
		 	}
		 	else
		 	{
		 		$result = newLesson($_REQUEST["student_name"], $_REQUEST["student_mail"], $start_time, $end_time,
		 	           	$_REQUEST["day"]);
		 	}

		 	if ($result === 1)
		 	{
		 		echo "<font style=\"font-size: larger; color: green\">Successfully added ".lesson()." to database.</font>";	
		 	}
		 	else
		 	{
		 		if (isset($result->start_time))
		 		{
		 			echo "<font style=\"font-size: larger; color: red\">Error: Conflicting ".lesson().": ".$result->student_name." at ".$result->start_time."</font>";	
		 		}
		 		elseif ($result == 2)
		 		{
		 			echo "<font style=\"font-size: larger; color: red\">Error: Invalid start time</font>";
		 		}
		 		elseif ($result == 3)
		 		{
		 			echo "<font style=\"font-size: larger; color: red\">Error: Invalid end time</font>";		 	
		 		}
		 		elseif ($result == 4)
		 		{
		 			echo "<font style=\"font-size: larger; color: red\">Error: Chosen start time is after chosen end time</font>";
		 		}
		 		elseif ($result == 5)
		 		{
		 			echo "<font style=\"font-size: larger; color: red\">Error: invalid day selected</font>";
		 		}
		 	}
	 	}
	 }
	 
	 elseif ($action == 'edit')
	 {
	 	if (isset($_REQUEST['delete_lesson']))
	 	{
	 		global $wpdb;
	 		$delete_sql = 'DELETE FROM '.WP_LPLANNER_TABLE.' WHERE lesson_ID = '.$_REQUEST['lesson_id'].' LIMIT 1';
	 		$wpdb->get_results($delete_sql);
	 		echo '<font style="font-size: larger; color: green">Successfully removed '.lesson().' from database.</font>';
	 	}
	 	else
	 	{
		 	// assemble the start and end times
		 	$start_time = assemble_time($_POST['start_time_hour'], $_POST['start_time_minute'], $_POST['start_time_ampm']);
		 	$end_time = assemble_time($_POST['end_time_hour'], $_POST['end_time_minute'], $_POST['end_time_ampm']);
		 	
		 	// check for conflicts/validity
		 	if (!is_after($end_time, $start_time))
		 	{
		 		echo "<font style=\"font-size: larger; color: red\">Error: Chosen start time is after chosen end time</font>";
		 	}
		 	elseif (($conflict = check_conflicts($start_time, $end_time, $_POST['day'], $_POST['lesson_id'])) !== false)
		 	{
		 		echo $_POST['lesson_id'];
		 		echo "<font style=\"font-size: larger; color: red\">Error: conflicting ".lesson().": ".$conflict->student_name." at ".$conflict->start_time."</font>";
		 	}
		 	else
		 	{
		 		if (isset($_POST['is_unavailable']))
		 		{
		 			$name = 'PLANNER_UNAVAILABLE';
		 		}
		 		else
		 		{
		 			$name = $_REQUEST['student_name'];
		 		}
			 	$update_sql = 'UPDATE '.WP_LPLANNER_TABLE.' SET day=\''.$_REQUEST['day'].'\', start_time=\''.$start_time.
			 		'\', end_time=\''.$end_time.'\', student_name=\''.$name.'\'';
			 	if (isset($_REQUEST['student_mail']) && (strlen($_REQUEST['student_mail']) > 0))
			 	{
			 		$update_sql .= ', student_mail=\''.$_REQUEST['student_mail'].'\'';
			 	}
			 	$update_sql .= ' WHERE lesson_id='.$_REQUEST['lesson_id'];
			 	
			 	
			 	global $wpdb;
			 	$wpdb->get_results($update_sql);
			 	
			 	echo 'Successfully modified lesson.';
		 	}
	 	}
	 }
	 
	 
	 ?>
	 <h2>Editing <?php echo lesson(true) ?>s Schedule</h2>
	 
	 <div style="width: 500px"> <?php echo planner(); ?></div>	 
	 <div style="width: 500px; font-size: 0.75em; text-align: center; margin-bottom: 1em">Click on a <?php echo lesson() ?> or open time slot to begin editing.</div>
	 
	 <?php 	 

	 
	 // If nothing else, we're going to be adding a lesson, so show the form
	wp_show_edit_form();

	 
	 
}


// This is the form used to add/edit/etc lessons
function wp_show_edit_form ($mode='add', $lesson_id=false)
{
	?>
	
	<form name="lp_form" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=lessons">
		<input type="hidden" name="action" id="action" value="<?php echo $mode ?>" />
		<input type="hidden" name="lesson_id" id="lesson_id" value="<?php echo $lesson_id ?>" />
		<table style="border-collapse: collapse; border: 1px solid #000000; width: 500px">
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				<?php echo student(true)?> Name:<br />
				<div style="font-size: 0.75em">OR Mark this block of time as unavailable:
				<input type="checkbox" name="is_unavailable" id="is_unavailable" onClick="jsUnavailable()" /></div>
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<input type="text" name="student_name" id="student_name" maxlength="30" />
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				<?php echo student(true)?> Contact (optional):
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<input type="text" name="student_mail" id="student_mail" maxlength="60" />
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				Day:
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<select name="day" id="day">
				<option value="0">Sunday</option>
				<option value="1">Monday</option>
				<option value="2">Tuesday</option>
				<option value="3">Wednesday</option>
				<option value="4">Thursday</option>
				<option value="5">Friday</option>
				<option value="6">Saturday</option>											
				</select>
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				<?php echo lesson(true)?> Start Time (HH:MM):
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;"> 
				<?php echo time_form_display('start_time', 'auto')?>
				</td>
			</tr>
			<tr>
				<td style="text-align: right; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;">
				<?php echo lesson(true)?> End Time (HH:MM): 
				</td>
				<td style="text-align: left; vertical-align: top; border: 1px solid #000000; padding: 1em 1em 1em 2em;">
				<?php echo time_form_display('end_time', 'auto')?>
				</td>
			</tr>
			<tr>
				<td style="text-align: center; vertical-align: top; border: 1px solid #000000; padding: 1em 2em 1em 1em;" colspan="2">
				<input type="submit" id="modifyappointment" value="Add Appointment" /><input type="submit" name="delete_lesson" value="Delete Appointment" />
				</td>
			</tr>
		</table>
	</form>
	
	
	<?php 
}






/**
 * Inserts the planner into a given news post (via the_content hook).
 * 
 * Replaces {PLANNER} with the display code from planner().
 * 
 * @param $content   Content of the news post (automatically passed via WP)
 * @return $content  Content of the news post, updated w/ planner display 
 */
function planner_insert($content)
{
  if (preg_match('{PLANNER}',$content))
    {
      $lp_output = planner();
      $content = str_replace('{PLANNER}',$lp_output,$content);
    }
  return $content;
}

/**
 * Compares two times for the usort custom sorting command.
 * 
 * Compares starting times
 * 
 * @param $a  Time 1
 * @param $b  Time 2
 * @return 1 if $a > $b, 0 if $a = $b, -1 if $a < $b
 */
function time_comp_start($a, $b)
{
	if (is_after($a->start_time, $b->start_time))
	{
		return 1;
	}
	elseif (is_after($b->start_time, $a->start_time))
	{
		return -1;
	}
	else
	{
		return 0;
	}
}
/**
 * Compares two times for the usort custom sorting command.
 * 
 * Compares ending times
 * 
 * @param $a  Time 1
 * @param $b  Time 2
 * @return 1 if $a > $b, 0 if $a = $b, -1 if $a < $b
 */
function time_comp_end($a, $b)
{
	if (is_after($a->end_time, $b->end_time))
	{
		return 1;
	}
	elseif (is_after($b->end_time, $a->end_time))
	{
		return -1;
	}
	else
	{
		return 0;
	}
}



/**
 * Get the number of cells needed between start and end times
 * @param $first  Start time
 * @param $last   End time
 * @return int   Number of cells
 */
function get_num_cells ($first, $last)
{
	if (!($block_time = get_config('block_time')))
	{
		$block_time = BLOCK_TIME;
	}
	
	$bph = 60 / $block_time; // bph = "blocks per hour"
	$fe = explode(":", $first);
	$le = explode(":", $last);
	$sum = 0;
	
	// if $last isn't after $first, return with zero
	if (!is_after($last,$first))
	{
		return 0;
	}
	
	if ($fe[1] != '00')
	{
		while ($fe[1] < 60)
		{
			$fe[1] += $block_time;
			$sum++;
		}
		$fe[0]++;
	}
	if ($le[1] != '00')
	{
		while ($le[1] > 00)
		{
			$le[1] -= $block_time;
			$sum++;
		}
	}
	
	$sum += ($le[0] - $fe[0]) * $bph;
	 
	return $sum;
}

/**
 * Adds a time of amount BLOCK_TIME to the time
 * @param $time   Time to add to
 * @return new time HH:MM
 */
function increment_time ($time)
{
	if (!($block_time = get_config('block_time')))
	{
		$block_time = BLOCK_TIME;
	}
	
	$exp = explode(":", $time);
	$exp[1] += $block_time;
	if ($exp[1] >= '60')
	{
		$exp[0]++;
		$exp[1] -= 60;
		if ($exp[1] == 0)
		{
			$exp[1] = '00';
		}
	}
	$r = $exp[0].':'.$exp[1];
	
	return $r;
}

/**
 * Returns the display of the time column in the planner display.
 * 
 * Don't call this directly!  Use planner()
 * 
 * @param $start    Starting time
 * @param $end      Ending time
 * @return HTML code for displaying planner time column
 */
function planner_time_column ($start, $end, $padding_cells)
{
	if (!($block_time = get_config('block_time')))
	{
		$block_time = BLOCK_TIME;
	}
	if (!($time_display_increment = get_config('time_display_increment')))
	{
		$time_display_increment = TIME_DISPLAY_INCREMENT;
	}
	$start = round_time($start, 'down');
	$end = round_time($end, 'up');
	
	
	$num_cells = get_num_cells($start, $end);
	$time_disp = $time_display_increment / $block_time;
	$counter = $time_disp; // counter for time display
	
	$col = '<td class="planner_time_outer">
	<div class="planner_day_table">';

	// we want the time display to start on a whole number, so go up to
	// nearest hour
	$current_time = $start;
	$first_explode = explode(":", $current_time);
	while ($first_explode[1] != '00')
	{
		$current_time = increment_time($current_time);
		$first_explode = explode(":", $current_time);
	}
	$pad_til_hour = get_num_cells($start, $current_time);
	
	
	// add padding cells
	for ($k = 0; $k < ($padding_cells + $pad_til_hour); $k++)
	{
		$col .= '<div class="planner_time_cell">&nbsp;</div>';
	}

	for ($k = 0; $k < ($num_cells - $pad_til_hour); $k++)
	{
		$col .= '<div class="planner_time_cell">';
		if ($counter == $time_disp)
		{			
			$col .= '<div class="planner_time_text">'.format_time($current_time).'</div>';
			$counter = 1;
		}
		else
		{
			$counter++;
		}
		$col .= '</div>
		';
		$current_time = increment_time($current_time);
	}

	// add padding cells
	for ($k = 0; $k < $padding_cells; $k++)
	{
		$col .= '<div class="planner_time_cell"></div>';
	}
	
	$col .= '</div></td>';
	return $col;
}

/**
 * Get the time format and display accordingly.  0 = 24 hour time, 1 = am/pm
 * @param $t  Time to be formatted
 * @return string containing formatted time
 */
function format_time ($t)
{
	$format = get_config('time_format');
	$te = explode(":", $t);
	if ($format == 0) // 24-hour
	{
		if ((int)$te[0] < 10)
		{
			$te[0] = '0'.(int)$te[0];
		} 		
	}
	else // am/pm
	{
		$te[0] = (int)$te[0]; // remove leading zeroes
		if ($te[0] > 12)
		{
			$te[0] -= 12;
		}
	}
	return $te[0].':'.$te[1];
}

/**
 * Gets the lesson color from the array of colors in the database.
 * @param $num  Lesson number (array index)
 * @return Background color, in hex format, c.f. style="background-color: lesson_colors(1)"
 */
function lesson_colors ($num)
{
	$colorlist = get_config('colors');
	if (isset($colorlist))
	{
		$lesson_colors = explode(",", $colorlist);
	}
	else
	{
		$lesson_colors = array('#F8C8D0', '#F8F080', '#86FFC0', '#98F880', '#C8E8C0');
	}

	while ($num >= count($lesson_colors))
	{
		$num -= count($lesson_colors);
	}
	
	return $lesson_colors[$num];
}

/**
 * Rounds a time to the nearest block size.
 * @param $time	Time to be rounded
 * @param $dir  Direction to be rounded; 'up' or 'down'
 * @return Time, rounded to nearest block size
 */
function round_time ($time, $dir)
{
	if (!($block_time = get_config('block_time')))
	{
		$block_time = BLOCK_TIME;
	}
	$te = explode(":", $time);
	
	// round the minutes down to a multiple of BLOCK_TIME
	if ($dir == 'down')
	{
		$rounded_min = floor($te[1] / $block_time) * $block_time;
	}
	else
	{
		$rounded_min = ceil($te[1] / $block_time) * $block_time;
	}
	if ($rounded_min == 0)
	{
		return $te[0].':00';
	}
	elseif ($roundedmin == 60)
	{
		$te[0]++;
		return $te[0].':00';
	}
	else
	{
		return $te[0].':'.$rounded_min;
	}
}

/**
 * Construct any extras (title/alt text, Javascript) for a table ID
 * @param $lesson  Lesson, as retrieved from the database; OR 'empty'
 * @param $time    Current time, only needed for empty spots
 * @param $day     Current day, only needed for empty spots
 * @return String containing title/javascript attributes
 */
function get_lesson_extras ($lesson, $time = false, $day = false)
{

	$title = 'title="';
	
	if ($lesson == 'empty')
	{
		$title .= 'This time slot is currently empty"';
		if (is_admin())
		{
			$te = explode(":", $time);
			$te[0] = (int)$te[0];
			$title .= ' onClick="jsAddNewLesson(\''.$te[0].'\', \''.$te[1].'\', \''.$day.'\')"';
		}
	}
	elseif ($lesson->student_name == 'PLANNER_UNAVAILABLE')
	{
		$title .= 'This time slot is currently unavailable."';
		$title .= "onClick=\"jsEditLesson('$lesson->lesson_ID','$lesson->day','".substr($lesson->start_time,0,2)."','".substr($lesson->start_time,3,2)."','".substr($lesson->end_time,0,2)."','".substr($lesson->end_time,3,2)."','$lesson->student_name','')\"";
	}
	else
	{	
		if ((get_config('show_student_names') == 1) || is_admin())
		{
			$title .= $lesson->student_name;
			if (((get_config('show_student_mail') == 1) || is_admin()) && (strlen($lesson->student_mail) > 0))
			{
				$title .= ' ('.$lesson->student_mail.')';
			}
		}
		else
		{
			$title .= lesson(true);
		}
		$title .= ' from '.format_time($lesson->start_time).' to '.format_time($lesson->end_time).'"';
		
		if (is_admin())
		{
			$title .= " onClick=\"jsEditLesson('$lesson->lesson_ID','$lesson->day','".substr($lesson->start_time,0,2)."','".substr($lesson->start_time,3,2)."','".substr($lesson->end_time,0,2)."','".substr($lesson->end_time,3,2)."','$lesson->student_name','$lesson->student_mail')\"";	
		}	
	}
	return $title;
}


/**
 * Function for the actual planner display.
 * 
 * Idea is that it is one table, with 7 columns (days of week) + time column, 
 * and many small rows, each representing 15 minute intervals.  Each day column
 * has an individual table in it to make the logic significantly easier.
 * 
 * @return HTML code that displays the lesson schedule
 */
function planner ()
{
	// check if the planner databases are set up
	lp_check();
	
	// set up lesson data
	$lessonArray = getLessons();	
	
	if (!($latest_time = get_config('display_end_time')) || ($latest_time == 'auto'))
	{
		// sort by end_time to find latest global end time
		usort($lessonArray, 'time_comp_end');
		$n = (count($lessonArray) - 1);
		$latest_time = false;
		while (($latest_time === false) && ($n >= 0))
		{
			if ($lessonArray[$n]->student_name != 'PLANNER_UNAVAILABLE')			
				$latest_time = $lessonArray[$n]->end_time;
			else
				$n--;
		}
		if ($latest_time === false)
		{
			$latest_time = '15:00';
		}
	}
	$latest_time = round_time($latest_time, 'up');
		
	usort($lessonArray, 'time_comp_start');
	
	// set the earliest display time
	if (!($earliest_time = get_config('display_start_time')) || ($earliest_time == 'auto'))
	{
		$n = 0;
		$earliest_time = false;
		while (($earliest_time === false) && ($n < count($lessonArray)))
		{		
			if ($lessonArray[$n]->student_name != 'PLANNER_UNAVAILABLE')
			{
				$earliest_time = $lessonArray[$n]->start_time;
			}
			else
			{
				$n++;
			}
		}
		if ($earliest_time === false)
		{
			$earliest_time = '12:00';
		}
	}
	$earliest_time = round_time($earliest_time, 'down');

	// get the number of planning cells
	if (!($padding_cells = get_config('padding_cells')))
	{
		$padding_cells = PADDING_CELLS;
	}
	
	// initialize the lesson counter (for colors)
	$lesson_number = 0;
	
	// initialize "incomplete cell" and "already rounded" bools
	$incomplete_cell = false;
	$already_rounded = false;
	
	// grab day bgcolor/border color values
	$daybg = get_config('day_bgcolor');
	$dayborder = get_config('day_border');
	
	// planner table headers
	$planner_body = '
	<table class="planner_table">
		<tr>
			<td class="planner_time_header"></td>
			<td colspan="7" class="planner_header_cell">
			<table class="planner_header" style="background-color: '.get_config("bh_bgcolor").'; border: 1px solid '.get_config("bh_border").'">
				<tr>
					<td>
					Current '.lesson(true).' Schedule	
					</td>
				</tr>
			</table>
			</td>
		</tr>
		<tr>
			<td class="planner_time_header"></td>
			<td class="planner_day_header" style="background-color: '.$daybg.'; border: 1px solid '.$dayborder.'">Sunday</td>
			<td class="planner_day_header" style="background-color: '.$daybg.'; border: 1px solid '.$dayborder.'">Monday</td>
			<td class="planner_day_header" style="background-color: '.$daybg.'; border: 1px solid '.$dayborder.'">Tuesday</td>
			<td class="planner_day_header" style="background-color: '.$daybg.'; border: 1px solid '.$dayborder.'">Wednesday</td>
			<td class="planner_day_header" style="background-color: '.$daybg.'; border: 1px solid '.$dayborder.'">Thursday</td>
			<td class="planner_day_header" style="background-color: '.$daybg.'; border: 1px solid '.$dayborder.'">Friday</td>
			<td class="planner_day_header" style="background-color: '.$daybg.'; border: 1px solid '.$dayborder.'">Saturday</td>
		</tr>
		<tr>
		';
	// add in the column that displays time on the left
	$planner_body .= planner_time_column ($earliest_time, $latest_time, $padding_cells);
	
	// retrieve color for unavailable slots/padding cells from db
	$unavailable_bgcolor = get_config('unavailable_color');
	
	for ($i = 0; $i < 7; $i++) // we do this for each day of the week
	{	
		$planner_body .= '<td class="planner_outer">
		<div class="planner_day_table" cellpadding="0" cellspacing="0">';
		
		// set the time to earliest lesson time (start of our table)
		$current_time = $earliest_time;
		
		// insert the padding, unavailable time slots at the start
		for ($j = 0; $j < $padding_cells; $j++)
		{
			$planner_body .= '<div class="planner_unavailable';
			if ($j == 0)
				$planner_body .= ' planner_cell_top';
			if ($j == ($padding_cells - 1))
				$planner_body .= ' planner_cell_bottom';
			if ($j > 0 && ($j < ($padding_cells - 1)))
				$planner_body .= ' planner_cell_mid';
			$planner_body .= '" style="background-color: '.$unavailable_bgcolor.'"title="This time slot is currently unavailable."></div>';
		}
		
		foreach ($lessonArray as $lesson)
		{
			
			if (($lesson->day == $i) && (!(is_after($earliest_time, $lesson->end_time))) && (!(is_after($lesson->start_time, $latest_time))))
			{
				// first check if we left the last iteration with an incomplete cell
				if ($incomplete_cell == true)
				{
					$current_time = increment_time($current_time);
					if (is_after($current_time, $lesson->start_time))
					{
						$planner_body .= 
							'<div style="height: 50%; width: 100%; background-color: '.lesson_colors($lesson_number).'" '.get_lesson_extras($lesson).'"></div>';
					}
					else
					{
						$planner_body .= '<div style="height: 50%; width: 100%"></div>';
					}
					$planner_body .= '</div>';
					$incomplete_cell = false;
					$already_rounded = true;
				}
				
				// if the lesson is after the time we are at right now, pad 'till we get there
				if (is_after($lesson->start_time, $current_time))
				{
					$rounded_start_time = round_time($lesson->start_time, 'down');
					$num_cells = get_num_cells($current_time, $rounded_start_time);
					for ($k = 0; $k < $num_cells; $k++)
					{

						$planner_body .=
						'<div class="planner_empty_spot" '.get_lesson_extras('empty', $current_time, $i).'></div>';
						$current_time = increment_time($current_time);
					}
					// check if we have a half-cell here and display it if so
					// but first see if we already completed the half-cell
					if ($already_rounded == true)
					{
						$already_rounded = false;
					}
					elseif ($rounded_start_time != substr($lesson->start_time, 0, 5))
					{
						$planner_body .= '<div class="planner_empty_spot">
						<div style="height: 50%; width: 100%"></div>
						<div style="height: 50%; width: 100%; background-color: '.lesson_colors($lesson_number).'" '.get_lesson_extras($lesson).'"></div>
						</div>';
						$current_time = increment_time($current_time);
					}
				}
				
				// set up the color to be used
				if ($lesson->student_name == 'PLANNER_UNAVAILABLE')
				{
					$bgcolor = $unavailable_bgcolor;
				}
				else
				{
					$bgcolor = lesson_colors($lesson_number);
				}
				
				
				// now output the lesson
					if (is_after($current_time, $lesson->start_time))
					{
						if (is_after($lesson->end_time, $latest_time))
							$n = get_num_cells($current_time, $latest_time);
						else
							$n = get_num_cells($current_time, round_time($lesson->end_time, 'down'));
					}
					else
					{
						if (is_after($lesson->end_time, $latest_time))
							$n = get_num_cells(round_time($lesson->start_time, 'up'), $latest_time);
						else 
							$n = get_num_cells(round_time($lesson->start_time, 'up'), round_time($lesson->end_time, 'down'));	
					}
					for ($k = 0; $k < $n; $k++)
					{
						if ($lesson->student_name == 'PLANNER_UNAVAILABLE')
						{
							$planner_body .= '<div class="planner_unavailable planner_other_unavailable_cell" style="background-color: '.$bgcolor.'" '.get_lesson_extras($lesson).'"></div>';	
						}
						else
						{
							$planner_body .= '<div class="planner_empty_spot" style="background-color: '.$bgcolor.'" '.get_lesson_extras($lesson).'"></div>';
						}
						$current_time = increment_time($current_time);
					}
					
					// check if we need a half-cell after the lesson; this will be incomplete & filled out
					// in the next iteration
					if ((round_time($lesson->end_time, 'down') != substr($lesson->end_time, 0, 5)) && ($n == get_num_cells(round_time($lesson->start_time, 'up'), round_time($lesson->end_time, 'down'))))
					{
						$planner_body .= '<div class="planner_empty_spot">
							<div style="height: 50%; width: 100%; background-color: '.$bgcolor.'" '.get_lesson_extras($lesson).'"></div>';
						$incomplete_cell = true;
					}
				
				if ($lesson->student_name != 'PLANNER_UNAVAILABLE')
					$lesson_number++;				
			}
		}
		// if we left off with an incomplete cell, finish it up
		if ($incomplete_cell == true)
		{
			$planner_body .= '<div style="height: 50%; width: 100%></div></div>';
			$incomplete_cell = false;
		}
		
		if (is_after($latest_time, $current_time))
		{
			$n = get_num_cells($current_time, $latest_time);
			for ($k = 0; $k < $n; $k++)
			{
				$planner_body .= '<div class="planner_empty_spot" '.get_lesson_extras('empty', $current_time, $i).'></div>';
				$current_time = increment_time($current_time);
			}
		}
		
		for ($j = 0; $j < $padding_cells; $j++)
		{
			$planner_body .= '<div class="planner_unavailable';
			if ($j == 0)
				$planner_body .= ' planner_cell_top';
			if ($j > 0 && ($j < ($padding_cells - 1)))
				$planner_body .= ' planner_cell_mid';
			$planner_body .= '" style="background-color: '.$unavailable_bgcolor.'" title="This time slot is currently unavailable."></div>';
		}
		
		$planner_body .= "</div>
		</td>";
	}
	
	$planner_body .= '</tr></table>';
	
	
	return $planner_body;
}



?>