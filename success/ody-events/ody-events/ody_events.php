<?php
/*
Plugin Name: Ody Events
Plugin URI: http://odyssey.webpage.gr/en/pages/wordpress-plugins-ody-events-pro
Description: Adds events functionality to posts.
Author: Notis Fragkopoulos @ korinthorama.gr
Version: 1.0
Author URI: http://odyssey.webpage.gr  
License: GPLv2 or later  
*/

define("ODY_EVENTS_PLUGIN_URL", WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)));
$pro_file = get_include("pro/color_settings.php");
if(is_file($pro_file)) {
	define("ODY_EVENTS_PLUGIN_NAME", "Ody Events Pro");
}else{
	define("ODY_EVENTS_PLUGIN_NAME", "Ody Events");
}
define("ODY_EVENTS_TEXT_DOMAIN", "ody_events");


global $wp_version;
if(!version_compare($wp_version, "3.0", ">=")){
	die("You need Wordpress version 3.0 or greater to run the ".ODY_EVENTS_PLUGIN_NAME." plugin");
}

function ody_events_activate(){
	global $wpdb;
	$table_name = $wpdb->prefix."ody_events";
	if($wpdb->get_var("SHOW TABLES LIKE ".$table_name) != $table_name){ // create table only if not exists
		$sql = "CREATE TABLE ".$table_name." (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`post_id` BIGINT UNSIGNED NOT NULL,
				`event_date` VARCHAR(50) NOT NULL,
				PRIMARY KEY  (`id`))";
		$wpdb->query($sql);
	}	
}
register_activation_hook(__FILE__, "ody_events_activate");

function ody_events_deactivate(){
	delete_option("ody_events_msg"); // we need to delete this option in order to auto create it upon activation
}
register_deactivation_hook(__FILE__, "ody_events_deactivate");

function ody_events_after_activation(){
	if( get_option("ody_events_msg") != "displayed"){ // display message only once
		?>
		<div id="message" class="updated">
		<p><b><?php _e("Please set the <a href='admin.php?page=ody-events-plugin'>plugin's settings</a> before using it.", ODY_EVENTS_TEXT_DOMAIN); ?></b></p>
		</div>
		<?php
	}
	update_option('ody_events_msg', "displayed"); // set value as flag
}
add_action('admin_notices', 'ody_events_after_activation');

function ody_delete_events($post_id){
	global $wpdb;
	$table_name = $wpdb->prefix."ody_events";
	$query="delete from $table_name where post_id='$post_id'";
	$wpdb->query($query);
}
add_action('delete_post', 'ody_delete_events');

function ody_ltd() {
	load_plugin_textdomain( ODY_EVENTS_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action('plugins_loaded', 'ody_ltd');

function ody_events_load_frontend_scripts(){
	wp_enqueue_script("swfobject");
}
add_action("wp_enqueue_scripts", "ody_events_load_frontend_scripts");

function ody_events_load_admin_scripts(){
    wp_enqueue_script( 'farbtastic' );	
	wp_enqueue_script("jquery-ui-datepicker");
    wp_enqueue_style( 'farbtastic' );	
	wp_enqueue_style('css-datepicker', ODY_EVENTS_PLUGIN_URL.'/css/ui-overcast/jquery-ui-1.7.3.custom.css');
}
add_action("admin_enqueue_scripts", "ody_events_load_admin_scripts");

function ody_events_init(){
	register_setting("ody_events_group", "ody_events_delete_on_uninstall");
	register_setting("ody_events_group", "ody_events_msg");
	register_setting("ody_events_group", "ody_post_types");
	register_setting("ody_events_group", "headerBackground");
	register_setting("ody_events_group", "buttonBackground");
	register_setting("ody_events_group", "buttonBackgroundHover");
	register_setting("ody_events_group", "eventsBackground");
	register_setting("ody_events_group", "eventsListBackground");

	// define initial values only if they are not already set up...
	if(!is_array(get_option("ody_post_types"))) update_option('ody_post_types' , array("post"));
	if(!get_option("ody_events_delete_on_uninstall")) update_option("ody_events_delete_on_uninstall" , "1");	
	if(!get_option("headerBackground")) update_option("headerBackground" , "#717576");
	if(!get_option("buttonBackground")) update_option("buttonBackground" , "#0b5b97");
	if(!get_option("buttonBackgroundHover")) update_option("buttonBackgroundHover" , "#48adfa");
	if(!get_option("eventsBackground")) update_option("eventsBackground" , "#1982d1");
	if(!get_option("eventsListBackground")) update_option("eventsListBackground" , "#ffffff");
}
add_action("admin_init", "ody_events_init");

function ody_events_add_admin_styles(){
	wp_enqueue_style("events_css", ODY_EVENTS_PLUGIN_URL."/css/styles.css");
}
add_action( 'admin_print_styles', 'ody_events_add_admin_styles' );

function ody_events_options_page(){
	if(isset($_GET['settings-updated'])){ // if we have form's data
	?>
	<div id="message" class="updated"><p><b><?php _e("The settings have been updated successfully", ODY_EVENTS_TEXT_DOMAIN);?></b></p></div>
	<?php
	}
	?>
	<div class="wrap">
	<?php screen_icon();?>
	<h2><?php echo ODY_EVENTS_PLUGIN_NAME;?></h2>
	<p><strong><?php _e("This plugin adds events functionality to the selected post types", ODY_EVENTS_TEXT_DOMAIN);?></strong></p>
	<form action="options.php" method="post">
	<?php settings_fields('ody_events_group');?>
	<table>
	<?php 
	$pro_file = get_include("pro/color_settings.php");
	if(is_file($pro_file)) {
		@include($pro_file);
	}else{
	?>
	<tr>
	<td colspan="2">
	<a href="javascript:void(0)" onclick="jQuery('#pro_message').toggle()"><?php _e("Need more options?", ODY_EVENTS_TEXT_DOMAIN);?></a><br><br>
	<div id="pro_message">
	<div class="pro_message">
	<h4><a href="http://odyssey.webpage.gr/gr/pages/wordpress-plugins-ody-events-pro?lang=2" target="_blank"><?php _e("Get the pro version!", ODY_EVENTS_TEXT_DOMAIN);?></a></h4>
	<?php _e("The pro version includes", ODY_EVENTS_TEXT_DOMAIN);?>:
	<ul class="pro_message_ul">
	<li><?php _e("Custum color management for the calendar widget", ODY_EVENTS_TEXT_DOMAIN);?></li>
	<li><?php _e("Sizing and positioning capabilities for the widget", ODY_EVENTS_TEXT_DOMAIN);?></li>
	<li><?php _e("A handy shortcode for including the widget in post content", ODY_EVENTS_TEXT_DOMAIN);?></li>
	<li><?php _e("Multilingual capabilities for the calendar widget", ODY_EVENTS_TEXT_DOMAIN);?></li>
	</ul>
	</div>
	</div>
	</td>
	</tr>
	<?php	
	}
	$pt = array("post", "page");
	foreach($pt as $entry){
	?>
	<tr>
	<td style="text-align:right;"><input type="checkbox" name="ody_post_types[]" value="<?php echo esc_attr($entry);?>" <?php if(in_array($entry, get_option("ody_post_types"))) echo " checked";?>></td>
	<td><?php echo __("Enable events for post type", ODY_EVENTS_TEXT_DOMAIN)." <strong>«".$entry."»</strong>";?></td>
	</tr>	
	<?php 
	} 
	?>
				
	<tr>
	<td style="text-align:right;"><input type="checkbox" id="ody_events_delete_on_uninstall" name="ody_events_delete_on_uninstall" value="1" <?php if(get_option("ody_events_delete_on_uninstall")) echo " checked";?>></td>
	<td><?php _e("Remove all plugin's data and settings on uninstall", ODY_EVENTS_TEXT_DOMAIN);?></td>
	</tr>
	<tr>
	<td>&nbsp;</td>
	<td>
	<!--πρέπει να συμπεριλαμβάνουμε σε hidden fields και τις παραμέτρους που δεν σκοπεύουμε να αλλάξουμε για να μην χάνονται οι τιμές τους στο αυτόματο saving με την settings_fields() -->
	<input type="hidden" name="ody_events_msg" value="<?php echo get_option("ody_events_msg");?>">
	<p class="submit"><input class="button-primary" type="submit" name="submitOptions" value="<?php _e("Save Settings", ODY_EVENTS_TEXT_DOMAIN);?>"></p>
	</td>
	</tr>
	</table>
	</form>
	</div>
	<?php
}

add_action('admin_footer', 'load_dynamic_js');
function load_dynamic_js(){
	?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$.datepicker.regional['selectedLanguage'] = {
	closeText: '<?php _e("Close", ODY_EVENTS_TEXT_DOMAIN);?>',
	prevText: '<?php _e("Previous", ODY_EVENTS_TEXT_DOMAIN);?>',
	nextText: '<?php _e("Next", ODY_EVENTS_TEXT_DOMAIN);?>',
	currentText: '<?php _e("Current Month", ODY_EVENTS_TEXT_DOMAIN);?>',
	monthNames: [
	'<?php _e("January", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("February", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("March", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("April", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("May", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("June", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("July", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("August", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("September", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("October", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("November", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("December", ODY_EVENTS_TEXT_DOMAIN);?>'
	],
	monthNamesShort: [
	'<?php _e("Jan", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Feb", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Mar", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Apr", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("May", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Jun", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Jul", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Aug", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Sep", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Oct", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Nov", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Dec", ODY_EVENTS_TEXT_DOMAIN);?>'
	],
	dayNames: [
	'<?php _e("Sunday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Monday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Tuesday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Wednesday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Thursday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Friday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Saturday", ODY_EVENTS_TEXT_DOMAIN);?>'
	],
	dayNamesShort: [
	'<?php _e("Sun", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Mon", ODY_EVENTS_TEXT_DOMAIN);?>'
	,'<?php _e("Tue", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Wed", ODY_EVENTS_TEXT_DOMAIN);?>'
	,'<?php _e("Thu", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Fri", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _e("Sat", ODY_EVENTS_TEXT_DOMAIN);?>'
	],
	dayNamesMin: [
	'<?php _ex("S", "Sort name of Sunday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _ex("M", "Sort name of Monday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _ex("T", "Sort name of Tuesday", ODY_EVENTS_TEXT_DOMAIN);?>'
	,'<?php _ex("W", "Sort name of Wednesday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _ex("T", "Sort name of Thirsday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _ex("F", "Sort name of Friday", ODY_EVENTS_TEXT_DOMAIN);?>',
	'<?php _ex("S", "Sort name of Saturday", ODY_EVENTS_TEXT_DOMAIN);?>'
	],
	weekHeader: '<?php _ex("Week", "Sort name of the word Week", ODY_EVENTS_TEXT_DOMAIN);?>',
	dateFormat: 'dd/mm/yy',
	firstDay: 1,
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['selectedLanguage']);
	$('#ui-datepicker-div').css("display", "block");
	$('#dialog_link, ul#icons li').hover(
	function() { $(this).addClass('ui-state-hover'); }, 
	function() { $(this).removeClass('ui-state-hover'); }
	);
		
	$('.datepicker').each(function(){
		$(this).datepicker();
	});
});
</script>
	
	<?php
}

// create entry in the admin menu
function ody_events_plugin_menu(){
	$pageTitle = ODY_EVENTS_PLUGIN_NAME." Settings"; // title of options page
	$menuTitle = ODY_EVENTS_PLUGIN_NAME; // τίτλος of menu option
	$capabilities = "manage_options"; // user's permissions
	$menuID = "ody-events-plugin"; // a unique id for this menu item
	$function = "ody_events_options_page";

	add_menu_page($pageTitle, $menuTitle, $capabilities, $menuID, $function, ODY_EVENTS_PLUGIN_URL."/images/icon.png");
}
add_action("admin_menu", "ody_events_plugin_menu");

include("events_widget.php"); // include a plugin's widjet
include("admin_icons.php"); // b/w icon turning mouseover colored...

add_action('admin_init','ody_events_meta_init');

function ody_events_meta_init($post_id){
	foreach (get_option('ody_post_types') as $type){
		add_meta_box('ody_events_meta', __("Events management", ODY_EVENTS_TEXT_DOMAIN), 'ody_events_meta_setup', $type, 'normal', 'high');
	}
	add_action('save_post','ody_events_meta_save');
}

function ody_events_meta_setup(){
	global $post;
	$meta = get_post_meta($post->ID,'_ody_events_meta',TRUE);
	// instead of writing HTML here, lets include a seperate php file
	include('events_form.php');
	// create a custom nonce for the verification of submission
	echo '<input type="hidden" name="ody_events_nonce" value="' . wp_create_nonce(__FILE__) . '" />';
}
 
function ody_events_meta_save($post_id){
	if(defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) return;	
	if(!wp_verify_nonce($_POST['ody_events_nonce'],__FILE__)) return $post_id;
	if($_POST['post_type'] == 'page'){
		if(!current_user_can('edit_page', $post_id)) return $post_id;
	}else{
		if(!current_user_can('edit_post', $post_id)) return $post_id;
	}
	$current_data = get_post_meta($post_id, '_ody_events_meta', TRUE);	
	$new_data = $_POST['_ody_events_meta'];
	meta_data_clean($new_data);
	if($current_data){
		if(is_null($new_data)) delete_post_meta($post_id,'_ody_events_meta');
		else update_post_meta($post_id,'_ody_events_meta',$new_data);
	}
	elseif(!is_null($new_data))
	{
		add_post_meta($post_id,'_ody_events_meta',$new_data,TRUE);
	}

	return $post_id;
}

function meta_data_clean(&$arr){
	if(is_array($arr)){
		foreach ($arr as $i => $v){
			if(is_array($arr[$i])){
				meta_data_clean($arr[$i]);
				if(!count($arr[$i])){
					unset($arr[$i]);
				}
			}else{
				if(trim($arr[$i]) == ''){
					unset($arr[$i]);
				}
			}
		}
		if(!count($arr)){
			$arr = NULL;
		}
	}
}

function get_include($filename){
	if(is_admin()){
		return "..".str_replace(home_url(), null, plugins_url())."/".plugin_basename(dirname(__FILE__))."/".$filename;
	}else{
		return "wp-content/plugins/".plugin_basename(dirname(__FILE__))."/".$filename;
	}
}

$pro_file = get_include("pro/shortcode.php");
if(is_file($pro_file)) {
	@include($pro_file);
}

?>