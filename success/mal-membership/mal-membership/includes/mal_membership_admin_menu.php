<?php

function mal_membership_admin_menu()
{
        add_menu_page(
                "Mal Membership Home Page", // page title
                "MM Home", // menu title
                "edit_posts", // capability, read is the lowest
                "mal_membership_view_home_page", // menu slug
        		"mal_membership_view_home_page", // function
        		MM_PLUGIN_URL . "/images/home_icon.gif" // icon url
        );


        // Contest

        add_menu_page(
                "Mal Membership Contest Page", // description of this
                "MM Contests", // menu title
                "edit_posts", // level of access, read is the lowest
                "mal_membership_contests", // related function name to tie sub menus to this main menu
        		"mal_membership_view_contests", // function
        		MM_PLUGIN_URL . "/images/contest_icon.gif" // icon url
        );

        add_submenu_page(
                "mal_membership_contests", // ties this submenu to parent menu
                NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                NULL, // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_contests", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_view_contests" // function that is being called
        );
        add_submenu_page(
                "mal_membership_contests", // ties this submenu to parent menu
                "New Contest", // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                "New Contest", // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_edit_contest&contest_id=new", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_edit_contest&contest_id=new"// function that is being called
        );
    add_submenu_page(
            "mal_membership_contests", // ties this submenu to parent menu
            NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
            NULL, // what is displayed
            "edit_posts", // level of access, read is the lowest
            "mal_membership_edit_contest", // same as add_menu_page to prevent a duplicate link from appearing
            "mal_membership_edit_contest"// function that is being called
    );
    add_submenu_page(
            "mal_membership_contests", // ties this submenu to parent menu
            NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
            NULL, // what is displayed
            "edit_posts", // level of access, read is the lowest
            "mal_membership_view_contest", // same as add_menu_page to prevent a duplicate link from appearing
            "mal_membership_view_contest"// function that is being called
    );



        // Prizes

        add_menu_page(
                "Mal Membership Prize Page", // description of this
                "MM Prizes", // menu title
                "edit_posts", // level of access, read is the lowest
                "mal_membership_prizes", // related function name to tie sub menus to this main menu
        		"mal_membership_view_prizes", // function
        		MM_PLUGIN_URL . "/images/prize_icon.gif" // icon url
        );

        add_submenu_page(
                "mal_membership_prizes", // ties this submenu to parent menu
                NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                NULL, // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_prizes", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_view_prizes" // function that is being called
        );
        add_submenu_page(
                "mal_membership_prizes", // ties this submenu to parent menu
                "New Prize", // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                "New Prize", // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_edit_prize&prize_id=new", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_edit_prize&prize_id=new"// function that is being called
        );
        add_submenu_page(
                "mal_membership_prizes", // ties this submenu to parent menu
                NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                NULL, // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_edit_prize", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_edit_prize"// function that is being called
        );
    add_submenu_page(
            "mal_membership_prizes", // ties this submenu to parent menu
            NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
            NULL, // what is displayed
            "edit_posts", // level of access, read is the lowest
            "mal_membership_select_winner_for_prize", // same as add_menu_page to prevent a duplicate link from appearing
            "mal_membership_select_winner_for_prize"// function that is being called
    );
    add_submenu_page(
            "mal_membership_prizes", // ties this submenu to parent menu
            NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
            NULL, // what is displayed
            "edit_posts", // level of access, read is the lowest
            "mal_membership_replace_winner", // same as add_menu_page to prevent a duplicate link from appearing
            "mal_membership_replace_winner"// function that is being called
    );


        // Members
        add_menu_page(
                "Mal Membership Members Page", // description of this
                "MM Members", // menu title
                "edit_posts", // level of access, read is the lowest
                "mal_membership_members", // related function name to tie sub menus to this main menu
        		"mal_membership_view_members", // function
        		MM_PLUGIN_URL . "/images/member_icon.gif" // icon url
        );
        add_submenu_page(
                "mal_membership_members", // ties this submenu to parent menu
                NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                NULL, // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_members", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_view_members" // function that is being called
        );
        add_submenu_page(
                "mal_membership_members", // ties this submenu to parent menu
                "New Member", // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                "New Member", // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_edit_member&member_id=new", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_edit_member&member_id=new"// function that is being called
        );
        add_submenu_page(
                "mal_membership_members", // ties this submenu to parent menu
                "View Entries", // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                "View Entries", // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_entries", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_entries"// function that is being called
        );


        add_submenu_page(
                "mal_membership_members", // ties this submenu to parent menu
                NULL, // what is displayed  You can leave it blank to have the main menu to act as a link and not have a sub menu
                NULL, // what is displayed
                "edit_posts", // level of access, read is the lowest
                "mal_membership_edit_member", // same as add_menu_page to prevent a duplicate link from appearing
                "mal_membership_edit_member"// function that is being called
        );

        /* Settings */

        add_menu_page(
                "Mal Membership Settings", // page title
                "MM Settings", // menu title
                "edit_posts", // capability, read is the lowest
                "mal_membership_settings", // menu slug
        		"mal_membership_settings", // function
        		MM_PLUGIN_URL . "/images/settings_icon.gif" // icon url
        );


}