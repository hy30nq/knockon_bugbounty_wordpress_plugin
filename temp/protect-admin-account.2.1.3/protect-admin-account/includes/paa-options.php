<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'THP_PAA_PLUGIN_DIR' ) ) exit;

if (!function_exists('thp_paa_admin_menu')) {
	function thp_paa_admin_menu() {
		if ( thp_paa_user_is_activator() ) {
			$menu_slug = 'thp-paa-admin-settings';
			add_menu_page( 
				__( 'Protect Admin Settings', 'protect-admin-account' ), 
				__( 'Protect Admin', 'protect-admin-account' ), 
				'activate_plugins', 
				$menu_slug, 
				false, 
				plugins_url( 'protect-admin-account/assets/img/protectadmin-icon.png' ), 
				70 // position menu below Users
			);

			add_submenu_page( 
				$menu_slug, 
				__( 'Protect Admin Settings', 'protect-admin-account' ), 
				__( 'Settings', 'protect-admin-account' ), 
				'activate_plugins', 
				$menu_slug, 
				'thp_paa_admin_settings' 
			);

			add_submenu_page( 
				$menu_slug, 
				__( 'Protect Admin Logs', 'protect-admin-account' ), 
				__( 'Logs', 'protect-admin-account' ), 
				'activate_plugins', 
				'thp-paa-admin-logs', 
				'thp_paa_admin_logs' 
			);
		}
	}
	add_action('admin_menu', 'thp_paa_admin_menu', 99);
}

if (!function_exists('thp_paa_admin_settings_footer')) {
	function thp_paa_admin_settings_footer ( $footer_text ) {
		$get_screen = get_current_screen();

		if ( $get_screen->parent_base !== 'thp-paa-admin-settings' ) return $footer_text;

		/* translators: 1: and 2: are strong html format, and 3: is URL */
		$review_text = esc_html__( 'Give %1$sProtect Admin%2$s a review! %3$s We really appreciate your support!', 'protect-admin-account' );
		$review_url  = 'https://wordpress.org/support/plugin/protect-admin-account/reviews/?filter=5';

		$footer_text = sprintf(
			$review_text,
			'<strong>',
			'</strong>',
			'<a href="' . $review_url . '" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
		);

		return $footer_text;
	}
	add_action('in_admin_footer', function(){
		add_filter( 'admin_footer_text', 'thp_paa_admin_settings_footer', 1, 99 );
	});
}

if (!function_exists('thp_paa_admin_settings')) {
	
	function thp_paa_admin_settings() {
	
		if (isset($_POST['action'])){
			if ( $_POST['action'] === 'update' )
				thp_paa_options_handler();
		}
		?>
		
		<div class="wrap">
		
			<h1><?php _e( "Protect Admin Settings", 'protect-admin-account' ); ?></h1>
			
			<form name="thp_paa_admin_settings_form" method="post" action="">
				
				<?php wp_nonce_field('update-options', 'thp_paa_admin_settings_nonce'); 
				
				$thp_paa_options = get_option( 'thp_paa_options' );
				?>
				
				<table class="form-table" role="presentation">
					<tbody>
					
					<tr>
					<th scope="row"><label for="thp_paa_options[protected_users]"><?php _e( "Admin Accounts", 'protect-admin-account' ); ?>:</label></th>
						<td>
						
						<?php
						$old_user_ID = !empty($thp_paa_options['protect_user1']) ? $thp_paa_options['protect_user1'] : 0; //user ID
						$admins = get_users( [ 'role__in' => [ 'administrator' ] ] ); //user objects
						$userchecked = 0;
						
						foreach ( $admins as $admin ) {
							
							if ( ($old_user_ID != 0) && ($admin->ID == $old_user_ID) ) {
								$userchecked = 1;
							}
							else {
								$userchecked = (!empty($thp_paa_options['protected_users'][$admin->ID]) ? $thp_paa_options['protected_users'][$admin->ID] : '');
							}
							
							?>
							
							<fieldset>
								<label for="thp_paa_options[protected_users][<?php echo $admin->ID; ?>]">
									<input type="checkbox" id="thp_paa_options[protected_users][<?php echo $admin->ID; ?>]" name="thp_paa_options[protected_users][<?php echo $admin->ID; ?>]" value="1" <?php checked( '1', $userchecked ); ?>>
									<?php echo $admin->user_login.' ('.$admin->user_email.')'; ?>
								</label>
							</fieldset>
							
						<?php
						} //end of for loop ?>
						
						<p class="description" style="font-style:italic; font-size:13px; padding-top:7px;"><?php _e( "Tick the user account(s) you want to protect. Only administrator accounts can be protected.", 'protect-admin-account' ); ?></p>
						</td>
					</tr>
					
					<tr>
					<th scope="row"><label for="thp_paa_options[emailnotif]"><?php _e( "Email Notification", 'protect-admin-account' ); ?>:</label></th>
						<td>
							<fieldset>
							<label for="thp_paa_options[emailnotif]">
							<input type="checkbox" id="thp_paa_options[emailnotif]" name="thp_paa_options[emailnotif]" value="1" <?php checked( '1', (!empty($thp_paa_options['emailnotif']) && thp_paa_is_paa_pro_active() ? $thp_paa_options['emailnotif'] : '') ); echo ( !thp_paa_is_paa_pro_active() ? 'disabled' : '' ) ?>>
							<?php
							if (!thp_paa_is_paa_pro_active()) {
								
								$pro_url = '/wp-admin/admin.php?page=thp-paa-admin-settings-pricing';
								
								$upgrade_link1 = sprintf( 
									/* translators: %s: is href for pro-version */
									wp_kses( __( 'Enable email notifications? (PRO only) <a href="%s">Upgrade to PRO</a>', 'protect-admin-account' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ), 
									esc_url( $pro_url ) 
								);
								
								echo $upgrade_link1;
							}
							else if (thp_paa_is_paa_pro_active()) {
								_e( "Enable email notifications? (PRO only)", 'protect-admin-account' );
							}
							?>
							</label>
							</fieldset>
							
							<p class="description" style="font-style:italic; font-size:13px; padding-top:7px;"><?php _e( "Get notified by email when someone attempts to modify your protected admin accounts.", 'protect-admin-account' ); ?></p>
						</td>
					</tr>
					
					<tr>
					<th scope="row"><label for="thp_paa_options[disable_plugin_install]"><?php _e( "Protect Plugins", 'protect-admin-account' ); ?>:</label></th>
						<td>
							<fieldset>
							<label for="thp_paa_options[disable_plugin_install]">
							<input type="checkbox" id="thp_paa_options[disable_plugin_install]" name="thp_paa_options[disable_plugin_install]" value="1" disabled>
							<?php
							if (!thp_paa_is_paa_pro_active()) {
								
								$upgrade_link2 = sprintf( 
									/* translators: %s: is url for pro-version */
									wp_kses( __( 'Allow plugin installation and removal by protected admins (disables for all other users) (Requires active PRO license) <a href="%s">Upgrade to PRO</a>', 'protect-admin-account' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ), 
									esc_url( $pro_url ) 
								);
								
								echo $upgrade_link2;
							}
							else if (thp_paa_is_paa_pro_active()) {
								_e( "Allow plugin installation and removal by protected admins (disables for all other users) (Requires active PRO license)", 'protect-admin-account' );
							}
							?>
							</label>
							</fieldset>
						</td>
					</tr>
					
					<tr>
					<th scope="row"><label><?php _e( "Protect Posts & Pages", 'protect-admin-account' ); ?>:</label></th>
						<td>
							<fieldset>
							<label for="thp_paa_options[protect_posts]">
							<input type="checkbox" id="thp_paa_options[protect_posts]" name="thp_paa_options[protect_posts]" value="1" <?php checked( '1', (!empty($thp_paa_options['protect_posts']) ? $thp_paa_options['protect_posts'] : '') ); ?>>
							<?php _e( 'Prevent posts published by protected admins from being edited or deleted.', 'protect-admin-account' ); ?>
							</label>
							</fieldset>

							<fieldset>
							<label for="thp_paa_options[protect_pages]">
							<input type="checkbox" id="thp_paa_options[protect_pages]" name="thp_paa_options[protect_pages]" value="1" disabled>
							<?php
							if (!thp_paa_is_paa_pro_active()) {
								
								$upgrade_link3 = sprintf( 
									/* translators: %s: is href for pro-version */
									wp_kses( __( 'Prevent pages published by protected admins from being edited or deleted. (Requires active PRO license) <a href="%s">Upgrade to PRO</a>', 'protect-admin-account' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ), 
									esc_url( $pro_url ) 
								);
								
								echo $upgrade_link3;
							}
							else if (thp_paa_is_paa_pro_active()) {
								_e( 'Prevent pages published by protected admins from being edited or deleted. (Requires active PRO license)', 'protect-admin-account' );
							}
							?>
							</label>
							</fieldset>
						</td>
					</tr>

					<tr>
					<th scope="row"><label><?php _e( "Buddypress", 'protect-admin-account' ); ?>:</label></th>

						<td>
							<fieldset>
							<label for="thp_paa_options[protect_bp_activities]">
							<input type="checkbox" id="thp_paa_options[protect_bp_activities]" name="thp_paa_options[protect_bp_activities]" value="1" <?php checked( '1', (!empty($thp_paa_options['protect_bp_activities']) && thp_paa_is_paa_pro_active() ? $thp_paa_options['protect_bp_activities'] : '') ); echo ( !thp_paa_is_paa_pro_active() ? 'disabled' : '' ) ?>>
							<?php
							if (!thp_paa_is_paa_pro_active()) {
								
								$pro_url = '/wp-admin/admin.php?page=thp-paa-admin-settings-pricing';
								
								$upgrade_link_bp = sprintf( 
									/* translators: %s: is href for pro-version */
									wp_kses( __( 'Prevent activities posted by protected admins from being edited or deleted. (PRO only) <a href="%s">Upgrade to PRO</a>', 'protect-admin-account' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ), 
									esc_url( $pro_url ) 
								);
								
								echo $upgrade_link_bp;
							}
							else if (thp_paa_is_paa_pro_active()) {
								_e( "Prevent activities posted by protected admins from being edited or deleted. (PRO only)", 'protect-admin-account' );
							}
							?>

							</label>
							</fieldset>

							<fieldset>
							<label for="thp_paa_options[protect_bp_groups]">
							<input type="checkbox" id="thp_paa_options[protect_bp_groups]" name="thp_paa_options[protect_bp_groups]" value="1" <?php checked( '1', (!empty($thp_paa_options['protect_bp_groups']) && thp_paa_is_paa_pro_active() ? $thp_paa_options['protect_bp_groups'] : '') ); echo ( !thp_paa_is_paa_pro_active() ? 'disabled' : '' ) ?>>
							<?php
							if (!thp_paa_is_paa_pro_active()) {
								
								$pro_url = '/wp-admin/admin.php?page=thp-paa-admin-settings-pricing';
								
								$upgrade_link_bp = sprintf( 
									/* translators: %s: is href for pro-version */
									wp_kses( __( 'Prevent groups created by protected admins from being edited or deleted. (PRO only) <a href="%s">Upgrade to PRO</a>', 'protect-admin-account' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ), 
									esc_url( $pro_url ) 
								);
								
								echo $upgrade_link_bp;
							}
							else if (thp_paa_is_paa_pro_active()) {
								_e( "Prevent groups created by protected admins from being edited or deleted. (PRO only)", 'protect-admin-account' );
							}
							?>

							</label>
							</fieldset>
						</td>
					</tr>

					</tbody>
				</table>
				
				<p>
					<input name="thp_paa_options[activator_id]" type="hidden" value="<?php echo (!empty($thp_paa_options['activator_id']) ? $thp_paa_options['activator_id'] : ''); ?>">
										
					<input class="button-primary" type="submit" name="Submit" value="<?php _e( "Save", 'protect-admin-account' ); ?>" />
				</p>
				
				<input type="hidden" name="action" value="update" />
			</form>
			
		</div>
		
	<?php
	}
}

if (!function_exists('thp_paa_admin_logs')) {
	
	function thp_paa_admin_logs() {
	
		?>
		
		<div class="wrap">
		
			<h1><?php _e( "Protect Admin Logs", 'protect-admin-account' ); ?></h1>
				
			<?php wp_nonce_field('update-options', 'thp_paa_admin_settings_nonce'); 
			
			$thp_paa_options = get_option( 'thp_paa_options' );
			?>
			
			<table class="form-table" role="presentation">
				<tbody>
				
				<tr>
				<th scope="row"><label for=""><?php _e( "Logged Actions", 'protect-admin-account' ); ?>:</label></th>
					<td>
						<p class="description" style="font-style:italic; font-size:13px;">
						<?php
						if (!thp_paa_is_paa_pro_active()) {
							_e( "When someone attempts to edit your protected admin accounts, the attempted actions are logged. The last one attempted action is shown below.", 'protect-admin-account' );
						}
						else if (thp_paa_is_paa_pro_active()) {
							_e( "When someone attempts to edit your protected admin accounts, the attempted actions are logged. The last attempted actions are shown below. You can see full log in /wp-content/uploads/paa-log/paa-log.txt.", 'protect-admin-account' );
						}
						?>
						</p>
						
						<br />
						
						<p style="display:block; border:1px solid #ccc; width:80%; padding:20px; font-size:13px;">
						<?php
							if (!thp_paa_is_paa_pro_active()) {
								
								$actionlog_arr = ( !empty( $thp_paa_options['actionlog'] ) ? $thp_paa_options['actionlog'] : '' );
								
								if ($actionlog_arr) {
									$actionlog_arr = array_reverse($actionlog_arr);
									
									echo $actionlog_arr[0];
								}
								elseif (!$actionlog_arr) {
									_e( "No attempted actions recorded.", 'protect-admin-account' );
								}
							}
							elseif (thp_paa_is_paa_pro_active()) {
								
								$actionlog_arr = ( !empty( $thp_paa_options['actionlog'] ) ? $thp_paa_options['actionlog'] : '' );
								
								$logabspath = trailingslashit(ABSPATH).'wp-content/uploads/paa-log/paa-log.txt';
								$loghomepath = trailingslashit(get_home_path()).'wp-content/uploads/paa-log/paa-log.txt';
								
								if ( file_exists( $logabspath ) )
									$logfilepath = $logabspath;
								elseif ( file_exists( $loghomepath ) )
									$logfilepath = $loghomepath;
								else
									$logfilepath = '';
								
								$logfilesize = filesize($logfilepath);
								
								if ($logfilesize > 1500) {
									$rawdata = file_get_contents( $logfilepath, false, null, $logfilesize - 1500 );
									$logdata = explode("\n", $rawdata);
									array_shift($logdata);
								}
								else {
									$rawdata = (file_exists($logfilepath) ? file_get_contents($logfilepath) : '');
									$logdata = explode("\n", $rawdata);
								}
								
								$logdata = array_filter( str_replace(array("\r", "\n"), '', $logdata) );
								
								if ( is_array($actionlog_arr) && is_array($logdata) )
									$logdata = array_merge($actionlog_arr, $logdata);
								elseif ( !is_array($actionlog_arr) && is_array($logdata) )
									$logdata = $logdata;
								elseif ( is_array($actionlog_arr) && !is_array($logdata) )
									$logdata = $actionlog_arr;
								
								// bug fix : https://wordpress.org/support/topic/php-warnings-170/
								$logdata = array_filter($logdata);

								if (is_array($logdata))
									usort($logdata, "thp_paa_compare_datetime");
								
								if ( ($logdata) && (array_filter($logdata) != []) ) {
									foreach ( $logdata as $logitem ) {
											echo nl2br($logitem."\n\n");
									}
								}
								else {
									_e( "No attempted actions recorded.", 'protect-admin-account' );
								}
							}
						?>
						</p>
						
						<?php
						if (!thp_paa_is_paa_pro_active()) {
							
							$pro_url = '/wp-admin/admin.php?page=thp-paa-admin-settings-pricing';
							
							$upgrade_link1 = sprintf( 
								/* translators: %s: is href for pro-version */
								wp_kses( __( 'To see FULL LOG of the attempted actions, please <a href="%s">upgrade to PRO</a>.', 'protect-admin-account' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ), 
								esc_url( $pro_url ) 
							);
						?>
							<p class="description" style="font-style:italic; font-size:13px; padding-top:7px;"><?php echo $upgrade_link1; ?></p>
						<?php } ?>
					</td>
				</tr>
				
				</tbody>
			</table>
			
			<p>
				<input name="thp_paa_options[activator_id]" type="hidden" value="<?php echo (!empty($thp_paa_options['activator_id']) ? $thp_paa_options['activator_id'] : ''); ?>">
				
				<?php
				if ($actionlog_arr) {
					foreach ($actionlog_arr as $key => $actionlog) {
						echo '<input name="thp_paa_options[actionlog]['.$key.']" type="hidden" value="'.$actionlog.'">';
					}
				}
				?>
				
			</p>
			
		</div>
		
	<?php
	}
}

if (!function_exists('thp_paa_options_handler')) {
	
	function thp_paa_options_handler() {
		
		if ( (isset( $_POST['thp_paa_admin_settings_nonce'])) && (wp_verify_nonce( $_POST['thp_paa_admin_settings_nonce'], 'update-options' )) ) {
			if ( (current_user_can( 'activate_plugins' )) && (current_user_can( 'manage_options' )) ) {
				
				$current_user = wp_get_current_user()->ID;
				$activator_id = thp_paa_get_activator_id();
				if ($current_user != $activator_id)
					wp_die( __( 'Sorry, you are not allowed to edit the settings.', 'protect-admin-account' ) );
				
				$new_options = filter_input(INPUT_POST, 'thp_paa_options', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);

				$user_ids = ( !empty( $new_options['protected_users'] ) ? $new_options['protected_users'] : '' );
				$user_ids = ( is_array($user_ids) ?	array_keys($user_ids) : $user_ids );
				
				if ($user_ids) {
					foreach ($user_ids as $user_id) {
						$user_obj = get_user_by('id', $user_id);
						$user_roles = !empty($user_obj->roles) ? $user_obj->roles : array();
						
						if (! in_array( 'administrator', $user_roles, true ) ) {
							echo '<div class="error"><p>';
							_e( "One or more user selected is not admin. All users selected must be admin. Settings are not saved.", 'protect-admin-account' );
							echo '</p></div>';
							return;
						}
					}
				}
				
				$status = update_option( 'thp_paa_options', $new_options );
				
				if ($status) {
					if ( get_option( 'thp_paa_options[protect_user1]' ) ) { //for users of version 0.1.0
						$deletion_status = delete_option( 'thp_paa_options[protect_user1]' );
						
						if ($deletion_status) {
							echo '<div class="notice notice-success"><p>';
							_e( "Settings have been updated successfully!", 'protect-admin-account' );
							echo '</p></div>';
						}
						else {
							echo '<div class="notice notice-error"><p>';
							_e( "Oops, something went wrong. Try saving your settings again.", 'protect-admin-account' );
							echo '</p></div>';
						}
					}
					else {
						echo '<div class="notice notice-success"><p>';
						_e( "Settings have been updated!", 'protect-admin-account' );
						echo '</p></div>';
					}
				} else
				if ( $status === false && $new_options ) {
					echo '<div class="notice notice-info is-dismissible"><p>';
					_e( "No changes.", 'protect-admin-account' );
					echo '</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>';
					_e( "Settings are not saved.", 'protect-admin-account' );
					echo '</p></div>';
				}
			}
		}
		else {
			echo '<div class="notice notice-error"><p>';
			_e( "Sorry, nonce verification failed. Settings are not saved.", 'protect-admin-account' );
			echo '</p></div>';
			exit;
		}
	}
}

if (!function_exists('thp_paa_options_submenus')) {
	function thp_paa_options_submenus($menu_ord) {
		global $submenu;

		if (array_key_exists("thp-paa-admin-settings", $submenu)) {
			$new_items_up = [];
			$new_items_low = [];
			$items_count = count($submenu['thp-paa-admin-settings']);
			$low_items = [
				'21' => 'thp-paa-admin-settings-contact', 
				'22' => 'thp-paa-admin-settings-affiliation', 
				'23' => 'thp-paa-admin-settings-wp-support-forum', 
				'24' => 'thp-paa-admin-settings-pricing'
			];
			foreach ($submenu['thp-paa-admin-settings'] as $key => $value) {
				if (in_array($value[2], $low_items)) {
					if ($value[2] == 'thp-paa-admin-settings-affiliation') {
						$value[0] = __( "Affiliates", 'protect-admin-account' );
					}
					$new_items_low[array_search ($value[2], $low_items)] = $value;
				} else {
					$new_items_up[] = $value;
				}
			}
			ksort($new_items_low);

			// Remove the originals
			unset($submenu['thp-paa-admin-settings']);

			// Add newly items to the list
			$submenu['thp-paa-admin-settings'] = [];
			$submenu['thp-paa-admin-settings'] += $new_items_up;
			$submenu['thp-paa-admin-settings'] += $new_items_low;
		}
		return $menu_ord;
	}

	add_filter('custom_menu_order', 'thp_paa_options_submenus');
}