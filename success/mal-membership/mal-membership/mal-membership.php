<?php
/*
    Plugin Name: mal-membership
    Plugin URI: http://mal-membership.unfuddle.com/svn/mal-membership_mal-membership/
    Description: Mal Membership allows for a members only section and the ability to hold contests
    Version: 0.0.5.6
    Author: Russell Albin
    Author URI: http://www.russellalbin.com
    License: GPLv2 or later
    */
    
    /*
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
    */

// Do not run our normal scripts if we are trying to deactivate this
if( ( $_GET['action'] != 'deactivate' ) && ( $_GET['mal_membership'] != 'deactivate' ) )
{


    define('MM_PLUGIN_URL', get_option('siteurl') . '/wp-content/plugins/mal-membership');
    define('MM_SITE_LOGIN', get_option('siteurl') . '/wp-login.php');
    define('MM_ADMIN_URL', get_option('siteurl') . '/wp-admin/admin.php');
    define('MM_WP_ADMIN', get_option('siteurl') . '/wp-admin/');
    define('MM_PLUGINS_URL', get_option('siteurl') . '/wp-admin/plugins.php');


    $_SESSION['mal_membership_version']								= '0.0.5.6'; // this has to match up with the Plugin Data above
    $_SESSION['mal_membership_database_version']					= '0.1';
    $_SESSION['mal_membership_installed_version'] 					= get_option( "mal_membership_version" );
    $_SESSION['mal_membership_installed_database_version'] 			= get_option( "mal_membership_database_version" );


    // Functions
    require_once(dirname(__FILE__) . '/includes/mal_membership_admin.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_admin_menu.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_add_action.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_add_filter.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_add_shortcode.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_ajax.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_contest.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_members.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_prize.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_general.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_help.php');
    require_once(dirname(__FILE__) . '/includes/mal_membership_settings.php');

    // Classes
    require_once(dirname(__FILE__) . '/classes/mal_membership_class_contest.php');
    require_once(dirname(__FILE__) . '/classes/mal_membership_class_members.php');
    require_once(dirname(__FILE__) . '/classes/mal_membership_class_prize.php');
    require_once(dirname(__FILE__) . '/classes/mal_membership_class_settings.php');

    // Activation and deactivation
    register_activation_hook(__FILE__, 'mal_membership_install');
    register_deactivation_hook(__FILE__, 'mal_membership_deactivation');

    // If the installed database version != the current one, go ahead and run the setup
    if( $_SESSION['mal_membership_database_version'] > $_SESSION['mal_membership_installed_database_version'] )
    {
        mal_membership_install();
    }

    if($_SESSION['mal_membership_version'] != $_SESSION['mal_membership_installed_version'])
    {
        // Update to the latest version
        update_option( "mal_membership_version", $_SESSION['mal_membership_version'] );
    }

    // Uninstall

    // make sure that the database does not need to be updated
    if( $_SESSION['mal_membership_database_version'] != $_SESSION['mal_membership_installed_database_version'] )
    {
        mal_membership_upgrade_database( $_SESSION['mal_membership_installed_database_version'] );
    }
    
    mal_membership_get_timezone();
    
    // Update our contests
    $contest = new mal_membership_contest();
    $contest->mal_membership_set_old_contests_as_completed();

    if(!empty($_POST['public_signup']))
    {
        $result = mal_membership_validate_public_information();
    }
    if(!empty($_POST['public_edit']))
    {
        $result = mal_membership_validate_public_information();

        if(empty($result))
        {

            $member_info = new mal_membership_member();
            $member_info->mal_membership_update_member();
        }
        else
        {
            include('./wp-includes/pluggable.php');
            wp_redirect($_POST['redirect_url']);
        }
    }

    if(!empty($_POST['member_email_login']))
    {
        $result = mal_membership_attempt_to_login_member();
    }

    if($_GET['action']=='logout')
    {
        require (ABSPATH . 'wp-includes/pluggable.php');
        wp_clear_auth_cookie();
    }
}
?>