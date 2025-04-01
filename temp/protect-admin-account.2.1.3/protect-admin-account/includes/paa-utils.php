<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'THP_PAA_PLUGIN_DIR' ) ) exit;

if (!function_exists('thp_paa_get_activator_id')) {
	function thp_paa_get_activator_id() {
		$thp_paa_options = get_option( 'thp_paa_options' );
		$activator_id = !empty($thp_paa_options['activator_id']) ? $thp_paa_options['activator_id'] : '';
		return $activator_id;
	}
}


if (!function_exists('thp_paa_is_paa_pro_active')) {
	function thp_paa_is_paa_pro_active() {
		if (!function_exists('is_plugin_active')) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		
		if( is_plugin_active( 'protect-admin-account-pro/index.php' ) ) {
			return true;
		}
		return false;
	}
}


if (!function_exists('thp_paa_compare_datetime')) {
	function thp_paa_compare_datetime($a, $b){
	
		$a = substr($a, 0, strpos($a, '['));
		$a = strtotime(str_replace('|','',$a));
		
		$b = substr($b, 0, strpos($b, '['));
		$b = strtotime(str_replace('|','',$b));
	
		if ($a == $b) {
			return 0;
		}
		return ($a > $b) ? -1 : 1;
	}
}
