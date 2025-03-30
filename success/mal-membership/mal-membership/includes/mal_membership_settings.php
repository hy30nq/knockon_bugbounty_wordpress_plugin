<?php

function mal_membership_settings()
{

    global $wpdb;
    global $current_user;
    $message = $_GET['message'];
    $settings_info = new mal_membership_settings();

    get_currentuserinfo($current_user->ID);

    echo '<div class="wrap">';

    if(!empty($message))
    {
        echo mal_membership_translate_message($message);
    }

    if (isset($_POST['mal_membership_non_member_message'])) {


        $errors = array();



        if (empty($_POST['mal_membership_non_member_message']))
        {
            $errors[] = 'You forgot the non member message to display when a visitor tries to go to a members only page';
        }


        if (empty($errors))
		{


			$settings_info->mal_membership_update_settings();
			die();
        }
		else
		{
            echo '<div class="error">';
            echo '<div style="font-weight: bold;">Errors found</div>';
            foreach ($errors as $list) {
                echo '<div class="errorDiv">&nbsp;&nbsp;' . $list . '</div>';
            }
            echo '</div>';
        }
    }

        $wp_user_level 	= $wpdb->prefix . 'user_level';
        $current_level 	= $current_user->$wp_user_level;


        if ( $current_user->user_level >= 7 )
		{

			?>
            <div id="mal-membership-settings-content-wrapper">
            <h3>Mal Membership version <?php echo get_option( "mal_membership_version" ); ?></h3>
            <h3>Mal Membership Database Version <?php echo get_option( "mal_membership_database_version" ); ?></h3>
            
            <div>&nbsp;</div>
        	<form method="post" id="malMembershipSettingsForm" class="malForm">
            <div>Message for non members<em>*</em><br></div>
            <textarea name="mal_membership_non_member_message" class="required" id="mal_membership_non_member_message" cols="60" rows="4"><?php echo mal_membership_sticky_input( $_POST['mal_membership_non_member_message'], get_option('mal_membership_non_member_message') ); ?></textarea>
            <div>Message visitors when they go to the sign up page<em>*</em><br></div>
            <textarea name="mal_membership_sign_up_message"  id="mal_membership_sign_up_message" cols="60" rows="4"><?php echo mal_membership_sticky_input( $_POST['mal_membership_sign_up_message'], get_option('mal_membership_sign_up_message') ); ?></textarea>
            <div>Message visitors when they go to the sign in page<em>*</em><br></div>
            <textarea name="mal_membership_sign_in_message"  id="mal_membership_sign_in_message" cols="60" rows="4"><?php echo mal_membership_sticky_input( $_POST['mal_membership_sign_in_message'], get_option('mal_membership_sign_in_message') ); ?></textarea>
            <div>Message visitors when they log out<em>*</em><br></div>
            <textarea name="mal_membership_log_out_message"  id="mal_membership_log_out_message" cols="60" rows="4"><?php echo mal_membership_sticky_input( $_POST['mal_membership_log_out_message'], get_option('mal_membership_log_out_message') ); ?></textarea>

            <div>Message visitors when they edit their account with NO errors<em>*</em><br></div>
            <textarea name="mal_membership_edit_success_message"  id="mal_membership_edit_success_message" cols="60" rows="4"><?php echo mal_membership_sticky_input( $_POST['mal_membership_edit_success_message'], get_option('mal_membership_edit_success_message') ); ?></textarea>


            <div class="clear"></div>
            <?php

		}
		else
		{
		?>
            <h2>Sorry you cant adjust the settings, you need to be an administrator</h2>
        <?php
		}
		?>
       <div class="clear float-left">
		   <?php
            // Make sure this is not a read only user
            //  general_functions.php:     sbm_check_read_only_user()
            if( ( mal_membership_check_read_only_user() == false ) || ( $current_user->ID == $id ) )
            {
                echo '<span><input type="submit" value="Submit" id="editUserSubmitButton"></span>';
            }
            //  general_functions.php:     sbm_cancel_button()
            echo mal_membership_cancel_button('sbm_view_home_page', 'cancel');
            ?>

        </div>

        </form>

        <br>
        <br>
        <?php
            //  help_functions.php:     sbm_display_help()
            echo mal_membership_display_help( 'settings' );
        ?>

        <div id="remove_plugin_message">
        	<div>If you need to uninstall this plugin, the database tables and options that were created during setup will NOT be deleted.</div>
        	<div>You can remove all the old settings and any database tables that were created by clicking this <a href="javascript: void(0);" id="malMembershipUninstall">link</a>.</div>
        </div>



    <div id="toggle-table-structure"><a href="javascript: void(0);" class="mal-toggle-view-link">Show/HIde Table Structure</a></div>
	<div id="mal-toggle-view-content">
    	<?php
			$query = "SHOW TABLES LIKE '%mal%'";
			$result = mysql_query($query);

			while($row = mysql_fetch_array($result))
			{
				echo '<h3>Table Name: ' . $row[0] . '</h3>';
                
				$sql = "DESCRIBE {$row[0]}";
				$ans = mysql_query($sql);
                $array = array();
				while( $return = mysql_fetch_array($ans) )
				{
					$array[] = '<b>' . $return[Field] . '<b>';

				}

                mal_membership_pre_array( $array );
				echo '<hr>';

			}
		?>
    </div>
    </div>
       <div id="ajax_message"></div>
   </div>
   <?php
}