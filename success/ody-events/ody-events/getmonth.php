<?
require("../../../wp-config.php");
if(!$_REQUEST["startframe"]){ 
	echo("done=1");
	exit; 
}
$lang = $_REQUEST["lang"];
$lang = ($lang == "auto") ? null : $lang;
$wpml =(!empty($lang)); // detect wpml
header('Content-type: text/html; charset=utf-8');
$loged=0;

class Calendar{

function Calendar()    {    }

function getDaysInMonth($month, $year)    {
	if ($month < 1 || $month > 12){
		return 0;
	}
	$d = $this->daysInMonth[$month - 1];
	if ($month == 2){
		if ($year%4 == 0){
			if ($year%100 == 0){
				if ($year%400 == 0){
					$d = 29;
				}
			}else{
				$d = 29;
			}
		}
	}
	return $d;
}


function adjustDate($month, $year){
	$a = array();  
	$a[0] = $month;
	$a[1] = $year;
	while ($a[0] > 12){
		$a[0] -= 12;
		$a[1]++;
	}
	while ($a[0] <= 0){
		$a[0] += 12;
		$a[1]--;
	}
	return $a;
}


function getMonthData($m, $y, $showYear = 1)    {
	$s = "";
	$a = $this->adjustDate($m, $y);
	$month = $a[0];
	$year = $a[1];        
	$monthName = $this->monthNames[$month - 1];
	$daysInMonth = $this->getDaysInMonth($month, $year);
	$date = getdate(mktime(12, 0, 0, $month, 1, $year));
	$first = $date["wday"];
	$firstIndex = ($first) ? $first : 7;
	$rest = $daysInMonth - (7-($firstIndex-1));
	$lastIndex = $rest % 7;
	$lastIndex = ($lastIndex)?$lastIndex : 7;
	$firstrowdays = (7-$firstIndex)+1;
	$rows =  ceil(($daysInMonth - $firstrowdays)/7) +1;
	$pmonth =$month-1;
	$pyear = $year;
	if($pmonth==0){
		$pmonth = 12;
		$pyear -=1;
	}
	$previousMonth = $this->getDaysInMonth($pmonth, $pyear);
	$queryString="firstIndex=".$firstIndex."&lastIndex=".$lastIndex."&firstrowdays=".$firstrowdays."&rows=".$rows."&daysInMonth=".$daysInMonth."&previousMonth=".$previousMonth;
	return $queryString;  
}

var $daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

function getDayInfo($day, $month, $year){
	global $wpdb;
	global $wpml;
	global $lang;
	$posts_table = $wpdb->prefix."posts";
	$wpml_table = $wpdb->prefix."icl_translations";	
	$table_name = $wpdb->prefix."ody_events";
	if($wpml){
		$eventsData = $wpdb->get_results($wpdb->prepare("select $table_name.post_id as post_id, FROM_UNIXTIME($table_name.event_date, '%%d') as day  
																				from $table_name, $wpml_table, $posts_table  where  
																				$table_name.post_id=$posts_table.id and 
																				$posts_table.post_status='publish' and 
																				FROM_UNIXTIME($table_name.event_date, '%%d')=%d  and 
																				FROM_UNIXTIME($table_name.event_date, '%%m')=%d  and 
																				FROM_UNIXTIME($table_name.event_date, '%%Y')=%d and 
																				$table_name.post_id=$wpml_table.element_id and 
																				$wpml_table.language_code='$lang'", $day, $month, $year));
	}else{
		$eventsData = $wpdb->get_results($wpdb->prepare("select distinct $table_name.id, $table_name.post_id as post_id, 
																				FROM_UNIXTIME($table_name.event_date, '%%d') as day  
																				from $table_name, $posts_table where  
																				FROM_UNIXTIME($table_name.event_date, '%%d')=%d  and 
																				FROM_UNIXTIME($table_name.event_date, '%%m')=%d  and 
																				FROM_UNIXTIME($table_name.event_date, '%%Y')=%d", $day, $month, $year));
			
	}
	$numOfRows = count($eventsData);
	if($numOfRows){
		$ret="";
		$counter=0;
		foreach($eventsData as $row){
			$counter++;
			$url = get_permalink($row->post_id); // non sef url
			$post_title = get_the_title($row->post_id); // the post's title;
			$excerpt = get_excerpt_outside_loop($row->post_id);
			$ret.="&id".$counter."=".$row->id."&title".$counter."=".urlencode($post_title)."&url".$counter."=".urlencode($url)."&excerpt".$counter."=".urlencode($excerpt);
		}
		$ret.="&numOfEvents=".$numOfRows;
		return $ret;
	}else{
		$ret.="&numOfEvents=0";
		return $ret;
	}
}

function getMonthEvents($month, $year){
	global $wpdb;
	global $wpml;
	global $lang;	
	$posts_table = $wpdb->prefix."posts";
	$wpml_table = $wpdb->prefix."icl_translations";	
	$table_name = $wpdb->prefix."ody_events";
	if($wpml){
		$results = $wpdb->get_results($wpdb->prepare("select distinct FROM_UNIXTIME($table_name.event_date, '%%d') as day 
																			from $table_name, $wpml_table, $posts_table where   
																			$table_name.post_id=$posts_table.id and 
																			$posts_table.post_status='publish' and 																			
																			FROM_UNIXTIME($table_name.event_date, '%%m')=%d  and 
																			FROM_UNIXTIME($table_name.event_date, '%%Y')=%d and 
																			$table_name.post_id=$wpml_table.element_id and 
																			$wpml_table.language_code='$lang'", $month, $year));
	}else{
		$results = $wpdb->get_results($wpdb->prepare("select distinct FROM_UNIXTIME($table_name.event_date, '%%d') as day 
																			from $table_name, $posts_table where   
																			$table_name.post_id=$posts_table.id and 
																			$posts_table.post_status='publish' and 																			
																			FROM_UNIXTIME($table_name.event_date, '%%m')=%d  and 
																			FROM_UNIXTIME($table_name.event_date, '%%Y')=%d", $month, $year));
	}
	$eventsDates = "";
	$temp="";
	foreach($results as $row){
		$eventsDates[]= $row->day;
	}
	for($n=1; $n <=31; $n++){
		$temp[$n-1] = 0;
		for($i=0; $i <= count($eventsDates); $i++){
			if($eventsDates[$i] == $n){
				$temp[$n-1] = $eventsDates[$i];
			}
		}
	}
	$eventsDates = implode("|", $temp);
	$pmonth =$month-1;
	$pyear = $year;
	if($pmonth==0){
		$pmonth = 12;
		$pyear -=1;
	}
	if($wpml){
		$results = $wpdb->get_results($wpdb->prepare("select distinct FROM_UNIXTIME($table_name.event_date, '%%d') as day 
																			from $table_name, $wpml_table, $posts_table where   
																			$table_name.post_id=$posts_table.id and 
																			$posts_table.post_status='publish' and 																			
																			FROM_UNIXTIME($table_name.event_date, '%%m')=%d  and 
																			FROM_UNIXTIME($table_name.event_date, '%%Y')=%d and 
																			$table_name.post_id=$wpml_table.element_id and 
																			$wpml_table.language_code='$lang'", $pmonth, $pyear));
	}else{
		$results = $wpdb->get_results($wpdb->prepare("select distinct FROM_UNIXTIME($table_name.event_date, '%%d') as day 
																			from $table_name, $posts_table where   
																			$table_name.post_id=$posts_table.id and 
																			$posts_table.post_status='publish' and 																																						
																			FROM_UNIXTIME($table_name.event_date, '%%m')=%d  and 
																			FROM_UNIXTIME($table_name.event_date, '%%Y')=%d", $pmonth, $pyear));
	}
	$peventsDates = "";
	$temp="";
	foreach($results as $row){
		$peventsDates[]= $row->day;
	}
	for($n=1; $n <=31; $n++){
		$temp[$n-1] = 0;
		for($i=0; $i <= count($peventsDates); $i++){
			if($peventsDates[$i] == $n){
				$temp[$n-1] = $peventsDates[$i];
			}
		}
	}
	$peventsDates = implode("|", $temp);
	
	
	$nmonth =$month+1;
	$nyear = $year;
	if($nmonth==13){
	$nmonth = 1;
	$nyear +=1;
	}
	if($wpml){
		$results = $wpdb->get_results($wpdb->prepare("select distinct FROM_UNIXTIME($table_name.event_date, '%%d') as day 
																			from $table_name, $wpml_table, $posts_table where   
																			$table_name.post_id=$posts_table.id and 
																			$posts_table.post_status='publish' and 																			
																			FROM_UNIXTIME($table_name.event_date, '%%m')=%d  and 
																			FROM_UNIXTIME($table_name.event_date, '%%Y')=%d and 
																			$table_name.post_id=$wpml_table.element_id and 
																			$wpml_table.language_code='$lang'", $nmonth, $nyear));
	}else{
		$results = $wpdb->get_results($wpdb->prepare("select distinct FROM_UNIXTIME($table_name.event_date, '%%d') as day 
																			from $table_name, $posts_table where   
																			$table_name.post_id=$posts_table.id and 
																			$posts_table.post_status='publish' and 																																						
																			FROM_UNIXTIME($table_name.event_date, '%%m')=%d  and 
																			FROM_UNIXTIME($table_name.event_date, '%%Y')=%d", $nmonth, $nyear));
	}	
	$neventsDates = "";
	$temp="";
	foreach($results as $row){
		$neventsDates[]= $row->day;
	}
	for($n=1; $n <=31; $n++){
		$temp[$n-1] = 0;
		for($i=0; $i <= count($neventsDates); $i++){
			if($neventsDates[$i] == $n){
				$temp[$n-1] = $neventsDates[$i];
			}
		}
	}
	$neventsDates = implode("|", $temp);
	
	$ret="&eventsDates=".$eventsDates."&peventsDates=".$peventsDates."&neventsDates=".$neventsDates."&done=1";
	return $ret;
}

} // end of class


if(!function_exists("get_excerpt_outside_loop")){
	function get_excerpt_outside_loop($post_id) {
		global $wpdb;
		$query = 'SELECT post_excerpt FROM '. $wpdb->posts .' WHERE ID = '. $post_id .' LIMIT 1';
		$result = $wpdb->get_results($query, ARRAY_A);
		$post_excerpt=$result[0]['post_excerpt'];
		return $post_excerpt;
	}
}


$day = (int)($_REQUEST['selectedDay']) ? $_REQUEST['selectedDay'] : date("j");
$month = (int)$_REQUEST['currentMonth'];
$year = (int)$_REQUEST['currentYear'];
$d = getdate(time()); 
if ($month == "") { $month = $d["mon"]; } 
if ($year == "") { $year = $d["year"]; } 

$cal = new Calendar; 
if($_REQUEST["mode"]!="events"){
echo $cal->getMonthData($month, $year);
}
echo $cal->getDayInfo($day, $month, $year);
echo $cal->getMonthEvents($month, $year);
echo("&loged=".$loged);
?>