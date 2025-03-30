/* This will have all our jquery we need to make our mal-membership plugin work awesome */

jQuery.noConflict();
jQuery(document).ready(function($) {

    /******** Messages ********/

    if($("div.mal-membership-message").length >= 1)
    {

        $("div.mal-membership-message").delay(3000).fadeOut( 3000 );

        $('div.mal-membership-message').hover(function() {

          $(this).stop(true, false);

        }, function() {

          $(this).stop(true, true).fadeOut( 3000 );
            
        });
    }
    
    /******** End Messages ********/

    /******** Settings ********/

    /* Show and Hide table structure */

    $(".mal-toggle-view-link").click( function() {

        if($("div#mal-toggle-view-content").css('display') == 'none' )
        {
            $("div#mal-toggle-view-content").fadeIn( 500 );
        }
        else
        {
            $("div#mal-toggle-view-content").fadeOut( 500 );
        }


    });

    /* uninstall the mal_membership */
    $("#malMembershipUninstall").click( function() {

        $.post("./admin-ajax.php", {
            action:"malMembershipUninstall"

        }, function(result)	{

            $('div#mal-membership-settings-content-wrapper').fadeOut(300);

            $("#ajax_message").fadeIn(300).html(result);

        });

    });


    /******** END Settings ********/

    /******** Date and Time Picker ********/

    /* Start Time */
    $('#startTimeButton').click( function(e) {
        $('#startTimeInput').AnyTime_noPicker().AnyTime_picker().focus();
        e.preventDefault();
      });
    /* End Time */
    $('#endTimeButton').click( function(e) {
        $('#endTimeInput').AnyTime_noPicker().AnyTime_picker().focus();
        e.preventDefault();
      });

    /******** End Date and Time Picker ********/


    /******** Validate Form ********/

       $("#malSubmitButton").click( function() {

           // One needs to be setup for each form

           /* Contests */
            if( $("form#malContest").length == 1 )
            {
               $("form#malContest").validate();
            }
           
           
       });

    /******** Search Members ********/
    $("button#search-members-button").click( function() {
        /* Make sure at least one checkbox is checked */
       var totalCheckboxes = $('input[type="checkbox"]').filter(':checked').length;
       /* make sure that there is something in the search box */
       var searchFor = $("#search-members").val();

        if( ( totalCheckboxes > 0 ) && ( searchFor.length > 0 ) )
        {
            $.post("./admin-ajax.php", {
                action:"malMembershipSearchMembers",
                    "limit": $('input[name="limit"]:checked').val(),
                    "pending": $("#pending").is(':checked'),
                    "active": $("#active").is(':checked'),
                    "disabled": $("#disabled").is(':checked'),
                    "order_by": $('input[name="order_by"]:checked').val(),
                    "sort_by": $('input[name="sort_by"]:checked').val(),
                    "search_for": searchFor

            }, function(result)	{

                $("#result_list").html(result);

            });
        }
        else
        {
            if( ( totalCheckboxes == 0 ) && ( searchFor.length == 0 ) )
            {
                alert( 'You need to select pending, active or disabled AND Please enter something in the search box' )
            }
            else if( totalCheckboxes == 0 )
            {
                alert( 'You need to select pending, active or disabled' );
            }
            else if( searchFor.length == 0 )
            {
                alert( 'Please enter something in the search box' );
            }
        }
    });



    /******** End Search Members ********/
/*
    $("a.uploaded-image").live( 'click', function() {
        alert($(this).attr('id'));
    })
*/
    /******** Show/Hide Prize Details **********/
    $(".show_hide_prize_details").live('click', function() {
       // Hide all open details
       $('.show-hide-div').fadeOut(100);
       $(this).find('ul').css('display', 'block');
        
       if( $('#show-hide-'+$(this).parent().attr('id')).css('display') == 'none')
       {
            $('#show-hide-'+$(this).parent().attr('id')).fadeIn(400);
       }
        else
       {
           $('#show-hide-'+$(this).parent().attr('id')).fadeOut(400);
       }
       
    });

    /* close icon/link */
    $('.close-link').live( 'click', function() {
        
       $('.show-hide-div').fadeOut(100);
    });
    /******** End show/hide prize details *********/

    /******** Set Maximum number of votes per day per prize *********/

    /* Button to show/hide the options for number of votes per day */
    $("#set_number_entries_per_day").live( 'click', function(e) {


        e.preventDefault();
        $.post("./admin-ajax.php", {
            action:"malMembershipSetNumberVotesPerDay",
                "contest_id": $('#contest_id').val(),
                "prize_id": $("#prize_id").val(),
                "start_date": $("#startTimeInput").val(),
                "end_date": $("#endTimeInput").val(),
                "default_number_of_votes": $("#default_number_of_votes").val()

        }, function(result)	{

            $("#show_calendar").html(result);

        });


    });

    /******** End Maximum number of votes per day per prize *********/
    /******** Toggle Image or YouTube video for contest *********/

    $(".change-media").change( function() {
        switch($(this).val())
        {
            case 'video':
                $("#contest-image").fadeOut(300, function() {
                    $("#contest-video").fadeIn(300);
                    $("#image-video-description").html('Copy the YouTube Embed Code');
                })
            break;
            case 'image':
                $("#contest-video").fadeOut(300, function() {
                    $("#contest-image").fadeIn(300);
                    $("#image-video-description").html('Large Image ( 500 width X 350 height)');
                })
            break;

        }

    })

    /******** End Toggle Image or YouTube video for contest *********/

    /********  Toggle view of completed contest for home page *********/

    $("#toggle_completed_contests").click( function(e){

        $("#completed-contests").toggle(300);
    })

    /********  End Toggle view of completed contest for home page *********/

    /******** Select the winner *********/
    $("#select_the_winner").click( function(e) {
        e.preventDefault();
        $.post("./admin-ajax.php", {
            action:"malMembershipSelectTheWinner",
                "contest_id": $('#contest_id').val(),
                "prize_id": $("#prize_id").val()

        }, function(result)	{

            $("#show_winner").html(result);

        });
    });
    /******** End Select the winner *********/

    /******** Select the NEW winner *********/
    $("#select_new_winner").click( function(e) {
        e.preventDefault();
        $.post("./admin-ajax.php", {
            action:"malMembershipSelectTheNewWinner",
                "user_id": $('#user_id').val(),
                "contest_id": $('#contest_id').val(),
                "prize_id": $("#prize_id").val()

        }, function(result)	{

            $("#show_new_winner").html(result);

        });
    });
    /******** End Select the NEW winner *********/


});

