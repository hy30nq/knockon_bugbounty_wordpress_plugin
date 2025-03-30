<?php
	
	require_once(dirname(__FILE__) . '/../../../wp-config.php');
	require_once( dirname(__FILE__) . '/loja-automatica.php' );
	                                     
	nocache_headers();
	
	$lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);
	if($lojaautomatica_opts['lojaautomatica_unix_cron'] == 'true') {
		lojaautomatica_unix_cron();		
	}
?>
