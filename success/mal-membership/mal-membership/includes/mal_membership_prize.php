<?php

    function mal_membership_replace_winner()
    {
        global $wpdb;
        global $current_user;
        get_currentuserinfo($current_user->ID);

        $member_info    = new mal_membership_member();
        $prize_info     = new mal_membership_prize();
        $contest_info   = new mal_membership_contest();

        $user_id = $_GET['user_id'];
        $contest_id = $_GET['contest_id'];
        $prize_id = $_GET['prize_id'];

        $member_info->mal_membership_get_member_by_id( $user_id );
        $contest_info->mal_membership_get_contest_by_id( $contest_id );
        $contest_status         = $contest_info->status;
        $contest_start_date     = $contest_info->start_date;
        $contest_end_date       = $contest_info->end_date;
        $contest_title          = $contest_info->contest_title;
        $contest_description    = $contest_info->contest_description;

        $prize_info->mal_membership_get_prize_by_id( $prize_id );

        $prize_status           = $prize_info->status;
        $prize_start_date       = $prize_info->start_date;
        $prize_end_date         = $prize_info->end_date;
        $prize_title            = $prize_info->prize_title;
        $prize_description      = $prize_info->prize_description;

        $query = "SELECT COUNT(user_id) as total FROM ".$wpdb->prefix."mal_contest_entry WHERE contest_id = '$contest_id' AND winner = 1 AND prize_id = $prize_id AND user_id = $user_id";
        $total = $wpdb->get_row($query);


        ?>
        <div class="wrap">
            <?php if( $total->total == '0'): ?>
                    <div class="error">The User selected is not set as a winner for this prize</div>
                    <?php /* Set user_id to null and make sure it throws an error and does not proceed with the page load */ ?>
                    <?php $user_id = null; ?>
            <?php endif; ?>

        <?php if(empty($user_id) || empty($contest_id) || empty($prize_id)): ?>
                  <div class="error">You reached this page in error</div>
        <?php else:  ?>

            <h2>Contest/Prize Information</h2>

            <h3>Contest Information</h3>
            <div>Contest Status: <?php echo $contest_status; ?></div>
            <div>Contest Title: <?php echo $contest_title; ?></div>
            <div>Contest Description: <?php echo $contest_description; ?></div>
            <div>Contest Start Date: <?php echo $contest_start_date; ?></div>
            <div>Contest End Date: <?php echo $contest_end_date; ?></div>
            <br>

            <h3>Prize Information</h3>

            <div>Prize Status: <?php echo $prize_status; ?></div>
            <div>Prize Title: <?php echo $prize_title; ?></div>
            <div>Prize Description: <?php echo $prize_description; ?></div>
            <div>Prize Start Date: <?php echo $prize_start_date; ?></div>
            <div>Prize End Date: <?php echo $prize_end_date; ?></div>
            <br>

            <h3>Select a new winner</h3>

            <div>Are you sure you want to select a new winner?</div>
            <?php /* our hidden fields to hold our values */ ?>
            <input type="hidden" id="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" id="contest_id" value="<?php echo $contest_id; ?>">
            <input type="hidden" id="prize_id" value="<?php echo $prize_id; ?>">

            <div>We are going to remove <?php echo $member_info->first_name; ?> <?php echo $member_info->last_name; ?> as the winner and pick a new one</div>
            <button id="select_new_winner">Select New Winner</button>
                    <fieldset>
                       <legend>And the new winner is...</legend>
                       <h3 id="show_new_winner"></h3>
                    </fieldset>

         <?php endif; ?>
        </div>
       <?php
    }
    function mal_membership_select_winner_for_prize()
    {
        global $wpdb;
        global $current_user;
        get_currentuserinfo($current_user->ID);

        $contest_info           = new mal_membership_contest();
        $prize_info             = new mal_membership_prize();
        $prize_id               = $_GET['prize_id'];

        $prize_info->mal_membership_get_prize_by_id( $prize_id );

        $contest_id             = $prize_info->contest_id;
        $start_date             = $prize_info->start_date;
        $end_date               = $prize_info->end_date;
        $title                  = $prize_info->prize_title;
        $description            = $prize_info->prize_description;

        ?>
        <div class="wrap">
            <?php if(empty($prize_id)) : ?>
                <div class="error">No Prize Selected</div>
                <?php exit(); ?>
            <?php endif; ?>
            <h2>Select the winner for <?php echo $title; ?></h2>
            <button id="select_the_winner">Select The winner for <?php echo $title; ?></button>
            <fieldset>
                <legend>And the winner is...</legend>
                <h3 id="show_winner"></h3>
            </fieldset>
            <input type="hidden" id="contest_id" value="<?php echo $contest_id; ?>">
            <input type="hidden" id="prize_id" value="<?php echo $prize_id; ?>">
        </div>
        <?php
    }

    function mal_membership_edit_prize()
    {
        global $wpdb;
        global $current_user;
        $contest_info           = new mal_membership_contest();
        $prize_info             = new mal_membership_prize();

        $pending_collection     = $contest_info->mal_membership_get_pending_contests();
        $active_collection      = $contest_info->mal_membership_get_active_contests();
        $combined_collection    = array_merge($pending_collection, $active_collection);
        $message                = $_GET['message'];
        $prize_id               = $_GET['prize_id'];

        if(empty($prize_id))
        {
            $prize_id = $_POST['prize_id'];
        }

        // make sure this is always set
        if(empty($prize_id))
        {
            $prize_id = 'new';
        }

        $prize_info->mal_membership_get_prize_by_id( $prize_id );
        get_currentuserinfo($current_user->ID);

        echo '<div class="wrap">';

        if(!empty($message))
        {
            echo mal_membership_translate_message($message);
        }

        if (isset($_POST['prize_id'])) {

            $errors = array();
            /*
            if (empty($_POST['prize_maximum_entries']))
            {
                $errors[] = 'You forgot the maximum number of entries';
            }
            */
            if (empty($_POST['number_of_prizes']))
            {
                $errors[] = 'You forgot the number of prizes your giving away';
            }
            if (empty($_POST['status']))
            {
                $errors[] = 'You forgot the status';
            }

            if (empty($_POST['start_date']))
            {
                $errors[] = 'You forgot the start date';
            }
            if (empty($_POST['end_date']))
            {
                $errors[] = 'You forgot the end_date';
            }
            // Now check to make sure the end date is not before the start date
            if(strtotime($_POST['end_date']) < strtotime($_POST['start_date']))
            {
                $errors[] = 'The end date ( ' . $_POST['end_date'] . ' ) is before the start date ( ' . $_POST['start_date'] . ' )';
            }

            if((empty($_POST['contest_id'])))
            {
                $errors[] = 'You forgot to choose a contest for this prize to belong to';
            }
            else
            {
                
                // Verify that the prize start and end dates are within the contest
                $contest_data = $contest_info->mal_membership_get_contest_by_id($_POST['contest_id']);
                if( strtotime($contest_data->start_date) > strtotime($_POST['start_date']) )
                {
                    $errors[] = 'The starting time for the prize is before the start of the contest';
                }
                if ( strtotime($contest_data->end_date) < strtotime($_POST['end_date']))
                {
                    $errors[] = 'The ending time for the prize is after the start of the contest';
                }


            }

            // Small image for this prize

            mal_membership_upload_image( 'prize_small_image_id', $_FILES['prize_small_image'], $_POST['old_prize_small_image_id'] );
            // Large image for this prize

            mal_membership_upload_image( 'prize_large_image_id', $_FILES['prize_large_image'], $_POST['old_prize_large_image_id'] );


            if (empty($errors))
            {

                $prize_info->mal_membership_update_prize();
                die();
            }
            else
            {
                ?>
                <div class="error">
                    <div class="bold-text errors-found">Errors found</div>
                    <ol>
                    <?php foreach ($errors as $list): ?>
                        <li class="error-item">&nbsp;&nbsp;<?php echo $list; ?></li>
                    <?php endforeach; ?>
                    </ol>
                </div>
                <?php
            }
        }

            $wp_user_level 	= $wpdb->prefix . 'user_level';
            $current_level 	= $current_user->$wp_user_level;

        if(!empty($_POST['status']))
        {
            $status = $_POST['status'];
        }
        else
        {
            $status = $prize_info->status;
        }

        switch($status)
        {
            case 'active': $active_selected = 'selected="selected"';
            break;
            case 'completed': $completed_selected = 'selected="selected"';
            break;
            case 'disabled': $disabled_selected = 'selected="selected"';
            break;
            default: $pending_selected = 'selected="selected"';
            break;
        }



    if( count($active_collection) == 0 && count($pending_collection) == 0 )
    {
        ?>
            <div class="error">
                <div class="bold-text errors-found">Errors found</div>
                <h3>You need to create a pending or an active contest before you can use this page</h3>
            </div>
        </div>
        <?php
        exit();
    }

        if($prize_info->contest_id)
        {
            $contest_id = $prize_info->contest_id;
        }
        else if (isset($_POST['contest_id']))
        {
            $contest_id = $_POST['contest_id'];
        }
        else if (!empty($_GET['contest_id']))
        {
            $contest_id = $_GET['contest_id'];
        }
        else{
            $contest_id = NULL;
        }
        
            if ( $current_user->user_level >= 7 )
            {

                // Get the start date and end date for the prize
                // If none is found, use the contests
                if(!empty($prize_info->start_date))
                {
                    $start_date = $prize_info->start_date;
                }
                else if(!empty($contest_id))
                {
                    $start_date = $contest_info->mal_membership_get_contest_by_id($contest_id)->start_date;
                }
                else{
                    $end_date = date("Y-m-d H:i:s");
                }
                
                if(!empty($prize_info->end_date))
                {
                    $end_date = $prize_info->end_date;
                }
                else if(!empty($contest_id))
                {
                    $end_date = $contest_info->mal_membership_get_contest_by_id($contest_id)->end_date;
                }
                else{
                    $start_date = date("Y-m-d H:i:s");
                }
                /*
                if( empty($contest_info->prize_maximum_entries) && empty($_POST['prize_maximum_entries']))
                {
                    $contest_info->prize_maximum_entries = 1;
                }
                */
                if( empty($contest_info->number_of_prizes) && empty($_POST['number_of_prizes']))
                {
                    $contest_info->number_of_prizes = 1;
                }


                ?>

                <h3>Add/Edit Prize</h3>
                <div>&nbsp;</div>
                <form method="post" id="malContest" class="malForm" action="admin.php?page=mal_membership_edit_prize" enctype="multipart/form-data">
                <fieldset>
                    <legend>Select from the box below. Only pending and active contest are listed!</legend>

                        <select name="contest_id" id="contest_id" class="required">
                        <option value="">-- Select One --</option>

                            <?php foreach( $combined_collection as $contest ): ?>
                                <option value="<?php echo $contest[ID]; ?>" <?php echo ($contest[ID] == $contest_id) ? 'selected="selected"' : '' ?>>
                                    Contest Status = <?php echo $contest['status']; ?>&nbsp;
                                    Title:<?php echo $contest['contest_title']; ?>&nbsp;
                                    Starts:<?php echo date("m/d/Y @ H:i:s", strtotime($contest[start_date])); ?>&nbsp;
                                    Ends:<?php echo date("m/d/Y @ H:i:s", strtotime($contest[end_date])); ?>&nbsp;

                                    Description: <?php echo substr($contest[description], 0, 20); ?>
                                    <?php if( strlen($contest[description]) > 20 ) echo '...'; ?>
                                </option>
                            <?php endforeach; ?>

                      </select>

                </fieldset>
                <table>
                    <tr>
                        <td class="float-left input-description">Prize Status<em>*</em></div></td>
                        <td>

                            <select name="status" class="required" id="status">
                                <option <?php echo $pending_selected; ?> value="pending">Pending</option>
                                <option <?php echo $active_selected; ?> value="active">Active</option>
                                <option <?php echo $completed_selected; ?> value="completed">Completed</option>
                                <option <?php echo $disabled_selected; ?> value="disabled">Disabled</option>
                            </select>

                        </td>
                    </tr>
                    <tr>
                        <td class="float-left input-description">
                            Number of prizes<em>*</em>
                        </td>
                        <td>
                            <input type="text" class="required number" autocomplete="off" id="number_of_prizes" name="number_of_prizes" value="<?php echo mal_membership_sticky_input($_POST['number_of_prizes'], $prize_info->number_of_prizes ) ?>"/>
                        </td>

                    </tr>
                    <tr>
                        <td class="float-left input-description">
                            Default number of votes per day<em>*</em>
                        </td>
                        <td>
                            <input type="text" class="required number" autocomplete="off" id="default_number_of_votes" value="1"/>
                        </td>

                    </tr>
                    <tr>
                        <td class="float-left input-description">
                           Set number of entries per day<em>*</em>
                        </td>
                        <td>
                            <button id="set_number_entries_per_day">Set Number Of Entries Per Day</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div id="show_calendar">
                                <?php
                                    // If this prize has the number of entries already set, display them
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
                                $days = mal_membership_get_days($start_date, $end_date );
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
                                    $prize_entry_detail = $prize_info->mal_membership_get_mal_prize_entry_details_by_prize_id( $prize_id, date("Y-m-d 00:00:00", strtotime($day)), date("Y-m-d 23:59:59", strtotime($day)) );
                                    $message .= '<td>
                                                    <div>'.$day.'</div>
                                                    <div><input type="text" class="required number number_of_votes" name="number_of_votes_for_day['.$day.']" size="4" value="'.$prize_entry_detail.'"></div>
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

                                echo $message;
                                ?>
                            </div>
                        </td>
                    </tr>
                        <tr>
                            <td class="float-left input-description">
                               Prize Start Date/Time<em>*</em>
                            </td>
                            <td>
                                <input type="text" class="required" autocomplete="off" id="startTimeInput" name="start_date" value="<?php echo mal_membership_sticky_input($_POST['start_date'], $start_date ) ?>"/>
                                <button id="startTimeButton">
                                    <img src="<?php echo MM_PLUGIN_URL; ?>/images/calendar.gif" alt="[calendar icon]"/>
                                </button>
                            </td>

                        </tr>

                    <tr>
                        <td class="float-left input-description">
                           Prize End Date/Time<em>*</em>
                        </td>
                        <td>
                            <input type="text" class="required" autocomplete="off" id="endTimeInput" name="end_date" value="<?php echo mal_membership_sticky_input($_POST['end_date'], $end_date ) ?>"/>
                            <button id="endTimeButton">
                                <img src="<?php echo MM_PLUGIN_URL; ?>/images/calendar.gif" alt="[calendar icon]"/>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td class="float-left input-description">
                           Prize Title<em>*</em>
                        </td>
                        <td>
                            <input type="text" class="required" autocomplete="off" id="prize_title" name="prize_title" value="<?php echo mal_membership_sticky_input($_POST['prize_title'], $prize_info->prize_title ) ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td class="float-left input-description">
                           Prize Description
                        </td>
                        <td>
                          <textarea rows="4" cols="60" id="prize_description" name="prize_description"><?php echo mal_membership_sticky_input($_POST['prize_description'], $prize_info->prize_description ) ?></textarea>

                        </td>
                    </tr>
                    <tr>
                        <td class="float-left">Prize Small image (  max 125 width X 125 height) </td>
                        <td>
                            <div><input type="file" size="30" id="prize_small_image"  name="prize_small_image" value="<?php echo $_POST['prize_small_image']; ?>"></div>
                                <?php if(!empty($prize_info->prize_small_image_id)): ?>
                                    <?php $image = wp_get_attachment_image_src($prize_info->prize_small_image_id, 'full', false); ?>
                                    <div>*** If you upload a new image, it will delete and replace the current one</div>
                                    <div><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></div>
                                    <input type="hidden" name="old_prize_small_image_id" value="<?php echo $prize_info->prize_small_image_id; ?>">
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="float-left">Prize Large image ( max 500 width X 350 height) </td>
                        <td>
                            <div><input type="file" size="30" id="prize_large_image" name="prize_large_image" value="<?php echo $_POST['prize_large_image']; ?>"></div>
                                <?php if(!empty($prize_info->prize_large_image_id)): ?>
                                    <?php $image = wp_get_attachment_image_src($prize_info->prize_large_image_id, 'full', false); ?>
                                    <div>*** If you upload a new image, it will delete and replace the current one</div>
                                    <div><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></div>
                                    <input type="hidden" name="old_prize_large_image_id" value="<?php echo $prize_info->prize_large_image_id; ?>">
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    </table>


                <div class="clear"></div>

                <?php

            }
            else
            {
            ?>
                <h2>Sorry you cant adjust the contest, you need to be an administrator</h2>
            <?php
            }
            ?>
           <div class="clear float-left">
               <?php
                // Make sure this is not a read only user
                //  general_functions.php:     sbm_check_read_only_user()
                if( mal_membership_check_read_only_user() == false )
                {
                    echo '<span><input type="submit" value="Submit" id="malSubmitButton"></span>';
                }
                //  mal_membership_general.php:     mal_membership_cancel_button()
                echo mal_membership_cancel_button('mal_membership_view_home_page', 'cancel');
                ?>

            </div>
        <input type="hidden" name="prize_id" id="prize_id" value="<?php echo $prize_id; ?>">
            </form>

            <br>
            <br>
            <?php
                //  help_functions.php:     sbm_display_help()
                echo mal_membership_display_help( 'prize' );
            ?>

           <div id="ajax_message"></div>

       </div>
       <?php

    }

    function mal_membership_view_prizes()
    {
        
        $pending_collection     = mal_membership_prize::mal_membership_get_pending_prizes();
        $active_collection      = mal_membership_prize::mal_membership_get_active_prizes();
        $completed_collection   = mal_membership_prize::mal_membership_get_completed_prizes();
        $disabled_collection    = mal_membership_prize::mal_membership_get_disabled_prizes();
        $combined_collection    = array_merge($pending_collection, $active_collection, $completed_collection, $disabled_collection);
        ?>
        <div class="wrap">

            <div class="mal-scroll-div mal-pending-contests">
                <h3>Pending, Active and Disabled Prizes</h3>
                <table style="width: 100%;">
                    <tr>
                        <td></td>
                        <th class="center-text">Start Date</th>
                        <th class="center-text">End Date</th>
                        <th class="center-text">Status</th>
                        <th class="center-text">Title</th>
                        <th class="center-text">Description</th>
                        <th class="center-text">View Winners</th>
                    </tr>
                    <?php $i = 1; ?>
                    <?php foreach( $combined_collection as $prize ): ?>
                    <tr class="<?php echo ($i % 2) ? 'white-row' : 'light-grey-row';  ?>">
                        <td><?php if(!empty($prize[ID])): ?><a href="./admin.php?page=mal_membership_edit_prize&prize_id=<?php echo $prize[ID]; ?>">Edit</a><?php endif; ?></td>
                        <td><?php echo date("m/d/Y @ H:i:s", strtotime($prize[start_date])); ?></td>
                        <td><?php echo date("m/d/Y @ H:i:s", strtotime($prize[end_date])); ?></td>
                        <td><?php echo $prize[status]; ?></td>
                        <td>
                            <?php echo substr($prize[prize_title], 0, 20); ?>
                            <?php if( strlen($prize[prize_title]) > 20 ) echo '...'; ?>
                        </td>
                        <td>
                            <?php echo substr($prize[prize_description], 0, 20); ?>
                            <?php if( strlen($prize[prize_description]) > 20 ) echo '...'; ?>
                        </td>
                        <td><a href="./admin.php?page=mal_membership_view_contest&contest_id=<?php echo $prize[contest_id]; ?>">View Winners</a></td>
                    </tr>
                    <?php $i++; ?>
                    <?php endforeach; ?>
                </table>
            </div>

        </div>
        <?php
    }

    function mal_membership_entries()
    {
        $prize_info         = new mal_membership_prize();
        $todays_start_date  = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y") ));
        $todays_end_date    = date("Y-m-d H:i:s", mktime(23,59,59,date("m"),date("d"),date("Y") ));
        $entries_today      = $prize_info->mal_membership_get_total_entries_between_dates($todays_start_date, $todays_end_date);
        $start_date         = $_POST['start_date'];
        $end_date           = $_POST['end_date'];

        if(!empty($start_date) && !empty($end_date))
        {
            $entries_for_period = $prize_info->mal_membership_get_total_entries_between_dates( $start_date, $end_date );
        }

        ?>
        <div class="wrap">
            <h3>View Contest Entries</h3>
            <h4>Total Contest Submissions today ( <?php echo $entries_today; ?> )</h4>
            <?php
            if(!empty($entries_for_period))
            {
                ?>
                <h4>Total submissions between <?php echo $start_date; ?> and <?php echo $end_date; ?> ( <?php echo $entries_for_period; ?> )</h4>

                <?php
            }
            ?>
            <div>Change Date</div>
            <form method="post">

                <div class="float-left">
                    <div>Start Date</div>
                    <input type="text" class="required" autocomplete="off" id="startTimeInput" name="start_date" value="<?php echo mal_membership_sticky_input($_POST['start_date'], $todays_start_date ) ?>"/>
                    <button id="startTimeButton">
                        <img src="<?php echo MM_PLUGIN_URL; ?>/images/calendar.gif" alt="[calendar icon]"/>
                    </button>
                </div>
                <div class="float-left">
                    <div>End Date</div>
                    <input type="text" class="required" autocomplete="off" id="endTimeInput" name="end_date" value="<?php echo mal_membership_sticky_input($_POST['end_date'], $todays_end_date ) ?>"/>
                    <button id="endTimeButton">
                        <img src="<?php echo MM_PLUGIN_URL; ?>/images/calendar.gif" alt="[calendar icon]"/>
                    </button>
                </div>
                <div class="clear"></div>
                <input type="submit" value="Submit">
            </form>
        </div>
        <?php
    }