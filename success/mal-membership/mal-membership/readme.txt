=== Mal Membership ===

Contributors: russell.albin
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LVN2Z9HGWTBYA
Tags: member only, contest
Requires at least: 3.2.1
Tested up to: 3.2.1
Stable tag: trunk

Members only section and hold contests - UNDER CONSTRUCTION!!! BE PATIENT 

== Description ==

Mal Membership allows for a members only section and ability to hold contests.

== Installation ==

1. Unzip the plugin file.
2. Upload the "mal-membership" folder to the "/wp-content/plugins/" directory. The "mal-membership" file must be located at this address: "http://your-wordpress-directory-address/wp-content/plugins/mal-membership/".
3. Activate the plugin through the "Plugins" menu in WordPress.
4. Configure the plugin through the Mal Membership Settings page in WordPress.


== Frequently Asked Questions ==

= How and why should I use this =

This is the best one out in wordpress...A Real FAQ is coming soon

= What is this plugin? =

This was developed to provide a members only section to malpearson.com and provide the ability to hold contests

== Screenshots ==



== Changelog ==
= 0.0.5.6 =
* Found another place where the data is in a loop and could display data incorrect data

= 0.0.5.5 =
* Tring to fix the same problem that previous winners will show up in the next winners information.

= 0.0.5.4 =
* Fixed a problem that was not clearing the values when displaying winners.  It would sometimes keep the previous data.

= 0.0.5.3 =
* Fixed a bug that was not updating the version of the plugin
* Removed the Development version text on the MM Home page

= 0.0.5.2 =
* Member/User can now edit their own information on the public facing side
* Ability to control the success message after a member edits their profile now provided in MM Settings page
* Fixed a log out issue when using the shortcode
* Changed the login and log out and password change for public users

= 0.0.5.1 =
* Ability to view entries for today as well as during a set of days

= 0.0.5.0 =
* Public sign up now has Country and changed the text for zip and State
* show email address for winners

= 0.0.4.9 =
* Fixed the colors for the table layouts
* Fixed the description of the prize that was not displaying on the home page

= 0.0.4.8 =
* Added a scrolling div around the list of contests to help with the large volume of results
* Added coloring of results to help with visual separation of results to several tables
* On the home page, there is a list of contests that need winners selected

= 0.0.4.7=
* On the Home page, when you click completed contests, that list that appears,
  fixed an issue that was not showing the completed contests properly.

= 0.0.4.6 =
* fixed a bug that was not setting the prize to completed if the contest is completed
* Added ability to view / retrieve prize information on several pages
* Added completed as a valid prize status
* Ability to change winners if needed
* Provided some additional links to view contests, view winners on some different pages

= 0.0.4.5 =
* Changed the title on the public side back to h2 with a class of mal-membership to control the css
* Fixed the search for members

= 0.0.4.4 =
* New menu to view a Contest ( rather than edit )
* Remove description from view all contest page it sometimes breaks if you have something weird in there.

= 0.0.4.3 =
* Removed a bad error message when their is a problem with the submission.

= 0.0.4.2 =
* Fixed the log out feature, it now takes the member back to the home page after logging them out

= 0.0.4.1 =
* Added 20 characters to the contest title

= 0.0.4.0 =
* Found a bug that was not showing contests if you went back to the login page
* Added a redirect to take care of this bug

= 0.0.3.9 =
* Added some css adjustments to the public side to help avoid some display issues

= 0.0.3.8 =
* Changed the <h2> to <h3> on the listing of contests

= 0.0.3.7 =
* Added a custom message to the log out page

= 0.0.3.6 =
* Removing some error messages that are not working the way I expected

= 0.0.3.5 =
* Added some text boxes to allow for custom messages to the sign in and sing up forms

= 0.0.3.4 =
* Added some text to the top of the sign up form

= 0.0.3.3 =
* Added a link on the home page to take you to the contests page

= 0.0.3.2 =
* Removed the word Title: from the public side when viewing contests/prizes
* Removed the word Description: from the public side when viewing contest/prizes

= 0.0.3.1 =
* Fixing an issue that was not displaying messages when no current contest or prizes are running
* Also trying to fix a bug that was not allowing a contest to be entered
* Adding some nice validation that the entry was successful

= 0.0.3.0 =
* fixed a bug that was not selecting the winners after the contest was over

= 0.0.2.9 =
* Fixed a bug in the public entry of contests that was not showing the messages after entering for a prize

= 0.0.2.8 =
* Fixed the ability for members to enter a contest for a prize
* Fixed the countdown of how many votes have been cast and how many are left

= 0.0.2.7 =
* Fixed the member sign up and sign in
* Changed admin menu to not show up in case a member knows how to log into the admin section.
* @TODO add, in the admin section, some details for the member about the prizes they are entered in, and what they have won

= 0.0.2.6 =
* Converted the members to use the user built into Wordpress.
* Changed many tables and references, it is remove all data using the settings of this plugin, deactivate and delete, then reinstall from wordpress.org
* Error handling fixed for public user self sign up!

= 0.0.2.5 =
* Changing format to use the default users tables and then use mal_members to hold the extra info

= 0.0.2.4 =
* found a bug that showed contests to not-logged in visitors

= 0.0.2.3 =
* Fixed a bug that was not returning all the winners should the prize have more than one winner
* Also will show the original winners if you try to select new winners for a contest that already has winners

= 0.0.2.2 =
* Fixed a bug with sites using permalinks and also those who do not

= 0.0.2.1 =
* Added some help instructions to the Settings page to assist in using short codes

= 0.0.2.0 =
* moved sign up to be part of the main short code
* added javascript redirect for attempts to go to a page that you should not see, like the sign in page when already signed in
* Reworked the validation of when your logged in or not and makes sure that the customer goes to the right page

= 0.0.1.9 =
* Setup had some missing columns 

= 0.0.1.8 =
* fixed a bug that was not allowing for a contest to be entered within 2 hours of the current time.
* Created ability to set the time zone for the installation and have it set to CST as default

= 0.0.1.7 =
* Added some ajax for the public side to allow for easier entry into contest.
* Changed the layout of the public side so that after a member signs up, they are automatically sent to the contests

= 0.0.1.6 =
* Fixed a bug that was not showing the number of entries per day for the prize in the admin panel

= 0.0.1.5 =
* Members can now enter contests after logging in

= 0.0.1.4 =
* Adding jquery to public side of web site

= 0.0.1.3 =
* Formatted the prizes and output for contests for the public view
* Added option for youtube video OR  image upload

= 0.0.1.2 =
* Trying to output the values of each day for the prize

= 0.0.1.1 =
* Fixed a bug that was not showing the proper days between start and end dates in when viewing them to set the
  number of entries that are possible

= 0.0.1.0
* Fixed spelling error in the admin menu
* Removed the maximum number of entries for the contest.  The prize will dictate the maximum number of entries
* Added a filter to advise if the winner of the contest will be at random OR by selection of an administrator
  The selection will be a selection of qualified entries.  They will be picking them in the admin panel and then
  the winner will be randomly selected.
* Now each day of the prize has the ability to set how many votes you can place

= 0.0.0.9 =
* Added a qualified entry field to the contest entry table to be able to designate that this entry is qualified to be a winner

= 0.0.0.8 =
* Added a datetime field to the contest entry table to track when the entry is made

= 0.0.0.7 =
* Added maximum number of entries for contests and prizes in the admin section

= 0.0.0.6 =
* Testing svn auto update

= 0.0.0.5 =
* Updating the readme and version numbers

= 0.0.0.4 =
* Added this plugin to an unfuddle repository

= 0.0.0.3 =
* Added the public sign up and login as well as log out features

= 0.0.0.2 =
* Cleaned up the way you create/edit contests and prizes.


= 0.0.0.1 =
*  10/02/2011
*  Basic development of plugin and functions

== Upgrade Notice ==

= 0.0.2.5 =
Overhaul of members, many tables updated UPDATE REQUIRED

== Arbitrary section ==

A description of the table structure ( out of date do not rely on this )

Table: mal_contest

        ID              = auto increment number
        status          = pending,active,completed,cancelled
        start_date      = the date/time the contest starts format: YYYY-MM-DD HH:MM:SS
        end_date        = the date/time the contest ends format: YYYY-MM-DD HH:MM:SS

Table: mal_members

        ID              = auto increment number
        status          = pending,active,disabled

Table: mal_prize

        ID              = auto increment number
        status          = pending,active,disabled
        contest_id      = ID of the mal_contest this is associated to

Table: mal_contest_data

        ( This table has all the details regarding the contest )

        ID              = auto increment number
        contest_id      = ID of the mal_contest this is associated to
        value           = description

Table: mal_member_data

        ( This table has all the details regarding the member )

        ID              = auto increment number
        user_id       = ID of the mal_contest this is associated to
        value           = description of member

Table: mal_prize_data

        ( This table has all the details regarding the prize )

        ID              = auto increment number
        prize_id        = ID of the mal_contest this is associated to
        value           = description of prize

Table: Javascript for date/time

        http://www.ama3.com/anytime/

Please let me know if you have any questions.  russell@russellalbin.com

== A brief Markdown Example ==


