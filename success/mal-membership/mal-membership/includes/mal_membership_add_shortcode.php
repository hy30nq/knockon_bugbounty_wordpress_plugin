<?php

    // Add the code to allow for public sign up
    add_shortcode( 'mal_membership_public_customer_signup', 'mal_membership_public_customer_signup' );
    add_shortcode( 'mal_membership_protected', 'mal_membership_protected');

        function mal_membership_check_for_logged_in_member()
        {
            global $current_user;
            get_currentuserinfo();
            if($current_user->ID == 0 )
            {
                return false;
            }
            else
            {
                return true;
            }


        }

        function mal_membership_get_logged_in_user_id()
        {
            global $current_user;
            get_currentuserinfo();
            return $current_user->ID;
        }

        function mal_membership_display_member_login_form( $email )
        {
           
           $content = '
            <form id="member_login" method="post">
                <div>
                    <label for="member_email_login">Email address</label>
                    <input type="text" class="required" size="40" id="member_email_login" name="member_email_login" value="' . $email . '">
                </div>
                <div>
                    <label for="member_password_login">Password</label>
                    <input type="password" class="required" size="40" id="member_password_login" name="member_password_login">
                </div>
                <input type="submit" name="submit" value="Submit" id="member_login_submit_button">
            </form>';

            return $content;

        }



/*
 * function: mal_membership_protected
 *
 *          To use this short code on a page
 *          [mal_membership_protected]
 *          Some valid arguments
 *          [mal_membership_protected contest=pending]
 *          [mal_membership_protected contest=active]
 *          [mal_membership_protected contest=completed]
 *          [mal_membership_protected contest=cancelled]
 *          [mal_membership_protected prize=pending]
 *          [mal_membership_protected prize=active]
 *          [mal_membership_protected prize=disabled]
 *          [mal_membership_protected member=login]
 *          [mal_membership_protected member=logout]
 *          [mal_membership_protected member=signup]
 *          [mal_membership_protected member=edit]
 *
  */

function mal_membership_protected( $args )
{
    //mal_membership_pre_array($_COOKIE);

    $contest_info   = new mal_membership_contest();
    $member_info    = new mal_membership_member();
    $prize_info     = new mal_membership_prize();
    $logged_in      = mal_membership_check_for_logged_in_member();
    $user_id        = mal_membership_get_logged_in_user_id();

    $collection     = array();
    $errors         = array();
    $current_url    = get_permalink();
    $site_url       = str_replace("http://", "", get_site_url());

    // If the URL has a question mark in it, then we need to do the ampersand
    // Also if the last character is a / we need the ampersand
    
    $last = $str[strlen($current_url)-1];
    if( ( $last == '/' ) || ( preg_match("/\?/", $current_url) ) )
    {
        $member_login_url       = $current_url . '&member=login';
        $member_contest_active  = $current_url . '&contest=active';
    }
    else
    {
        $member_login_url       = $current_url . '?member=login';
        $member_contest_active  = $current_url . '?contest=active';
    }


    // Lets see if they are trying to log in

    if(!empty($_POST['member_email_login']))
    {
        if(!empty($_POST['errors_found']))
        {
            // Not correct, try again
            foreach($_POST['errors_found'] as $error)
            {
                $errors[] = $error;
            }

        }
        else
        {

            // change the array to take the user to the active contests
            $args = array();
            $args[contest] = 'active';
        }
    }
    else if (!is_numeric($user_id) )
    {
        $user_id = 0;
    }

        // If they wanted to be entered into a contest
        if(isset($_POST['user_id']))
        {
            $contest_id                 = $_POST['contest_id'];
            $prize_id                   = $_POST['prize_id'];
            $user_id                    = $_POST['user_id'];
            $entry_date                 = date("Y-m-d 00:00:00");
            $entry_date_end             = date("Y-m-d 23:59:59");
            $qualified_entry            = 1;

            // If this member has not exceeded the number of entries for today
            // Go ahead and enter them
            $max_number_of_entries      = (int)$prize_info->mal_membership_get_mal_prize_entry_details_by_prize_id($prize_id, $entry_date, $entry_date_end);
            $total_entries_so_far       = (int)$prize_info->mal_membership_get_total_entries_for_prize_today($contest_id, $prize_id, $user_id, $entry_date );



            if( $total_entries_so_far <= $max_number_of_entries )
            {
                // Enter the member into this contest for this prize
                $prize_info->mal_membership_enter_member_into_contest_for_prize();
            }

        }

    // check to see if the get has contest=active
        if($_GET['contest'] == 'active')
        {
                // change the array to take the user to the active contests
                $args = array();
                $args[contest] = 'active';

        }
        if($_GET['member'] == 'login')
        {
                // change the array to take the user to the active contests
                $args = array();
                $args[member] = 'login';

        }
    // Make sure there are no arguments
    if(!empty($args))
    {
        // based on the arguments in the array, display the proper page
        foreach($args as $key => $arg)
        {
            $type = $key;
            $status = $arg;

            if($key == 'contest')
            {
                switch($status)
                {
                    case 'pending':
                        $collection = $contest_info->mal_membership_get_pending_contests();
                    break;
                    case 'active':
                        $collection = $contest_info->mal_membership_get_active_contests();
                    break;
                    case 'completed':
                        $collection = $contest_info->mal_membership_get_completed_contests();
                    break;
                    case 'cancelled':
                        $collection = $contest_info->mal_membership_get_cancelled_contests();
                    break;
                    case 'running':
                        $collection = $contest_info->mal_membership_get_running_contests();

                }
                    if(mal_membership_check_for_logged_in_member())
                    {
                        if(count($collection) == 0)
                        {
                            ?>
                                <h1>There are no contests/prizes that are active right now</h1>
                            <?php
                        }
                        else
                        {
                    ?>

                    <table>
                        <?php $i = 1; ?>
                        <?php foreach( $collection as $contest ): ?>

                        <tr style="background-color:<?php echo ($i % 2) ? '' : '';  ?>;">
                            <td>
                                <h2 class="mal-membership"><?php echo $contest[contest_title]; ?></h2>
                               
                                <h3 class="mal-membership-h3">Contest Start: <?php echo date("m/d/Y @ h:i:s A", strtotime($contest[start_date])); ?> End: <?php echo date("m/d/Y @ h:i:s A", strtotime($contest[end_date])); ?></h3>
                                <!-- div>Current status of contest: <?php echo $contest[status]; ?></div -->

                                <?php if(!empty($contest[contest_large_image_id])): ?>
                                    <fieldset>
                                        <!-- legend>Large Contest Image</legend -->
                                        <?php if(!empty($contest[contest_large_image_id])) : ?>
                                        <?php $image = wp_get_attachment_image_src($contest[contest_large_image_id], 'full', false); ?>
                                        <div><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></div>
                                        <?php endif; ?>
                                        <?php if(!empty($contest[youtube_embed])) : ?>
                                        <div><?php echo $contest[youtube_embed]; ?></div>
                                        <?php endif; ?>

                                    </fieldset>
                                <?php endif; ?>
                                <?php /* if(!empty($contest[contest_small_image_id])): ?>
                                    <fieldset>
                                        <!-- legend>Small Contest Image</legend -->
                                        <?php $image = wp_get_attachment_image_src($contest[contest_small_image_id], 'full', false); ?>
                                        <div><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></div>
                                    </fieldset>
                                <?php endif; */ ?>

                                <?php if(!empty($contest['contest_description'])): ?>
                                    <fieldset>
                                        <!-- legend>Contest Description</legend -->
                                        <?php echo $contest[contest_description]; ?>
                                    </fieldset>
                                <?php endif; ?>
                                <fieldset>
                                    <legend>Prizes</legend>

                                    <?php if(!empty($contest[ID])): ?>
                                    <?php $prize_collection = $prize_info->mal_membership_get_prizes_by_contest_id($contest[ID], 'active', 'active DESC'); ?>

                                        <?php if(count($prize_collection) > 0 ): ?>
                                            <div class="float-left" id="show-hide-<?php echo $contest[ID]; ?>"></div>
                                        <?php else: ?>
                                            <div class="float-left">No Prizes&nbsp;|&nbsp;</div>
                                        <?php endif; ?>
                                        <?php if($contest[status] != 'completed'): ?>
                                            <div class="float-left"></div>
                                        <?php endif; ?>
                                            <?php $j = 1; ?>
                                            <?php if(count($prize_collection) > 0 ): ?>
                                                <div><a href="javascript: void(0);" id="<?php echo $contest[ID]; ?>" class="prize-info">Show/Hide Prize Information</a></div>
                                            <?php endif; ?>

                                            <div class="show-hide-div"  id="show-hide-prize-<?php echo $contest[ID]; ?>">
                                                <?php foreach($prize_collection as $prize ): ?>
                                                <?php $total_entries_for_prize = $prize_info->mal_membership_get_mal_prize_entry_details_by_prize_id($prize[ID], date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")); ?>
                                                <?php $total_votes_for_prize_by_member = $prize_info->mal_membership_get_total_entries_for_prize_today($contest[ID], $prize[ID], $user_id, date("Y-m-d 00:00:00") ); ?>
                                                <ul id="prize-<?php echo $prize[ID]; ?>" class="prize-list <?php echo ($prize_info->mal_membership_is_prize_active($prize[ID], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"))) ? 'green-row' : 'blue-row';  ?>">
                                                    <li>Today, this prize has a maximum of <?php echo $total_entries_for_prize ?> entries by any member</li>
                                                    <li>For this prize, you have entered <span id="prize-<?php echo $prize[ID]; ?>-votes-by-member"><?php echo $total_votes_for_prize_by_member ?></span> times</li>

                                                    <li>Prize # <?php echo $i; ?></li>
                                                    <li><?php echo $prize[prize_title]; ?></li>
                                                    <li><?php echo $prize[prize_description]; ?></li>
                                                    <!-- li>Status: <?php echo $prize[status]; ?></li -->
                                                    <li>
                                                        Prize Start: <?php echo date("m/d/Y @ h:i:s A", strtotime($prize[start_date])); ?>
                                                        Prize End: <?php echo date("m/d/Y @ h:i:s A", strtotime($prize[end_date])); ?></li>

                                                    <?php $j++; ?>
                                                    <?php /* if(!empty($prize[prize_small_image_id])): ?>
                                                        <?php $image = wp_get_attachment_image_src($prize[prize_small_image_id], 'full', false); ?>
                                                        <li><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></li>
                                                    <?php endif; */ ?>
                                                    <?php if(!empty($prize[prize_large_image_id])): ?>
                                                        <?php $image = wp_get_attachment_image_src($prize[prize_large_image_id], 'full', false); ?>
                                                        <li><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>"></li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <?php if( ( $total_entries_for_prize > 0 ) && ( $total_votes_for_prize_by_member < $total_entries_for_prize ) ) : ?>
                                                        <button class="submit-for-this-prize" id="<?php echo $prize[ID]; ?>">Enter Me for this prize</button>
                                                            
                                                            <input type="hidden" id="contest-id-<?php echo $prize[ID]; ?>" value="<?php echo $contest[ID]; ?>">
                                                            <input type="hidden" id="prize-id-<?php echo $prize[ID]; ?>" value="<?php echo $prize[ID]; ?>">
                                                            <input type="hidden" id="user-id-<?php echo $prize[ID]; ?>" value="<?php echo $user_id; ?>">


                                                        <div id="entry-message-id-<?php echo $prize[ID]; ?>" class="entry-message"></div>
                                                        <?php else: ?>
                                                            <div>You can not enter this contest for this prize today</div>
                                                        <?php endif; ?>
                                                    </li>
                                                </ul>
                                                <?php endforeach; ?>
                                            </div>
                                    <?php endif; ?>
                                     </fieldset>

                            </td>
                        </tr>
                        <tr>
                            <td class="dashed-bottom">

                            </td>
                        </tr>
                        <?php $i++; ?>
                        <?php endforeach; ?>
                    </table>

                        <?php

                        }
                    }
                    else
                    {
                        
                        // Instead of a message, take them to the login page
                        ?>
                            <h2 class="mal-membership">You are not logged in</h2>

                       

                        <!-- script language="javascript" type="text/javascript">window.location = "<?php echo $member_login_url; ?>"</script -->
                        <?php
                    }

            }
            else if ($key == 'prize')
            {
                switch($status)
                {
                    case 'pending':
                        $collection = $prize_info->mal_membership_get_pending_prizes();
                    break;
                    case 'active':
                        $collection = $prize_info->mal_membership_get_active_prizes();
                    break;
                    case 'disabled':
                        $collection = $prize_info->mal_membership_get_disabled_prizes();
                    break;

                }
                if(!mal_membership_check_for_logged_in_member())
                {

                ?>
                <table>
                    <?php $i = 1; ?>
                    <?php foreach( $collection as $contest ): ?>

                    <tr style="background-color:<?php echo ($i % 2) ? '' : '';  ?>;">
                        <td>
                            <h2 class="mal-membership"><?php echo $contest[contest_title]; ?></h2>
                            <h3 class="mal-membership-h3">Prize Start: <?php echo date("m/d/Y @ h:i:s A", strtotime($contest[start_date])); ?> End: <?php echo date("m/d/Y @ h:i:s A", strtotime($contest[end_date])); ?></h3>
                            <div>Current status of prize: <?php echo $contest[status]; ?></div>
                            <?php if(!empty($contest['contest_description'])): ?>
                                <fieldset>
                                    <legend>Prize Description</legend>
                                    <?php echo $contest[prize_description]; ?>
                                </fieldset>
                            <?php endif; ?>
                            <fieldset>
                                <legend>Prizes</legend>

                                <?php if(!empty($contest[ID])): ?>
                                    <?php $prize_collection = $prize_info->mal_membership_get_prizes_by_contest_id($contest[ID], '', 'active DESC, disabled DESC'); ?>
                                    <?php if(count($prize_collection) > 0 ): ?>
                                        <div class="float-left" id="show-hide-<?php echo $contest[ID]; ?>"></div>
                                    <?php else: ?>
                                        <div class="float-left">No Prizes&nbsp;|&nbsp;</div>
                                    <?php endif; ?>
                                    <?php if($contest[status] != 'completed'): ?>
                                        <div class="float-left"></div>
                                    <?php endif; ?>
                                        <?php $j = 1; ?>
                                            <?php if(count($prize_collection) > 0 ): ?>
                                                <div><a href="javascript: void(0);" id="<?php echo $contest[ID]; ?>" class="prize-info">Show/Hide Prize Information</a></div>
                                            <?php endif; ?>
                                            <div class="default-hidden show-hide-div"  id="show-hide-prize-<?php echo $contest[ID]; ?>">
                                            <?php foreach($prize_collection as $prize ): ?>
                                            <ul id="contest-<?php echo $prize[ID]; ?>" class="prize-list <?php echo ($prize_info->mal_membership_is_prize_active($prize[ID], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"))) ? 'green-row' : 'blue-row';  ?>">
                                                <li>Prize Title: <?php echo $prize[prize_title]; ?></li>
                                                <li>Prize Description: <?php echo $prize[prize_description]; ?></li>
                                                <li>Prize Status: <?php echo $prize[status]; ?></li>
                                                <li>Prize Start Date: <?php echo date("m/d/Y @ h:i:s A", strtotime($prize[start_date])); ?></li>
                                                <li>Prize End Date: <?php echo date("m/d/Y @ h:i:s A", strtotime($prize[end_date])); ?></li>
                                            </ul>
                                            <?php $j++; ?>
                                            <?php endforeach; ?>
                                        </div>
                                <?php endif; ?>
                                 </fieldset>
                            <hr>
                        </td>
                    </tr>
                    <?php $i++; ?>
                    <?php endforeach; ?>
                </table>
                <?php

                }
            }
           else if ($key == 'member')
            {
                switch($status)
                {
                    case 'login':

                        $logged_in          = mal_membership_check_for_logged_in_member();

                        if(!$logged_in)
                        {

                            // See if the posted information is correct
                            if( $user_id != 0 )
                            {

                               ?>

                                <!-- script language="javascript" type="text/javascript">window.location = "<?php echo $member_contest_active; ?>"</script -->
                                <?php
                                echo get_option('mal_membership_non_member_message');

                            }

                            if($_GET['password'] == 'changed' && empty($errors) )
                            {
                                ?>
                                    <div class="success">
                                        <div class="bold-text success-found">Password changed</div>
                                        <ol>
                                           <li class="success-item">For security reasons, because you changed your password, you need to log in again</li>
                                        </ol>
                                    </div>
                                <?php
                            }

                            if(!empty($errors))
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

                             //wp_register('', '');
                            // display the login form
                            echo get_option('mal_membership_sign_in_message');
                            
                            echo mal_membership_display_member_login_form($_POST['member_email_login']);
                            /*wp_login_form(
                                        array(
                                            'echo' => true,
                                            'redirect' => site_url( $_SERVER['REQUEST_URI'] )
                                        ));
                                  */
                        }
                        else
                        {
                            //mal_membership_pre_array($_COOKIE);
                           ?>

                            <script language="javascript" type="text/javascript">window.location = "<?php echo $member_contest_active; ?>"</script>

                           <?php
                        }

                    break;
                    case 'logout':
                        if($_GET['logout'] == 'true')
                        {
                            $msg = get_option('mal_membership_log_out_message');
                            if(empty($msg))
                            {
                                echo '<h3 class="mal-membership-h3">You are logged out!</h3>';
                            }
                            else
                            {
                                echo $msg;
                            }
                        }
                        else
                        {
                            //wp_logout();
                        ?>

                         <script language="javascript" type="text/javascript">window.location = "<?php wp_logout_url(home_url()) ?>?action=logout&logout=true"</script>

                        <?php
                        }

                    break;
                    case 'signup':
                        /*
                         * *******************
                         * The form is posted and we check on mal_membership.php
                         * To see what action to take
                         * In this case, sign up the new user
                         * *******************
                         */
                        global $current_user;
                        get_currentuserinfo();

                        if (mal_membership_check_for_logged_in_member())
                        {
                           ?>

                            <script language="javascript" type="text/javascript">window.location = "<?php echo $member_contest_active; ?>"</script>

                           <?php

                        }
                        else
                        {
                                /*
                                 * $_POST['errors_found'] is built on mal_membership_validate_public_information()
                                 *
                                 * mal_membership_members.php
                                 * mal_membership_validate_public_information()
                                 *
                                */
                                if(empty($_POST['errors_found']))
                                {
                                    // Do nothing for now
                                }
                                else
                                {
                                    ?>
                                        <div class="error">
                                            <div class="bold-text errors-found">Errors found</div>
                                            <ol>
                                            <?php foreach ($_POST['errors_found'] as $list): ?>
                                                <li class="error-item">&nbsp;&nbsp;<?php echo $list; ?></li>
                                            <?php endforeach; ?>
                                            </ol>
                                        </div>
                                    <?php
                                }

                              if( (!$current_user->ID) )
                              {
                               echo get_option('mal_membership_sign_up_message');
                              ?>




                        <form id="public_customer_signup" method="post">
                            <table>
                                <tr>
                                    <td class="float-left input-description">
                                       First Name<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="first_name" name="first_name" value="<?php echo mal_membership_sticky_input($_POST['first_name'], $member_info->first_name ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Last Name<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="last_name" name="last_name" value="<?php echo mal_membership_sticky_input($_POST['last_name'], $member_info->last_name ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Address<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="address_1" name="address_1" value="<?php echo mal_membership_sticky_input($_POST['address_1'], $member_info->address_1 ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Address (2)
                                    </td>
                                    <td>
                                        <input type="text" autocomplete="off" id="address_2" name="address_2" value="<?php echo mal_membership_sticky_input($_POST['address_2'], $member_info->address_2 ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       City<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="city" name="city" value="<?php echo mal_membership_sticky_input($_POST['city'], $member_info->city ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       State/Provence<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="state" name="state" value="<?php echo mal_membership_sticky_input($_POST['state'], $member_info->state ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Zip/Postal Code<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="zip_code" name="zip_code" value="<?php echo mal_membership_sticky_input($_POST['zip_code'], $member_info->zip_code ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Country Code (ie US, CA, MX)<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="country_code" name="country_code" maxlength="2" size="2" value="<?php echo mal_membership_sticky_input($_POST['country_code'], $member_info->country_code ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Phone 1<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="phone_1" name="phone_1" value="<?php echo mal_membership_sticky_input($_POST['phone_1'], $member_info->phone_1 ) ?>"/>
                                    </td>

                                </tr>
                                <td class="float-left input-description">
                                   Phone 2
                                </td>
                                <td>
                                    <input type="text" autocomplete="off" id="phone_2" name="phone_2" value="<?php echo mal_membership_sticky_input($_POST['phone_2'], $member_info->phone_2 ) ?>"/>
                                </td>

                                </tr>
                                    <td class="float-left input-description">
                                       Email<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required email" autocomplete="off" id="user_email" name="user_email" value="<?php echo mal_membership_sticky_input($_POST['user_email'], $member_info->user_email ) ?>"/>
                                    </td>

                                </tr>
                                </tr>
                                    <td class="float-left input-description">
                                       Password <em>*</em>
                                    </td>
                                    <td>
                                        <input type="password" class="required" autocomplete="off" id="member_password" name="member_password" />
                                    </td>

                                </tr>
                                </tr>
                                    <td class="float-left input-description">
                                      Verify Your Password <em>*</em>
                                    </td>
                                    <td>
                                        <input type="password" class="required" autocomplete="off" id="confirm_member_password" name="confirm_member_password" />
                                    </td>

                                </tr>

                                </table>

                                        <input type="hidden" name="user_id" value="new">
                                        <input type="hidden" name="status" value="active">
                                        <input type="hidden" name="public_signup" value="true">
                                        <input type="hidden" name="redirect_url" value="<?php echo the_permalink(); ?>">
                                        <div class="clear"></div>
                            <input id="malSubmitButton" type="submit" value="Submit">
                            </form>
                            <?php
                              }
                              else
                              {
                                // output a message if needed that it was successful
                                ?>
                                <h3 class="mal-membership-h3">Thank you for signing up, you may now visit the members only section!</h3>
                                <br>
                                <br>
                                <?php

                              }
                        } // Ends check to see if they are not signed in
                    break;
                    case 'edit':
                        /*
                         * *******************
                         * The form is posted and we check on mal_membership.php
                         * To see what action to take
                         * In this case, edit this user
                         * *******************
                         */
                        global $current_user;
                        get_currentuserinfo();

                        if (mal_membership_check_for_logged_in_member())
                        {
                            $member_info = new mal_membership_member();
                            $member_info->mal_membership_get_member_by_id($current_user->ID);

                                /*
                                 * $_POST['errors_found'] is built on mal_membership_validate_public_information()
                                 *
                                 * mal_membership_members.php
                                 * mal_membership_validate_public_information()
                                 *
                                */
                                if(empty($_POST['errors_found']))
                                {
                                   // Output the success message
                                    echo get_option('mal_membership_edit_success_message');
                                }
                                else
                                {
                                    ?>
                                        <div class="error">
                                            <div class="bold-text errors-found">Errors found</div>
                                            <ol>
                                            <?php foreach ($_POST['errors_found'] as $list): ?>
                                                <li class="error-item">&nbsp;&nbsp;<?php echo $list; ?></li>
                                            <?php endforeach; ?>
                                            </ol>
                                        </div>
                                    <?php
                                }


                              ?>




                        <form id="public_customer_edit" method="post">
                            <table>
                                <tr>
                                    <td class="float-left input-description">
                                       First Name<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="first_name" name="first_name" value="<?php echo mal_membership_sticky_input($_POST['first_name'], $member_info->first_name ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Last Name<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="last_name" name="last_name" value="<?php echo mal_membership_sticky_input($_POST['last_name'], $member_info->last_name ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Address<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="address_1" name="address_1" value="<?php echo mal_membership_sticky_input($_POST['address_1'], $member_info->address_1 ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Address (2)
                                    </td>
                                    <td>
                                        <input type="text" autocomplete="off" id="address_2" name="address_2" value="<?php echo mal_membership_sticky_input($_POST['address_2'], $member_info->address_2 ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       City<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="city" name="city" value="<?php echo mal_membership_sticky_input($_POST['city'], $member_info->city ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       State/Provence<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="state" name="state" value="<?php echo mal_membership_sticky_input($_POST['state'], $member_info->state ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Zip/Postal Code<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="zip_code" name="zip_code" value="<?php echo mal_membership_sticky_input($_POST['zip_code'], $member_info->zip_code ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Country Code (ie US, CA, MX)<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="country_code" name="country_code" maxlength="2" size="2" value="<?php echo mal_membership_sticky_input($_POST['country_code'], $member_info->country_code ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td class="float-left input-description">
                                       Phone 1<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required" autocomplete="off" id="phone_1" name="phone_1" value="<?php echo mal_membership_sticky_input($_POST['phone_1'], $member_info->phone_1 ) ?>"/>
                                    </td>

                                </tr>
                                <td class="float-left input-description">
                                   Phone 2
                                </td>
                                <td>
                                    <input type="text" autocomplete="off" id="phone_2" name="phone_2" value="<?php echo mal_membership_sticky_input($_POST['phone_2'], $member_info->phone_2 ) ?>"/>
                                </td>

                                </tr>
                                    <td class="float-left input-description">
                                       Email<em>*</em>
                                    </td>
                                    <td>
                                        <input type="text" class="required email" autocomplete="off" id="user_email" name="user_email" value="<?php echo mal_membership_sticky_input($_POST['user_email'], $member_info->user_email ) ?>"/>
                                    </td>

                                </tr>
                                <tr>
                                    <td>Change Password? <label for="no_change_password">No</label> || <input type="radio" checked="checked" id="no_change_password" value="no" name="change_password" class="change_password"><label for="yes_change_password">Yes</label><input type="radio" id="yes_change_password" value="yes" name="change_password" class="change_password">
                                    </td>
                                </tr>
                                </tr>
                                    <td class="float-left input-description default-hidden">
                                       Password <em>*</em>
                                    </td>
                                    <td class="default-hidden">
                                        <input type="password" class="required" autocomplete="off" id="new_member_password" name="member_password" />
                                    </td>

                                </tr>
                                </tr>
                                    <td class="float-left input-description default-hidden">
                                      Verify Your Password <em>*</em>
                                    </td>
                                    <td class="default-hidden">
                                        <input type="password" class="required" autocomplete="off" id="confirm_new_member_password" name="confirm_member_password" />
                                    </td>
                                </tr>

                                </table>

                                        <input type="hidden" name="user_id" value="<?php echo $current_user->ID; ?>">
                                        <input type="hidden" name="status" value="active">
                                        <input type="hidden" name="public_edit" value="true">
                                        <input type="hidden" name="redirect_url" value="<?php echo the_permalink(); ?>">
                                        <div class="clear"></div>
                            <input id="malEditSubmitButton" type="submit" value="Submit">
                            </form>
                            <?php
                        } // Ends check to see if they are signed in
                        else
                        {
                            ?>

                             <script language="javascript" type="text/javascript">window.location = "<?php echo $member_login_url; ?>&password=changed"</script>

                            <?php
                        }
                    break;
                }

            }

        }
        
    }

}

?>