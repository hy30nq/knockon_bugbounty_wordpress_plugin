<?php

class mal_membership_settings{

    public function mal_membership_update_settings()
    {
        foreach($_POST as $key=>$value)
        {
            update_option( $key, $value );
        }
        mal_membership_redirect( 'mal_membership_settings', 'success_settings' );
        exit;

    }

}