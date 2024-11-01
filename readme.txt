=== Plugin Name ===
Contributors: geilt
Donate link: http://www.esotech.org/
Tags: events, calendar, widget, post-type
Requires at least: 3.3.2
Tested up to: 3.3.2
Stable tag: trunk

Creates a post type �Events� and Widget that will display a Calendar and/or list of Events.

== Description ==

This plugin is designed to create a post type "Events" and Widget that will display a Calendar and/or list of Events. The Events post type includes a custom Taxonomy "Calendars" so you can assign an event to multiple calendars. Adding an event allows you to choose a start date and end date using jQuery Ui's Date Picker, as well as adding your own custom link and location displayed by Google Maps.

The widget allows you to customize the display of a list of events or calendars. There are many CSS classes included in the HTML to style the Widget to your needs. You can show the Calendar, list of Events, or a combination of both. 

The Calendars provides pop out functionality for events if desired, with the ability to include a custom size Google map of the event location as well as link to the event page, which you can customize in your own template.

[More Details](http://www.esotech.org/plugins/simpul/simpul-events/)

= Planned Features =
* Selectable Default Stylesheet
* ShortCode.
* Template Function(Implemented but in Beta - use get_simpul_events($args) ). 

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory or search for it and install.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add events under the new "Events" Section
4. Add an Event under "Events" and "Add New".
5. Choose a Date Begin and Date End
6. Choose a Link (if needed)
7. Choose a Location (if needed).
8. Go to Appearance ->Widgets
9. Drag the "Event Calendar" Widget into the Sidebar you want to use.
10. Choose the options as you need them. You can display just the calendar or just the events as a list, or both. The Calendar will always be above the list.

== Frequently Asked Questions ==

= Does this include a styled calendar =

Not yet, but that feature will come soon. We are deciding on what is a good standard calendar theme, we may go with something that works with the Wordpress TwentyEleven theme.

= How do I get the calendar to Display? =

You must check of the "Calendar" feature in the widget option after adding the Widget to your sidebar.

== Screenshots ==

1. Sortable List of Events showing all Meta Information.
2. Adding an Event.
3. Adding an Event showing jQuery UI Dropdown.
4. Add or edit Calendars.
5. Robust Widget Options.

== Changelog ==
= 1.8.5 =
1. Fixed Bug on Calender Traversing. Had invalid selector (left a , in there somewhere).

= 1.8.4 =
1. Class is now simpul-events and ID is simpul_events. Updated JS to reflect.
2. Updated jquery-ui to load from Wordpress.

= 1.8.3 =
1. Wrapped Calendar to prevent movement instead. For real this time.

= 1.8.2 =
1. Changed append to prepend in JS to prevent Calendar events from popping up above the Calendar.

= 1.8.1 =
1. Fixed critical Ajax bug that broke jQuery.

= 1.8 =
1. Added Ajax for indefinite movement between months. This is triggered by the click event on: .simpul-calendar-header-right or .simpul-calendar-header-left
2. Added support for multiple events on hover, added attributes to element for debugging, etc.

= 1.7.3 = 
1. Updated script queuing for efficiency and to prevent any conflict with other plugins, including other simpul plugins.
 
= 1.7.2 = 
1. Added control to change Title Element. 

= 1.7.1 =
1. Fixed Issue with Number of Events not Limiting. 

= 1.7 =
1. Added Calendar Event Rollover Option to Rollover Disabling.
2. Organized Rollover Settings better.
3. Added Linkable Calendar Day which works with rollover or without.
4. Added class simpul-calendar-link for Calendar Day Links.

= 1.6 =
1. Used wp_plugin_basename() to load files. The name of the file folder on Wordpress.org was different than the file folder on our development install, which caused issues. This was causing all jQuery custmizations to not load on install, including the Drop Down Calendar on both the back and front end.
2. Enqeued jQuery before our custom jQuery script so that the calendar would load properly on the front end and backend. 
3. Removed Duplicate "Calendar" Checkbox.
 
= 1.0 =
* First Upload