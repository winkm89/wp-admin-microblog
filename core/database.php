<?php
/**
 * Database access class for likes
 * @package wp-admin-microblog
 * @subpackage database
 * @since 3.0
 */
class wpam_likes {
    /**
     * Gets the likes of a specific messages
     * @param int $message_id       The ID of the message
     * @param string $output_type   Default ARRAY_A
     * @return array|object
     * @since 3.0
     */
    public static function get_likes($message_id, $output_type = ARRAY_A) {
        global $wpdb;
        $sql = "SELECT * FROM " . WPAM_ADMIN_BLOG_LIKES . " WHERE `post_ID` = '$message_id'";
        return $wpdb->get_results($sql, $output_type);
    }
    
    /**
     * Count the likes for a message
     * @param int $message_id
     * @return string
     * @since 3.0
     */
    public static function count_likes($message_id) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM " . WPAM_ADMIN_BLOG_LIKES . " WHERE `post_ID` = '$message_id'";
        return $wpdb->get_var($sql);
    }
    
    /**
     * Adds a like
     * @param int $message_id   The ID of the message
     * @param int $user_id      The ID of the user
     * @return int
     * @since 3.0
     */
    public static function add_like($message_id, $user_id) {
        global $wpdb;
        $message_id = intval($message_id);
        $user_id = intval($user_id);
        
        // Test if the like already exists
        $test = $wpdb->query("SELECT * FROM " . WPAM_ADMIN_BLOG_LIKES . " WHERE `post_ID` = '$message_id' AND `user_ID` = '$user_id'");
        if ( $test > 0 ) {
            return false;
        }
        
        $wpdb->insert(WPAM_ADMIN_BLOG_LIKES, array('post_ID' => $message_id, 'user_ID' => $user_id), array('%d', '%d'));
        return $wpdb->insert_id;
    }
    
    /**
     * Deletes a like
     * @param int $message_id
     * @param int $user_id
     * @since 3.0
     */
    public static function delete_like($message_id, $user_id) {
        global $wpdb;
        $message_id = intval($message_id);
        $user_id = intval($user_id);
        $wpdb->query("DELETE FROM " . WPAM_ADMIN_BLOG_LIKES . " WHERE `post_ID` = '$message_id' AND `user_ID` = '$user_id'");
    }
    
    /**
     * Checks if a like exists (true) or not (false). 
     * @param int $message_id
     * @param int $user_id
     * @return boolean
     * @since 3.0
     */
    public static function check_like ($message_id, $user_id) {
        global $wpdb;
        $message_id = intval($message_id);
        $user_id = intval($user_id);
        
        $test = $wpdb->query("SELECT * FROM " . WPAM_ADMIN_BLOG_LIKES . " WHERE `post_ID` = '$message_id' AND `user_ID` = '$user_id'");
        if ( $test > 0 ) {
            return true;
        }
        return false;
    }
    
}

/**
 * Database access class for tags
 * @package wp-admin-microblog
 * @subpackage database
 * @since 3.0
 */
class wpam_tags {
    
    /**
     * Returns the tags 
     * @global type $wpdb
     * @param int $message_id       Optional
     * @param string $output_type   Optional
     * @return array|object
     * @since 3.0
     */
    public static function get_tags($message_id = 0, $output_type = ARRAY_A) {
        global $wpdb;
        $message_id = intval($message_id);
        if ( $message_id === 0 ) {
            return $wpdb->get_results("SELECT `tag_id`, `name` FROM " . WPAM_ADMIN_BLOG_TAGS, $output_type);
        }
        return $wpdb->get_results( "SELECT tags.tag_ID, tags.name FROM " . WPAM_ADMIN_BLOG_RELATIONS . " rel INNER JOIN " . WPAM_ADMIN_BLOG_TAGS . " tags ON rel.tag_ID = tags.tag_ID WHERE rel.post_ID = '$message_id'", $output_type);
    }
    
    /**
     * Add tags
     * @param int $message_id
     * @param string $content
     * @since 3.0
     */
    public static function add_tags ($message_id, $content) {
        global $wpdb;
        // tags
        if ( preg_match_all("/[#]+[A-Za-z0-9-_]+/", $content, $match) ) {
            for ($x = 0; $x < count($match[0]); $x++) {
                $match[0][$x] = str_replace('#', '', $match[0][$x]);
                $match[0][$x] = trim($match[0][$x]);
                $sql = "SELECT `tag_ID` FROM " . WPAM_ADMIN_BLOG_TAGS . " WHERE `name` = '" . $match[0][$x]  ."'";
                $check = $wpdb->query($sql);
                
                // if not, then insert tag
                if ($check == 0){
                    $wpdb->insert(WPAM_ADMIN_BLOG_TAGS, array('name' => $match[0][$x]), array('%s') );
                    $new_tag_id = $wpdb->insert_id;
                }
                else {
                    $new_tag_id = $wpdb->get_var($sql);
                }
                
                // check if the relation already exist
                $test = $wpdb->query("SELECT `post_ID` FROM " . WPAM_ADMIN_BLOG_RELATIONS ." WHERE `post_ID` = '$message_id' AND `tag_ID` = '$new_tag_id'");
                if ($test !== 0) {
                    continue;
                }
                
                $sql = "INSERT INTO " . WPAM_ADMIN_BLOG_RELATIONS ." (`post_ID`, `tag_ID`) VALUES ('$message_id', '$new_tag_id')";
                $wpdb->query($sql);
            }
        }
    }
    
    /**
     * Delete tags if where are deleted in the message. Used for update_message
     * @param int $message_ID
     * @param string $content 
     * @since 3.0
    */
    public static function del_tags($message_ID, $content) {
        global $wpdb;
        $row = $wpdb->get_results("SELECT DISTINCT r.rel_ID, t.name FROM `" . WPAM_ADMIN_BLOG_POSTS ."` p
                            LEFT JOIN `" . WPAM_ADMIN_BLOG_RELATIONS ."` r ON r.post_ID = p.post_ID
                            LEFT JOIN `" . WPAM_ADMIN_BLOG_TAGS . "` t ON t.tag_ID = r.tag_ID
                            WHERE r.post_ID = '$message_ID'", ARRAY_A);
        foreach ($row as $row) {
            $haystack = '#' . $row['name'];
            $search = strpos($haystack, $content);
            if ( $search === false ) {
                $wpdb->query("DELETE FROM " . WPAM_ADMIN_BLOG_RELATIONS ." WHERE `rel_ID` = '" . $row['rel_ID'] . "'");
            }
        }
    }
    
}

