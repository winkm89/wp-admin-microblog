<?php

/**
 * The wpam parsing class for messages
 */
class wpam_message {
    
    /** 
    * Add new message
    * @param string $content    - text of a message
    * @param int $user          - WordPress user ID
    * @param int $parent        - ID of the parent message. Used if the new message is a reply (default: 0)
    * @param int $is_sticky     - 0(false) or 1(true)
    */
    static function add_message ($content, $user, $parent, $is_sticky) {
        global $wpdb;
        if ($content != '') {
            $content = nl2br($content);
            $post_time = current_time('mysql',0);
            
            // check message duplications
            $duplicate = $wpdb->get_var("SELECT `text` FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `text` = '$content' AND `post_parent` = '$parent' AND `user` = '$user'");
            if ( $duplicate != '' ) {
                if ( strlen($duplicate) == strlen($content) ) {
                    return false;
                }
            }
            
            // insert message
            $wpdb->insert( WPAM_ADMIN_BLOG_POSTS, array( 
                'post_parent' => $parent,
                'text' => $content,
                'date' => $post_time,
                'sort_date' => $post_time,
                'last_edit' => $post_time,
                'user' => $user,
                'is_sticky' => $is_sticky ), 
                array( '%d', '%s', '%s', '%s', '%s', '%d', '%d' ) );
            $new_message_id = $wpdb->insert_id;
            
            // update sort_date for parent
            if ( $parent != 0 ) {
                $wpdb->update(WPAM_ADMIN_BLOG_POSTS, array('sort_date' => $post_time ), array('post_ID' => $parent), array('%s'), array('%d') );
            }
            
            // add tags
            wpam_tags::add_tags($new_message_id, $content);
            
            // send notifications
            wpam_message::send_notifications($content, $user);
        }
    }
    
    /**
     * Gets the ID of the latest message
     * @return int
     * @since 3.0
     */
    public static function get_latest_message_id () {
        global $wpdb;
        return $wpdb->get_var("SELECT `post_ID` FROM " . WPAM_ADMIN_BLOG_POSTS . " ORDER BY `post_ID` DESC LIMIT 0,1");
    }


    /**
     * Returns a single message
     * @param int $message_id
     * @param string $output_type
     * @return object|array
     * @since 3.0
     */
    public static function get_message($message_id, $output_type = ARRAY_A) {
        global $wpdb;
        $message_id = intval($message_id);
        return $wpdb->get_row("SELECT * FROM " . WPAM_ADMIN_BLOG_POSTS . " WHERE `post_ID` = '$message_id'", $output_type);
    }
    
    /**
     * Returns messages
     * @param array $args
     * @param boolean $count
     * @return object|array
     * @since 3.0
     */
    public static function get_messages( $args = array(), $count = false ) {
        $defaults = array(
            'search' => '',
            'author' => '',
            'tag' => '',
            'sort_order' => 'date',
            'message_limit' => '',
            'number_messages' => '',
            'rpl' => '',
            'output_type' => OBJECT
        ); 
        $args = wp_parse_args( $args, $defaults );
        extract( $args, EXTR_SKIP );
        
        global $wpdb;
        $number_messages = intval($number_messages);
        $message_limit = intval($message_limit);
        
        // Define sort_order
        if ( $sort_order === 'date' ) {
            $order_by = '`date` DESC';
        }
        else {
            $order_by = '`sort_date` DESC, `date` DESC';
        }
        
        // build SQL requests
        if ( $search != '' || $author != '' || $tag != '' ) {
            $select = "SELECT DISTINCT p.post_ID, p.post_parent, p.text, p.date, p.sort_date, p.last_edit, p.user, p.is_sticky FROM " . WPAM_ADMIN_BLOG_POSTS ." p
                          LEFT JOIN " . WPAM_ADMIN_BLOG_RELATIONS ." r ON r.post_ID = p.post_ID
                          LEFT JOIN " . WPAM_ADMIN_BLOG_TAGS . " t ON t.tag_ID = r.tag_ID";
            // is author and search?
            if ($author != '' && $search != '') {
               $where = "WHERE p.user = '$author' AND p.text LIKE '%$search%'";
            }
            // is search
            elseif ($author == '' && $search != '') {
               $where = "WHERE p.text LIKE '%$search%'";
            }
            // is author
            elseif ($author != '' && $search == '') {
               $where = "WHERE p.post_parent = '0' AND p.user = '$author'";
            }
            else {
               $where = "";
            }
            // is tag?
            if ($tag != '') {
                if ($where != "") {
                   $where = $where . "AND t.tag_ID = $tag";
                }
                else {
                   $where = "WHERE t.tag_ID = '$tag'";
                }
            }	
            $sql = "$select $where ORDER BY p.post_ID DESC LIMIT $message_limit, $number_messages";
            $test_sql = "$select $where";				
        }
        // is replies?
        elseif( $rpl != '' ) {
            $sql = "SELECT * FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `post_ID` = '$rpl' ORDER BY `post_ID` DESC LIMIT $message_limit, $number_messages";
            $test_sql = "SELECT `post_ID` FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `post_ID` = '$rpl'";
        }
        
        // Normal SQL
        else {
            if ( $rpl == 0 ) {
                $sql = "SELECT * FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `post_parent` = '0' ORDER BY `is_sticky` DESC, $order_by LIMIT $message_limit, $number_messages";
                $test_sql = "SELECT `post_ID` FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `post_parent` = '0'";  
            }
            else {
                $sql = "SELECT * FROM " . WPAM_ADMIN_BLOG_POSTS ." ORDER BY `is_sticky` DESC, $order_by LIMIT $message_limit, $number_messages";
                $test_sql = "SELECT `post_ID` FROM " . WPAM_ADMIN_BLOG_POSTS ."";
            }
        }
        
        if ( $count === true ) {
            return $wpdb->query($test_sql);
        }
        
        return $wpdb->get_results($sql, $output_type);
    }

    /**
     * Send notifications
     * @param string $text
     * @param int $user
     */
    private static function send_notifications($text, $user) {
        global $wpdb;

        $user = get_userdata($user);
        $text = $text . ' ';
        $text = wpam_message::replace_bbcode($text, 'delete');
        $text = str_replace("<br />","",$text);
        $text = str_replace('(file://)', 'http://', $text);
        $text = __('Author:','wp-admin-microblog') . ' ' . $user->display_name . chr(13) . chr(10) . chr(13) . chr(10) . $text;
        $text = $text . chr(13) . chr(10) . '________________________' . chr(13) . chr(10) . __('Login under the following address to create a reply:','wp-admin-microblog') . ' ' . wp_login_url();

        $headers = 'From: ' . get_bloginfo() . ' <' . get_bloginfo('admin_email') . '>' . "\r\n\\";
        $subject = get_bloginfo() . ': ' .__('New message in wp admin micoblog','wp-admin-microblog');
        
        $sql = "SELECT DISTINCT user FROM " . WPAM_ADMIN_BLOG_POSTS ."";
        $users = $wpdb->get_results($sql);
        foreach ($users as $element) {
            $user_info = get_userdata($element->user);
            $the_user = "@" . $user_info->user_login . " ";
            $test = strpos($text, $the_user);
            if ( $test !== false ) {
                wp_mail( $user_info->user_email, $subject, $text, $headers );
            }	
        }
        // auto notifications
        $notifications = wpam_get_options('auto_notifications');
        if ( $notifications != false && $notifications != '' ) {
            $mails = explode(chr(13) . chr(10), $notifications);
            foreach ( $mails as $mail ) {
                $mail = trim($mail);
                if (is_email($mail) ) {
                    wp_mail( $mail, $subject, $text, $headers );
                }
            }
        }
    }
    
    /**
     * Delete message
     * @param int $message_ID
     * @param int $level
     * @version 2
     * @since 2.3
    */
    public static function del_message($message_ID, $level) {
        global $wpdb;
        $wpdb->query("SET AUTOCOMMIT=0");
        $wpdb->query("START TRANSACTION");
        if ( $level === 2 ) {
            wpam_message::reset_sort_date($message_ID);
        }
        $wpdb->query("DELETE FROM " . WPAM_ADMIN_BLOG_LIKES ." WHERE `post_ID` = '$message_ID'");
        $wpdb->query("DELETE FROM " . WPAM_ADMIN_BLOG_RELATIONS ." WHERE `post_ID` = '$message_ID'");
        $wpdb->query("DELETE FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `post_ID` = '$message_ID'");
        $wpdb->query("COMMIT");
    }
    
    /**
     * This function resets the sort_date of a parent message
     * @param type $message_ID
     * return boolean
     * @since 2.3.2
     */
    private static function reset_sort_date($message_ID) {
        global $wpdb;
        // Select parent_id
        $parent_id = intval( $wpdb->get_var("SELECT `post_parent` FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `post_ID` = '$message_ID'") );
        if ( $parent_id === 0 ) {
            $wpdb->query("ROLLBACK");
            return false;
        }
        // load date from latest child or use original post date
        $date = $wpdb->get_var("SELECT `date` FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `post_parent` = '$parent_id' AND `post_ID` != '$message_ID' ORDER BY `post_ID` DESC LIMIT 0,1");
        if ( $date == '' ) {
            $date = $wpdb->get_var("SELECT `date` FROM " . WPAM_ADMIN_BLOG_POSTS ." WHERE `post_ID` = '$parent_id'");
        }
        // update sort_date
        $wpdb->update(WPAM_ADMIN_BLOG_POSTS, array('sort_date' => $date ), array('post_ID' => $parent_id), array('%s'), array('%d') );
        $wpdb->query("COMMIT");
        return true;
    }
    
    /** 
    * Update message
    * @param int $message_ID
    * @param string $text
    */
    static function update_message($message_ID, $text) {
        global $wpdb;
        $text = nl2br($text);
        $post_time = current_time('mysql',0);
        $wpdb->update(WPAM_ADMIN_BLOG_POSTS, array('text' => $text, 'last_edit' => $post_time ), array('post_ID' => $message_ID), array('%s','%s'), array('%d') );
        wpam_tags::del_tags($message_ID, $text);
        wpam_tags::add_tags($message_ID, $text);
    }
    
    /**
    * Remove sticky message and make it to a normal one
    * @param int $message_ID        The ID of the message
    * @param int $is_sticky         0 or 1
    * @since version 1.2.0
    */
    static function update_sticky($message_ID, $is_sticky) {
        global $wpdb;
        $wpdb->update(WPAM_ADMIN_BLOG_POSTS, array('is_sticky' => $is_sticky ), array('post_ID' => $message_ID), array('%d'), array('%d') );
    }

    /** 
    * Add a message as WordPress blog post
    * @param string $content    The content of the message
    * @param string $title      The title of the message
    * @param int $author_id     The user id
    */
    static function add_as_wp_post ($content, $title, $author_id) {
        if ($title == '') {
            $title = __('Short message','wp-admin-microblog');
        }
        $content = str_replace('(file://)', 'http://', $content);
        $message = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => $author_id,
            'tags_input' => ''
        );
        
        if ( preg_match_all("/[#]+[A-Za-z0-9-_]+/", $content, $match) ) {
            for ($x = 0; $x < count($match[0]); $x++) {
                $name = str_replace('#', '', $match[0][$x]);
                $name = trim($name);
                $message['tags_input'] = $message['tags_input'] . $name . ', ';
                $message['post_content'] = str_replace($match[0][$x], '', $message['post_content']);
            }
            $message['tags_input'] = substr($message['tags_input'], 0, -2);
        }	
        wp_insert_post( $message );
    }
    
    /**
     * Prepare a message for screen
     * @param string $text
     * @param array $tags
     * @return string
     */
    public static function prepare($text, $tags) {
        $text = self::replace_bbcode($text);
        $text = self::replace_url($text);
        $text = self::replace_tags($text, $tags);
        $text = stripslashes($text);
        return $text;
    }
    
    /**
     * Replace bb-codes
     * @param string $text
     * @param string $mode (replace or delete)
     * @return string 
     * @access private
     */
    private static function replace_bbcode($text, $mode = 'replace') {
        if ($mode == 'replace') {
            $text = preg_replace("/\[b\](.*)\[\/b\]/Usi", "<strong>\\1</strong>", $text); 
            $text = preg_replace("/\[i\](.*)\[\/i\]/Usi", "<em>\\1</em>", $text); 
            $text = preg_replace("/\[u\](.*)\[\/u\]/Usi", "<u>\\1</u>", $text);
            $text = preg_replace("/\[s\](.*)\[\/s\]/Usi", "<s>\\1</s>", $text);
            $text = preg_replace("/\[sup\](.*)\[\/sup\]/Usi", "<sup>\\1</sup>", $text);
            $text = preg_replace("/\[sub\](.*)\[\/sub\]/Usi", "<sub>\\1</sub>", $text);
            $text = preg_replace("/\[red\](.*)\[\/red\]/Usi", '<span style="color:red;">\\1</span>', $text);
            $text = preg_replace("/\[blue\](.*)\[\/blue\]/Usi", '<span style="color:blue;">\\1</span>', $text);
            $text = preg_replace("/\[green\](.*)\[\/green\]/Usi", '<span style="color:green;">\\1</span>', $text);
            $text = preg_replace("/\[orange\](.*)\[\/orange\]/Usi", '<span style="color:orange;">\\1</span>', $text);
            $text = preg_replace("/\[code\](.*)\[\/code\]/Usi", '<div class="wpam-code">\\1</div>', $text);
        }
        if ($mode == 'delete') {
            $text = preg_replace("/\[b\](.*)\[\/b\]/Usi", "\\1", $text); 
            $text = preg_replace("/\[i\](.*)\[\/i\]/Usi", "\\1", $text); 
            $text = preg_replace("/\[u\](.*)\[\/u\]/Usi", "\\1", $text);
            $text = preg_replace("/\[s\](.*)\[\/s\]/Usi", "\\1", $text);
            $text = preg_replace("/\[sup\](.*)\[\/sup\]/Usi", "\\1", $text);
            $text = preg_replace("/\[sub\](.*)\[\/sub\]/Usi", "\\1", $text);
            $text = preg_replace("/\[red\](.*)\[\/red\]/Usi", "\\1", $text);
            $text = preg_replace("/\[blue\](.*)\[\/blue\]/Usi", "\\1", $text);
            $text = preg_replace("/\[green\](.*)\[\/green\]/Usi", "\\1", $text);
            $text = preg_replace("/\[orange\](.*)\[\/orange\]/Usi", "\\1", $text);
            $text = preg_replace("/\[code\](.*)\[\/code\]/Usi", "\\1", $text);
        }
        return $text;
    }
    
    /**
     * Replace urls in a string
     * @param string $text
     * @return string
     * @access private
     */
    private static function replace_url($text) {
        // correct a problem when <br /> stands behind an url
        $text = str_replace("<br />"," <br />",$text);
        if ( preg_match_all("((http://|https://|ftp://|file://|mailto:|news:)[^ ]+)", $text, $match) ) {
            for ($x = 0; $x < count($match[0]); $x++) {
                // Local Files via WordPress Media Library
                if ( $match[1][$x] == 'file://' ) {
                    $text = self::handle_file_urls($text, $match, $x);
                    continue;
                }
                
                // External links
                if ( getimagesize($match[0][$x] ) !== false) {
                    $text = self::handle_file_urls($text, $match, $x, 'external');
                }       
                else {
                    $text = self::handle_links($text, $match, $x);
                }
            }
        }
        return $text;
    }
    
    /**
     * Replace hashtags
     * @param string $text      The message text
     * @param array $tags       An array of tags
     * @return string
     * @access private
     * @since version 2.1.0
     */
    private static function replace_tags ($text, $tags) {
        if ( preg_match_all("/[#]+[A-Za-z0-9-_]+/", $text, $match) ) {
            for ($x = 0; $x < count($match[0]); $x++) {
                $name = str_replace('#', '', $match[0][$x]);
                $name = trim($name);
                foreach ($tags as $tag) {
                    if ($tag['name'] == $name) {
                        $text = str_replace($match[0][$x], ' <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;tag=' . $tag['tag_ID'] . '" title="' . __('Show related messages','wp-admin-microblog') . '">#' . $tag['name'] . '</a> ', $text);
                    }
                }
            }
        }
        return $text;
    }
    
    
    /**
     * Transforms normal URLs into image or link embeds
     * @param string $text      The message text
     * @param array $match      The array of matched URLs
     * @param int $x            The current array position
     * @param string $flag      To modes are available here: internal (default) and external
     * @return string
     * @access private
     * @since 3.0
     */
    private static function handle_file_urls ($text, $match, $x, $flag = 'internal') {
        $link = str_replace('file://', 'http://', $match[0][$x]);
        
        // if it's a file
        if ( getimagesize($link) === false ) {
            return str_replace($match[0][$x], ' <a href="' . $link . '" target="_blank" title="' . $link . '">' . basename($match[0][$x]) . '</a> ', $text);
        }
        
        // if it's an image
        $domain = ($flag === 'external') ? '<b>[' . self::extract_domain($link) . ']</b> ' : '';
        return str_replace($match[0][$x], '<a href="' . $link . '" target="_blank" title="' . $link . '"><img class="wpam-image" src="' . $link . '" title="' . $link . '" alt=""/><br/><span class="wpam-image-caption">'. $domain . basename($match[0][$x]) . '</span></a> ', $text);
    }
    
    /**
     * Transforms normal URLs into a html link
     * @param string $text  The message text
     * @param array $match  The array of matched URLs
     * @param int $x        The current array position
     * @return string
     * @access private
     * @since 3.0
     */
    private static function handle_links ($text, $match, $x) {
        $link_text = $match[0][$x];
        $length = strlen($link_text);
        $link_text = substr($link_text, 0 , 50);
        if ($length > 50) {
            $link_text .= '[...]';
        }
        return str_replace($match[0][$x], ' <a href="' . $match[0][$x] . '" target="_blank" title="' . $match[0][$x] . '">' . $link_text . '</a> ', $text);
    }
    
    /**
     * Returns the domain name of an URL
     * @return string
     * @access private
     * @since 3.0
     */
    private static function extract_domain($url) {
        if( preg_match('/^(?:\w+:\/\/)?(?:[^\/]+\.)*(\w+\.\w+)(?:$|\/)/', $url, $match) !== false ) {
            return esc_html($match[1]); 
        }
        return '';
    }
    
}
