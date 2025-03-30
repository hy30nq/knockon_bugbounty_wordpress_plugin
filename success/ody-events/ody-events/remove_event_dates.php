<?php 
require("../../../wp-config.php");
global $wpdb;
$table_name = $wpdb->prefix."ody_events";
$post = (int)$_REQUEST['post'];
$d = $_REQUEST['d'];
$d = explode("/", $d);
$day = $d[0];
$month = $d[1];
$year = $d[2];
$timestamp = mktime(0,0,0,$month,$day,$year);
$q = "DELETE FROM $table_name WHERE event_date='$timestamp' and post_id='$post'";
$wpdb->query($q);
echo "ok";
?>