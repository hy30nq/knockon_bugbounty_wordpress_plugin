<?php

/*

A widget consists of one class with four methods

*/

class odyCalendar extends WP_Widget {
	function odyCalendar(){ // constructor with same name as the className
		$widget_options = array(
			'classname' => 'ody-calendar',
			'description' => __("An Iphone style calendar for displaying the events", ODY_EVENTS_TEXT_DOMAIN)
		);
		parent::WP_Widget("ody_calendar", __("Ody Events Calendar", ODY_EVENTS_TEXT_DOMAIN), $widget_options); // unique ID, a localized Title for admin panel widget bar and the options array variable
	}
	
	function widget($args, $instance){ // the $args has widget's system parameters and the $instance has the user's parameters	
		extract($args, EXTR_SKIP); // extract as local vars
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance );
		echo $before_widget;
		$pro_file = get_include("pro/widget_settings.php");
		if(is_file($pro_file)) {
			@include($pro_file);
		}else{	
			$calendarWidth = 270;
			$margin = -20;
			$calendarHeight = (int)(167*($calendarWidth/100));
		}
		$flashID = 	"ody_calendar".rand();
		?>
		<div id="<?php echo $flashID;?>"></div>
		<script type="text/javascript">
			var flashvars = {};
			flashvars.calendarTitle="<?php echo $title;?>";
			flashvars.path="<?php echo ODY_EVENTS_PLUGIN_URL;?>/";	
			<?php 
			$wpml =(ICL_LANGUAGE_CODE != "ICL_LANGUAGE_CODE"); // detect wpml		
			if($wpml){
			?>
			flashvars.lang = "<?php echo ICL_LANGUAGE_CODE;?>";	
			<?php
			}
			?>			
		<?php		
		$pro_file = get_include("pro/flash_settings.php");
		if(is_file($pro_file)) {
			@include($pro_file);
		}
		?>
			var attributes = {};
			attributes.wmode = "transparent";
			attributes.id = "<?php echo $flashID;?>";
			swfobject.embedSWF("<?php echo ODY_EVENTS_PLUGIN_URL;?>/calendar.swf", "<?php echo $flashID;?>", "<?php echo $calendarWidth;?>", "<?php echo $calendarHeight;?>", "9", "expressInstall.swf", flashvars, attributes);
		</script>
		<style>
		object#<?php echo $flashID;?>{
			max-width:<?php echo $calendarWidth;?>px;
			height: <?php echo $calendarHeight;?>px;
			margin-left: <?php echo $margin;?>px;
		}
		</style>		
		<?php
		echo $after_widget;
	}
	
	function form($instance){
		?>
		<div style="text-align:right">
		<div style="text-align:left">
		<label for "<?php echo $this->get_field_id('title');?>"><?php _e("Title", ODY_EVENTS_TEXT_DOMAIN);?> : </label>
		</div>
		<input class="widefat" type="text" id="<?php $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" value="<?php echo esc_attr($instance["title"]);?>">
		<?php
		$pro_file = get_include("pro/widget_form.php");
		if(is_file($pro_file)) {
			@include($pro_file);
		}
		?>
		</div>
		<?php		
	}
} // end class

// outside the class we register the widget
function ody_calendar_init(){
	register_widget("odyCalendar");
}
add_action("widgets_init", "ody_calendar_init");

?>