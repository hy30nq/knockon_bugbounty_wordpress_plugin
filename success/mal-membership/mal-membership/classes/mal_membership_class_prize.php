<?php

class mal_membership_prize {


    private function mal_membership_create_prize( $start_date, $end_date, $status, $contest_id )
    {
        global $wpdb;
        global $current_user;
        $admin_user_id = $current_user->ID;

        $wpdb->insert(  $wpdb->prefix."mal_prize", array(  'status'=> $status, 'start_date'=> $start_date, 'end_date'=> $end_date , 'admin_user_id'=> $admin_user_id , 'contest_id'=> $contest_id ), array( '%s', '%s', '%s', '%d', '%d' ) );

        return $wpdb->insert_id;
    }

    private function get_prizes_by_status( $status )
    {
        global $wpdb;

            $results_array = array();
            $query = "SELECT ID FROM " . $wpdb->prefix . "mal_prize WHERE status = '$status'";
            $result = $wpdb->get_results($query);

            foreach($result as $row)
            {

				$query = "SELECT
				                    contest_id,
									start_date,
									end_date,
									status
								FROM
									".$wpdb->prefix."mal_prize
								WHERE
									ID = $row->ID";

				$resulta = $wpdb->get_row($query);


                $results_array[$row->ID]['ID']          = $row->ID;
                $results_array[$row->ID]['contest_id']  = $resulta->contest_id;
                $results_array[$row->ID]['start_date']  = $resulta->start_date;
				$results_array[$row->ID]['end_date']    = $resulta->end_date;
				$results_array[$row->ID]['status']	    = $resulta->status;

                    $query = "SELECT
                                    data_name,
                                    value
                              FROM
                                    ".$wpdb->prefix."mal_prize_data
                              WHERE
                                    prize_id = $row->ID";
                    $resultb = $wpdb->get_results($query);

                    foreach($resultb as $rowa)
                    {
                            $results_array[$row->ID][$rowa->data_name] = stripslashes($rowa->value);
                    }


            }

        return $results_array;
    }
    private function get_todays_active_prizes_by_contest_id( $contest_id, $start_date, $end_date )
    {
        global $wpdb;

            $results_array = array();
            $query = "SELECT ID FROM " . $wpdb->prefix . "mal_prize WHERE contest_id = '$contest_id' AND status = 'active' AND start_date = '$start_date' AND end_date = '$end_date'";
            $result = $wpdb->get_results($query);

            foreach($result as $row)
            {

				$query = "SELECT
				                    contest_id,
									start_date,
									end_date,
									status
								FROM
									".$wpdb->prefix."mal_prize
								WHERE
									ID = $row->ID";

				$resulta = $wpdb->get_row($query);


                $results_array[$row->ID]['ID']          = $row->ID;
                $results_array[$row->ID]['contest_id']  = $resulta->contest_id;
                $results_array[$row->ID]['start_date']  = $resulta->start_date;
				$results_array[$row->ID]['end_date']    = $resulta->end_date;
				$results_array[$row->ID]['status']	    = $resulta->status;

                    $query = "SELECT
                                    data_name,
                                    value
                              FROM
                                    ".$wpdb->prefix."mal_prize_data
                              WHERE
                                    prize_id = $row->ID";
                    $resultb = $wpdb->get_results($query);

                    foreach($resultb as $rowa)
                    {
                            $results_array[$row->ID][$rowa->data_name] = stripslashes($rowa->value);
                    }


            }

        return $results_array;
    }
    private function get_total_entries_for_prize_between_dates( $start_date, $end_date )
    {
		    global $wpdb;

				    $query = "SELECT COUNT(*) as total FROM ".$wpdb->prefix."mal_contest_entry WHERE entry_date BETWEEN '$start_date' AND '$end_date'";
                    $result = $wpdb->get_row($query);
                    $total = $result->total;
                    if($total == NULL)
                    {
                        $total = 0;
                    }
            return $total;

    }

    public function mal_membership_get_prizes_by_contest_id( $contest_id, $status = '', $order_by = '' )
    {
        if(!empty($contest_id))
        {
            global $wpdb;
            
            $results_array = array();

            if(!empty($status))
            {
                $add_status = " AND status = '$status' ";
            }
            if(!empty($filter_by))
            {
                $add_order_by = " ORDER BY $order_by ";
            }
                    $query = "SELECT
                                        ID,
                                        start_date,
                                        end_date,
                                        status
                                    FROM
                                        ".$wpdb->prefix."mal_prize
                                    WHERE
                                        contest_id = $contest_id
                                        $add_status
                                        $add_order_by";

                    $result = $wpdb->get_results($query);
                    foreach($result as $row)
                    {
                        $results_array[$row->ID][ID]            = $row->ID;
                        $results_array[$row->ID][contest_id]    = $contest_id;
                        $results_array[$row->ID][start_date] 	= $row->start_date;
                        $results_array[$row->ID][end_date]      = $row->end_date;
                        $results_array[$row->ID][status] 		= $row->status;

                            $query = "SELECT
                                            data_name,
                                            value
                                      FROM
                                            ".$wpdb->prefix."mal_prize_data
                                      WHERE
                                            prize_id = $row->ID";
                            $data_results = $wpdb->get_results($query);

                            foreach($data_results as $data_result)
                            {
                                    $results_array[$row->ID][$data_result->data_name]   = stripslashes($data_result->value);
                            }
                    }
                return $results_array;
        }
    }

    public function mal_membership_get_mal_prize_entry_details_by_prize_id( $prize_id, $start_date, $end_date )
    {
        if(empty($prize_id) || empty($start_date) || empty($end_date))
        {
            //die('No prize id, no start date or no end date');
            // Do not show anything, we are missing some information
        }
        else
        {
		    global $wpdb;
				$query = "SELECT
                                maximum_number_votes
                            FROM
                                ".$wpdb->prefix."mal_prize_entry_details
                            WHERE
                                prize_id = $prize_id
                            AND
                                start_date = '$start_date'
                            AND
                                end_date = '$end_date'";

                    $result = $wpdb->get_row($query);
                    $total = $result->maximum_number_votes;
                    if($total == NULL)
                    {
                        $total = 0;
                    }
            return $total;
        }
    }
    private function get_total_entries_for_prize_today( $contest_id, $prize_id, $user_id, $date )
    {
		    global $wpdb;

				    $query = "SELECT COUNT(*) as total FROM ".$wpdb->prefix."mal_contest_entry WHERE contest_id = $contest_id AND prize_id = $prize_id AND user_id = $user_id AND entry_date = '$date'";
                    $result = $wpdb->get_row($query);
                    $total = $result->total;
                    if($total == NULL)
                    {
                        $total = 0;
                    }
            return $total;

    }

    public function mal_membership_get_prize_by_id( $id )
    {
        if( $id != 'new' )
        {

		    global $wpdb;
				$query = "SELECT
				                    ID,
				                    contest_id,
									start_date,
									end_date,
									status
								FROM
									".$wpdb->prefix."mal_prize
								WHERE
									ID = $id";

				$result = $wpdb->get_row($query);
                $this->ID           = $result->ID;
                $this->contest_id 	= $result->contest_id;
                $this->start_date 	= $result->start_date;
				$this->end_date     = $result->end_date;
				$this->status 		= $result->status;

                    $query = "SELECT
                                    data_name,
                                    value
                              FROM
                                    ".$wpdb->prefix."mal_prize_data
                              WHERE
                                    prize_id = $id";
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
    private function get_oldest_prize()
    {
        global $wpdb;
        $query = "SELECT
                        start_date,
                    FROM
                        ".$wpdb->prefix."mal_prize
                    ORDER BY
                       start_date ASC
                    LIMIT 0,1";
        $result = $wpdb->get_row($query);
        return $result->start_date;
    }

    public function mal_membership_get_active_prizes()
    {
        return mal_membership_prize::get_prizes_by_status( 'active' );
    }
    public function mal_membership_get_completed_prizes()
    {
        return mal_membership_prize::get_prizes_by_status( 'completed' );
    }

    public function mal_membership_get_disabled_prizes()
    {
        return mal_membership_prize::get_prizes_by_status( 'disabled' );

    }
    public function mal_membership_get_total_entries_between_dates( $start_date, $end_date )
    {
        return mal_membership_prize::get_total_entries_for_prize_between_dates( $start_date, $end_date );
    }
    public function mal_membership_get_oldest_prize()
    {
        return $this->get_oldest_prize();
    }

    public function mal_membership_get_pending_prizes()
    {
        return mal_membership_prize::get_prizes_by_status( 'pending' );
    }

    public function mal_membership_get_todays_active_prizes_by_contest_id( $contest_id, $start_date, $end_date )
    {
        return mal_membership_prize::get_todays_active_prizes_by_contest_id( $contest_id, $start_date, $end_date );
    }

    public function mal_membership_get_total_entries_for_prize_today( $contest_id, $prize_id, $user_id, $date )
    {
        return mal_membership_prize::get_total_entries_for_prize_today( $contest_id, $prize_id, $user_id, $date );
    }
    public function mal_membership_enter_member_into_contest_for_prize()
    {
        global $wpdb;
        $contest_id                 = $_POST['contest_id'];
        $prize_id                   = $_POST['prize_id'];
        $user_id                    = $_POST['user_id'];
        $entry_date                 = date("Y-m-d 00:00:00");
        $entry_date_end             = date("Y-m-d 23:59:59");
        $qualified_entry            = 1;

        $query = "INSERT INTO
                        ".$wpdb->prefix."mal_contest_entry
                    (
                        contest_id,
                        prize_id,
                        user_id,
                        entry_date,
                        qualified_entry
                     )
                    VALUES
                    (
                        %d,
                        %d,
                        %d,
                        %s,
                        %d
                    )";

        // do an insert
        $entry = $wpdb->query( $wpdb->prepare( $query , array(  $contest_id, $prize_id, $user_id, $entry_date, $qualified_entry) ) );
    }

    /*
     * Function: mal_membership_is_prize_active()
     *
     * @params prize id
     * $returns boolean
     */
    public function mal_membership_is_prize_active( $prize_id, $start_date, $end_date )
    {
        global $wpdb;

        $query = "SELECT COUNT(*) AS total FROM  ".$wpdb->prefix."mal_prize WHERE ID = '$prize_id' AND status = 'active' AND start_date <  '$start_date' AND  end_date >  '$end_date'";
        $result = $wpdb->get_var($query);
        
        if($result == 1)
        {
            return true;
        }
        else
        {
            return false;
        }

    }


    public function mal_membership_update_prize()
    {
        global $wpdb;
        global $current_user;
        $admin_user_id = $current_user->ID;

		$prize_id = $_POST['prize_id'];
        if( $prize_id != 'new' )
        {

            // Remove all old entries
            $query = "DELETE FROM
                                ".$wpdb->prefix."mal_prize_data
                            WHERE
                                prize_id = '$prize_id'
                            ";
            $wpdb->query($query);
            // REmove the old details
            $delete = "DELETE FROM ".$wpdb->prefix."mal_prize_entry_details WHERE contest_id = '{$_POST['contest_id']}' AND prize_id = '{$_POST['prize_id']}'";
            $wpdb->query($delete);

        }
        else
        {
            $prize_id = mal_membership_prize::mal_membership_create_prize($_POST['start_date'], $_POST['end_date'], $_POST['status'], $_POST['contest_id']);
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
                                case 'prize_id':
                                case 'status':
                                break;
                                case 'number_of_votes_for_day':
                                    foreach( $_POST['number_of_votes_for_day'] as $date => $num )
                                    {
                                        $query = "INSERT INTO
                                                        ".$wpdb->prefix."mal_prize_entry_details
                                                    (
                                                        contest_id,
                                                        prize_id,
                                                        maximum_number_votes,
                                                        start_date,
                                                        end_date
                                                     )
                                                    VALUES
                                                    (
                                                        %d,
                                                        %d,
                                                        %d,
                                                        %s,
                                                        %s
                                                    )";

                                        // do an insert
                                        $wpdb->query( $wpdb->prepare( $query , array(  $_POST['contest_id'], $prize_id, $num, date("Y-m-d 00:00:00", strtotime($date)), date("Y-m-d 23:59:59", strtotime($date)) ) ) );
                                    }
                                break;
                                default;
                            $query = "INSERT INTO
                                            ".$wpdb->prefix."mal_prize_data
                                        (
                                            prize_id,
                                            data_name,
                                            value
                                         )
                                        VALUES
                                        (
                                            %d,
                                            %s,
                                            %s
                                        )";

                            // do an insert
                            $wpdb->query( $wpdb->prepare( $query , array(  $prize_id, $key, $value ) ) );
                                break;
                            }
                        }

                    }
                    // Now update the status, start date, end date and admin_user_id
                    $query = "UPDATE ".$wpdb->prefix."mal_prize SET start_date = '{$_POST['start_date']}', end_date = '{$_POST['end_date']}', status = '{$_POST['status']}', admin_user_id = '$admin_user_id', contest_id = '{$_POST['contest_id']}' WHERE ID = '$prize_id'";
                    $wpdb->query($query);

                    if($_POST['prize_id'] == 'new')
                    {
                        $redirect_message = 'success_prize_new';
                    }
                    else
                    {
                        switch($_POST['status'])
                        {
                            case 'active':
                                $redirect_message = 'success_prize_active';
                            break;
                            case 'disabled':
                                $redirect_message = 'success_prize_disabled';
                            break;
                            default:
                                $redirect_message = 'success_prize_pending';
                            break;

                        }

                    }
                    // redirect user back to the view odometer list page
                    //  general_functions.php:     sbm_redirect()
                    mal_membership_redirect('mal_membership_edit_prize&prize_id='.$prize_id, $redirect_message);

    }

}