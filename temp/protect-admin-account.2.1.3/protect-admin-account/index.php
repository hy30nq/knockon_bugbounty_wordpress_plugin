<?php
/**
 * Plugin Name: Protect Admin
 * Plugin URI: https://protectadmin.com/plugin/protect-admin-account-pro-wordpress-plugin/
 * Description: Protect admin accounts from being deleted or modified by other users.
 * Version: 2.1.3
 * Author: Keystroke Click
 * Author URI: https://keystrokeclick.com/
 * Text Domain: protect-admin-account
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Freemius SDK code
if ( ! function_exists( 'papro_fs' ) ) {
    // Create a helper function for easy SDK access.
    function papro_fs() {
        global $papro_fs;

        if ( ! isset( $papro_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $papro_fs = fs_dynamic_init( array(
                'id'                  => '12260',
                'slug'                => 'protect-admin-account',
                'premium_slug'        => 'protect-admin-account-pro',
                'type'                => 'plugin',
                'public_key'          => 'pk_a5a9b5dccb1c943261f8f2dea4614',
                'is_premium'          => false,
                'premium_suffix'      => 'PRO',
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'has_affiliation' => 'selected',
				'menu'                => array(
                    'slug'            => 'thp-paa-admin-settings',
                ),
                'is_live'             => true,
            ) );
        }

        return $papro_fs;
    }

    // Init Freemius.
    papro_fs();
    // Signal that SDK was initiated.
    do_action( 'papro_fs_loaded' );
}

defined( 'THP_PAA_PLUGIN_DIR' ) or define ( 'THP_PAA_PLUGIN_DIR', plugin_dir_path(__FILE__) );
defined( 'THP_PAA_PLUGIN_URL' ) or define ( 'THP_PAA_PLUGIN_URL', plugin_dir_url(__FILE__) );

require_once( THP_PAA_PLUGIN_DIR . 'includes/paa-options.php' );
require_once( THP_PAA_PLUGIN_DIR . 'includes/paa-filters.php' );
require_once( THP_PAA_PLUGIN_DIR . 'includes/paa-utils.php' );


if (!function_exists('thp_paa_admin_action_links')) {
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'thp_paa_admin_action_links' );
 
	function thp_paa_admin_action_links ( $actions ) {
		$paa_actions = array(
			'settings' => '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=thp-paa-admin-settings') ) .'">' . __('Settings', 'protect-admin-account') . '</a>',
		);
		if ( thp_paa_is_paa_pro_active() ) { unset( $paa_actions['go_pro'] ); }
		$actions = array_merge($paa_actions, $actions);
		return $actions;
	}
}


if (!function_exists('thp_paa_save_activator_id')) {
	function thp_paa_save_activator_id() {
		if ( (current_user_can( 'activate_plugins' )) && (current_user_can( 'manage_options' )) ) {
			$current_user = wp_get_current_user();
			$activator_id = filter_var($current_user->ID, FILTER_VALIDATE_INT);
			
			$thp_paa_options = get_option( 'thp_paa_options', array() );
			$thp_paa_options['activator_id'] = $activator_id;
			
			update_option( 'thp_paa_options', $thp_paa_options );
		}
	}
	register_activation_hook( __FILE__, 'thp_paa_save_activator_id' );
}

if (!function_exists('thp_paa_check_version_update')) {
	function thp_paa_check_version_update() {
		if ( thp_paa_is_paa_pro_active() && thp_paa_user_is_activator() ) {
			$thp_paapro_latest_v = '1.1.2';
			
			$thp_paapro_data = get_plugin_data( plugin_dir_path( __DIR__ ).'protect-admin-account-pro/index.php', false, false );
			$thp_paapro_user_v = $thp_paapro_data['Version'];
			
			if ( version_compare($thp_paapro_user_v, $thp_paapro_latest_v, '<') ) {
				$url = 'https://protectadmin.com/my-account/';
				$notice_string = sprintf( 
					/* translators: %s: is href for pro-version */
					wp_kses( __( 'There is a new update available for Protect Admin Pro. Please login to <a href="%s" target="_blank" rel="noopener noreferrer">your account</a>  to download the latest version.', 'protect-admin-account' ), array(  'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ), 
					esc_url( $url ) 
				);
				echo '<div class="notice notice-warning is-dismissible">';
				echo '<p>'.$notice_string.'</p>';
				echo '</div>';
			}
		}
	}
	add_action('admin_notices', 'thp_paa_check_version_update');
}

if (!function_exists('thp_paa_get_protected_user_id')) {
	function thp_paa_get_protected_user_id() {
		$thp_paa_options = get_option( 'thp_paa_options' );
		
		$old_protected_user_id = ( !empty($thp_paa_options['protect_user1']) ? $thp_paa_options['protect_user1'] : '' ); //backward compatibility v0.1.0
		$old_protected_user_id = (int) $old_protected_user_id;
		
		$protected_user_ids = ( !empty( $thp_paa_options['protected_users'] ) ? $thp_paa_options['protected_users'] : '' );
		
		$protected_user_ids = ( is_array($protected_user_ids) ? array_keys($protected_user_ids) : $protected_user_ids );
		
		if ($old_protected_user_id)
			$protected_user_ids[] = $old_protected_user_id;
		
		$protected_user_ids = ( is_array($protected_user_ids) ? array_unique( $protected_user_ids ) : $protected_user_ids );
		
		return $protected_user_ids;
	}
}

if (!function_exists('thp_paa_email_notif_is_enable')) {
	function thp_paa_email_notif_is_enable() {
		$thp_paa_options = get_option( 'thp_paa_options' );
		$enabled = !empty($thp_paa_options['emailnotif']) ? true : false;
		
		return $enabled;
	}
}


if (!function_exists('thp_paa_user_is_activator')) {
	function thp_paa_user_is_activator() {
		$current_user = wp_get_current_user()->ID;
		$activator_id = thp_paa_get_activator_id();
		
		if ($current_user == $activator_id)
			return true;
		else
			return false;
	}
}


if (!function_exists('thp_paa_hide_plugin')) {
	function thp_paa_hide_plugin($plugins) {
		
		if ( !thp_paa_user_is_activator() ) {
			unset( $plugins['protect-admin-account/index.php'] );
			unset( $plugins['protect-admin-account-pro/index.php'] );
		}

		return $plugins;
	}
	add_filter('all_plugins', 'thp_paa_hide_plugin');
}


if (!function_exists('thp_paa_deactivate_plugin')) {
	function thp_paa_deactivate_plugin() {
		$thp_paa_options = get_option( 'thp_paa_options' );
		
		if ( !$thp_paa_options )
			deactivate_plugins( plugin_basename( __FILE__ ) );
	}
	add_action( 'pre_current_active_plugins', 'thp_paa_deactivate_plugin' );
}


if (!function_exists('thp_paa_log_actions_to_db')) {
	function thp_paa_log_actions_to_db($attempted_action, $attempted_by, $protecteduserid) {
		if (thp_paa_is_paa_pro_active())
			return;
		
		$protected_user_obj = get_user_by('id', $protecteduserid);
		$protected_user_acc = $protected_user_obj->user_login.' ('.$protected_user_obj->user_email.')';
		
		$attemptor_user_obj = get_user_by('id', $attempted_by);
		$attemptor_user_acc = $attemptor_user_obj->user_login.' ('.$attemptor_user_obj->user_email.')';
		
		$action_string = '';
		
		if ($attempted_action == 'user_deletion') {
			$action_string = sprintf( __( 'Attempted deletion of user account %s by user %s.', 'protect-admin-account-pro' ), $protected_user_acc, $attemptor_user_acc );
		}
		elseif ($attempted_action == 'edit_user_url_access') {
			$action_string = sprintf( __( 'User %s tried to access profile of user %s via WordPress dashboard.', 'protect-admin-account-pro' ), $attemptor_user_acc, $protected_user_acc );
		}
		elseif ($attempted_action == 'update_user_profile') {
			$action_string = sprintf( __( 'User %s tried to edit profile of user %s.', 'protect-admin-account-pro' ), $attemptor_user_acc, $protected_user_acc );
		}
		elseif ($attempted_action == 'set_user_role') {
			$action_string = sprintf( __( 'User %s attempted to change role of user %s', 'protect-admin-account-pro' ), $attemptor_user_acc, $protected_user_acc );
		}
		elseif ($attempted_action == 'email_change') {
			$action_string = sprintf( __( 'User %s attempted to change email of user %s.', 'protect-admin-account-pro' ), $attemptor_user_acc, $protected_user_acc );
		}
		
		if ( (isset($_SERVER['REMOTE_ADDR'])) && (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) )
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = '';
		
		$actionlog = date_i18n(get_option('date_format'), current_time('timestamp')).' | '.date_i18n(get_option('time_format'), current_time('timestamp')).' ['.__( 'Timezone', 'protect-admin-account-pro' ).': '.wp_timezone_string().'] | ['.__( 'IP address', 'protect-admin-account-pro' ).'] '.$ipaddress.' - '.$action_string;
		
		$actionlog_arr = array();
		
		if (current_user_can( 'manage_options' )) {
			
			$thp_paa_options = get_option( 'thp_paa_options' );
			$actionlog_arr = ( !empty( $thp_paa_options['actionlog'] ) ? $thp_paa_options['actionlog'] : '' );
			
			if ($actionlog_arr) {
				end($actionlog_arr);
				$last_key = key($actionlog_arr);
				reset($actionlog_arr);
				
				if ($last_key < 2) {
					$thp_paa_options['actionlog'][$last_key + 1] = $actionlog;
				}
				elseif ($last_key >= 2) {
					
					foreach ($actionlog_arr as $keyindex => $actionlog_item) {
						if ($keyindex == 2) {
							$thp_paa_options['actionlog'][$keyindex] = $actionlog;
						}
						elseif (($keyindex != 2) && ($keyindex < 2)) {
							$thp_paa_options['actionlog'][$keyindex] = $actionlog_arr[$keyindex + 1];
						}
						else {
							return;
						}
					}
				}
				reset($actionlog_arr);
			}
			else {
				$thp_paa_options['actionlog'][0] = $actionlog;
			}
			
			update_option( 'thp_paa_options', $thp_paa_options );
		}
	}
	add_action('thp_paa_before_termination_wpdie', 'thp_paa_log_actions_to_db', 10, 3);
}

require_once( THP_PAA_PLUGIN_DIR . 'includes/paa-protection-users.php' );
require_once( THP_PAA_PLUGIN_DIR . 'includes/paa-protection-posts.php' );
