<?php

class mal_membership_member {


    private function mal_membership_create_member( $_POST )
    {
        global $wpdb;

        // First step enter them into the users table
        /*
         * user_login = ( user_email )
         * user_pass ( MD5 )
         * user_nicename = ( user_email )
         * user_email = ( user_email )
         * user_url ( empty )
         * user_registered = (YYYY-MM-DD HH:ii:ss)
         * user_activation_key = ( empty )
         * user_status = ( 0 )
         * display_name = ( user_email )
         */

        $wpdb->insert(  $wpdb->prefix."users", array(
                                                          'user_login'=> $_POST['user_email'],
                                                          'user_pass' => md5($_POST['member_password']),
                                                          'user_nicename' => $_POST['user_email'],
                                                          'user_email' => $_POST['user_email'],
                                                          'user_registered' => date("Y-m-d H:i:s"),
                                                          'user_status' => '0',
                                                          'display_name' => $_POST['user_email']
                                                     ),
                                                    array(
                                                         '%s',
                                                         '%s',
                                                         '%s',
                                                         '%s',
                                                         '%s',
                                                         '%s',
                                                         '%s'
                                                    ) );
        $user_id = $wpdb->insert_id;
        
        // Second step enter the additional information into the user_meta
        /*
         * first_name
         * last_name
         * nickname ( user_email )
         * description = ( empty )
         * rich_editing = true
         * comment_shortcuts = false
         * admin_color = fresh
         * use_ssl = 0
         * show_admin_bar_front = false
         * show_admin_bar_admin = false
         * aim = ( empty )
         * yim = ( empty )
         * jabber = ( empty )
         * wp_capabilities = a:1:{s:10:"subscriber";s:1:"1";}
         * wp_user_level =  0
         * default_password_nag =  1
         * 
         */

        $usermeta_info = array(
                                  'first_name'=> $_POST['first_name'],
                                  'last_name' => $_POST['last_name'],
                                  'rich_editing' => 'true',
                                  'comment_shortcuts' => 'false',
                                  'admin_color' => 'fresh',
                                  'use_ssl' => '0',
                                  'user_status' => '0',
                                  'show_admin_bar_front' => 'false',
                                  'show_admin_bar_admin' => 'false',
                                  'wp_capabilities' => 'a:1:{s:10:"subscriber";s:1:"1";}',
                                  'wp_user_level' => '0',
                                  'default_password_nag' => '0'
                             );
                    foreach($usermeta_info as $key => $value)
                    {

                            $query = "INSERT INTO
                                            ".$wpdb->prefix."usermeta
                                        (
                                            user_id,
                                            meta_key,
                                            meta_value
                                         )
                                        VALUES
                                        (
                                            %d,
                                            '%s',
                                            '%s'
                                        )";

                            // do an insert
                            $wpdb->query( $wpdb->prepare( $query , array(  $user_id, $key, $value ) ) );

                    }

        
        // Lastly the remaining information into the mal_members
        if(empty($_POST['status']))
        {
            // Set the status to pending if one is not specified
            $status = 'pending';
        }

        $wpdb->insert(  $wpdb->prefix."mal_members", array(  'status'=> $status, 'user_id' => $user_id ), array( '%s', '%d' ) );


        return $user_id;
    }

    private function get_members_by_attribute( $status = '', $search_for = '', $sort_by = '', $order_by = '', $total_results = '' )
    {
        global $wpdb;

        if(!empty($total_results))
        {
            $limit = ' LIMIT '. $total_results;
        }

            $results_array = array();
            $query = "SELECT
                            ".$wpdb->prefix."mal_member_data.`user_id`,
                            `data_name`,
                            `value`,
                            `status`
                        FROM
                            ".$wpdb->prefix."mal_members,
                            ".$wpdb->prefix."mal_member_data
                        WHERE
                        ".$wpdb->prefix."mal_members.user_id = ".$wpdb->prefix."mal_member_data.user_id
                        AND
                            `data_name` = '$sort_by'
                        AND
                            `value` LIKE '%$search_for%' $limit";

            $result = $wpdb->get_results($query);

            foreach($result as $row)
            {

                $results_array[$row->user_id]['user_id']    = $row->user_id;
                $results_array[$row->user_id]['status']	    = $row->status;

                    $querya = "SELECT
                                    `data_name`,
                                    `value`
                              FROM
                                    ".$wpdb->prefix."mal_member_data
                              WHERE
                                    `user_id` = $row->user_id";
                    $resulta = $wpdb->get_results($querya);

                    foreach($resulta as $rowa)
                    {
                            $results_array[$row->user_id][$rowa->data_name] = $rowa->value;
                    }

            }

        return mal_membership_sort_list( $results_array, $sort_by, $order_by );

    }

    private function get_members_by_status( $status, $sort_by = '', $order_by = '' )
    {
        global $wpdb;
        
            $results_array = array();
            $query = "SELECT user_id FROM " . $wpdb->prefix . "mal_members WHERE status = '$status'";
            $result = $wpdb->get_results($query);

            foreach($result as $row)
            {

				$query = "SELECT
									status
								FROM
									".$wpdb->prefix."mal_members
								WHERE
									user_id = $row->user_id";

				$resulta = $wpdb->get_row($query);


                $results_array[$row->user_id]['user_id']    = $row->user_id;
				$results_array[$row->user_id]['status']	    = $resulta->status;

                    $query = "SELECT
                                    data_name,
                                    value
                              FROM
                                    ".$wpdb->prefix."mal_member_data
                              WHERE
                                    user_id = $row->user_id";
                    $resultb = $wpdb->get_results($query);

                    foreach($resultb as $rowa)
                    {
                            $results_array[$row->user_id][$rowa->data_name] = $rowa->value;
                    }

            }
        
        return mal_membership_sort_list( $results_array, $sort_by, $order_by );

    }

    public function mal_membership_get_member_by_id( $id )
    {
        if( $id != 'new' )
        {

		    global $wpdb;

             $user_info = get_userdata( $id );

				$query = "SELECT
				                    user_id,
									status
								FROM
									".$wpdb->prefix."mal_members
								WHERE
									user_id = $id";

				$result = $wpdb->get_row($query);
                $this->user_id      = $result->user_id;
				$this->status 		= $result->status;

                    $query = "SELECT
                                    data_name,
                                    value
                              FROM
                                    ".$wpdb->prefix."mal_member_data
                              WHERE
                                    user_id = $id";
                    $result = $wpdb->get_results($query);

                    foreach($result as $row)
                    {
                            $key 				= $row->data_name;
                            $value 				= $row->value;
                            $this->$key 		= $value;

                    }
            return $this;

        } // end check to see if this is a new entry, and if so, skip this

    }

    public function mal_membership_get_total_active_members()
    {

        global $wpdb;
        $query = "SELECT
                  COUNT(*) AS total
                        FROM
                            ".$wpdb->prefix."mal_members
                        WHERE
                            status = 'active'";

        $result = $wpdb->get_var($query);
        return $result;
    }

    public function mal_membership_get_active_members()
    {
        return mal_membership_member::get_members_by_status( 'active' );
    }

    public function mal_membership_get_disabled_members()
    {
        return mal_membership_member::get_members_by_status( 'disabled' );

    }

    public function mal_membership_get_pending_members()
    {
        return mal_membership_member::get_members_by_status( 'pending' );
    }

    public function mal_membership_get_members_using_filters( $status, $search_for, $sort_by, $order_by, $limit )
    {
        return mal_membership_member::get_members_by_attribute( $status, $search_for, $sort_by, $order_by, $limit );
    }

    public function mal_membership_verify_unique_email( $user_id, $email )
    {
        global $wpdb;
        if(!empty($user_id))
        {
            $query = "SELECT
                      COUNT(*) as total
                            FROM
                                ".$wpdb->prefix."mal_member_data
                            WHERE
                                user_id != '$user_id'
                            AND
                                data_name = 'user_email'
                            AND
                                value = '$email'";
        }
        else
        {
            // Public user sign up does not have a member id
            $query = "SELECT
                      COUNT(*) as total
                            FROM
                                ".$wpdb->prefix."mal_member_data
                            WHERE
                                data_name = 'user_email'
                            AND
                                value = '$email'";
        }

        $result = $wpdb->get_var($query);

        if($result == 0 )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function mal_membership_login_member( $email, $password )
    {

        include('./wp-includes/pluggable.php');
        $ok = user_pass_ok( $email, $password );
        if( $ok == true )
        {
            //require('./wp-blog-header.php');
            $user_login = $email;
            $user = get_userdatabylogin($user_login);
            $user_id = $user->ID;
            wp_set_current_user($user_id, $user_login);
            wp_set_auth_cookie($user_id);
            do_action('wp_login', $user_login);


            return $user_id;

        }
        else
        {
            return false;
        }
    }

    public function mal_membership_update_member()
    {
        global $wpdb;
        global $current_user;
        $admin_user_id = $current_user->ID;

		$user_id = $_POST['user_id'];
        if( $user_id != 'new' )
        {

            // Remove all old entries
            $query = "DELETE FROM
                                ".$wpdb->prefix."mal_member_data
                            WHERE
                                user_id = '$user_id'
                            ";
            $wpdb->query($query);
            
        }
        else
        {
            $user_id = mal_membership_member::mal_membership_create_member( $_POST );
        }

                    foreach($_POST as $key => $value)
                    {

                        if(!empty($value))
                        {
                            switch($key)
                            {
                                
                                case 'user_id':
                                case 'status':
                                case 'public_signup':
                                case 'redirect_url':
                                case 'confirm_member_password':
                                case 'display_name':
                                case 'user_login':
                                case 'user_pass':
                                case 'member_password':
                                case 'user_nicename':
                                case 'user_url':
                                case 'user_registered':
                                case 'user_activation_key':
                                case 'user_status':
                                case 'display_name':
                                case 'nickname':
                                case 'description':
                                case 'rich_editing':
                                case 'comment_shortcuts':
                                case 'admin_color':
                                case 'use_ssl':
                                case 'show_admin_bar_front':
                                case 'show_admin_bar_admin':
                                case 'aim':
                                case 'yim':
                                case 'jabber':
                                case 'wp_capabilities':
                                case 'wp_user_level':
                                case 'default_password_nag':
                                break;
                                default;
                            $query = "INSERT INTO
                                            ".$wpdb->prefix."mal_member_data
                                        (
                                            user_id,
                                            data_name,
                                            value
                                         )
                                        VALUES
                                        (
                                            %d,
                                            '%s',
                                            '%s'
                                        )";

                            // do an insert
                            $wpdb->query( $wpdb->prepare( $query , array(  $user_id, $key, $value ) ) );
                                break;
                            }
                        }

                    }

                    // No need to do this if it is new, this has already been completed
                    if($_POST['user_id'] != 'new')
                    {

                        // Now update the status, start date, end date and admin_user_id
                        $query = "UPDATE ".$wpdb->prefix."mal_members SET status = '{$_POST['status']}' WHERE user_id = '$user_id'";
                        $wpdb->query($query);

                        // Change the password only if change_password is set to yes or is not a submitted field
                        if( ($_POST['change_password'] == 'yes') || (!isset($_POST['change_password'])) )
                        {
                            // Update the password
                            $query = "UPDATE ".$wpdb->prefix."users SET user_pass = md5('{$_POST['member_password']}') WHERE ID = '$user_id'";
                            $wpdb->query($query);
                        }
                            // Update the users table
                            $query = "UPDATE ".$wpdb->prefix."users SET user_login = '{$_POST['user_email']}', user_nicename = '{$_POST['user_email']}', user_email = '{$_POST['user_email']}', display_name = '{$_POST['user_email']}' WHERE ID = '$user_id'";
                            $wpdb->query($query);

                        // Update the usermeta table
                        $query = "UPDATE ".$wpdb->prefix."usermeta SET meta_value = '{$_POST['first_name']}' WHERE meta_key = 'first_name' AND user_id = '$user_id'";
                        $wpdb->query($query);
                        
                        $query = "UPDATE ".$wpdb->prefix."usermeta SET meta_value = '{$_POST['last_name']}' WHERE meta_key = 'last_name' AND user_id = '$user_id'";
                        $wpdb->query($query);

                    }


                    if($_POST['user_id'] == 'new')
                    {
                        $redirect_message = 'success_member_new';
                    }
                    else
                    {
                        switch($_POST['status'])
                        {
                            case 'active':
                                $redirect_message = 'success_member_active';
                            break;
                            case 'disabled':
                                $redirect_message = 'success_member_disabled';
                            break;
                            default:
                                $redirect_message = 'success_member_pending';
                            break;

                        }

                    }
                if($_POST['public_signup'] == true)
                {
                    // Sign them in
                    include('./wp-includes/pluggable.php');
                    //require('./wp-blog-header.php');
                    $user_login = $_POST['user_email'];
                    $user = get_userdatabylogin($user_login);
                    $user_id = $user->ID;
                    wp_set_current_user($user_id, $user_login);
                    wp_set_auth_cookie($user_id);
                    do_action('wp_login', $user_login);

                    return $user_id;
                }
                else if ($_POST['public_edit'] == true )
                {
                    include('./wp-includes/pluggable.php');
                    wp_redirect($_POST['redirect_url'].'&edit=complete');
                }
                else
                {
                    //  general_functions.php:     mal_membership_redirect()
                    mal_membership_redirect('mal_membership_edit_member&user_id='.$user_id, $redirect_message);
                }
    }

}