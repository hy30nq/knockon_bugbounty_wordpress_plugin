<?php 
require("../../../wp-config.php");
global $wpdb;
$table_name = $wpdb->prefix."ody_events";
$post = (int)$_REQUEST['post'];
$d = $_REQUEST['d'];
$registeredDates = array();
$formatedDate = $d;
$daysOfEvent = (is_numeric($_REQUEST['daysOfEvent'])) ? abs($_REQUEST['daysOfEvent']) : 1;

$d = explode("/", $d);
$day = $d[0];
$month = $d[1];
$year = $d[2];
if($day < 1 || $day > 31) exit;
if($month < 1 || $month > 12) exit;
if(empty($year) || strlen($year) != 4) exit;
if(!checkdate($month, $day, $year))  exit;

for($i=0; $i<$daysOfEvent; $i++){
	$timestamp = mktime(0,0,0,$month,$day,$year) + ($i*86400);
	$formatedDate = date('d', $timestamp)."/".date('m', $timestamp)."/".date('Y', $timestamp);
	// lets see if event date exists in order to  save only new event dates
	$results = $wpdb->get_results($wpdb->prepare("select event_date from $table_name where  post_id=%d and event_date=%s", $post, $timestamp));

	$numOfRows = count($results);
	if(empty($numOfRows)){ // save it only if does not exists
		$wpdb->query("insert into $table_name values(null, '$post', '$timestamp')");
		if($registeredDates[$timestamp] != "added"){
			echo "<div class='date_tag'><a href='javascript:void(0)' onclick='removeTag(this, \"" .$formatedDate. "\");'></a><tag>" .$formatedDate. "</tag></div>";
			$registeredDates[$timestamp] = "added";
		}		
	}
}
?>