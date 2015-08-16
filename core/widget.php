<?php

/**
 * Dashboard Widget
 */
function wpam_widget_function() {
    global $current_user;
    global $wpdb;
    get_currentuserinfo();
    $user = $current_user->ID;
    $text = isset( $_POST['wp_admin_blog_edit_text'] ) ? htmlspecialchars($_POST['wp_admin_blog_edit_text']) : '';
    $sticky_for_dash = wpam_get_options('sticky_for_dash');
    $media_upload = wpam_get_options('media_upload');
    // actions
    if ( isset($_POST['wpam_nm_submit']) ) {
        // form fields
        $new = array(
            'text' => htmlspecialchars($_POST['wpam_nm_text']),
            'headline' => htmlspecialchars($_POST['wpam_nm_headline'])
        );
        $is_sticky = isset ( $_POST['wpam_is_sticky'] ) ? 1 : 0;
        wpam_message::add_message($new['text'], $user, 0, $is_sticky);
        // add as a blog post if it is wished
        if ( isset( $_POST['wpam_as_wp_post'] ) ) { 
            if ($_POST['wpam_as_wp_post'] == 'true') {
               wpam_message::add_as_wp_post($new['text'], $new['headline'], $user);
            }
        }
    }
    if (isset($_POST['wp_admin_blog_edit_message_submit'])) {
       $edit_message_ID = intval($_POST['wp_admin_blog_message_ID']);
       wpam_message::update_message($edit_message_ID, $text);
    }
    if (isset($_POST['wp_admin_blog_reply_message_submit'])) {
       $parent_ID = intval($_POST['wp_admin_blog_parent_ID']);
       wpam_message::add_message($text, $user, $parent_ID, 0);
    }
    if (isset($_GET['wp_admin_blog_delete'])) {
       $delete = intval($_GET['wp_admin_blog_delete']);
       $l = intval($_GET['wp_admin_blog_level']);
       wpam_message::del_message($delete, $l);
    }
    if (isset($_GET['wp_admin_blog_remove'])) {
       $remove = intval($_GET['wp_admin_blog_remove']);
       wpam_message::update_sticky($remove, 0);
    }
    if (isset($_GET['wp_admin_blog_add'])) {
       $add = intval($_GET['wp_admin_blog_add']);
       wpam_message::update_sticky($add, 1);
    }
    
    // print js
    $args = array ('auto_reload_interval' => 60000);
    wpam_screen::print_javascript_paramenters($args);

    echo '<form method="post" name="wp_admin_blog_dashboard_widget" id="wp_admin_blog_dashboard_widget" action="index.php">';
    echo '<div id="wpam_new_message" style="display:none;">';

    if ( $media_upload == 'true' ) {
       echo '<div class="wpam_media_buttons" style="text-align:right;">' .  wpam_media_buttons() . '</div>';
    }
    echo '<textarea name="wpam_nm_text" id="wpam_nm_text" cols="70" rows="4" style="width:100%;"></textarea>';
    echo '<table style="width:100%; border-bottom:1px solid rgb(223 ,223,223); padding:10px;">';
    echo '<tr>';
    
    // Add message options
    if ( current_user_can( 'use_wp_admin_microblog_bp' ) || current_user_can( 'use_wp_admin_microblog_sticky' ) ) {
        echo '<td style="vertical-align:top; padding-top:5px;"><a onclick="javascript:wpam_showhide(' . "'" . 'wpam_message_options' . "'" . ')" style="cursor:pointer; font-weight:bold;">+ ' .  __('Options', 'wp_admin_blog') . '</a>';
        echo '<table style="width:100%; display: none; float:left; padding:5px;" id="wpam_message_options">';
        if ( current_user_can( 'use_wp_admin_microblog_sticky' ) ) { 
             echo '<tr><td style="border-bottom-width:0px;"><input name="wpam_is_sticky" id="wpam_is_sticky" type="checkbox"/> <label for="wpam_is_sticky">' . __('Sticky this message','wp_admin_blog') . '</label></td></tr>';
        }
        if ( current_user_can( 'use_wp_admin_microblog_bp' ) ) { 
             echo '<tr><td style="border-bottom-width:0px;"><input name="wpam_as_wp_post" id="wpam_as_wp_post" type="checkbox" value="true" onclick="javascript:wpam_showhide(' . "'" . 'wpam_as_wp_post_title' . "'" .')" /> <label for="wpam_as_wp_post">' . __('as WordPress blog post', 'wp_admin_blog') . '</label> <span style="display:none;" id="wpam_as_wp_post_title">&rarr; <label for="wpam_nm_headline">' . __('Title', 'wp_admin_blog') . ' </label><input name="wpam_nm_headline" id="wpam_nm_headline" type="text" style="width:95%;" /></span></td></tr>';
        }
        echo '</table>';
    }
    // END
    
    echo '<td style="text-align:right; vertical-align:top;"><input type="submit" name="wpam_nm_submit" id="wpam_nm_submit" class="button-primary" value="' . __('Send', 'wp_admin_blog') . '" /></td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';

    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="wpam-dashboard">';
    if ( $sticky_for_dash == 'true' ) {
        $sql = "SELECT * FROM " . WPAM_ADMIN_BLOG_POSTS . " ORDER BY is_sticky DESC, post_ID DESC LIMIT 0, 5";   
    }
    else {
        $sql = "SELECT * FROM " . WPAM_ADMIN_BLOG_POSTS . " ORDER BY post_ID DESC LIMIT 0, 5";
    }
    $rows = $wpdb->get_results($sql);
    $sql = "SELECT COUNT(post_parent) AS gesamt, post_parent FROM " . WPAM_ADMIN_BLOG_POSTS . " GROUP BY post_parent";
    $replies = $wpdb->get_results($sql);
    
    // Options for messages
    $options = array (
        'auto_reply' => false,
        'user' => $user,
        'date_format' => WPAM_DEFAULT_DATE_FORMAT,
        'wp_date_format' => get_option('date_format'),
        'wp_time_format' => get_option('time_format'),
        'sticky_for_dash' => ($sticky_for_dash == 'true') ? true : false,
        'is_widget' => true
    );
    
    foreach ($rows as $post) {
        $user_info = get_userdata($post->user);
        $count_rep = 0;
        $level = 2;
        // Count Number of Replies
        foreach ($replies as $rep) {
           if ($rep->post_parent == $post->post_ID) {
              $count_rep = $rep->gesamt + 1;
           }

           if ($rep->post_parent == $post->post_parent && $post->post_parent != 0) {
              $count_rep = $rep->gesamt + 1;
           }
        }
        
        // sticky post options
        // change background color for sticky posts
        $class = 'wpam-normal';
        if ( $post->is_sticky == 1 && $sticky_for_dash == 'true' ) {
            $class = 'wpam-sticky';
        }
        
        
        // Handles post parent
        if ($post->post_parent == 0) {
            $post->post_parent = $post->post_ID;
            $level = 1;
        }
        
        echo '<tr class="' . $class . '">';
        echo '<td style="border-bottom:1px solid rgb(223,223,223); padding: 12px 0 0 5px;" valign="top" width="40"><div class="wpam_user_image" title="' . $user_info->display_name . ' (' . $user_info->user_login . ')">' . get_avatar($user_info->ID, 30) . '</div></td>';
        echo '<td style="border-bottom:1px solid rgb(223,223,223); padding: 7px 5px 7px 0;">';
        
        echo wpam_templates::message($post, $args, $user_info, $options, $level, $count_rep);
        
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</form>';
    echo '<script type="text/javascript" charset="utf-8" id="wpam_edit_message" src="'. plugins_url() . '/wp-admin-microblog/js/messages.js' . '"></script>';
}

