<?php
/**
 * Plugin Name: Loja Automática
 * Plugin URI: http://www.lojaautomatica.com.br
 * Description: Quer abrir uma loja e não tem produtos? O plugin Loja Automática cria uma loja com produtos do Mercado Livre. Você escolhe as categorias e os produtos são adicionados automaticamente de tempos em tempos. Ganhe comissão sempre que um produto for vendido.
 * Version: 1.0.0
 * Author: Eneas Gesing
 * Author URI: http://blog.gesing.com.br
 *
 * @package LOJAAUTOMATICA
 */

set_time_limit(0);

define('LOJAAUTOMATICA_OPTIONS', 'lojaautomatica_opts');
define('LOJAAUTOMATICA_VERSION', '1.0.0');

require_once('include/tools.php');
require_once('include/custom_types.php');

/* Set up the plugin. */
add_action('plugins_loaded', 'lojaautomatica_setup');  
//add cron intervals
add_filter('cron_schedules', 'lojaautomatica_intervals');
//Actions for Cron job
add_action('lojaautomatica_cron', 'lojaautomatica_cron_hook');
/* Create table when admin active this plugin*/
register_activation_hook(__FILE__,'lojaautomatica_activation');
register_deactivation_hook(__FILE__, 'lojaautomatica_deactivation');

function lojaautomatica_activation(){
	$lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);
	if(!empty($lojaautomatica_opts)){
	   $lojaautomatica_opts['version'] = LOJAAUTOMATICA_VERSION;
	   update_option(LOJAAUTOMATICA_OPTIONS, $lojaautomatica_opts); 	
	}else{
	   $opts = array(
		'version' => LOJAAUTOMATICA_VERSION		
	  );
	  // add the configuration options
	  add_option(LOJAAUTOMATICA_OPTIONS, $opts);   	
	}	
	
	//test if cron active
	if (!(wp_next_scheduled('lojaautomatica_cron')))
		wp_schedule_event(time(), 'lojaautomatica_intervals', 'lojaautomatica_cron');
	
	lojaautomatica_create_table();
	
	create_custom();
	
	flush_rewrite_rules();
}

function lojaautomatica_deactivation(){
    wp_clear_scheduled_hook('lojaautomatica_cron');	
}

function lojaautomatica_cron_hook($id = null){
    global $wpdb;
    $lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);
    $lojaautomatica_table = $wpdb->prefix.'lojaautomatica';
    if (!$id)
		$query = "SELECT * FROM $lojaautomatica_table WHERE categoria_ml NOT IN (SELECT pai FROM $lojaautomatica_table) ORDER BY RAND() LIMIT 1";
    else
    	$query = "SELECT * FROM $lojaautomatica_table WHERE id = ".$id;
    
    $categoria = $wpdb->get_row($query);
    
    lojaautomatica_do_job($categoria->id);
    
    $where = array('id' => $categoria->id);
    $wpdb->update($wpdb->prefix.'lojaautomatica', array('ultima_verificacao' => time()), $where);
}

function lojaautomatica_unix_cron(){
	lojaautomatica_cron_hook();
}

function lojaautomatica_intervals($schedules){
   $intervals['lojaautomatica_intervals'] = array('interval' => '200', 'display' => 'lojaautomatica');
   $schedules=array_merge($intervals,$schedules);
   return $schedules;	
}

/*
 *Create database table for this plugin
*/
function lojaautomatica_create_table(){
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  global $wpdb;
  $lojaautomatica_table = $wpdb->prefix . 'lojaautomatica';    
  
  if( $wpdb->get_var( "SHOW TABLES LIKE '$lojaautomatica_table'" ) != $lojaautomatica_table ){
         $sql = "CREATE TABLE " . $lojaautomatica_table . " (
         id       bigint(20) auto_increment primary key,
         nome_categoria_ml varchar(100),
         categoria_ml int(11),
         categoria_wp int(11),
         pai int(11),
         ultima_verificacao varchar(20) 
         );";
         dbDelta($sql);  	  
         $h = fopen(dirname(__FILE__).'/log.txt', 'w'); fwrite($h, $sql); fclose($h);
  }
  
}

/* 
 * Set up the lojaautomatica plugin and load files at appropriate time. 
*/
function lojaautomatica_setup(){
   $lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);	
   /* Set constant path for the plugin directory */
   define('LOJAAUTOMATICA_DIR', plugin_dir_path(__FILE__));
   define('LOJAAUTOMATICA_ADMIN', LOJAAUTOMATICA_DIR.'/admin/');
   define('LOJAAUTOMATICA_INC', LOJAAUTOMATICA_DIR.'/include/');

   /* Set constant path for the plugin url */
   define('LOJAAUTOMATICA_URL', plugin_dir_url(__FILE__));
   define('LOJAAUTOMATICA_CSS', LOJAAUTOMATICA_URL.'css/');
   define('LOJAAUTOMATICA_JS', LOJAAUTOMATICA_URL.'js/');
   
   if(isset($lojaautomatica_opts['unix_cron']) && $lojaautomatica_opts['unix_cron'] == 'true'){
       wp_clear_scheduled_hook('lojaautomatica_cron');
   }else{
	   //test if cron active
	   if (!(wp_next_scheduled('lojaautomatica_cron')))
	     wp_schedule_event(time(), 'lojaautomatica_intervals', 'lojaautomatica_cron');   
   }      

   if(is_admin())
      require_once(LOJAAUTOMATICA_ADMIN.'admin.php');

    /* post action */
   add_action('publish_post', 'lojaautomatica_publish');
   
   //$cron = wp_get_schedules();
   //echo( "CRON jobs: " . print_r( $cron, true ) );
}


?>
