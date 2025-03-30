<?php

    function mal_membership_display_help( $help_topic )
    {
        $message = '<div id="mal-help"><h1>Help for ' . ucfirst($help_topic) . '</h1><div class="mal-help-content-wrapper">';

        switch( $help_topic )
        {
            case 'settings':

                $message .= 'Enter a message you want your visitors to see if they try to access a members only page.';
                $message .= '<ul>
                                <li>To use: Copy this short code on a page or a post</li>
                                <li>Everything after that will be protected and not visible unless they log in</li>
                                <li>[mal_membership_protected]Everything in between is protected[/mal_membership_protected]</li>
                                <li>Some valid arguments</li>
                                <li>[mal_membership_protected contest=pending] * Displays pending contests</li>
                                <li>[mal_membership_protected contest=active] * Displays active contests</li>
                                <li>[mal_membership_protected contest=completed] * Displays completed contests</li>
                                <li>[mal_membership_protected contest=cancelled] * Displays cancelled contests</li>
                                <li>[mal_membership_protected prize=pending] * Displays pending prizes</li>
                                <li>[mal_membership_protected prize=active] * Displays active prizes</li>
                                <li>[mal_membership_protected prize=disabled] * Displays disabled prizes</li>
                                <li>[mal_membership_protected member=login] * Displays member login page</li>
                                <li>[mal_membership_protected member=logout] * Displays member logout</li>
                                <li>[mal_membership_protected member=signup] * Displays member sign up form</li>
                                <li>[mal_membership_protected member=edit] * Displays a form so that the member can update their own information</li>
                           </ol>';
            break;
            case 'contest':
               
                $message .= 'Required<br>
                                <ol>
                                <li>Status</li>
                                <li>Start Date</li>
                                <li>End Date</li>
                                <li>Contest Title ( Max 40 characters )</li>
                                </ol>';
            break;
            case 'prize':

                $message .= 'Required<br>
                                <ol>
                                <li>Status</li>
                                <li>Start Date</li>
                                <li>End Date</li>
                                <li>Title</li>
                                </ol>';
            break;
            case 'member':

                $message .= 'Required<br>
                                <ol>
                                <li>First Name</li>
                                <li>Last Name</li>
                                <li>Address</li>
                                <li>City</li>
                                <li>State</li>
                                <li>Zip</li>
                                <li>Phone 1</li>
                                <li>Email</li>
                                </ol>';
            break;
            default:
                echo 'DEFAULT HELP';
            break;
        }

        $message .= '</div></div>';
        echo $message;
    }
