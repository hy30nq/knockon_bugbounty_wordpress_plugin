<?php
    function mal_membership_get_timezone()
    {
        $timezone = get_option( 'sbm_timezone' );

        if( empty( $timezone ) )
        {
            // Set the timezone to CST since that will be our default
            update_option( 'sbm_timezone', 'America/Chicago' );
            $timezone = get_option( 'sbm_timezone' );
        }
        date_default_timezone_set( $timezone );

        return $timezone;

    }



    function mal_membership_pre_array( $array )
    {
        echo '<pre>';
        print_r( $array );
        echo '</pre>';
    }

    function mal_membership_cancel_button( $page, $message )
    {
            $content = '<span><input type="button" value="Cancel" onclick="javascript: window.location = \'./admin.php?page='.$page.'&message='.$message.'\';"></span>';

            return $content;
    }
    function mal_membership_add_prize_button( $contest_id, $alt_msg = '' )
    {
            if(empty($alt_msg))
            {
                $content = '<span><input type="button" value="Do Not Save. Add a prize for this contest" onclick="javascript: window.location = \'./admin.php?page=mal_membership_edit_prize&contest_id='.$contest_id.'\';"></span>';
            }
            else
            {
                $content = '<span><input type="button" value="'.$alt_msg.'" onclick="javascript: window.location = \'./admin.php?page=mal_membership_edit_prize&contest_id='.$contest_id.'\';"></span>';
            }

            return $content;
    }

    function mal_membership_check_read_only_user()
    {
        global $wpdb;
        global $current_user;
        $user_level         = $current_user->user_level;

        if( $user_level == 0 )
        {
            return true;
        }
        else
        {
            return false;
        }

    }
    function mal_membership_sticky_input( $post, $default )
    {
        if (!empty($post))
        {
            $content = $post;
        }
        else
        {
            $content = $default;
        }
        return $content;
    }

    /*
     * function mal_membership_translate_message
     * @params $message - what the message is in the url and then what it should be displayed as
     * @returns user friendly message for what just happened
     */

    function mal_membership_translate_message( $message )
    {
        $output = '<div class="mal-membership-message"><div class="mal-message-content-wrapper">';

        switch( $message )
        {
            case 'success_settings':
                $output .= 'The settings for mal membership were saved';
            break;
            case 'success_contest_new':
                $output .= 'The new contest was saved';
            break;
            case 'success_contest':
                $output .= 'The contest was saved';
            break;
            case 'success_contest_pending':
                $output .= 'The contest was saved and its status set to pending';
            break;
            case 'success_contest_active':
                $output .= 'The contest was saved and its status set to active';
            break;
            case 'success_contest_completed':
                $output .= 'The contest was saved and its status set to completed';
            break;
            case 'success_contest_cancelled':
                $output .= 'The contest was saved and its status set to cancelled';
            break;
            case 'success_prize_new':
                $output .= 'The new prize was saved';
            break;
            case 'success_prize_pending':
                $output .= 'The prize was saved and its status set to pending';
            break;
            case 'success_prize_active':
                $output .= 'The prize was saved and its status set to active';
            break;
            case 'success_prize_disabled':
                $output .= 'The prize was saved and its status set to disabled';
            break;
            case 'success_member_new':
                $output .= 'The new member was saved';
            break;
            case 'success_member_pending':
                $output .= 'The member was saved and its status set to pending';
            break;
            case 'success_member_active':
                $output .= 'The member was saved and its status set to active';
            break;
            case 'success_member_disabled':
                $output .= 'The member was saved and its status set to disabled';
            break;
            default:
                // show nothing
                $output .= NULL;
            break;
        }

        $output .= '</div></div>';
        return $output;
    }

    /*
     * function mal_membership_redirect
     *
     * @params $url - url to redirect to
     * @params $message - message to pass along, for example a success message or errors
     */
    function mal_membership_redirect( $page, $message = '' )
    {
        echo '<script language="javascript" type="text/javascript">window.location = \'./admin.php?page=' . $page . '', '&message=' . $message . '\';</script>';

    }

    function mal_membership_view_home_page()
    {
        $contest_info       = new mal_membership_contest();
        $prize_info         = new mal_membership_prize();
        $members_info       = new mal_membership_member();

        $active_contests    = $contest_info->mal_membership_get_active_contests();
        $pending_contests   = $contest_info->mal_membership_get_pending_contests();
        $completed_contests = $contest_info->mal_membership_get_completed_contests();
        $active_total       = $members_info->mal_membership_get_total_active_members();
        $entries_today      = $prize_info->mal_membership_get_total_entries_between_dates(date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y") )), date("Y-m-d H:i:s", mktime(23,59,59,date("m"),date("d"),date("Y") )));

        $completed_contests_needing_winners = $contest_info->mal_membership_get_completed_contests_needing_winners();

        ?>
            <div class="wrap">
                <h3>Total Contest Submissions today ( <a href="./admin.php?page=mal_membership_entries"><?php echo $entries_today; ?></a> )</h3>
                <h3>Total Active Members ( <?php echo $active_total; ?> )</h3>

                <h3>Active Contests ( <a href="./admin.php?page=mal_membership_contests"><?php echo count( $active_contests ); ?></a> )</h3>

                <h3>Pending Contests ( <?php echo count( $pending_contests ); ?> )</h3>

                <h3>Completed Contests ( <a href="./admin.php?page=mal_membership_contests"><?php echo count( $completed_contests ); ?></a> )</h3>
                <div id="completed-contests-needing-winners-selected">
                    <h2>Below is a list of prizes that need winners selected</h2>
                    <table style="width: 800px;" class="black-table-border add-padding">
                    <tr>
                        <th class="center-text large-column">Title</th>
                        <th class="center-text large-column">Start Date</th>
                        <th class="center-text large-column">End Date</th>
                    </tr>
                            <?php $i = 1; ?>
                            <?php foreach( $completed_contests_needing_winners as $contest ): ?>

                            <tr class="<?php echo ($i % 2) ? 'white-row' : 'light-grey-row';  ?>">
                                <td class="large-column">
                                    <?php echo substr($contest[contest_title], 0, 20); ?>
                                    <?php if( strlen($contest[contest_title]) > 20 ) echo '...'; ?>
                                </td>
                                <td class="large-column"><?php echo $contest[start_date]; ?></td>
                                <td class="large-column"><?php echo $contest[end_date]; ?></td>

                            </tr>
                            <?php if(!empty($contest[ID])): ?>
                                <?php $prize_collection = $prize_info->mal_membership_get_prizes_by_contest_id($contest[ID], '', 'active DESC, disabled DESC'); ?>
                                    <?php $j = 1; ?>
                                        <?php foreach($prize_collection as $prize ): ?>
                                        <tr id="contest-<?php echo $prize[ID]; ?>" class="prize-list <?php echo ($i % 2) ? 'white-row' : 'light-grey-row'; ?>">
                                            <td></td>
                                            <td colspan="3">
                                                <div>Prize # <?php echo $j; ?> ( <a class="" href="./admin.php?page=mal_membership_select_winner_for_prize&prize_id=<?php echo $prize[ID] ?>&keepThis=true&TB_iframe=true&height=250&width=400" class="thickbox">Select Winner</a> )
                                                Title: <?php echo $prize[prize_title]; ?></div>
                                                Description: <?php echo $prize[prize_description]; ?>
                                                <div>Start Date: <?php echo $prize[start_date]; ?>
                                                End Date: <?php echo $prize[end_date]; ?></div>
                                            </td>
                                        </tr>
                                    <?php $j++; ?>
                                <?php endforeach; ?>
                                <tr class="dark-grey-row"><td colspan="4"></td></tr>
                            <?php endif; ?>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                    </table>
                </div>

            </div>
        <?php
    }
    /*
     * @params array, order_by_type = ASC or DESC, order_by ( first name, last name, etc )
     */
    function mal_membership_sort_list( $array, $sort_by, $order_by )
    {

        foreach($array as $member)
        {

            switch($sort_by)
            {
                case 'last_name':
                    $array[$member[last_name]] = $member;
                break;
                case 'first_name':
                    $array[$member[first_name]] = $member;
                break;
                case 'member_email';
                    $array[$member[member_email]] = $member;
                break;
                default;
                break;
            }
            unset($array[$member[ID]]);
        }

        switch( $order_by )
        {
            case 'ASC';
                    krsort( $array );
            break;
            case 'DESC';
                    ksort( $array );
            break;
            case 'natsort';
                    natsort($array);
            break;
            default;
                if(!empty( $array ))
                {
                    krsort( $array );
                }
            break;
        }

        return $array;
    }

    function mal_membership_upload_image( $name_id, $files, $old_image_id = '' )
    {
            // Small image for this contest
            if(!empty($files['name']))
            {

                if (
                        (
                                ( $files['type'] == 'image/gif' ) ||
                                ( $files['type'] == 'image/jpeg' ) ||
                                ( $files['type'] == 'image/pjpeg' ) ||
                                ( $files['type'] == 'image/png' )
                        )
                    )
                    {
                        if ($files["error"] > 0)
                        {
                            $errors[] = "Error Return Code: " . $files["error"] . "<br />";
                        }
                        else
                        {


                            if(!empty($files['name']))
                            {
                                // Move the file, then check to see if it exists and if so, then update the record

                                $upload = wp_handle_upload($files, array('test_form' => false));
                                $attachment = array(
                                 'post_mime_type' => $upload['type'],
                                 'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                                 'post_content' => '',
                                 'post_status' => 'inherit',
                                 'guid' => $upload['url']
                                );
                                  $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                                  // you must first include the image.php file
                                  // for the function wp_generate_attachment_metadata() to work
                                  require_once(ABSPATH . 'wp-admin/includes/image.php');
                                  $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                                  wp_update_attachment_metadata( $attach_id, $attach_data );


                                if( $attach_id && !empty( $old_image_id ) )
                                {
                                    // remove the old image
                                    if(!wp_delete_attachment($old_image_id))
                                    {
                                        
                                        $errors[] = 'There was a problem deleting the old contest small image!';

                                    }

                                }

                                if ( $attach_id)
                                {
                                    $_POST[$name_id] 	= $attach_id;
                                    return $_POST[$name_id];
                                }


                            }

                        }

                    }
                    else
                    {
                        $errors[] = "Invalid file type for the small image, only .jpg, .gif and .png image types are supported!";
                        return $errors;
                    }
            }
            else{
                // pass the old id along so it will not be lost
                $_POST[$name_id] = $old_image_id;
                return $_POST[$name_id];
            }

    }
    /*
     * Function mal_membership_select_winner
     * The winner is always chosen at random
     * 
     * @params contest_id and prize_id
     * @returns Array of winners
     */

    function mal_membership_select_winner($contest_id, $prize_id)
    {
        $winners = array();

        return $winners;
    }

    function mal_membership_get_days($sStartDate, $sEndDate){
      // Firstly, format the provided dates.
      // This function works best with YYYY-MM-DD
      // but other date formats will work thanks
      // to strtotime().
      $sStartDate = date("Y-m-d", strtotime($sStartDate));
      $sEndDate = date("Y-m-d", strtotime($sEndDate));

      // Start the variable off with the start date
      $aDays[] = $sStartDate;

      // Set a 'temp' variable, sCurrentDate, with
      // the start date - before beginning the loop
      $sCurrentDate = $sStartDate;

      // While the current date is less than the end date
      while($sCurrentDate < $sEndDate){
        // Add a day to the current date
        $sCurrentDate = gmdate("Y-m-d", strtotime("+1 day", strtotime($sCurrentDate)));

        // Add this new day to the aDays array
        $aDays[] = $sCurrentDate;
      }

      // Once the loop has finished, return the
      // array of days.
      return $aDays;
    }

    function mal_membership_convert_number_to_day_of_the_week( $number, $type )
    {

        switch( $type )
        {
            case 'long':
                $array  = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
            break;
            case 'short':
                $array  = array('Sun', 'Mon', 'Tue', 'Wed', 'Thurs', 'Fri', 'Sat');
            break;
            case 'xs':
                $array  = array('Su', 'M', 'T', 'W', 'Th', 'F', 'Sa');
            break;
        }

        return  $array[$number];
    }