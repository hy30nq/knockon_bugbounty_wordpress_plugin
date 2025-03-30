<?php

	$url = wp_get_shortlink($_REQUEST['post']); // non sef url
	// if we want to turn the $url to a sefURL:  get_permalink(url_to_postid($url))
	if(empty($_REQUEST['post'])){
		_e("After submission you 'll be able to add event dates", ODY_EVENTS_TEXT_DOMAIN);
	}else{
		//get existing events
		global $wpdb;
		$table_name = $wpdb->prefix."ody_events";
		$eventDates = $wpdb->get_results($wpdb->prepare("select id, post_id, event_date from $table_name where  post_id=%d group by event_date", $_REQUEST['post']));
	?>
	<script type="text/javascript">
	function addTag(tag, daysOfEvent){
		var tagContainer = jQuery('#eventDates');
		jQuery('#eventDate').val('');
		jQuery('#daysOfEvent').val('1');	
		if(tag != ""){
			if(isTagExists(tag) === false) {
				jQuery.ajax({
					url: '<?php echo ODY_EVENTS_PLUGIN_URL;?>/add_event_dates.php',
					data: { post: "<?php echo $_REQUEST['post'];?>", d: tag, daysOfEvent: daysOfEvent},
					success: function(data) {
						if(data.indexOf("<div class='date_tag'>") != -1) {
							if(tagContainer.text().indexOf("<?php _e("No Data...", ODY_EVENTS_TEXT_DOMAIN);?>") != -1) {
								tagContainer.html("");
								}
							tagContainer.append(data);
						}else{
							if(daysOfEvent == 1) alert("<?php _e("An error occured\\nthe date was not added", ODY_EVENTS_TEXT_DOMAIN);?>");
							if(daysOfEvent > 1) alert("<?php _e("An error occured\\nthe dates were not added", ODY_EVENTS_TEXT_DOMAIN);?>");
						}
					}
				});			
			}else{
				alert("<?php _e("This date already exists", ODY_EVENTS_TEXT_DOMAIN);?>");
			}
		}
	}
	
	function removeTag(elm, d){
		var tagContainer = jQuery('#eventDates');
		jQuery.ajax({
			url: '<?php echo ODY_EVENTS_PLUGIN_URL;?>/remove_event_dates.php',
			data: { post: "<?php echo $_REQUEST['post'];?>", d: d },
			success: function(data) {
				if(data == "ok") {
					jQuery(elm).parent().remove();
					if(tagContainer.html() == ""){
						tagContainer.append("<div class='nodata'><?php _e("No Data...", ODY_EVENTS_TEXT_DOMAIN);?></div>");
					}
				}else{
					alert("<?php _e("An error occured\\nthe date was not removed", ODY_EVENTS_TEXT_DOMAIN);?>");
				}
			}
		});	
	}
	
	function isTagExists(tag){
		var exists = false;
		var content;
		var needle = "<tag>" + tag + "</tag>";
		var content = document.getElementById("eventDates").innerHTML;
			if(content){
				if(content.indexOf(needle) != -1){
					 exists = true;
				}
			}
		return exists;
	}
	</script>
	
	<table>
	<tr>
	<td class="td_label">
	<?php _e("The starting event date is", ODY_EVENTS_TEXT_DOMAIN);?>
	<?php $dpClass = rand(); ?>
	</td>
	<td>
	<input type="text" name="startingDate" id="eventDate" class="<?php echo $dpClass;?>" size="15">
	 <?php _e("and the event lasts for", ODY_EVENTS_TEXT_DOMAIN);?> <input type="text" name="daysOfEvent" id="daysOfEvent" size="3" value="1" onkeyup="this.value=(isNaN(this.value)) ? '1' : this.value;"> <?php _e("days", ODY_EVENTS_TEXT_DOMAIN);?>&nbsp;&nbsp;<input type="button" value="<?php _e("Add", ODY_EVENTS_TEXT_DOMAIN);?>" class="button-primary" onclick="addTag(jQuery('#eventDate').val(), jQuery('#daysOfEvent').val());">
	<script>
	jQuery(document).ready(function() {
		 jQuery('.<?php echo $dpClass;?>').datepicker();
	});
	</script>
	</td>
	</tr>
	</table>
	<div style="text-align:center;"><h5 class="dates_header"><?php _e("Event's dates (dd/mm/yyyy)", ODY_EVENTS_TEXT_DOMAIN);?></h5></div>
	<div id="eventDates">
	<?php
	if(!count($eventDates)){
	?>
	<div class='nodata'><?php _e("No Data...", ODY_EVENTS_TEXT_DOMAIN);?></div>
	<?php
	}
	if($eventDates){
		foreach($eventDates as $eventDate){
			$d = date('d', $eventDate->event_date)."/".date('m', $eventDate->event_date)."/".date('Y', $eventDate->event_date);
			?>
			<div class='date_tag'><a href='javascript:void(0)' onclick='removeTag(this, "<?php echo $d;?>");'></a><tag><?php echo $d;?></tag></div>
			<?php
		}
	}
}
?>
</div>