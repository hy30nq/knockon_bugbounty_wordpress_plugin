<?php
//get lojaautomatica data from database by jobid
function lojaautomatica_get_data($jobid){
  global $wpdb;
  $lojaautomatica_table = $wpdb->prefix.'lojaautomatica';
  $query = "SELECT * FROM $lojaautomatica_table WHERE id=".$jobid;
  $data = $wpdb->get_row($query, ARRAY_A);	
  return $data;
}

//DoJob
function lojaautomatica_do_job($jobid) {
	global $lojaautomatica_dojob_message;
	if (empty($jobid))
		return false;
	require_once(dirname(__FILE__).'/lojaautomatica_dojob.php');
	$lojaautomatica_dojob= new lojaautomatica_dojob($jobid);
	unset($lojaautomatica_dojob);
	return $lojaautomatica_dojob_message;
}

function lojaautomatica_cache_image($url){
	if( strpos($url, "icon_") !== FALSE)
	      return false;
	global $rpf_options;
	$contents = lojaautomatica_get_file($url);
	
	if( !$contents )
		return false;
	$basename = basename($url);
	$paresed_url = parse_url($basename);
	
	$filename = substr(md5(time()), 0, 5) . '_' . $paresed_url['path'];
    	
	
	$pluginpath = LOJAAUTOMATICA_URL;
    	$real_cachepath=dirname(dirname(__FILE__)).'/cache';
	if(is_writable(	$real_cachepath ) ){
		
		if($contents){

			file_put_contents($real_cachepath . $filename, $contents);
			$i=@exif_imagetype($real_cachepath . $filename);
			if($i)
				return $pluginpath . $cachepath . rawurlencode($filename);
		}
	}else{
		
		echo " directory is not writable";
		
	}
    
	return false;
}

add_filter( 'cron_schedules', 'cron_add_schedules' );

function cron_add_schedules( $schedules ) {
	// Adds once weekly to the existing schedules.
	$schedules['30minutes'] = array(
			'interval' => 30*60,
			'display' => __( '30 Minutos' )
	);
	$schedules['15minutes'] = array(
			'interval' => 15*60,
			'display' => __( '15 Minutos' )
	);
	return $schedules;
	$schedules['2hours'] = array(
			'interval' => 120*60,
			'display' => __( '2 Horas' )
	);
}

?>
