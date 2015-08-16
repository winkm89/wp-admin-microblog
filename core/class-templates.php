<?php

/**
 * Description of templates
 *
 * @author Micha
 */
class wpam_templates {
    /**
     * Returns a single message with the full template
     * @param object $message       The message array
     * @param array $tags           The tag array
     * @param array $user_info      The user_info array
     * @param array $options        The options array
     * @param int $level            The tree level (default is 1, for comments use 2)
     * @param int $count_rep        The number of replies in this discussion (default is 0)
     * @return string
     * @since 3.0
     */
    public static function message ($message, $tags, $user_info, $options, $level = 1, $count_rep = 0) {
        $message_text = wpam_message::prepare( $message->text, $tags );
       
        // print messages
        $r =  '<div id="wp_admin_blog_message_' . $message->post_ID . '" class="wpam-blog-message">
                <div class="wpam-message-info-row">
                     <div class="wpam-message-info-details">
                        ' . self::date($message->date, $options) . ' | ' . __('by','wp_admin_blog') . ' ' . $user_info->display_name . self::replies_menu ($message, $count_rep) . '
                     </div>
                     <div class="wpam-message-info-likes">
                        ' . self::like_menu($message) . '
                     </div>
                     <div class="wpam-message-info-clear"></div>
                </div>
                <div class="wpam-message-content">' . $message_text . '</div>
                <div class="wpam-row-actions">' . self::action_menu($message, $user_info, $options, $level) . '</div>
            </div>';
        return $r;
    }
    
    /**
     * The like menu
     * @param object $message
     * @return string
     * @since 3.0
     */
    public static function like_menu ($message) {
        $hidden = 'hidden';
        $number = wpam_likes::count_likes($message->post_ID);
        if ( $number > 0 ) {
            $hidden = '';
        }
        $like_menu = '<a class="wpam-like-star dashicons-before dashicons-star-filled ' . $hidden . '" id="wpam_like_' . $message->post_ID . '" message_id="' . $message->post_ID . '" href="' . plugins_url() . '/wp-admin-microblog/ajax.php?like_id=' . $message->post_ID . '" title="' . __('Show Likes','wp_admin_blog') . '">' . $number . '</a>';
        return $like_menu;
    }
    
    /**
     * The replies menu (used for the widget)
     * @param object $message
     * @param int $count_rep
     * @return string
     * @since 3.0
     */
    public static function replies_menu ($message, $count_rep) {
        if ($count_rep != 0) {
            return ' | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;rpl=' . $message->post_parent . '" title="' . _n( 'One Reply', $count_rep . ' Replies', $count_rep, 'wp_admin_blog' ) . '">' . $count_rep . ' ' . __( 'Replies', 'wp_admin_blog' ) . '</a>';
        }
        return '';
    }

    /**
     * The action menu
     * @param object $message       The message array
     * @param array $user_info      The user_info array
     * @param array $options        The options array
     * @param int $level            The tree level (defalt is 1, for comments use 2)
     * @since 3.0
     */
    public static function action_menu ($message, $user_info, $options, $level = 1) {
        $edit_button = '';
        global $current_user;
        
        // Define Links
        if ( $options['is_widget'] === true ) {
            $link_sticky = 'index.php?wp_admin_blog_add=' . $message->post_ID;
            $link_unsticky = 'index.php?wp_admin_blog_remove=' . $message->post_ID;
            $link_delete = 'index.php?wp_admin_blog_delete=' . $message->post_ID . '&amp;wp_admin_blog_level=' . $level;
        }
        else {
            $link_sticky = 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php&add=' . $message->post_ID;
            $link_unsticky = 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php&remove=' . $message->post_ID;
            $link_delete = 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;delete=' . $message->post_ID . '&amp;level=' . $level;
        }
        
        // Handles post parent
        if ($message->post_parent == '0') {
            $message->post_parent = $message->post_ID;
        }
        
        // sticky menu options
        $sticky_option = '';
        if ( current_user_can( 'use_wp_admin_microblog_sticky' ) && $options['sticky_for_dash'] === true && $level == 1 ) {
            if ( $message->is_sticky == 0 ) {
                $sticky_option = '<a class="wpam-sticky-button dashicons-before dashicons-admin-post" href="' . $link_sticky . '" title="' . __('Sticky this message','wp_admin_blog') . '" >' . __('Sticky','wp_admin_blog') . '</a> | ';
            }
            else {
                $sticky_option = '<a class="wpam-sticky-button dashicons-before dashicons-admin-post" href="' . $link_unsticky . '" title="' . __('Unsticky this message','wp_admin_blog') . '">' . __('Unsticky','wp_admin_blog') . '</a> | ';
            }
        }

        // Show message edit options if the user is the author of the message or the blog admin
        if ( $message->user == $options['user'] || current_user_can('manage_options') ) {
            
            // Edit button
            $edit_button .= '<a class="wpam_message_edit dashicons-before dashicons-welcome-write-blog" id="wpam_message_edit_' . $message->post_ID . '" message_id="' . $message->post_ID . '" style="cursor:pointer;" title="' . __('Edit this message','wp_admin_blog') . '">' . __('Edit','wp_admin_blog') . '</a> | ';
            
            // Sticky button
            $edit_button .= $sticky_option;
            
            // Delete button
            $edit_button .= '<a class="wpam-delete-button dashicons-before dashicons-dismiss" href="' . $link_delete . '" title="' . __('Click to delete this message','wp_admin_blog') . '" style="color:#FF0000">' . __('Delete','wp_admin_blog') . '</a> | ';
        }
        
        // Reply button
        $edit_button .= '<a class="wpam-reply-button dashicons-before dashicons-format-chat" onclick="javascript:wpam_replyMessage(' . $message->post_ID . ',' . $message->post_parent . ',' . "'" . $options['auto_reply'] . "'" . ',' . "'" . $user_info->user_login . "'" . ')" style="cursor:pointer; color:#009900;" title="' . __('Write a reply','wp_admin_blog') . '"> ' . __('Reply','wp_admin_blog') . '</a>';
        
        // Like button
        $check = wpam_likes::check_like($message->post_ID, $current_user->ID);
        $title = (  $check === false ) ? __('Like','wp_admin_blog') : __('Unlike','wp_admin_blog');
        $class = (  $check === false ) ? 'dashicons-star-filled' : 'dashicons-star-empty';
        $edit_button .= ' | <a class="dashicons-before ' . $class . ' wpam_message_like" id="wpam_like_button_' . $message->post_ID . '" message_id="' . $message->post_ID . '" style="cursor:pointer;" title="' . $title . '">' . $title . '</a>';
        
        return $edit_button;
    }
    
    /**
     * Prepares the displayed date for a message
     * @param $post_time        The post time (mysql)
     * @param $options          The options array
     * @since 3.0
     */
    public static function date ($post_time, $options) {
        $time = wpam_core::datesplit($post_time);
        $timestamp = mktime($time[0][3], $time[0][4], $time[0][5], $time[0][1], $time[0][2], $time[0][0] );
        
        // get human time difference
        $time_difference = human_time_diff( $timestamp, current_time('timestamp') ) . ' ' . __( 'ago', 'wp_admin_blog' );
        
        // get time
        $message_time = date($options['wp_time_format'], $timestamp);
        
        // get date
        $message_date = date($options['wp_date_format'], $timestamp);

        // handle date formats
        if ( date($options['wp_date_format']) == $message_date ) {
            $message_date = __('Today','wp_admin_blog');
        }
        
        // end result
        if ($options['date_format'] === 'date') {
            return '<span title="' . $time_difference . '">' . $message_date . ' | ' . $message_time . '</span>';
        }
        
        return '<span title="' . $message_date . ' | ' . $message_time . '">' . $time_difference . '</span>';
    }
}
