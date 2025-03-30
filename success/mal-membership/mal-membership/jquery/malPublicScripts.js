jQuery.noConflict();
jQuery(document).ready(function($) {

    $('.submit-for-this-prize').live('click', function(e) {
        $(this).html('Submitting Entry');
        var prizeId = $(this).attr('id');
        var msg = '';
        
        // Use Ajax to enter this
        e.preventDefault();

        jQuery.post(
            MyAjax.ajaxurl,
            {
                action       : 'mal-membership-ajax-submit',
                "contest_id" : $("#contest-id-" + $(this).attr('id')).val(),
                "prize_id"   : $("#prize-id-" + $(this).attr('id')).val(),
                "user_id"    : $("#user-id-" + $(this).attr('id')).val(),
                
                // send the nonce along with the request
                postCommentNonce : MyAjax.postCommentNonce
            },
            function( response ) {
                var result = response.split(',');
                //var numLeft = Number(result[1]);

                // Now update the total number of votes for this
                //response[0] = total votes for this prize for this member
                //response[1] = votes left for this prize for this member
                $("#prize-" + prizeId + "-votes-by-member").html(result[0]);
                if(result[1] == '0')
                {
                    msg = 'No more entries today';
                     $("#"+prizeId).html(msg).attr('disabled', 'disabled');
                }
                else
                {
                    msg = 'You can enter a ' + result[1] +' more times';
                    $("#"+prizeId).html(msg);

                }
                $("#entry-message-id-" + prizeId).css('display', 'block').html(msg).delay(3000).fadeOut( 3000 );

                //$("#"+prizeId).html('No More Entries').attr("disabled", "disabled");


                    //$(this).html('You are entered for this prize - You can enter ' + result[1] + ' more time');


            }

        );

       /*
        $.ajax({
                url:"./wp-admin/admin-ajax.php",
                type:'POST',
                data:'action=malMembershipEnterForThisPrize&contest_id='+$('#contest_id').val()+'&prize_id=' + $("#prize_id").val(),
            success:function(totalVotes){
            }
        });
        */

    });


    $("a.prize-info").live( 'click', function() {
        var showHideId = "show-hide-prize-" + $(this).attr('id');
        var currentStatus = $("#" + showHideId).css('display');
        if(currentStatus == 'none' )
        {
            $("#" + showHideId).fadeIn(1000);
        }
        else
        {
            $("#" + showHideId).fadeOut(1000);
        }

        })

        // Change Password if yes, show default-hidden
    $('.change_password:input').click( function() {

            if($(this).val() == 'yes')
            {
                if($(".default-hidden").not(":visible"))
                {
                    $(".default-hidden").fadeIn('300');
                }
            }
            else
            {
                if($(".default-hidden").is(":visible"))
                {
                    $(".default-hidden").fadeOut('300');
                }
            }
        });
});

