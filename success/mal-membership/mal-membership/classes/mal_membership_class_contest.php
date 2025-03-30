<?php

class mal_membership_contest {

    

    private function mal_membership_create_contest( $start_date, $end_date, $status )
    {
        global $wpdb;
        global $current_user;
        $admin_user_id = $current_user->ID;

        $wpdb->insert(  $wpdb->prefix."mal_contest", array(  'status'=> $status, 'start_date'=> $start_date, 'end_date'=> $end_date , 'admin_user_id'=> $admin_user_id ), array( '%s', '%s', '%s', '%d' ) );

        return $wpdb->insert_id;
    }

    private function get_contests_by_status( $status, $start_date = '', $end_date = '' )
    {
        global $wpdb;

            $results_array = array();
            if(empty($start_date))
            {
                $query = "SELECT ID FROM " . $wpdb->prefix . "mal_contest WHERE status = '$status'";
            }
            else
            {
                // We only want to show contests that are good through today
                $query = "SELECT ID FROM " . $wpdb->prefix . "mal_contest WHERE start_date < '$start_date' AND end_date > '$end_date' AND status = '$status'";
            }
            $result = $wpdb->get_results($query);

            foreach($result as $row)
            {

				$query = "SELECT
									start_date,
									end_date,
									status
								FROM
									".$wpdb->prefix."mal_contest
								WHERE
									ID = $row->ID";

				$resulta = $wpdb->get_row($query);
                

                $results_array[$row->ID]['ID']  = $row->ID;
                $results_array[$row->ID]['start_date']  = $resulta->start_date;
				$results_array[$row->ID]['end_date']    = $resulta->end_date;
				$results_array[$row->ID]['status']	    = $resulta->status;

                    $query = "SELECT
                                    data_name,
                                    value
                              FROM
                                    ".$wpdb->prefix."mal_contest_data
                              WHERE
                                    contest_id = $row->ID";
                    $resultb = $wpdb->get_results($query);

                    foreach($resultb as $rowa)
                    {
                            $results_array[$row->ID][$rowa->data_name] = stripslashes($rowa->value);
                    }


            }
        
        return $results_array;
    }

    public function mal_membership_set_old_contests_as_completed()
    {
        global $wpdb;

            $now = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y")));
            $query = "SELECT ID FROM " . $wpdb->prefix . "mal_contest WHERE end_date < '$now'";
            $result = $wpdb->get_results($query);

            foreach($result as $row)
            {
                $sql = "UPDATE ".$wpdb->prefix."mal_contest SET status='completed' WHERE ID = $row->ID";
            	$wpdb->query($sql);
                $sql = "UPDATE ".$wpdb->prefix."mal_prize SET status='completed' WHERE contest_id = $row->ID";
                $wpdb->query($sql);
            }
        
    }
    public function mal_membership_get_contest_by_id( $id )
    {
        if( $id != 'new' )
        {

		    global $wpdb;
				$query = "SELECT
				                    ID,
									start_date,
									end_date,
									status
								FROM
									".$wpdb->prefix."mal_contest
								WHERE
									ID = $id";

				$result = $wpdb->get_row($query);
                $this->ID           = $result->ID;
				$this->start_date 	= $result->start_date;
				$this->end_date     = $result->end_date;
				$this->status 		= $result->status;

                    $query = "SELECT
                                    data_name,
                                    value
                              FROM
                                    ".$wpdb->prefix."mal_contest_data
                              WHERE
                                    contest_id = $id";
                    $result = $wpdb->get_results($query);

                    foreach($result as $row)
                    {
                            $key 				= $row->data_name;
                            $value 				= stripslashes($row->value);
                            $this->$key 		= $value;

                    }
            return $this;

        } // end check to see if this is a new entry, and if so, skip this

    }

    public function mal_membership_get_completed_contests_needing_winners()
    {
        global $wpdb;

        $results_array  = array();
        $complete_list  = array();
        $winner_list    = array();

        $query = "SELECT DISTINCT contest_id FROM `".$wpdb->prefix."mal_contest_entry` WHERE winner =0";
        $result = $wpdb->get_results($query);

        foreach($result as $row)
        {
           $complete_list[] = $row->contest_id;

        }
        $query = "SELECT DISTINCT contest_id FROM `".$wpdb->prefix."mal_contest_entry` WHERE winner =1";
        $result = $wpdb->get_results($query);

        foreach($result as $row)
        {
           $winner_list[] = $row->contest_id;

        }

       $result_list = $this->_removeDuplicates($complete_list,$winner_list);

        foreach($result_list as $list)
        {

				$query = "SELECT
									start_date,
									end_date,
									status
								FROM
									".$wpdb->prefix."mal_contest
								WHERE
									ID = $list";

				$resulta = $wpdb->get_row($query);


                $results_array[$list]['ID']  = $list;
                $results_array[$list]['start_date']  = $resulta->start_date;
				$results_array[$list]['end_date']    = $resulta->end_date;
				$results_array[$list]['status']	    = $resulta->status;

                    $query = "SELECT
                                    data_name,
                                    value
                              FROM
                                    ".$wpdb->prefix."mal_contest_data
                              WHERE
                                    contest_id = $list";
                    $resultb = $wpdb->get_results($query);

                    foreach($resultb as $rowa)
                    {
                            $results_array[$list][$rowa->data_name] = stripslashes($rowa->value);
                    }

        }

        return $results_array;
    }
    private function _removeDuplicates($first_array, $second_array) {
        foreach ($first_array as $key => $first_array_id) {
            foreach ($second_array as $k => $second_array_id) {
                if ($first_array_id === $second_array_id) {
                    unset($first_array[$key]);
                }
            }
        }
        return $first_array;
    }

    public function mal_membership_get_active_contests()
    {
        return mal_membership_contest::get_contests_by_status( 'active' );
    }
    public function mal_membership_get_running_contests()
    {
        return mal_membership_contest::get_contests_by_status( 'active', date("Y-m-d H:i:s"), date("Y-m-d 23:59:59"));
    }

    public function mal_membership_get_completed_contests()
    {
        return mal_membership_contest::get_contests_by_status( 'completed' );

    }
    
    public function mal_membership_get_pending_contests()
    {
        return mal_membership_contest::get_contests_by_status( 'pending' );
    }

    public function mal_membership_get_cancelled_contests()
    {
        return mal_membership_contest::get_contests_by_status( 'cancelled' );
    }

    public function mal_membership_update_contest()
    {
        global $wpdb;
        global $current_user;
        $admin_user_id  = $current_user->ID;
        $status         = $_POST['status'];

		$contest_id = $_POST['contest_id'];
        if( $contest_id != 'new' )
        {

            // Remove all old entries
            $query = "DELETE FROM ".$wpdb->prefix."mal_contest_data WHERE contest_id = '$contest_id'";
            $wpdb->query($query);

            // If they have an old_image_id AND are now using youtube embed we need to delete that old image
            if(!empty($_POST['youtube_embed']) && !empty($_POST['old_contest_large_image_id']))
            {
                wp_delete_attachment($_POST['old_contest_large_image_id']);
            }

        }
        else
        {
            $contest_id = mal_membership_contest::mal_membership_create_contest($_POST['start_date'], $_POST['end_date'], $_POST['status']);
        }

                    foreach($_POST as $key => $value)
                    {

                        if(!empty($value))
                        {
                            switch($key)
                            {
                                case 'start_date':
                                case 'end_date':
                                case 'contest_id':
                                case 'status':
                                break;
                                default;
                            $query = "INSERT INTO ".$wpdb->prefix."mal_contest_data ( contest_id, data_name, value ) VALUES ( %d, %s, %s )";
                            // do an insert
                            $wpdb->query( $wpdb->prepare( $query , array(  $contest_id, $key, $value ) ) );
                                break;
                            }
                        }

                    }
                    // Now update the status, start date, end date and admin_user_id
                    $query = "UPDATE ".$wpdb->prefix."mal_contest SET start_date = '{$_POST['start_date']}', end_date = '{$_POST['end_date']}', status = '$status', admin_user_id = '$admin_user_id' WHERE ID = '$contest_id'";
                    $wpdb->query($query);

                    if($_POST['contest_id'] == 'new')
                    {
                        $redirect_message = 'success_contest_new';
                    }
                    else
                    {
                        switch($_POST['status'])
                        {
                            case 'active':
                                $redirect_message = 'success_contest_active';
                            break;
                            case 'completed':
                                $redirect_message = 'success_contest_completed';
                            break;
                            case 'cancelled':
                                $redirect_message = 'success_contest_cancelled';
                            break;
                            default:
                                $redirect_message = 'success_contest_pending';
                            break;

                        }

                    }
                    // reset $_POST
                    foreach($_POST as $key => $post)
                    {
                        $_POST[$key] = NULL;
                    }
                    $_POST = NULL;
                    // redirect user back to the view odometer list page
                    //  general_functions.php:     sbm_redirect()
                    mal_membership_redirect('mal_membership_edit_contest&contest_id='.$contest_id, $redirect_message);
                    exit();
    }

}