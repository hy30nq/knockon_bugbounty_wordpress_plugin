<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'THP_PAA_PLUGIN_DIR' ) ) exit;

if (!function_exists('thp_paa_revert_email_change')) {
	function thp_paa_revert_email_change( $errors, $update, $user_obj ) {
		
		$admins = thp_paa_get_protected_user_id();
			
		if ($admins && is_array($admins)) {
			foreach ($admins as $admin) {
				if (isset($user_obj->ID) && ($user_obj->ID == $admin )) {
					if ( !thp_paa_user_is_activator() ) {
						$old = get_user_by('id', $user_obj->ID);
						
						if ( $user_obj->user_email != $old->user_email ) {
							if ( current_user_can('edit_users') ) { //security check
								$user_obj->user_email = $old->user_email;
								
								$attempted_action = 'email_change';
								$attempted_by = get_current_user_id();
								$protecteduserid = $admin;
								do_action( 'thp_paa_before_termination_wpdie', $attempted_action, $attempted_by, $protecteduserid );
								
								wp_die( __( "Sorry, you are not allowed to change this user’s email.", "protect-admin-account" ) );
							}
						}
					}
				}
			}
		}
	}
	add_action('user_profile_update_errors', 'thp_paa_revert_email_change', 10, 3);
}


if (!function_exists('thp_paa_prevent_edit_user_url_access')) {
	
	function thp_paa_prevent_edit_user_url_access() {
		global $pagenow;
		
		if ( $pagenow == 'user-edit.php' ) {
						
			$userid = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
			$admins = thp_paa_get_protected_user_id();
			
			if ($admins && is_array($admins)) {
				foreach ($admins as $admin) {
					if ( $userid == $admin ) {
						if ( thp_paa_user_is_activator() ) {
							return;
						}
						elseif ( !thp_paa_user_is_activator() ) {
							
							$attempted_action = 'edit_user_url_access';
							$attempted_by = get_current_user_id();
							$protecteduserid = $userid;
							do_action( 'thp_paa_before_termination_wpdie', $attempted_action, $attempted_by, $protecteduserid );
							
							wp_die( __( 'Sorry, you are not allowed to edit this user.', 'protect-admin-account' ) );
						}
					}
				}
			}
		}
	}
	add_action('admin_init', 'thp_paa_prevent_edit_user_url_access');
}


if (!function_exists('thp_paa_prevent_user_deletion')) {
	function thp_paa_prevent_user_deletion( $userid ) {
		
		$admins = thp_paa_get_protected_user_id();
		
		if ($admins && is_array($admins)) {
			foreach ($admins as $admin) {
				if ( $userid == $admin ) {
					if ( thp_paa_user_is_activator() ) {
						return;
					}
					elseif ( !thp_paa_user_is_activator() ) {
						$user_obj = get_user_by('id', $userid);
						$name = $user_obj->user_login;
						$protecteduserid = $userid;
						
						$attempted_action = 'user_deletion';
						$attempted_by = get_current_user_id();
						do_action( 'thp_paa_before_termination_wpdie', $attempted_action, $attempted_by, $protecteduserid );
						
						wp_die( sprintf( 
							/* translators: %s: is for name */
							__( 'Sorry, you are not allowed to delete user %s. If you are deleting users in bulk, some users before this might have been successfully deleted.', 'protect-admin-account' ), 
							$name 
						));
					}
				}
			}
		}
	}
	add_action('delete_user', 'thp_paa_prevent_user_deletion');
}


if (!function_exists('thp_paa_prevent_update_user')) {
	function thp_paa_prevent_update_user($userid) {
		
		$admins = thp_paa_get_protected_user_id();
			
		if ($admins && is_array($admins)) {
			foreach ($admins as $admin) {
				if ( $userid == $admin ) {
					if ( thp_paa_user_is_activator() ) {
						return;
					}
					elseif ( !thp_paa_user_is_activator() ) {
						
						$attempted_action = 'update_user_profile';
						$attempted_by = get_current_user_id();
						$protecteduserid = $userid;
						do_action( 'thp_paa_before_termination_wpdie', $attempted_action, $attempted_by, $protecteduserid );
						
						wp_die( __( 'Sorry, you are not allowed to edit this user.', 'protect-admin-account' ) );
					}
				}
			}
		}
	}
	add_action('edit_user_profile_update', 'thp_paa_prevent_update_user');
}


if (!function_exists('thp_paa_hide_pwd_fields')) {
	function thp_paa_hide_pwd_fields() {
		if (isset($_GET['user_id'])) {
						
			$userid = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
			$admins = thp_paa_get_protected_user_id();
			
			if ($admins && is_array($admins)) {
				foreach ($admins as $admin) {
					if ( $userid == $admin ) {
						if ( thp_paa_user_is_activator() )
							return true;
						elseif ( !thp_paa_user_is_activator() )
							return false;
					}
				}
			}
		}
	return true;
	}
	add_filter('show_password_fields', 'thp_paa_hide_pwd_fields');
}


if (!function_exists('thp_paa_remove_checkbox')) {
	function thp_paa_remove_checkbox() {
		global $pagenow;
		
		if ( $pagenow == 'users.php' ) {
						
			$current_user = wp_get_current_user()->ID;
			$admins = thp_paa_get_protected_user_id();
			
			if ($admins && is_array($admins)) {
				
				echo '<style type="text/css">';
				
				foreach ($admins as $admin) {
					if ( !thp_paa_user_is_activator() && $admin != $current_user) {
						echo '
						table.users tr#user-'.$admin.' th.check-column input[type="checkbox"] {
							display: none !important;
						}
						';
					}
				}
				
				echo '</style>';
			}
		}
	}
	add_action('admin_head', 'thp_paa_remove_checkbox');
}


if (!function_exists('thp_paa_revert_user_role')) {
	function thp_paa_revert_user_role($userid, $role, $old_roles) {
		
		$admins = thp_paa_get_protected_user_id();
			
		if ($admins && is_array($admins)) {
			foreach ($admins as $admin) {
				if ( $userid == $admin ) {
					if ( !thp_paa_user_is_activator() ) {
						if (current_user_can('edit_users')) { //security check
							
							$user_object = new WP_User($userid);
							$user_object->remove_role($role);
							
							foreach ($old_roles as $old_role) {
								$user_object->add_role($old_role);
							}
							
							$name = $user_object->user_login;
							
							$attempted_action = 'set_user_role';
							$attempted_by = get_current_user_id();
							$protecteduserid = $userid;
							do_action( 'thp_paa_before_termination_wpdie', $attempted_action, $attempted_by, $protecteduserid );
							
							wp_die( sprintf( 
								/* translators: %s: is for name */
								__( 'Sorry, you are not allowed to change role of user %s.', 'protect-admin-account' ), 
								$name 
							));
						}
					}
				}
			}
		}
	}
	add_action('set_user_role', 'thp_paa_revert_user_role', 10, 3);
}
