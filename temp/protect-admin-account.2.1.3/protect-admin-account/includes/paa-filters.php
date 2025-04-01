<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'THP_PAA_PLUGIN_DIR' ) ) exit;

if (!function_exists('thp_paa_user_filter')) {
	function thp_paa_user_filter($actions, $user_object) {

		$thp_paa_options = get_option( 'thp_paa_options' );
		$protected_users = array_key_exists('protected_users', $thp_paa_options) ? $thp_paa_options['protected_users'] : '';
		if ( !empty( $protected_users ) && array_key_exists($user_object->ID, $protected_users) && ($protected_users[$user_object->ID] == '1')) {
			$activator_id = thp_paa_get_activator_id();
			$current_user = wp_get_current_user()->ID;
			if ($current_user != $activator_id) {
				unset($actions['delete']);
			}
		}
		return $actions;
	}
	add_filter( 'user_row_actions', 'thp_paa_user_filter', 1, 2 );
}