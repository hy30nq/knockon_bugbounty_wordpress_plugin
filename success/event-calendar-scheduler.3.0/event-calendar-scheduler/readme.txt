=== Event Calendar / Scheduler ===
Contributors: dhtmlx
Donate link: http://www.dhtmlx.com/docs/contact.shtml
Tags: event calendar, planner, event, calendar, events, scheduler, plugin, date, archive, recurring events, event list, repeating events, event management, events calendar, calendar widget, maps, booking calendar, ajax, javascript, upcoming events, orginizer
Requires at least: 2.0.2
Tested up to: 3.3.1
Stable tag: 3.0

Event calendar plugin that allows you to add a nice-looking scheduler/planner with drag-n-drop interface, recurring events, Google Map integration.  

== Description ==

An easy to implement event calendar plugin built on top of <a href="http://dhtmlx.com/docs/products/dhtmlxScheduler/">dhtmlxScheduler</a> 
that provides an Ajax-based scheduling interface similar to Google Calendar. The plugin allows you 
to manage single or multiple user events, display any type of events and appointments, and put a list of the upcoming events on a side bar. 

Users can add/edit/delete events on the fly and easily change event date and time by simply dragging the event boxes. 
You can set up different levels of permissions to people who will use the calendar: make the scheduler read-only or allow
a group of users to edit the events through a web-based calendar interface.

The calendar can be configured to display events in Day, Week, Month, Agenda, Timeline view, as well as in any custom view. The intuitive admin panel 
makes it easy to customize the calendar to your needs. You can use the plugin as an event calendar or as archive calendar to display your blog posts archive (in this case it works in read-only mode). 

<b>Main Features</b>

- Day, Week, Month, Year, Agenda, Timeline Views
- The ability to create a custom View 
- Ajax-enabled, intuitive interface
- Drag-n-drop support to configure event date and time
- Easy-to-customize
- Single/multi-day events 
- Customizable time scale 
- Recurring events 
- Configurable access and edit rights
- Upcoming events widget
- Localization to more than 20 languages

<b>What's New in 3.0</b>

- Week Agenda View
- Map View (integration with Google Maps)
- Timeline View
- Full day event
- The ability to display event creator 
- Export to/from Google Calendar
- Built-in export to PDF and iCal
- WPMU support


Requirements

- PHP 5.0 or greater
- MySQL 4.0 or greater

== Installation ==

1. Upload the content of event-calendar-scheduler.zip to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

After plugin activation, you will have a new page in your blog, with calendar on it. 
You can configure it through Plugins menu.

**SideBar installation**

To have the list of upcoming events on the sidebar, you can add the next line into sidebar's template 
<code>
 <?php if (function_exists('scheduler_sidebar')) echo scheduler_sidebar(); ?>
</code>

If you are using a widget-capable theme, "Upcoming Events" widget can be used for the same.
 
**Export to iCal format**

To add such possibility, just add the next link somewhere on the page ( inside post, or inside sidebar's template )

<code>
<a href='./wp-content/plugins/event-calendar-scheduler/ical.php'>Export events</a>
</code>

If you need to export only upcoming events, the link will look as 
<code>
<a href='./wp-content/plugins/event-calendar-scheduler/ical.php?oncoming'>Export upcoming events</a>
</code>

== Frequently Asked Questions ==

= The scheduler is distorted, it doesn't look good. =

The scheduler was tested with most popular themes for Wordpress, but still it’s possible that theme used in your case is not compatible with the scheduler's styles. 
Please drop an email to   dhtmlx [at] gmail [dot] com   with the name of used theme.

= Scheduler throws "Incorrect XML" error  =

Most probably you are using php 4.x , which is not supported.
In settings of plugin enable "Debug mode" and check the problematic page again - now it must contain more readable problem description. 

= How to change the scheduler's style =

+ Go to the [http://dhtmlx.com/docs/products/dhtmlxScheduler/skinBuilder/index.shtml](http://dhtmlx.com/docs/products/dhtmlxScheduler/skinBuilder/index.shtml)
+ Create and download custom skin pack
+ Unzip skin pack to the wp-content\plugins\event-calendar-scheduler\codebase 
+ Because skin can be reset by future updates, it has sense to store skin-pack somewhere for later usage as well

= How I can edit|delete events =

All operations can be done through the public GUI
[documentation](http://dhtmlx.com/dhxdocs/doku.php?id=dhtmlxscheduler:external_plugin:wordpress)

Also, you can create new events during post creating | editing
[documentation](http://dhtmlx.com/dhxdocs/doku.php?id=dhtmlxscheduler:external_plugin:wordpress#post_creating_form)

= I still not able to create new event =

Check the settings of scheduler, the "Add" action must be enabled for the related user group, to be able to add the new event. 

= I have a question - ... = 
If something is still not clear - you can ask your question at [dhtmlx support forum](http://forum.dhtmlx.com/viewforum.php?f=6)

== Screenshots ==

1. Events calendar within a blog page 
2. A new event window
3. Admin panel
4. Week View
5. Map View (integration with Google Map)
6. Week Agenda View
7. Timeline View

== Changelog ==

= 1.0 =
Initial release.

= 1.1 =
+ improved compatibility with themes of Wordpress
+ rights management is extended
+ agenda view is added
- problem with events in non-latin encoding is fixed
- problem with absolute paths is fixed

= 1.2 =
+ details are shown in readonly mode if user has "view" access
+ ability to export data in ical format
+ ability to place list of upcoming events on sidebar
+ ability to create direct links to specific date is added
+ multi-day events can be rendered on daily and weekly view
* calendar widget is added to the "new event" form
- problem with quotes in event's text is fixed

= 1.2.1 =
- hotfix for path on linux based installations

= 1.3 =
+ year view
+ custom skins
+ recurring events related fixes
+ js code updated to the dhtmlxScheduler 2.1
+ WordPress MU compatibility 
+ client side localization for 13 languages

= 2.0 =
+ new admin panel
+ optional mini-calendar navigation 
+ units view 
+ configurable time|text templates
+ backend GUI for events management
+ ability to define custom fields
+ ability to define event's color
+ new skin 
+ codebase updated to dhtmlxScheduler 2.2
+ compatible with WP 3.x

2.3
+ codebase updated to scheduler 2.3

2.3.1
+ fixes for WP 3.1.1

= 3.0 =
+ week agenda view
+ map view (integration with Google Map)
+ current time indicator
+ full day event
+ show the event creator name
+ timeline view
+ export to/from Google Calendar
+ print to PDF button
+ export to iCal button
+ WPMU support