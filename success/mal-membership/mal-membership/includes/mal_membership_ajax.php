<?php

/*
 *  Function:   mal_membership_select_new_winner
 *              Uses ajax to remove the original winner and select a new one
 *
 *  @param
 *  @return New winner information
 */
    function mal_membership_select_new_winner()
    {
        global $wpdb;

        $member_info    = new mal_membership_member();

        $contest_id     = $_POST['contest_id'];
        $prize_id       = $_POST['prize_id'];
        $user_id        = $_POST['user_id'];
        $winner_info    = '';

        if( empty($contest_id) || empty($prize_id) || empty($user_id) )
        {

            $winner_info = 'There was a problem picking a new winner! Please try again.';
        }
        else
        {
            // Make sure that the person they are replacing is a winner for the contest
            $query = "SELECT COUNT(user_id) as total FROM ".$wpdb->prefix."mal_contest_entry WHERE contest_id = '$contest_id' AND winner = 1 AND prize_id = $prize_id AND user_id = $user_id";
            $total = $wpdb->get_row($query);

            if($total->total == '0')
            {
                $winner_info = 'The user that was submitted was not a winner for this prize!';
            }
            else
            {

                // Select the winner
                $query = "SELECT ID, user_id FROM ".$wpdb->prefix."mal_contest_entry WHERE contest_id = '$contest_id' AND winner = 0 AND prize_id = $prize_id ORDER BY RAND() LIMIT 1";
                $results = $wpdb->get_results($query);

                if(count($results) > 0)
                {
                    $i = 1;
                    foreach($results as $row)
                    {
                        // Update the original winner and set to NOT the winner
                        $remove_winner = "UPDATE ".$wpdb->prefix."mal_contest_entry SET winner = '0' WHERE user_id = '$user_id' AND contest_id = '$contest_id' AND prize_id = '$prize_id'";
                        $wpdb->query($remove_winner);

                        // Update the table to set this member as the winner
                        $new_winner = "UPDATE ".$wpdb->prefix."mal_contest_entry SET winner = '1' WHERE ID = '$row->ID'";
                        $wpdb->query($new_winner);

                        $member_info->mal_membership_get_member_by_id($row->user_id);

                        $winner_info .= 'Winner # ' . $i . '<br>';
                        $winner_info .= 'Name: ' . $member_info->first_name . ' ' . $member_info->last_name . ' ( <a href="./admin.php?page=mal_membership_replace_winner&contest_id='.$contest_id.'&prize_id='.$prize_id.'&user_id='.$row->user_id.'">Replace This Winner</a> )<br>';
                        $winner_info .= 'Address: ' . $member_info->address_1 . '<br>';
                        $winner_info .= 'Address: ' . $member_info->address_2 . '<br>';
                        $winner_info .= 'City/State/Zip: ' . $member_info->city . ' ' . $member_info->state . '. ' . $member_info->zip_code . '<br>';
                        $winner_info .= 'Phone 1: ' . $member_info->phone_1 . '<br>';
                        $winner_info .= 'Phone 2: ' . $member_info->phone_2 . '<br>';
                        $winner_info .= 'Email: ' . $member_info->user_email . '<br>';
                        $winner_info .= '<hr>';
                        $i++;
                        // Reset data to prevent it from showing up again by accident
                        $member_info->first_name    = null;
                        $member_info->last_name     = null;
                        $member_info->address_1     = null;
                        $member_info->address_2     = null;
                        $member_info->city          = null;
                        $member_info->state         = null;
                        $member_info->zip_code      = null;
                        $member_info->phone_1       = null;
                        $member_info->phone_2       = null;
                        $member_info->user_email    = null;

                    }
                }
                else
                {
                    // Over-ride the winner_info and just put out the message that no one entered this prize
                     $winner_info = 'No one else entered for this prize, the original winner will remain as the winner.';
                }
            }
        }

        echo $winner_info;
        exit();
    }
    /*
     *  Function:   mal_membership_search_members
     *              Uses ajax to fetch a list of members based on the filters
     *
     *  @param
     *  @return <tr><td>edit</td><td>first name</td><td>last name</td><td>address</td><td>phone</td><td>email</td></tr>
     */

    function mal_membership_select_the_winner()
    {
        global $wpdb;
        
        $member_info    = new mal_membership_member();
        $prize_info   = new mal_membership_prize();

        $contest_id     = $_POST['contest_id'];
        $prize_id       = $_POST['prize_id'];
        $winner_info    = '';

        // first check to see if a winner has already been selected
        $query = "SELECT COUNT(user_id) AS total_winners FROM ".$wpdb->prefix."mal_contest_entry WHERE contest_id = '$contest_id' AND prize_id = '$prize_id' AND winner = 1";
        $result = $wpdb->get_row($query);


        if( $result->total_winners == 0 )
        {
            $total_prizes = $prize_info->mal_membership_get_prize_by_id($prize_id)->number_of_prizes;

            // Select the winner
            $query = "SELECT ID, user_id FROM ".$wpdb->prefix."mal_contest_entry WHERE contest_id = '$contest_id' AND winner = 0 AND prize_id = $prize_id ORDER BY RAND() LIMIT $total_prizes";
            $results = $wpdb->get_results($query);

            if(count($results) > 0)
            {
                $i = 1;
                foreach($results as $row)
                {
                    // Update the table to set this member as the winner
                    $update_query = "UPDATE ".$wpdb->prefix."mal_contest_entry SET winner = '1' WHERE ID = '$row->ID'";
                    $wpdb->query($update_query);

                    $member_info->mal_membership_get_member_by_id($row->user_id);

                    $winner_info .= 'Winner # ' . $i . '<br>';
                    $winner_info .= 'Name: ' . $member_info->first_name . ' ' . $member_info->last_name . ' ( <a href="./admin.php?page=mal_membership_replace_winner&contest_id='.$contest_id.'&prize_id='.$prize_id.'&user_id='.$row->user_id.'">Replace This Winner</a> )<br>';
                    $winner_info .= 'Address: ' . $member_info->address_1 . '<br>';
                    $winner_info .= 'Address: ' . $member_info->address_2 . '<br>';
                    $winner_info .= 'City/State/Zip: ' . $member_info->city . ' ' . $member_info->state . '. ' . $member_info->zip_code . '<br>';
                    $winner_info .= 'Phone 1: ' . $member_info->phone_1 . '<br>';
                    $winner_info .= 'Phone 2: ' . $member_info->phone_2 . '<br>';
                    $winner_info .= 'Email: ' . $member_info->user_email . '<br>';
                    $winner_info .= '<hr>';
                    $i++;

                    // Reset data to prevent it from showing up again by accident
                    $member_info->first_name    = null;
                    $member_info->last_name     = null;
                    $member_info->address_1     = null;
                    $member_info->address_2     = null;
                    $member_info->city          = null;
                    $member_info->state         = null;
                    $member_info->zip_code      = null;
                    $member_info->phone_1       = null;
                    $member_info->phone_2       = null;
                    $member_info->user_email    = null;

                }
            }
            else
            {
                // Over-ride the winner_info and just put out the message that no one entered this prize
                 $winner_info = 'No one entered for this prize';
            }

        }
        else
        {
            $i = 1;
            $winner_collection = mal_membership_get_winners_for_prize($contest_id, $prize_id);
            foreach($winner_collection as $member_id => $winner)
            {
                // Reset values to prevent carry over
                $winner['first_name']   = '';
                $winner['last_name']    = '';
                $winner['address_1']    = '';
                $winner['address_2']    = '';
                $winner['city']         = '';
                $winner['state']        = '';
                $winner['zip_code']     = '';
                $winner['phone_1']      = '';
                $winner['phone_2']      = '';

                $winner_info .= '<h2>Previous Results</h2>';
                $winner_info .= 'Winner # ' . $i . '<br>';
                $winner_info .= 'Name: ' . $winner['first_name'] . ' ' . $winner['last_name'] . ' ( <a href="./admin.php?page=mal_membership_replace_winner&contest_id='.$contest_id.'&prize_id='.$prize_id.'&user_id='.$row->user_id.'">Replace This Winner</a> )<br>';
                $winner_info .= 'Address: ' . $winner['address_1'] . '<br>';
                $winner_info .= 'Address: ' . $winner['address_2'] . '<br>';
                $winner_info .= 'City/State/Zip: ' . $winner['city'] . ' ' . $winner['state'] . '. ' . $winner['zip_code'] . '<br>';
                $winner_info .= 'Phone 1: ' . $winner['phone_1'] . '<br>';
                $winner_info .= 'Phone 2: ' . $winner['phone_2'] . '<br>';
                $winner_info .= '<hr>';
                $i++;
            }


        }

        echo $winner_info;
        exit();
    }

    function mal_membership_get_winners_for_prize($contest_id, $prize_id)
    {
        global $wpdb;

        $member_info    = new mal_membership_member();
        $winner_info    = array();

        // show the winners information
        // Select the winner
        $query = "SELECT ID, user_id FROM ".$wpdb->prefix."mal_contest_entry WHERE contest_id = '$contest_id' AND winner = 1 AND prize_id = $prize_id";
        $results = $wpdb->get_results($query);
        foreach($results as $row)
        {

            $member_info->mal_membership_get_member_by_id($row->user_id);

            //Reset the values so they dont show up again

            $member_info->first_name    = null;
            $member_info->last_name     = null;
            $member_info->address_1     = null;
            $member_info->address_2     = null;
            $member_info->city          = null;
            $member_info->state         = null;
            $member_info->zip_code      = null;
            $member_info->phone_2       = null;

            $winner_info[$row->user_id]['user_id']      = $row->user_id;
            $winner_info[$row->user_id]['first_name']   = $member_info->first_name;
            $winner_info[$row->user_id]['last_name']    = $member_info->last_name;
            $winner_info[$row->user_id]['address_1']    = $member_info->address_1;
            $winner_info[$row->user_id]['address_2']    = $member_info->address_2;
            $winner_info[$row->user_id]['city']         = $member_info->city;
            $winner_info[$row->user_id]['state']        = $member_info->state;
            $winner_info[$row->user_id]['zip_code']     = $member_info->zip_code;
            $winner_info[$row->user_id]['phone_1']      = $member_info->phone_1;
            $winner_info[$row->user_id]['phone_2']      = $member_info->phone_2;
        }


        return $winner_info;
    }

    function mal_membership_search_members()
    {
        global $wpdb;
        $member_info    = new mal_membership_member();
        $member_list    = array();
        $result_collection = array();
        // Limit should always have a value, but if not, make it 25
        $limit      = $_POST['limit'];
        if(empty($limit))
        {
            $limit = 25;
        }
        // Pending, active and disabled are boolean true or false
        $pending        = $_POST['pending'];
        $active         = $_POST['active'];
        $disabled       = $_POST['disabled'];

        $order_by       = $_POST['order_by'];
        $sort_by        = $_POST['sort_by'];
        $search_for     = $_POST['search_for'];

        if( $pending == 'true' )
        {
            $result_collection[] = $member_info->mal_membership_get_members_using_filters( 'pending', $search_for, $sort_by, $order_by, $limit );
        }
        if( $active == 'true' )
        {
            $result_collection[] = $member_info->mal_membership_get_members_using_filters( 'active', $search_for, $sort_by, $order_by, $limit );
        }
        if( $disabled == 'true' )
        {
            $result_collection[] = $member_info->mal_membership_get_members_using_filters( 'disabled', $search_for, $sort_by, $order_by, $limit );
        }


            $output = '<table><tr class="medium-column left-text"><th></th><th class="medium-column left-text">First Name</th><th class="medium-column left-text">Last Name</th><th class="medium-column left-text">Email</th></tr>';

             $i = 0;
            foreach( $result_collection as $member )
            {

                foreach($member as $data)
                {
                    $member_data = $member_info->mal_membership_get_member_by_id($data[user_id]);

                    if(!mal_membership_has_member_been_displayed($member_list, $data[user_id]))
                    {
                        $output .= '<tr>
                                        <td class="medium-column"><a href="admin.php?page=mal_membership_edit_member&user_id='.$data[user_id].'">Edit</a></td>
                                        <td class="medium-column">' . $member_data->first_name . '</td>
                                        <td class="medium-column">' . $member_data->last_name . '</td>
                                        <td class="medium-column">' . $member_data->user_email . '</td>
                                     </tr>';
                         $i++;
                    }
                    $member_list[] = $data[user_id];
                }
            }

            $output .='</table>';

       if($i == 0)
       {
           $output = 'No results match your search criteria';
       }
        echo $output;
        exit();
    }

function mal_membership_has_member_been_displayed($member_list, $member_id)
{
    $count = 0;
    foreach($member_list as $item)
    {
        if($member_id == $item)
        {
            $count++;
        }
    }
    if($count == 0 )
    {
        return false;
    }
    else
    {
        return true;
    }
}

/*
     *  Function:   mal_membership_clear_all_tables
     *              Uses ajax to clear all all tables that were created during install of the plugin
     *              Also removes all options created during install of the plugin
     *
     *  @param
     *  @return several messages as the function process
     */

    function mal_membership_uninstall()
	{

		global $wpdb;
        $wpdb->show_errors();

        $query = "SHOW TABLES LIKE '%mal%'";
        $result = mysql_query($query);

        while($row = mysql_fetch_array($result))
        {
            $sql = "DROP TABLE {$row[0]}";
            $ans = mysql_query($sql);


        }

        /*
		//This removes the tables created during setup
        $sql = "DROP TABLE
                        ".$wpdb->prefix."mal_contest,
                        ".$wpdb->prefix."mal_members,
                        ".$wpdb->prefix."mal_prize,
                        ".$wpdb->prefix."mal_contest_data,
                        ".$wpdb->prefix."mal_member_data,
                        ".$wpdb->prefix."mal_prize_data";
        $query = $wpdb->query($sql);
        */


        // Remove all the options we setup during install of the plugin
        
        delete_option( 'mal_membership_version' );
        delete_option( 'mal_membership_database_version' );
        delete_option( 'mal_membership_non_member_message' );


        // Destroy all the sessions for our plugin
        $_SESSION['mal_membership_version']						= NULL;
        $_SESSION['mal_membership_database_version']			= NULL;
        $_SESSION['mal_membership_installed_version'] 			= NULL;
        $_SESSION['mal_membership_installed_database_version'] 	= NULL;

        $nonce = wp_create_nonce('my-nonce');
        //echo '<h2><a href="plugins.php?action=deactivate&plugin=mal-membership%2Fmal-membership.php&plugin_status=all&paged=1&s&;_wpnonce='. $nonce .'">Please click here, to deactivate the plugin!</a></h2>';
        echo '<h2><a href="plugins.php?mal_membership=deactivate">Deactivate the plugin by clicking here and using the deactivate link</a></h2>';
		//This  exit(); is needed otherwise you get a a 0 at the end of the string/returned value
		exit();
	}

    function mal_membership_set_number_votes_per_day()
    {
        global $wpdb;

        $contest_info               = new mal_membership_contest();

        $message                    = '';
        $contest_id                 = $_POST['contest_id'];
        $prize_id                   = $_POST['prize_id'];
        $start_date                 = strtotime($_POST['start_date']);
        $end_date                   = strtotime($_POST['end_date']);
        $contest                    = $contest_info->mal_membership_get_contest_by_id($contest_id);
        $contest_start_date         = strtotime($contest->start_date);
        $contest_end_date           = strtotime($contest->end_date);
        $default_number_of_votes    = $_POST['default_number_of_votes'];

        // Verify that the start date and end date for the prize are within the start and end date of the contest
        if( ( $start_date >= $contest_start_date ) && ( $end_date <= $contest_end_date ) )
        {
            $message .= '<table style="border:  1px solid black; width: 800px;">';
            $message .= '<tr>
                            <th>Sunday</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                            <th>Saturday</th>
                        </tr>';
            $days = mal_membership_get_days($_POST['start_date'], $_POST['end_date'] );
            $i = 0;
            $count = (int) count($days) - 1;

            foreach($days as $day)
            {
               $day_of_the_week = date("w", strtotime($day));
               
               if(  $i == 0 || ( $day_of_the_week == 0 ) )
               {
                   $message .='<tr>';
               }
                
               if($i == 0)
               {
                   // Fill in td's that are before the first day


                   switch( $day_of_the_week )
                   {
                       case 0:
                       break;
                       case 1:
                           $message .= '<td></td>';
                       break;
                       case 2:
                           $message .= '<td></td><td></td>';
                       break;
                       case 3:
                           $message .= '<td></td><td></td><td></td>';
                       break;
                       case 4:
                           $message .= '<td></td><td></td><td></td><td></td>';
                       break;
                       case 5:
                           $message .= '<td></td><td></td><td></td><td></td><td></td>';
                       break;
                       case 6:
                           $message .= '<td></td><td></td><td></td><td></td><td></td><td></td>';
                       break;
                   }
               }

                $message .= '<td>
                                <div>'.$day.'</div>
                                <div><input type="text" class="required number number_of_votes" name="number_of_votes_for_day['.$day.']" size="4" value="'.$default_number_of_votes.'"></div>
                             </td>';
                if( $i == $count )
                {
                    switch( $day_of_the_week )
                    {
                        case 0:
                            $message .= '<td></td><td></td><td></td><td></td><td></td><td></td>';
                        break;
                        case 1:
                            $message .= '<td></td><td></td><td></td><td></td><td></td>';
                        break;
                        case 2:
                            $message .= '<td></td><td></td><td></td><td></td>';
                        break;
                        case 3:
                            $message .= '<td></td><td></td><td></td>';
                        break;
                        case 4:
                            $message .= '<td></td><td></td>';
                        break;
                        case 5:
                            $message .= '<td></td>';
                        break;
                        case 6:
                        break;
                    }
                }
                if( $day_of_the_week == 6 )
                {
                    $message .='</tr>';
                }
                $i++;
            }
            $message .= '</table>';
        }
        else
        {
            $message = '<div class="error">
                Check the start and end dates for the prize, they do not appear to be correct<br>
                Contest Start: '.$contest->start_date . ' Contest End: ' . $contest->end_date . '<br>
                Prize Start: ' . $_POST['start_date'] . ' Prize End: ' . $_POST['end_date'] . '

                </div>';
        }


        echo $message;
        exit();
    }

    function mal_membership_prize_entry_submit()
    {
        $prize_info                         = new mal_membership_prize();

        $nonce                              = $_POST['postCommentNonce'];
        $user_id                            = $_POST['user_id'];
        $prize_id                           = $_POST['prize_id'];
        $contest_id                         = $_POST['contest_id'];
        $total_entries_for_prize            = (int) $prize_info->mal_membership_get_mal_prize_entry_details_by_prize_id($prize_id, date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59"));
        $total_votes_for_prize_by_member    = (int) $prize_info->mal_membership_get_total_entries_for_prize_today($contest_id, $prize_id, $user_id, date("Y-m-d 00:00:00") );
        if($user_id != null)
        {
            // check to see if the submitted nonce matches with the
            // generated nonce we created earlier
            if ( ! wp_verify_nonce( $nonce, 'myajax-post-comment-nonce' ) )
            {
                 die ( 'There has been a problem with your contest entry submission, please try again later.' );
            }

            // If the total entries for the prize is greater than or equal to the total entries then allow the entry
            if($total_entries_for_prize > $total_votes_for_prize_by_member)
            {

                // Enter this member for the prize
                $prize_info->mal_membership_enter_member_into_contest_for_prize();
                $total_votes_for_prize_by_member    = (int) $prize_info->mal_membership_get_total_entries_for_prize_today($contest_id, $prize_id, $user_id, date("Y-m-d 00:00:00") );

            }


            $votes_left = $total_entries_for_prize - $total_votes_for_prize_by_member;


            // ignore the request if the current user doesn't have
            // sufficient permissions
            if ( current_user_can( 'edit_posts' ) ) {
                // get the submitted parameters
                $postID = $_POST['postID'];

                // generate the response
                //$response = json_encode( array( 'success' => true ) );

                // response output
                //header( "Content-Type: application/json" );
                //echo $response;
            }
            //header( "Content-Type: application/json" );
            echo $total_votes_for_prize_by_member . ',' . $votes_left;

        }
        else
        {
            echo 'ERROR NO USER ID';
        }
        // IMPORTANT: don't forget to "exit"
        exit;
    }


