<?php

    function mal_membership_edit_contest()
    {
        global $wpdb;
        global $current_user;
        $contest_info   = new mal_membership_contest();

        $message        = $_GET['message'];
        $contest_id     = $_GET['contest_id'];
        // Setup the upload directory information
        $uploadDir      = wp_upload_dir();
        $upload_directory = $uploadDir['url'];


        if(empty($contest_id))
        {
            $contest_id = $_POST['contest_id'];
        }
        // make sure this is always set
        if(empty($contest_id))
        {
            $contest_id = 'new';
        }

        get_currentuserinfo($current_user->ID);

        echo '<div class="wrap">';

        if(!empty($message))
        {
            echo mal_membership_translate_message($message);
        }

        if (isset($_POST['contest_id']))
        {



            $errors = array();


            if (empty($_POST['status']))
            {
                $errors[] = 'You forgot the status';
            }
           /*
            *  if (empty($_POST['contest_maximum_entries']))
            {
                $errors[] = 'You forgot the maximum number of entries';
            }
           */
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
            
            if(empty($_POST['contest_title']))
            {
                $errors[] = 'You forgot the contest title';
            }
            // Small image for this contest

            mal_membership_upload_image( 'contest_small_image_id', $_FILES['contest_small_image'], $_POST['old_contest_small_image_id'] );

            // Large image for this contest

            mal_membership_upload_image( 'contest_large_image_id', $_FILES['contest_large_image'], $_POST['old_contest_large_image_id'] );
            

            if (empty($errors))
            {

                $contest_info->mal_membership_update_contest();
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
        
            $contest_info->mal_membership_get_contest_by_id( $contest_id );

            $wp_user_level 	= $wpdb->prefix . 'user_level';
            $current_level 	= $current_user->$wp_user_level;

        if(!empty($_POST['status']))
        {
            $status = $_POST['status'];
        }
        else
        {
            $status = $contest_info->status;
        }

        switch($status)
        {
            case 'active': $active_selected = 'selected="selected"';
            break;
            case 'completed': $completed_selected = 'selected="selected"';
            break;
            case 'cancelled': $cancelled_selected = 'selected="selected"';
            break;
            default: $pending_selected = 'selected="selected"';
            break;
        }


        if( empty($contest_info->contest_maximum_entries) && empty($_POST['contest_maximum_entries']))
        {
            $contest_info->contest_maximum_entries = 1;
        }

            if ( $current_user->user_level >= 7 )
            {

                ?>

                <h3>Add/Edit Contest</h3>
                <div>&nbsp;</div>
                <form method="post" id="malContest" class="malForm" action="admin.php?page=mal_membership_edit_contest" enctype="multipart/form-data">
                <table>
                    <tr>
                        <td class="float-left input-description">Contest Status<em>*</em></div></td>
                        <td>

                            <select name="status" class="required" id="status">
                                <option <?php echo $pending_selected; ?> value="pending">Pending</option>
                                <option <?php echo $active_selected; ?> value="active">Active</option>
                                <option <?php echo $completed_selected; ?> value="completed">Completed</option>
                                <option <?php echo $cancelled_selected; ?> value="cancelled">Cancelled</option>
                            </select>

                        </td>
                    </tr>
                        <!-- tr>
                            <td class="float-left input-description">
                                Maximum Number of entries<em>*</em>
                            </td>
                            <td>
                                <input type="text" class="required number" autocomplete="off" id="contest_maximum_entries" name="contest_maximum_entries" value="<?php echo mal_membership_sticky_input($_POST['contest_maximum_entries'], $contest_info->contest_maximum_entries ) ?>"/>
                            </td>

                        </tr -->
                    </tr>
                        <tr>
                            <td class="float-left input-description">
                                Start Date/Time<em>*</em>
                            </td>
                            <td>
                                <input type="text" class="required" autocomplete="off" id="startTimeInput" name="start_date" value="<?php echo mal_membership_sticky_input($_POST['start_date'], $contest_info->start_date ) ?>"/>
                                <button id="startTimeButton">
                                    <img src="<?php echo MM_PLUGIN_URL; ?>/images/calendar.gif" alt="[calendar icon]"/>
                                </button>
                            </td>

                        </tr>

                    <tr>
                        <td class="float-left input-description">
                            End Date/Time<em>*</em>
                        </td>
                        <td>
                            <input type="text" class="required" autocomplete="off" id="endTimeInput" name="end_date" value="<?php echo mal_membership_sticky_input($_POST['end_date'], $contest_info->end_date ) ?>"/>
                            <button id="endTimeButton">
                                <img src="<?php echo MM_PLUGIN_URL; ?>/images/calendar.gif" alt="[calendar icon]"/>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td class="float-left input-description">
                            Contest Title <em>*</em>
                        </td>
                        <td>
                            <input type="text" class="required" autocomplete="off" id="contest_title" name="contest_title" size="30" maxlength="60" value="<?php echo mal_membership_sticky_input($_POST['contest_title'], $contest_info->contest_title ) ?>"/>

                        </td>
                    </tr>
                    <tr>
                        <td class="float-left input-description">
                            Description
                        </td>
                        <td>
                          <textarea rows="4" cols="60" name="contest_description"><?php echo mal_membership_sticky_input($_POST['contest_description'], $contest_info->contest_description ) ?></textarea>

                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <td class="float-left">Small image ( 500 width X 125 height) </td>
                        <td>
                            <div><input type="file" size="30" name="contest_small_image" value="<?php echo $_POST['contest_small_image']; ?>"></div>
                                <?php if(!empty($contest_info->contest_small_image_id)): ?>
                                    <?php $image = wp_get_attachment_image_src($contest_info->contest_small_image_id, 'full', false); ?>
                                    <div>*** If you upload a new image, it will delete and replace the current one</div>
                                    <div><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></div>
                                    <input type="hidden" name="old_contest_small_image_id" value="<?php echo $contest_info->contest_small_image_id; ?>">
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <td colspan="2"><h3>Select One - An Image or the YouTube embed code</h3>
                            <input <?php if(!empty($contest_info->contest_large_image_id)) { echo 'checked="checked"'; } ?> type="radio" name="media_type" id="image" class="change-media" value="image"> <label for="image"> Image</label> || <input <?php if(!empty($contest_info->youtube_embed)) { echo 'checked="checked"'; } ?> type="radio" name="media_type" id="video" class="change-media" value="video"> <label for="video"> Video </label></td>
                    </tr>
                    <tr>
                        <td class="float-left" id="image-video-description">Large Image ( 500 width X 350 height) </td>
                        <td>
                            <div id="contest-image" <?php if(empty($contest_info->contest_large_image_id)) { echo 'class="default-hidden"'; } ?>>
                                <div><input type="file" size="30" name="contest_large_image" value="<?php echo $_POST['contest_large_image']; ?>"></div>
                                <?php if(!empty($contest_info->contest_large_image_id)): ?>
                                    <?php $image = wp_get_attachment_image_src($contest_info->contest_large_image_id, 'full', false); ?>
                                    <div>*** If you upload a new image, it will delete and replace the current one</div>
                                    <div><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></div>
                                    <input type="hidden" name="old_contest_large_image_id" value="<?php echo $contest_info->contest_large_image_id; ?>">
                                <?php endif; ?>
                            </div>
                            <div id="contest-video" <?php if(empty($contest_info->youtube_embed)) { echo 'class="default-hidden"'; } ?>>
                                <textarea rows="2" cols="60" name="youtube_embed"><?php echo $contest_info->youtube_embed; ?></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><hr></td>
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
            <?php if(($contest_id != 'new') && ($contest_info->status != 'completed')): ?>
                <?php echo mal_membership_add_prize_button($contest_id); ?>
            <?php endif; ?>
            </div>
            <input type="hidden" name="contest_id" id="contest_id" value="<?php echo $contest_id; ?>">
            </form>

            <br>
            <br>
            <?php
                //  help_functions.php:     sbm_display_help()
                echo mal_membership_display_help( 'contest' );
            ?>

           <div id="ajax_message"></div>

       </div>
       <?php
    }
function mal_membership_view_contest()
{
    global $wpdb;
    global $current_user;
    $contest_info   = new mal_membership_contest();
    $prize_info     = new mal_membership_prize();

    $message        = $_GET['message'];
    $contest_id     = $_GET['contest_id'];
    // Setup the upload directory information
    $uploadDir      = wp_upload_dir();
    $upload_directory = $uploadDir['url'];


    if(empty($contest_id))
    {
        $contest_id = $_POST['contest_id'];
    }
    // make sure this is always set
    if(empty($contest_id))
    {
        $contest_id = 'new';
    }

    get_currentuserinfo($current_user->ID);

    echo '<div class="wrap">';

    if(!empty($message))
    {
        echo mal_membership_translate_message($message);
    }


        $contest_info->mal_membership_get_contest_by_id( $contest_id );

        $wp_user_level 	= $wpdb->prefix . 'user_level';
        $current_level 	= $current_user->$wp_user_level;

    if(!empty($_POST['status']))
    {
        $status = $_POST['status'];
    }
    else
    {
        $status = $contest_info->status;
    }

    switch($status)
    {
        case 'active': $active_selected = 'selected="selected"';
        break;
        case 'completed': $completed_selected = 'selected="selected"';
        break;
        case 'cancelled': $cancelled_selected = 'selected="selected"';
        break;
        default: $pending_selected = 'selected="selected"';
        break;
    }


    if( empty($contest_info->contest_maximum_entries) && empty($_POST['contest_maximum_entries']))
    {
        $contest_info->contest_maximum_entries = 1;
    }

        if ( $current_user->user_level >= 7 )
        {

            ?>

            <h3>View Contest</h3>
            <div>&nbsp;</div>
            <table border="1">
                <tr>
                    <td class="float-left input-description">Contest Status<?php echo $contest_info->status; ?></div></td>
                </tr>
                </tr>
                    <tr>
                        <td class="float-left input-description">
                            Start Date/Time <?php echo $contest_info->start_date; ?>
                        </td>

                    </tr>

                <tr>
                    <td class="float-left input-description">
                        End Date/Time <?php echo $contest_info->end_date; ?>
                    </td>
                </tr>
                <tr>
                    <td class="float-left input-description">
                        Contest Title <?php echo $contest_info->contest_title; ?>
                    </td>
                </tr>
                <tr>
                    <td class="float-left input-description">
                        Description <?php echo $contest_info->contest_description; ?>
                    </td>
                </tr>
                <tr>
                    <td>
                            <?php if(!empty($contest_info->contest_small_image_id)): ?>
                                <?php $image = wp_get_attachment_image_src($contest_info->contest_small_image_id, 'full', false); ?>
                                <div>Small image ( <?php echo $image[1]; ?> width X <?php echo $image[2]; ?> height) </div>
                                <div><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div <?php if(empty($contest_info->contest_large_image_id)) { echo 'class="default-hidden"'; } ?>>
                            <?php if(!empty($contest_info->contest_large_image_id)): ?>
                                <?php $image = wp_get_attachment_image_src($contest_info->contest_large_image_id, 'full', false); ?>
                                <div>Large Image ( <?php echo $image[1]; ?> width X <?php echo $image[2]; ?> height) </div>
                                <div><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></div>
                            <?php endif; ?>
                        </div>
                        <div <?php if(empty($contest_info->youtube_embed)) { echo 'class="default-hidden"'; } ?>>
                            <?php echo $contest_info->youtube_embed; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><hr></td>
                </tr>
                <tr>
                    <td>
                        <h3>Prize List</h3>
                        <?php $prize_collection = $prize_info->mal_membership_get_prizes_by_contest_id($contest_id, '', 'active DESC, disabled DESC'); ?>
                        <?php $j = 1; ?>
                        <div>
                            <?php foreach($prize_collection as $prize ): ?>
                                <div>Title: <?php echo $prize[prize_title]; ?></div>
                                <div>Description: <?php echo $prize[description]; ?></div>
                                <div>Status: <?php echo $prize[status]; ?></div>
                                <div>Start Date: <?php echo $prize[start_date]; ?></div>
                                <div>End Date: <?php echo $prize[end_date]; ?></div>


                            <?php $winner_collection = mal_membership_get_winners_for_prize($contest_id, $prize[ID]) ?>
                            <?php if(count($winner_collection) > 0): ?>
                                <h3>Winner List</h3>
                            <?php endif; ?>
                            <?php if(count($winner_collection) == 0): ?>
                                <h3>No Winners for <?php echo $prize[prize_title]; ?>, Select Winner click <a href="./admin.php?page=mal_membership_select_winner_for_prize&prize_id=<?php echo $prize[ID]; ?>">here</a>!</h3>
                            <?php endif; ?>
                            <?php $i = 1; ?>
                            <?php foreach($winner_collection as $winner): ?>
                                Winner # <?php echo $i; ?><br>
                                Name: <?php echo $winner['first_name']; ?> <?php echo $winner['last_name']; ?> ( <a href="./admin.php?page=mal_membership_replace_winner&contest_id=<?php echo $contest_id; ?>&prize_id=<?php echo $prize[ID]; ?>&user_id=<?php echo $winner['user_id']; ?>">Replace This Winner</a> )<br>
                                Address: <?php echo $winner['address_1']; ?><br>
                                Address: <?php echo $winner['address_2']; ?><br>
                                City/State/Zip: <?php echo $winner['city']; ?> <?php echo $winner['state']; ?>. <?php echo $winner['zip_code']; ?><br>
                                Phone 1: <?php echo $winner['phone_1']; ?><br>
                                Phone 2: <?php echo $winner['phone_2']; ?><br>
                                <hr>
                             <?php  $i++; ?>
                            <?php endforeach; ?>
                            <?php $j++; ?>
                            <?php endforeach; ?>
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
            <h2>Sorry you cant view the contest, you need to be an administrator</h2>
        <?php
        }
        ?>
       <div class="clear float-left">
           <?php
            // Make sure this is not a read only user
            //  general_functions.php:     sbm_check_read_only_user()
            //  mal_membership_general.php:     mal_membership_cancel_button()
            echo mal_membership_cancel_button('mal_membership_view_home_page', 'cancel');
            ?>
        <?php if(($contest_id != 'new') && ($contest_info->status != 'completed')): ?>
            <?php echo mal_membership_add_prize_button($contest_id, 'Add a prize for this contest' ); ?>
        <?php endif; ?>
        </div>

        <br>
        <br>
        <?php
            //  help_functions.php:     sbm_display_help()
            echo mal_membership_display_help( 'contest' );
        ?>

       <div id="ajax_message"></div>

   </div>
   <?php
}

    function mal_membership_view_contests()
    {
        $prize_info   = new mal_membership_prize();

        $pending_collection     = mal_membership_contest::mal_membership_get_pending_contests();
        $active_collection      = mal_membership_contest::mal_membership_get_active_contests();
        $completed_collection   = mal_membership_contest::mal_membership_get_completed_contests();
        $cancelled_collection   = mal_membership_contest::mal_membership_get_cancelled_contests();
        $merged_collection      = array_merge($pending_collection, $active_collection, $completed_collection, $cancelled_collection);
        if(count($pending_collection) == 0 )
        {
            $pending_collection[0][start_date] = 'None';
        }
        if(count($active_collection) == 0 )
        {
            $active_collection[0][start_date] = 'None';
        }
        if(count($completed_collection) == 0 )
        {
            $completed_collection[0][start_date] = 'None';
        }
        if(count($cancelled_collection) == 0 )
        {
            $cancelled_collection[0][start_date] = 'None';
        }

        ?>
        <div class="wrap">

                        <h3>All Contests Pending, Active, Completed, Cancelled</h3>
                        <table>
                        <tr>
                            <td class="small-column"></td>
                            <th class="center-text large-column">Start Date</th>
                            <th class="center-text large-column">End Date</th>
                            <th class="center-text small-column">Status</th>
                            <th class="center-text large-column">Title</th>
                            <!-- th class="center-text large-column">Description</th -->
                            <th class="center-text extralarge-column">Prizes</th>
                        </tr>
                        </table>
                        <div class="mal-scroll-div">
                            <table class="black-table-border add-padding">
                                <?php $i = 1; ?>
                                <?php foreach( $merged_collection as $contest ): ?>

                                <tr class="<?php echo ($i % 2) ? 'white-row' : 'light-grey-row';  ?>">
                                    <td class="small-column">
                                        <?php if(!empty($contest[ID])): ?>
                                        <a href="./admin.php?page=mal_membership_edit_contest&contest_id=<?php echo $contest[ID]; ?>">Edit</a> ||
                                        <a href="./admin.php?page=mal_membership_view_contest&contest_id=<?php echo $contest[ID]; ?>">View</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="large-column"><?php echo $contest[start_date]; ?></td>
                                    <td class="large-column"><?php echo $contest[end_date]; ?></td>
                                    <td class="small-column"><?php echo $contest[status]; ?></td>
                                    <td class="large-column">
                                        <?php echo substr($contest[contest_title], 0, 20); ?>
                                        <?php if( strlen($contest[contest_title]) > 20 ) echo '...'; ?>
                                    </td>
                                    <!-- td class="large-column">
                                        <?php /* echo substr($contest[contest_description], 0, 20); */ ?>
                                        <?php /* if( strlen($contest[contest_description]) > 20 ) echo '...'; */ ?>
                                    </td -->
                                    <td class="extralarge-column relative-position">
                                        <?php if(!empty($contest[ID])): ?>
                                            <?php $prize_collection = $prize_info->mal_membership_get_prizes_by_contest_id($contest[ID], '', 'active DESC, disabled DESC'); ?>
                                            <?php if(count($prize_collection) > 0 ): ?>
                                                <div class="float-left" id="<?php echo $contest[ID]; ?>"><a href="javascript: void(0);" class="show_hide_prize_details">Show/hide prize details</a>&nbsp;|&nbsp;</div>
                                            <?php else: ?>
                                                <div class="float-left">No Prizes&nbsp;|&nbsp;</div>
                                            <?php endif; ?>
                                        <?php if($contest[status] != 'completed'): ?>
                                            <div class="float-left"><a href="./admin.php?page=mal_membership_edit_prize&contest_id=<?php echo $contest[ID]; ?>">Add Prize</a></div>
                                        <?php endif; ?>
                                        <?php if($contest[status] == 'completed'): ?>
                                            <div class="float-left"><a href="./admin.php?page=mal_membership_view_contest&contest_id=<?php echo $contest[ID]; ?>">View Winners</a></div>
                                        <?php endif; ?>
                                                <?php $j = 1; ?>
                                                <div  id="show-hide-<?php echo $contest[ID]; ?>" class="default-hidden show-hide-div show-hide-<?php echo $contest[ID]; ?>">
                                                    <?php foreach($prize_collection as $prize ): ?>
                                                    <ul id="contest-<?php echo $prize[ID]; ?>" class="prize-list <?php echo ($prize_info->mal_membership_is_prize_active($prize[ID], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"))) ? 'green-row' : 'blue-row';  ?> show-hide-<?php echo $contest[ID]; ?>">
                                                        <li><a class="white-link close-link" href="javascript: void(0); "><img src="<?php echo MM_PLUGIN_URL; ?>/images/close.gif"></a></li>
                                                        <li>Title: <?php echo $prize[prize_title]; ?></li>
                                                        <li>Description: <?php echo $prize[description]; ?></li>
                                                        <li>Status: <?php echo $prize[status]; ?></li>
                                                        <li>Start Date: <?php echo $prize[start_date]; ?></li>
                                                        <li>End Date: <?php echo $prize[end_date]; ?></li>
                                                        <li><a class="white-link" href="./admin.php?page=mal_membership_edit_prize&prize_id=<?php echo $prize[ID] ?>">Edit Prize</a></li>
                                                    </ul>
                                                    <?php $j++; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                        <?php endif; ?>
                                        <div class="clear-left"></div>
                                    </td>
                                </tr>
                                <?php $i++; ?>
                                <?php endforeach; ?>
                            </table>
                        </div>
        </div>
        <?php
    }