=== WP Admin Microblog ===
Contributors: Michael Winkler
Tags: microblog, microblogging, admin, communication, collaboration, scratchpad
Requires at least: 3.8
Tested up to: 4.6.1
Stable tag: 3.0.3

Make the communication and collaboration in your team easier or use it as a scratchpad.

== Description ==

WP Admin Microblog adds a separate microblog in your WordPress backend. The plugin transforms automatically urls to links, supports tagging and some bbcodes and it's possible to send a message via e-mail to other users. In addition you can read, respond, edit and delete messages directly from your dashboard. So, WP Admin Microblog is great for supporting the communication within blog teams or it's a nice scratchpad. ;-)

= Supported Languages =
* English 
* German
* Spanish
* Swedish
* Turkish

= Further information = 
* [Developer blog](https://mtrv.wordpress.com/microblog/) 
* [WP Admin Microblog on GitHub](https://github.com/winkm89/wp-admin-microblog)  

= Disclaimer =  
Use at your own risk. No warranty expressed or implied is provided. 

== Credits ==

Copyright 2010-2016 by Michael Winkler

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

= Licence information of external ressources =
* document-new-6.png  by Oxygen Icons 4.3.1 http://www.oxygen-icons.org/ (Licence: LPGL)

== Installation ==

1. Download the plugin.
2. Extract all the files. 
3. Upload everything (keeping the directory structure) to your plugins directory.
4. Activate the plugin through the 'plugins' menu in WordPress.

== Screenshots ==

1. Main interface
2. Dashboard widget
3. The options

== Frequently Asked Questions ==

= How can I send a message as e-mail notification? =
If you will send your message via e-mail to any user, so write @username (example: @admin).

= How can I add tags to a message? =
Since version 2.1 you can add tags directly as hashtags. Examples: #2012, #Microblog

= How can I overwrite default settings like the number of messages per page? = 
The default settings WPAM_DEFAULT_TAGS, WPAM_DEFAULT_NUMBER_MESSAGES and WPAM_DEFAULT_SORT_ORDER can be overwritten if you define this parameters for example in your wp-config.php.

= How can I format text? =
You can use the following BBCodes for text formatting:
* [b]bold[/b], 
* [i]italic[/i], 
* [u]underline[/u], 
* [s]strikethrough[/s], 
* [red]red[/red], 
* [blue]blue[/blue], 
* [green]green[/green], 
* [orange]orange[/orange], 
* [sup]superscript[/sup], 
* [sub]subscript[sub], 
* [code]code[/code].  

Combinations like [red][s]text[/s][/red] are possible.

== Changelog ==

= 3.0.3 - (16.09.2016) =
* Changed: Switch get_currentuserinfo() calls to wp_get_current_user()

= 3.0.2 - (16.03.2016) =
* New: Spanish translation added (Thanks to Alfonso Montejo)

= 3.0.1 - (28.09.2015) =
* New: Swedish translation added (Thanks to mepmepmep)

= 3.0 - (16.08.2015) =
* New: Like buttons added
* New: Support for the following BBCodes added: [code]
* New: Images will be displayed directly
* New: GUI is now more responsive
* New: Buttons in reply and edit fields can now be localized
* Bugfix: "No tags available" message were not displayed

= 2.3.5 - (22.07.2015) =
* New: New screen option for date format
* New: Turkish translation added (Thanks to EuroNur | Yeni Asya Team)

= 2.3.4 - (13.07.2015) =
* New: Support for the following BBCodes added: [sub], [sup]
* New: Better sticky post style 
* Bugfix: Fixed a bug in the sticky post function of the dashboard widget

= 2.3.3 - (07.06.2014) =
* Bugfix: Fixed a bug, which prevents the deletion of messages.

= 2.3.2 - (07.06.2014) =
* New: Automatic check for new messages can be disabled
* Changed: If a comment is deleted, the sort date of the message is reseted.
* Changed: Default database engine is now INNODB
* Changed: Some default settings can now be overwritten (number of messages per page, number of tags, sort option)

= 2.3.0/2.3.1 - (06.06.2014) =
* New: User specific screen options added (number of messages per page, number of tags, sort option)
* New: New sort option for messages added
* New: Automatic check for new messages added
* Changed: UI modifications for better integration in WordPress 3.8+

= 2.2.4 - (27.11.2013) =
* This is a service release for supporting all current WordPress versions and PHP 5.4+

= 2.2.3 - (21.11.2012) =
* Bugfix: Change the send method for edits and replies from GET to POST

= 2.2.2 - (25.10.2012) =
* Bugfix: Fixed a incomplete call of wpam_message::add_message

= 2.2.1 - (11.08.2012) =
* Bugfix: Prevent message duplications

= 2.2.0 - (08.08.2012) =
* New: Add support for network installations

= 2.1.0 - (14.02.2012) =
* New: Hashtags are now available
* New: More options for email notifications

= 2.0.1 - (24.12.2011) =
* Bugfix: Small spelling mistake

= 2.0.0 - (24.12.2011) =
* New: Redesigned dashboard widget
* New: Redesigned user interface
* Changed: Use add_help_tab instead an own help tab on main screen
* Changed: Move WPAM settings page from WordPress settings to WPAM menu section

= 1.3.0 - (26.09.2011) =
* New: Admins can edit all messages

= 1.2.0 - (22.09.2011) =
* New: Sticky messages

= 1.1.0 - (11.09.2011) =
* New: Changing the title of the dashboard widget and the title of the microblog page
* Bugfix: 'file://' was not correct replaced with 'http://' in function wp_admin_blog_replace_url
* Bugfix: Using of undefined constants
* Bugfix: Using of undefined variables
* Bugfix: Deprecated call of load_plugin_textdomain

= 1.0.0 - (05.06.2011) =
* New: Using the colors red, blue, green and orange for text highlighting
* Changed: Page menu
* Changed: Compatibility to WordPress 3.2
* Killed: Language files for en_US (because it's already the basic plugin langauge)

= 0.9.7 - (14.03.2011) =
* Bugfix: Use the Wordpress gmt offset (= WordPress timezone settings) for adding messages. Before the plugin used the MySQL server time

= 0.9.6 - (15.01.2011) =
* New: You can select which user group can publish a message as a WordPress blog post 

= 0.9.5 - (21.11.2010) =
* New: Adding files to messages via WordPress Media Library
* Bugfix: Some small GUI improvements
* Bugfix: Better URL replacement

= 0.9.4 - (27.09.2010) =
* New: Writing new messages directly from the dashboard
* New: Displaying human time difference (i.e. 8 hours ago) instead of the date
* Bugfix: The plugin displays a false number of replies

= 0.9.3 = 
* Changed: german translation

= 0.9.2 =
* Changed: Display today instead of the current date
* Bugfix: Tags where sent to WordPress if the user post it as WordPress post
* Bugfix: Fixed style for the search button 

= 0.9.1 =
* New: Option to post a message as WordPress blog article

= 0.9.0 =
* Changed: Some style improvements
* Bugfix: Fixed some problems with the e-mail notification
* Bugfix: Fixed an UTF-8 problem

= 0.6.4 =
* New: Cut URLs which are longer than 50 chars
* Bugfix: Fixed a possible division across zero in the tag cloud

= 0.6.3 =
* New: Option for changing number of tags
* New: Option for changing number of messages per page
* New: Option for auto notifications
* Bugfix: Fixed some bugs in the e-mail notification

= 0.6.2 = 
* Changed: Structure of the file directory
* Bugfix: Better URL replacing

 0.6.1 =
* New: Dashboard widget

= 0.5.3 =
* Bugfix: Fixed a problem with `<br />` tags, when editing messages
* Bugfix: Fixed some security vulnerabilities (SQL injections)
* Bugfix: Prevent sending of blank messages

= 0.5.2 =
* New: bbcodes for bold, italic, strikethrough and underline
* Bugfix: Fixed two security vulnerabilities (XSS)


= 0.5.1 =
* Changed: The plugin displays a warning if there are no tags in the database --> prevent a SQL error
* Bugfix: Fixed an installer bug

= 0.5.0 =
* First public release