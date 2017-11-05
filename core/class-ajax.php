<?php
/**
 * This class contains all ajax based functions for the plugin
 * @since 3.0
 */
class wpam_ajax {
    /**
     * Checks if there are new messages available and prints an info abaout that
     * @param $p_id     The current post id
     * @since 3.0
     */
    public static function new_message_check($p_id) {
        global $wpdb;
        $p_id = intval($p_id);
        $test = intval( $wpdb->get_var("SELECT COUNT(*) FROM " . WPAM_ADMIN_BLOG_POSTS . " WHERE `post_ID` > '$p_id'") );
        if ( $test !== 0 ) {
            echo __('New messages available','wp-admin-microblog');
        }
    }
    
    /**
     * Prints the text content of the message for edit via ajax
     * @param int $message_id   The ID of the message
     * @since 3.0
     */
    public static function get_message_text_for_edit($message_id) {
        $message = wpam_message::get_message($message_id);
        echo stripslashes($message['text']);
    }
    
    /**
     * Adds a like or deletes it
     * @global object $current_user
     * @param int $message_id
     * @since 3.0
     */
    public static function add_like ($message_id) {
        $current_user = wp_get_current_user();
        
        $check= wpam_likes::check_like($message_id, $current_user->ID);
        if ( $check === false ) {
            wpam_likes::add_like($message_id, $current_user->ID);
            echo 'added';
        }
        else {
            wpam_likes::delete_like($message_id, $current_user->ID);
            echo 'deleted';
        }
    }
    
    /**
     * Show like details
     * @param int $message_id
     * @since 3.0
     */
    public static function show_likes ($message_id) {
        $likes = wpam_likes::get_likes($message_id);
        echo '<!doctype html>';
        echo '<html>';
        echo '<head>';
        echo '<meta charset="utf-8">';
        echo '<title>List of likes</title>';
        echo '</head>';
        echo '<body>';
        echo '<div id="content">';
        foreach ($likes as $like) {
            $user = get_userdata( $like['user_ID'] );
            echo '<div class="like_entry">';
            echo '<div class="wpam_user_image" style="float:left;">' . get_avatar($user->ID, 50) . '</div>';
            echo '<div class="wpam_user_name" style="float:left;">' . $user->display_name . ' (' . $user->user_login . ')</div>';
            echo '<div class="clear"></div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</body>';
        echo '</html>';
    }
}

