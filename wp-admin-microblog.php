<?php
/*
Plugin Name: WP Admin Microblog
Plugin URI: http://mtrv.wordpress.com/microblog/
Description: Adds a microblog in your WordPress backend.
Version: 3.0
Author: Michael Winkler
Author URI: http://mtrv.wordpress.com/
Min WP Version: 3.8
Max WP Version: 4.3
*/

/*
   LICENCE
 
    Copyright 2010-2015  Michael Winkler

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	
	
   You find the license text under:
   http://www.opensource.org/licenses/gpl-2.0.php

   LICENCE Information of included parts
   - document-new-6.png (Oxygen Icons 4.3.1 by http://www.oxygen-icons.org/) - Licence: LPGL
*/

// Define databases
global $wpdb;

if ( !defined('WPAM_ADMIN_BLOG_POSTS') ) {
    define('WPAM_ADMIN_BLOG_POSTS', $wpdb->prefix . 'admin_blog_posts'); }
    
if ( !defined('WPAM_ADMIN_BLOG_TAGS') ) {
    define('WPAM_ADMIN_BLOG_TAGS', $wpdb->prefix . 'admin_blog_tags'); }
    
if ( !defined('WPAM_ADMIN_BLOG_RELATIONS') ) {
    define('WPAM_ADMIN_BLOG_RELATIONS', $wpdb->prefix . 'admin_blog_relations'); }
    
    if ( !defined('WPAM_ADMIN_BLOG_LIKES') ) {
    define('WPAM_ADMIN_BLOG_LIKES', $wpdb->prefix . 'admin_blog_likes'); }
    
if ( !defined('WPAM_ADMIN_BLOG_META') ) {
    define('WPAM_ADMIN_BLOG_META', $wpdb->prefix . 'admin_blog_meta'); }

/*
 *  Define overwritable system defaults
 */

// Number of tags
if ( !defined('WPAM_DEFAULT_TAGS') ) {
    define('WPAM_DEFAULT_TAGS', 50); }
    
// Number of messages
if ( !defined('WPAM_DEFAULT_NUMBER_MESSAGES') ) {
    define('WPAM_DEFAULT_NUMBER_MESSAGES', 10); }
    
/* 
 * Default sort order for messages
 * Possible Values:
 * - date
 * - date_last_comment
 */
if ( !defined('WPAM_DEFAULT_SORT_ORDER') ) {
    define('WPAM_DEFAULT_SORT_ORDER', 'date'); }
    
/*
 * Date format for messages
 * Possible Values
 * - time_difference
 * - date
 */
if ( !defined('WPAM_DEFAULT_DATE_FORMAT') ) {
    define('WPAM_DEFAULT_DATE_FORMAT', 'time_difference'); }    

// load microblog name
$wpam_blog_name = get_option('wp_admin_blog_name');
if ( $wpam_blog_name == false ) {
     $wpam_blog_name = 'Microblog';
}

// includes
require_once('core/class-ajax.php');
require_once('core/class-tables.php');
require_once('core/class-templates.php');
require_once('core/database.php');
require_once('core/general.php');
require_once('core/screen.php');
require_once('core/settings.php');
require_once('core/messages.php');
require_once('core/update.php');
require_once('core/widget.php');

// Define menu
function wpam_menu() {
   global $wpam_blog_name;
   global $wpam_admin_page;
   $wpam_admin_page = add_menu_page(__('Blog','wp_admin_blog'), $wpam_blog_name,'use_wp_admin_microblog', __FILE__, 'wpam_page', plugins_url() . '/wp-admin-microblog/images/logo.png');
   add_action("load-$wpam_admin_page", 'wpam_add_help_tab');
   add_action("load-$wpam_admin_page", 'wpam_screen_options');
   add_submenu_page('wp-admin-microblog/wp-admin-microblog.php', __('Settings','wp_admin_blog'), __('Settings','wp_admin_blog'), 'administrator', 'wp-admin-microblog/settings.php', 'wpam_settings');
}

/** 
 * Returns the current wpam version
 * @return string
 * @since 2.3
*/
function wpam_get_version() {
    return '3.0';
}

/** 
 * Display media buttons
 * adapted from P2-Theme
*/
function wpam_media_buttons() {
   include_once ABSPATH . '/wp-admin/includes/media.php';
   ob_start();
   do_action( 'media_buttons' );
   return ob_get_clean();
}

/** 
 * WP Admin Microblog Page Menu (= teachPress Admin Page Menu)
 * @access public
 * @param $number_entries (Integer)	-> Number of all available entries
 * @param $entries_per_page (Integer)	-> Number of entries per page
 * @param $current_page (Integer)	-> current displayed page
 * @param $entry_limit (Integer) 	-> SQL entry limit
 * @param $page_link (String)		-> example: admin.php?page=wp-admin-microblog/wp-admin-microblog.php
 * @param $link_attributes (String)	-> example: search=$search&amp;tag=$tag
 * @param $type - top or bottom, default: top
*/
function wpam_page_menu ($number_entries, $entries_per_page, $current_page, $entry_limit, $page_link = '', $link_attributes = '', $type = 'top') {
   // if number of entries > number of entries per page
   if ($number_entries > $entries_per_page) {
      $num_pages = floor (($number_entries / $entries_per_page));
      $mod = $number_entries % $entries_per_page;
      if ($mod != 0) {
         $num_pages = $num_pages + 1;
      }

      // first page / previous page
      if ($entry_limit != 0) {
         $back_links = '<a href="' . $page_link . '&amp;limit=1&amp;' . $link_attributes . '" title="' . __('first page','wp_admin_blog') . '" class="page-numbers">&laquo;</a> <a href="' . $page_link . '&amp;limit=' . ($current_page - 1) . '&amp;' . $link_attributes . '" title="' . __('previous page','wp_admin_blog') . '" class="page-numbers">&lsaquo;</a> ';
      }
      else {
         $back_links = '<a class="first-page disabled">&laquo;</a> <a class="prev-page disabled">&lsaquo;</a> ';
      }
      $page_input = ' <input name="limit" type="text" size="2" value="' .  $current_page . '" style="text-align:center;" /> ' . __('of','wp_admin_blog') . ' ' . $num_pages . ' ';

      // next page/ last page
      if ( ( $entry_limit + $entries_per_page ) <= ($number_entries)) { 
         $next_links = '<a href="' . $page_link . '&amp;limit=' . ($current_page + 1) . '&amp;' . $link_attributes . '" title="' . __('next page','wp_admin_blog') . '" class="page-numbers">&rsaquo;</a> <a href="' . $page_link . '&amp;limit=' . $num_pages . '&amp;' . $link_attributes . '" title="' . __('last page','wp_admin_blog') . '" class="page-numbers">&raquo;</a> ';
      }
      else {
         $next_links = '<a class="next-page disabled">&rsaquo;</a> <a class="last-page disabled">&raquo;</a> ';
      }

      // for displaying number of entries
      if ($entry_limit + $entries_per_page > $number_entries) {
         $anz2 = $number_entries;
      }
      else {
         $anz2 = $entry_limit + $entries_per_page;
      }

      // return
      if ($type == 'top') {
         return '<div class="tablenav-pages"><span class="displaying-num">' . ($entry_limit + 1) . ' - ' . $anz2 . ' ' . __('of','wp_admin_blog') . ' ' . $number_entries . ' ' . __('Entries','wp_admin_blog') . '</span> ' . $back_links . '' . $page_input . '' . $next_links . '</div>';
      }
      else {
         return '<div class="tablenav"><div class="tablenav-pages"><span class="displaying-num">' . ($entry_limit + 1) . ' - ' . $anz2 . ' ' . __('of','wp_admin_blog') . ' ' . $number_entries . ' ' . __('Entries','wp_admin_blog') . '</span> ' . $back_links . ' ' . $current_page . ' ' . __('of','wp_admin_blog') . ' ' . $num_pages . ' ' . $next_links . '</div></div>';
      }	
   }
}

/**
 * Get WPAM options
 * @param string $name
 * @param string $category
 * @return boolean 
 */
function wpam_get_options($name = '', $category = '') {
    global $wpdb;
    if ( $category != '' ) {
        $row = $wpdb->get_results("SELECT * FROM " . WPAM_ADMIN_BLOG_META . " WHERE `category` = '$category'", ARRAY_A);
    }
    if ( $name != '' ) {
        $row = $wpdb->get_var("SELECT `value` FROM " . WPAM_ADMIN_BLOG_META . " WHERE `variable` = '$name'");
    }
    
    if ( $row == '' ) {
        return false;
    }
    return $row;

}

/**
 * Add dashboard widget
 */
function wpam_add_widgets() {
    if ( current_user_can( 'use_wp_admin_microblog' ) ) {
        // load microblog name
        $name = wpam_get_options('blog_name_widget', '');
        if ( $name == false || $name == '' ) {
            $name = 'Microblog';
        }
        $str = "'";
        $title = '<a onclick="wpam_showhide(' . $str . 'wpam_new_message' . $str . ')" style="cursor:pointer; text-decoration:none; font-size:12px; font-weight:bold; color:#464646;" title="' . __('New Message','wp_admin_blog') . '">' . $name . ' <img src="' .  plugins_url() . '/wp-admin-microblog/images/document-new-6.png' . '" heigth="12" width="12" /></a>';
        wp_add_dashboard_widget('wpam_dashboard_widget', '' . $title . '', 'wpam_widget_function');
    }
}

/*
 * Add scripts and stylesheets
*/ 
function wpam_header() {
    $page = '';
    // Define $page
    if ( isset($_GET['page']) ) {
        $page = $_GET['page'];
    }
    // load scripts only, when it's wp_admin_blog page
    if ( strpos($page, 'wp-admin-microblog') !== FALSE || strpos($_SERVER['PHP_SELF'], 'wp-admin/index.php') !== FALSE ) {
        wp_register_script('wp_admin_blog', plugins_url() . '/wp-admin-microblog/js/wp-admin-microblog.js');
        wp_register_style('wp_admin_blog_css', plugins_url() . '/wp-admin-microblog/css/wp-admin-microblog.css');
        wp_enqueue_style('wp_admin_blog_css');
        wp_enqueue_style('teachpress-jquery-ui-dialog.css', includes_url() . '/css/jquery-ui-dialog.min.css');
        wp_enqueue_script('wp_admin_blog');
        wp_enqueue_script('media-upload');
        add_thickbox();
        wp_enqueue_script(array('jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-resizable', 'jquery-ui-autocomplete', 'jquery-ui-sortable', 'jquery-ui-dialog'));
    }
    // load the hack for the normal WP Admin Microblog page
    if ( strpos($page, 'wp-admin-microblog') !== FALSE ) {
        wp_register_script('wpam_upload_hack', plugins_url() . '/wp-admin-microblog/js/media-upload-hack.js');
        wp_enqueue_script('wpam_upload_hack');
    }
    // load the hack for the dashboard, when the user say yes
    $test = get_option('wp_admin_blog_media_upload');
    if (strpos($_SERVER['REQUEST_URI'], 'wp-admin/index.php') !== FALSE && $test == 'true') {
        wp_register_script('wpam_upload_hack', plugins_url() . '/wp-admin-microblog/media-upload-hack.js');
        wp_enqueue_script('wpam_upload_hack');
    }
}

/**
 * WPAM plugin activation
 * @param boolean $network_wide
 * @since 2.2.0
 */
function wpam_activation ( $network_wide ) {
    global $wpdb;
    // it's a network activation
    if ( $network_wide ) {
        $old_blog = $wpdb->blogid;
        // Get all blog ids
        $blogids = $wpdb->get_col($wpdb->prepare("SELECT `blog_id` FROM $wpdb->blogs"));
        foreach ($blogids as $blog_id) {
            switch_to_blog($blog_id);
            wpam_install();
        }
        switch_to_blog($old_blog);
        return;
    } 
    // it's a normal activation
    else {
        wpam_install();
    }
}

/**
 * Installer
 * @since 1.0
 */
function wpam_install () {
    wpam_tables::create();
}

/**
 * Uninstalling
 */
function wpam_uninstall() {
    wpam_tables::remove();
}

// load language support
function wpam_language_support() {
    load_plugin_textdomain('wp_admin_blog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Register WordPress hooks
register_activation_hook( __FILE__, 'wpam_activation');
add_action('init', 'wpam_language_support');
add_action('admin_init','wpam_header');
add_action('admin_menu','wpam_menu');
add_action('wp_dashboard_setup','wpam_add_widgets');