<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) exit();


$mustRemoveData = get_option('ody_events_delete_on_uninstall');
if(!empty($mustRemoveData)){ // αν έχει οριστεί από τον χρήστη να διαγράφονται τα δεδομένα κατά την απεγκατάσταση του plugin...
	global $wpdb;
	$table_name = $wpdb->prefix."ody_events";
	if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") == $table_name){ // μόνο αν υπάρχει το table	
		$wpdb->query("DROP TABLE ".$table_name);
	}
	delete_option("ody_events_delete_on_uninstall");
	delete_option("ody_post_types");
	delete_option("headerBackground");
	delete_option("buttonBackground");
	delete_option("buttonBackgroundHover");
	delete_option("eventsBackground");
	delete_option("eventsListBackground");
	delete_option("widget_ody_calendar"); // remove widget settings
}
?>