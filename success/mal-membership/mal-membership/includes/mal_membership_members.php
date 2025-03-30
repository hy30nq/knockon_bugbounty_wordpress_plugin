<?php


    function mal_membership_edit_member()
    {
        global $wpdb;
        global $current_user;

        $message = $_GET['message'];
        $user_id = $_GET['user_id'];

        $member_info = new mal_membership_member();

        if(empty($user_id))
        {
            $user_id = $_POST['user_id'];
        }

        // make sure this is always set
        if(empty($user_id))
        {
            $user_id = 'new';
        }

        $member_info->mal_membership_get_member_by_id( $user_id );
        get_currentuserinfo($current_user->ID);

        echo '<div class="wrap">';

        if(!empty($message))
        {
            echo mal_membership_translate_message($message);
        }

        if (isset($_POST['user_id'])) {

            $errors = array();

            if (empty($_POST['status']))
            {
                $errors[] = 'You forgot the status';
            }
            if (empty($_POST['first_name']))
            {
                $errors[] = 'You forgot the first name';
            }
            if (empty($_POST['last_name']))
            {
                $errors[] = 'You forgot the last name';
            }
            if (empty($_POST['address_1']))
            {
                $errors[] = 'You forgot the address';
            }
            if (empty($_POST['city']))
            {
                $errors[] = 'You forgot the city';
            }
            if (empty($_POST['state']))
            {
                $errors[] = 'You forgot the state';
            }
            if (empty($_POST['zip_code']))
            {
                $errors[] = 'You forgot the zip code';
            }
            if (empty($_POST['country_code']))
            {
                $errors[] = 'You forgot the country code';
            }
            else
            {
                // uppercase the entry
                $_POST['country_code'] = strtoupper($_POST['country_code']);
            }
            if (empty($_POST['phone_1']))
            {
                $errors[] = 'You forgot the phone number';
            }
            else
            {
                // The phone is not empty but we need to validate what was entered
                // We only want numbers so first we strip out any characters that are not numbers
                $number = str_replace(" ", "", preg_replace ('/[^\d\s]/', '', $_POST['phone_1']));
                $length = strlen($number);
                if( $length == '7' || $length == '10' )
                {
                    // do nothing for now
                }
                else
                {
                    //  not correct number of numbers
                    $errors[] = 'Please check the phone number again, 7 or 10 digit phone numbers only';
                }
            }

            if (empty($_POST['user_email']))
            {
                $errors[] = 'You forgot the email address';
            }
            else
            {
                // Check to make sure the email address is unique
                if(!mal_membership_member::mal_membership_verify_unique_email($_POST['user_id'], $_POST['member_email']))
                {
                    $errors[] = 'The email address has already been used, please enter a new email address';
                }
            }
            if (empty($_POST['member_password']))
            {
                $errors[] = 'You forgot the password';
            }

            if (empty($errors))
            {


                mal_membership_member::mal_membership_update_member();
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
            $status = $member_info->status;
        }

        switch($status)
        {
            case 'active': $active_selected = 'selected="selected"';
            break;
            case 'disabled': $disabled_selected = 'selected="selected"';
            break;
            default: $pending_selected = 'selected="selected"';
            break;
        }


            if ( $current_user->user_level >= 7 )
            {

                ?>

                <h3>Add/Edit member</h3>
                <div>&nbsp;</div>
                <form method="post" id="malContest" class="malForm" action="admin.php?page=mal_membership_edit_member">
                <table>
                    <tr>
                        <td class="float-left input-description">Member Status<em>*</em></div></td>
                        <td>

                            <select name="status" class="required" id="status">
                                <option <?php echo $pending_selected; ?> value="pending">Pending</option>
                                <option <?php echo $active_selected; ?> value="active">Active</option>
                                <option <?php echo $disabled_selected; ?> value="disabled">Disabled</option>
                            </select>

                        </td>
                    </tr>
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
                           State<em>*</em>
                        </td>
                        <td>
                            <input type="text" class="required" autocomplete="off" id="state" name="state" value="<?php echo mal_membership_sticky_input($_POST['state'], $member_info->state ) ?>"/>
                        </td>

                    </tr>
                    <tr>
                        <td class="float-left input-description">
                           Zip Code<em>*</em>
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
                            <input type="text" class="required" autocomplete="off" id="country_code" size="2" maxlength="2" name="country_code" value="<?php echo mal_membership_sticky_input($_POST['country_code'], $member_info->country_code ) ?>"/>
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
                            <input type="text" class="required" autocomplete="off" id="member_password" name="member_password" value="<?php echo mal_membership_sticky_input($_POST['member_password'], $member_info->member_password ) ?>"/>
                        </td>

                    </tr>

                    <tr>
                        <td class="float-left input-description">
                           Member Notes
                        </td>
                        <td>
                          <textarea rows="4" cols="60" name="notes"><?php echo mal_membership_sticky_input($_POST['notes'], $member_info->notes ) ?></textarea>

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
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id; ?>">
            </form>

            <br>
            <br>
            <?php
                //  help_functions.php:     sbm_display_help()
                echo mal_membership_display_help( 'member' );
            ?>

           <div id="ajax_message"></div>

       </div>
       <?php

    }

    function mal_membership_view_members()
    {

        ?>
        <div class="wrap">

                <h3>Search members</h3>
                <fieldset class="admin-fieldset">
                    <legend>Limit results</legend>
                    <h3>Limit number of results</h3>
                    <div>
                        <label for="limit-25">Limit 25</label>
                        <input type="radio" name="limit" id="limit-25" value="25" checked="checked"> ||
                        <label for="limit-50">Limit 50</label>
                        <input type="radio" name="limit" id="limit-50" value="50"> ||
                        <label for="limit-75">Limit 75</label>
                        <input type="radio" name="limit" id="limit-75" value="75"> ||
                        <label for="limit-100">Limit 100</label>
                        <input type="radio" name="limit" id="limit-100" value="100">
                    </div>
                    <div>
                        <h3>Include these types of members</h3>
                        <label for="pending">Pending</label>
                        <input type="checkbox" id="pending" name="pending" value="pending"> ||
                        <label for="active">Active</label>
                        <input type="checkbox" id="active" name="active" value="active" checked="checked"> || 
                        <label for="disabled">disabled</label>
                        <input type="checkbox" id="disabled" name="disabled" value="disabled">

                    </div>
                    <div>
                        <h3>Order by Ascending or Descending</h3>
                        <label for="ASC">Ascending</label>
                        <input type="radio" name="order_by" id="ASC" value="ASC" checked="checked"> ||
                        <label for="DESC">Descending</label>
                        <input type="radio" name="order_by" id="DESC" value="DESC">
                    </div>
                    <div>
                        <h3>Sort by Field</h3>
                        <label for="sort_by_last_name">Last Name</label>
                        <input type="radio" name="sort_by" id="sort_by_last_name" value="last_name" checked="checked"> ||
                        <label for="sort_by_first_name">First Name</label>
                        <input type="radio" name="sort_by" id="sort_by_first_name" value="first_name">
                        <label for="sort_by_member_email">Email</label>
                        <input type="radio" name="sort_by" id="sort_by_member_email" value="user_email">
                    </div>

                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend>Search Members</legend>
                    Search:
                    <input type="text" id="search-members" size="40" autocomplete="off">
                    <button id="search-members-button">Search</button>

                    <div>Search Notes:  The search will attempt to match any part of the string you pass along</div>
                    <div>For example: alb in the search will match on: </div>
                    <ol>
                        <li>Albert</li>
                        <li>Malberinski</li>
                        <li>Dekalb</li>
                    </ol>
                </fieldset>
                <fieldset class="admin-fieldset">
                    <legend>Search Results</legend>
                    
                  <div id="result_list" class="search_members_results">Enter a few letters in the search box, then hit submit.</div>

                </fieldset>

        </div>
        <?php
    }

    function mal_membership_validate_public_information()
    {

        $member_info = new mal_membership_member();
        $errors = array();
        // If this is new, all the fields are required
        if(isset( $_POST['first_name'] ))
        {
            $errors = array();

            if (empty($_POST['first_name']))
            {
                $errors[] = 'You forgot the first name';
            }
            if (empty($_POST['last_name']))
            {
                $errors[] = 'You forgot the last name';
            }
            if (empty($_POST['address_1']))
            {
                $errors[] = 'You forgot the address';
            }
            if (empty($_POST['city']))
            {
                $errors[] = 'You forgot the city';
            }
            if (empty($_POST['state']))
            {
                $errors[] = 'You forgot the state';
            }
            if (empty($_POST['zip_code']))
            {
                $errors[] = 'You forgot the zip code';
            }
            if (empty($_POST['country_code']))
            {
                $errors[] = 'You forgot the country code';
            }
            else
            {
                // Upper case the data
                $_POST['country_code'] = strtoupper($_POST['country_code']);
            }
            if (empty($_POST['phone_1']))
            {
                $errors[] = 'You forgot the phone number';
            }
            else
            {
                // The phone is not empty but we need to validate what was entered
                // We only want numbers so first we strip out any characters that are not numbers
                $number = str_replace(" ", "", preg_replace ('/[^\d\s]/', '', $_POST['phone_1']));
                $length = strlen($number);
                if( $length == '7' || $length == '10' )
                {
                    // do nothing for now
                }
                else
                {
                    //  not correct number of numbers
                    $errors[] = 'Please check the phone number again, 7 or 10 digit phone numbers only';
                }
            }

            if (empty($_POST['user_email']))
            {
                $errors[] = 'You forgot the email address';
            }
            else
            {
                // Check to make sure the email address is unique
                if(!$member_info->mal_membership_verify_unique_email($_POST['user_id'], $_POST['user_email']))
                {
                    $errors[] = 'The email address has already been used, please enter a new email address';
                }
            }

            if( ($_POST['change_password'] == 'yes') || (!isset($_POST['change_password'])) )
            {
                // If the change password is set to yes OR it is not there at all validate passwords
                if (empty($_POST['member_password']))
                {
                    $errors[] = 'You forgot the password';
                }
                else
                {
                    if( $_POST['member_password'] != $_POST['confirm_member_password'] )
                    {
                        $errors[] = 'The passwords do not match';
                    }
                }
            }

            if(empty($errors))
            {
                // setup the public user
                return $member_info->mal_membership_update_member();

            }
            else
            {
                return $_POST['errors_found'] = $errors;
            }
        }
    }

function mal_membership_attempt_to_login_member()
{
    $member_info = new mal_membership_member();

        $member_id = $member_info->mal_membership_login_member( $_POST['member_email_login'], $_POST['member_password_login'] );
        if($member_id == 0)
        {
            // Not correct, try again
            $errors[] = 'Login not correct, please try again!';
        }
        else
        {

            return;
        }
    
    return $_POST['errors_found'] = $errors;

}