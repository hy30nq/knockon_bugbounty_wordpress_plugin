<?php
// The purpose for this page is to hold the functions that are for admin section only, not directly related to the Mal Membership

	// Creates a direct link to settings for Mal Membership
	function mal_membership_settings_link($links)
	{

		$settings_link = '<a href="admin.php?page=mal_membership_settings">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}
	// Add a quick link to view members, contests on the new adminbar
	function mal_membership_admin_bar_add_links()
	{
		global $wp_admin_bar;

		$wp_admin_bar->add_menu( array(
			'parent' => false, // use 'false' for a root menu, or pass the ID of the parent menu
			'id' => 'mal_membership_view_members', // link ID, defaults to a sanitized title value
			'title' => __('Members'), // link title
			'href' => admin_url( 'admin.php?page=mal_membership_view_members'), // name of file
			'meta' => false // array of any of the following options: array( 'html' => '', 'class' => '', 'onclick' => '', target => '', title => '' );
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mal_membership_view_members', // use 'false' for a root menu, or pass the ID of the parent menu
			'id' => 'mal_membership_view_members', // link ID, defaults to a sanitized title value
			'title' => __('New Member'), // link title
			'href' => admin_url( 'admin.php?page=mal_membership_edit_member&status=new'), // name of file
			'meta' => false // array of any of the following options: array( 'html' => '', 'class' => '', 'onclick' => '', target => '', title => '' );
		));
        $wp_admin_bar->add_menu( array(
            'parent' => false, // use 'false' for a root menu, or pass the ID of the parent menu
            'id' => 'mal_membership_view_contests', // link ID, defaults to a sanitized title value
            'title' => __('Contests'), // link title
            'href' => admin_url( 'admin.php?page=mal_membership_contests'), // name of file
            'meta' => false // array of any of the following options: array( 'html' => '', 'class' => '', 'onclick' => '', target => '', title => '' );
        ));
        $wp_admin_bar->add_menu( array(
            'parent' => 'mal_membership_view_contests', // use 'false' for a root menu, or pass the ID of the parent menu
            'id' => 'mal_membership_view_contests', // link ID, defaults to a sanitized title value
            'title' => __('New Contest'), // link title
            'href' => admin_url( 'admin.php?page=mal_membership_edit_contest&contest_id=new'), // name of file
            'meta' => false // array of any of the following options: array( 'html' => '', 'class' => '', 'onclick' => '', target => '', title => '' );
        ));


	}



   function mal_membership_upgrade_database( $mal_membership_installed_database_version )
   {

        global $wpdb;

        $latest_database_version = $_SESSION['mal_membership_database_version'];

            switch( $mal_membership_installed_database_version )
            {
                case 'new': // current installed version, in this case its a new install
                    // Set our latest database version number
                    $next_version = $latest_database_version;

                    // Create a new table - contest **
                    $query = "SHOW TABLES LIKE '".$wpdb->prefix."mal_contest'";
                    if($wpdb->get_var($query) != $wpdb->prefix.'mal_contest')
                   {
                      $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."mal_contest` (
                              `ID` int(11) NOT NULL AUTO_INCREMENT,
                              `status` varchar(9) NOT NULL DEFAULT 'pending' COMMENT 'pending,active,completed,cancelled',
                              `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                              `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                              `admin_user_id` int(11) NOT NULL COMMENT 'ID of the admin user who last edited the file',
                              PRIMARY KEY (`ID`)
                            ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

                      $wpdb->query($sql);

                   }

                    // Create a new table - contest data **
                    $query = "SHOW TABLES LIKE '".$wpdb->prefix."mal_contest_data'";
                    if($wpdb->get_var($query) != $wpdb->prefix.'mal_contest_data')
                   {
                      $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."mal_contest_data` (
                                  `ID` int(11) NOT NULL AUTO_INCREMENT,
                                  `contest_id` int(11) NOT NULL,
                                  `data_name` varchar(255) NOT NULL,
                                  `value` text,
                                  PRIMARY KEY (`ID`)
                                ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";
                      $wpdb->query($sql);

                   }


                    // Create a new table - members **
                    $query = "SHOW TABLES LIKE '".$wpdb->prefix."mal_members'";
                    if($wpdb->get_var($query) != $wpdb->prefix.'mal_members')
                   {
                        $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."mal_members` (
                                  `user_id` bigint(20) NOT NULL,
                                  `status` varchar(9) NOT NULL DEFAULT 'pending' COMMENT 'pending,active,disabled',
                                  `admin_user_id` int(11) NOT NULL COMMENT 'ID of the admin user who last edited the file',
                                  PRIMARY KEY (`user_id`)
                                ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1" ;

                      $wpdb->query($sql);
                   }


                    // Create a new table - member data **
                    $query = "SHOW TABLES LIKE '".$wpdb->prefix."mal_member_data'";
                    if($wpdb->get_var($query) != $wpdb->prefix.'mal_member_data')
                   {
                      $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."mal_member_data` (
                                  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
                                  `user_id` int(11) NOT NULL,
                                  `data_name` varchar(255) NOT NULL,
                                  `value` text,
                                  `admin_user_id` int(11) NOT NULL COMMENT 'ID of the admin user who last edited the file',
                                  PRIMARY KEY (`ID`)
                                ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";
                      $wpdb->query($sql);

                   }

                    // Create a new table - prizes **
                    $query = "SHOW TABLES LIKE '".$wpdb->prefix."mal_prize'";
                    if($wpdb->get_var($query) != $wpdb->prefix.'mal_prize')
                   {
                      $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."mal_prize` (
                                  `ID` int(11) NOT NULL AUTO_INCREMENT,
                                  `contest_id` int(11) NOT NULL,
                                  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                                  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                                  `status` varchar(9) NOT NULL DEFAULT 'pending' COMMENT 'pending,active,disabled',
                                  `admin_user_id` int(11) NOT NULL COMMENT 'ID of the admin user who last edited the file',
                                  PRIMARY KEY (`ID`)
                                ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

                      $wpdb->query($sql);
                   }

                    // Create a new table - prize data **
                    $query = "SHOW TABLES LIKE '".$wpdb->prefix."mal_prize_data'";
                    if($wpdb->get_var($query) != $wpdb->prefix.'mal_prize_data')
                   {
                      $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."mal_prize_data` (
                                  `ID` int(11) NOT NULL AUTO_INCREMENT,
                                  `prize_id` int(11) NOT NULL,
                                  `data_name` varchar(255) NOT NULL,
                                  `value` text,
                                  `admin_user_id` int(11) NOT NULL COMMENT 'ID of the admin user who last edited the file',
                                  PRIMARY KEY (`ID`)
                                ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

                      $wpdb->query($sql);
                   }
                        
                    // Create a new table - contest entry **
                    $query = "SHOW TABLES LIKE '".$wpdb->prefix."mal_contest_entry'";
                    if($wpdb->get_var($query) != $wpdb->prefix.'mal_contest_entry')
                   {
                        $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."mal_contest_entry` (
                                    `ID` int(11) NOT NULL AUTO_INCREMENT,
                                    `contest_id` int(11) NOT NULL,
                                    `user_id` int(11) NOT NULL,
                                    `prize_id` int(11) NOT NULL,
                                    `winner` CHAR(1) NOT NULL DEFAULT '0',
                                    `entry_date` DATETIME NOT NULL DEFAULT  '0000-00-00 00:00:00',
                                    `qualified_entry` CHAR(1) NOT NULL DEFAULT '0',
                                    PRIMARY KEY (`ID`)
                                  ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

                        $wpdb->query($sql);
                   }
                    // Create a new table - prize entry details **
                    $query = "SHOW TABLES LIKE '".$wpdb->prefix."mal_prize_entry_details'";
                    if($wpdb->get_var($query) != $wpdb->prefix.'mal_prize_entry_details')
                   {
                      $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."mal_prize_entry_details` (
                                  `ID` int(11) NOT NULL AUTO_INCREMENT,
                                  `contest_id` int(11) NOT NULL,
                                  `prize_id` int(11) NOT NULL,
                                  `maximum_number_votes` CHAR(10) NOT NULL DEFAULT '1',
                                  `start_date` DATETIME NOT NULL DEFAULT  '0000-00-00 00:00:00',
                                  `end_date` DATETIME NOT NULL DEFAULT  '0000-00-00 00:00:00',
                                  PRIMARY KEY (`ID`)
                                ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

                      $wpdb->query($sql);
                   }
                    // Create some default values for the non members to see if they try to view a members only page
                    update_option('mal_membership_non_member_message', 'This is a members only section, please create your account to take advantage of the contests');

                    // now update the database version
                    update_option("mal_membership_database_version", $next_version);
                    // run it again to see if we need to continue move the
                    mal_membership_upgrade_database($next_version);
                break;

                /*
                    commented out for future use this is used to upgrade the database if needed

                case '0.1':
                    $next_version = '0.2';

                    // now update the
                    update_option("mal_membership_database_version", $next_version);
                    $_SESSION['mal_membership_installed_database_version'] = $next_version;
                    // run it again to see if we need to continue move the
                    mal_membership_upgrade_database($next_version);
                break;
                */
                default;

                    // This is either a new install OR the last loop through
                    if(empty($mal_membership_database_version))
                    {
                        $mal_membership_database_version = $latest_database_version;
                    }
                    update_option("mal_membership_database_version", $mal_membership_database_version);
                    $_SESSION['mal_membership_installed_database_version'] = $mal_membership_database_version;
                break;
            }

   }



    function mal_membership_remove_data()
    {
            echo '<div class="wrap">';


            echo '<br><br><div id="remove_message6"><div>If you need to uninstall this plugin, the database tables and options that were created durning setup will NOT be deleted.</div><div>You can remove all the old settings and any database tables that were created by clicking this <a href="javascript: void(0);" id="malClearAllTables">link</a>.</div></div>';
            echo '<div id="message"></div>';

            echo '<br><br><br><br><br><br><br><br><br><br><br>';

            echo '</div>';

    }

    function mal_membership_install()
    {

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $mal_membership_database_version = $_SESSION['mal_membership_installed_database_version'];


        if (empty( $_SESSION['mal_membership_installed_database_version'] ))
        {
            $mal_membership_database_version = 'new';
        }

        // run through the upgrade to see if we need any updates
        //  mal_membership_admin.php:     mal_membership_upgrade_database()
        mal_membership_upgrade_database( $mal_membership_database_version );

        // Update the current version
        update_option("mal_membership_version", $_SESSION['mal_membership_version']);


    }

    function mal_membership_deactivation()
    {
        
        
    }

?>