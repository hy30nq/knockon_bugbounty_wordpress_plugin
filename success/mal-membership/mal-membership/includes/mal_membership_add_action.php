<?php

if(!empty($_SESSION['mal_membership_version']))
{


    add_action( 'admin_menu', 'mal_membership_admin_menu' );
    add_action( 'wp_before_admin_bar_render', 'mal_membership_admin_bar_add_links' );

    function mv_my_theme_styles()
    {
       if (!is_admin())
        wp_enqueue_style('my-custom-style', MM_PLUGIN_URL . '/css/public-style.css',false,'1.1','all');
        wp_enqueue_script( 'jquery' );

        // add the just jquery for the public side
        wp_register_script( 'mal-publicscripts', MM_PLUGIN_URL . '/jquery/malPublicScripts.js' );
        wp_enqueue_script( 'mal-publicscripts' );
        // embed the javascript file that makes the AJAX request
        wp_enqueue_script( 'my-ajax-request', plugin_dir_url( __FILE__ ) . 'mal_membership_ajax.php', array( 'jquery' ) );
        wp_localize_script( 'my-ajax-request', 'MyAjax', array(
            // URL to wp-admin/admin-ajax.php to process the request
            'ajaxurl'          => admin_url( 'admin-ajax.php' ),

            // generate a nonce with a unique ID "myajax-post-comment-nonce"
            // so that you can check it later when an AJAX request is sent
            'postCommentNonce' => wp_create_nonce( 'myajax-post-comment-nonce' ),
            )
        );
    }
    // Load our public css to the public so we can use the error colors
    add_action('init','mv_my_theme_styles');

    function mal_membership_load_custom_wp_admin_style()
    {
        wp_register_style( 'custom_wp_admin_css', MM_PLUGIN_URL . '/css/admin-style.css', false, '1.0.0' );
        wp_enqueue_style( 'custom_wp_admin_css' );
        wp_register_style( 'anytime_css', MM_PLUGIN_URL . '/css/anytime.css', false, '1.0.0' );
        wp_enqueue_style( 'anytime_css' );
        wp_enqueue_style( 'thickbox' );
    }

    // Loads our custom ADMIN section css
    add_action('admin_enqueue_scripts', 'mal_membership_load_custom_wp_admin_style');

    wp_register_script( 'mal-anytime', MM_PLUGIN_URL . '/jquery/malAnyTime.js' );
    wp_register_script( 'mal-validate', MM_PLUGIN_URL . '/jquery/malValidate.js' );
    wp_register_script( 'mal-dom_ready', MM_PLUGIN_URL . '/jquery/malDomReady.js' );

	function mal_membership_enqueue_admin_jquery()
    {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'mal-anytime' );
        wp_enqueue_script( 'mal-validate' );
        wp_enqueue_script( 'mal-dom_ready' );
        wp_enqueue_script( 'thickbox' );
	}

	add_action('admin_init', 'mal_membership_enqueue_admin_jquery');

    /* AJAX */
    
	// Mal Membership Uninstall
	add_action('wp_ajax_malMembershipUninstall', 'mal_membership_uninstall');

    // Mal Membership Search Members
    add_action('wp_ajax_malMembershipSearchMembers', 'mal_membership_search_members');

    // Mal Membership set number of votes per prize per day
    add_action('wp_ajax_malMembershipSetNumberVotesPerDay', 'mal_membership_set_number_votes_per_day');

    // Mal Membership select the winner at random
    add_action('wp_ajax_malMembershipSelectTheWinner', 'mal_membership_select_the_winner');

    // Mal Membership Sign Member up for the prize
    add_action('wp_ajax_malMembershipEnterForThisPrize', 'mal_membership_enter_for_this_prize');
    
    // if both logged in and not logged in users can send this AJAX request,
    // add both of these actions, otherwise add only the appropriate one
    add_action( 'wp_ajax_nopriv_mal-membership-ajax-submit', 'mal_membership_prize_entry_submit' );
    
    add_action( 'wp_ajax_mal-membership-ajax-submit', 'mal_membership_prize_entry_submit' );
    // Select a new winner and remove the original winner
    add_action( 'wp_ajax_malMembershipSelectTheNewWinner', 'mal_membership_select_new_winner' );


}

