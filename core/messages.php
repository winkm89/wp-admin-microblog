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
        global $admin_blog_posts;
        if ($content != '') {
            $content = nl2br($content);
            $post_time = current_time('mysql',0);
            // check message duplications
            $duplicate = $wpdb->get_var("SELECT `text` FROM $admin_blog_posts WHERE `text` = '$content' AND `post_parent` = '$parent' AND `user` = '$user'");
            if ( $duplicate != '' ) {
                if ( strlen($duplicate) == strlen($content) ) {
                    return false;
                }
            }
            // insert message
            $wpdb->insert( $admin_blog_posts, array( 
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
                $wpdb->update($admin_blog_posts, array('sort_date' => $post_time ), array('post_ID' => $parent), array('%s'), array('%d') );
            }
            // add tags
            wpam_message::add_tags($new_message_id, $content);
            // send notifications
            wpam_message::send_notifications($content, $user);
        }
    }

    /**
     * Send notifications
     * @param string $text
     * @param int $user
     */
    private static function send_notifications($text, $user) {
        global $wpdb;
        global $admin_blog_posts;

        $user = get_userdata($user);
        $text = $text . ' ';
        $text = wpam_message::replace_bbcode($text, 'delete');
        $text = str_replace("<br />","",$text);
        $text = str_replace('(file://)', 'http://', $text);
        $text = __('Author:','wp_admin_blog') . ' ' . $user->display_name . chr(13) . chr(10) . chr(13) . chr(10) . $text;
        $text = $text . chr(13) . chr(10) . '________________________' . chr(13) . chr(10) . __('Login under the following address to create a reply:','wp_admin_blog') . ' ' . wp_login_url();

        $headers = 'From: ' . get_bloginfo() . ' <' . get_bloginfo('admin_email') . '>' . "\r\n\\";
        $subject = get_bloginfo() . ': ' .__('New message in wp admin micoblog','wp_admin_blog');
        
        $sql = "SELECT DISTINCT user FROM $admin_blog_posts";
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
     * Add tags
     * @param int $message_id
     * @param string $content 
     */
    private static function add_tags ($message_id, $content) {
        global $wpdb;
        global $admin_blog_tags;
        global $admin_blog_relations;
        // tags
        if ( preg_match_all("/[#]+[A-Za-z0-9-_]+/", $content, $match) ) {
            for ($x = 0; $x < count($match[0]); $x++) {
                $match[0][$x] = str_replace('#', '', $match[0][$x]);
                $match[0][$x] = trim($match[0][$x]);
                $sql = "SELECT `tag_ID` FROM $admin_blog_tags WHERE `name` = '" . $match[0][$x]  ."'";
                $check = $wpdb->query($sql);
                // if not, then insert tag
                if ($check == 0){
                    $wpdb->insert($admin_blog_tags, array('name' => $match[0][$x]), array('%s') );
                    $new_tag_id = $wpdb->insert_id;
                }
                else {
                    $new_tag_id = $wpdb->get_var($sql);
                }
                // check if the relation already exist
                $test = $wpdb->query("SELECT `post_ID` FROM $admin_blog_relations WHERE `post_ID` = '$message_id' AND `tag_ID` = '$new_tag_id'");
                // if not, then insert the relation
                if ($test == 0) {
                    $sql = "INSERT INTO $admin_blog_relations (`post_ID`, `tag_ID`) VALUES ('$message_id', '$new_tag_id')";
                    $wpdb->query($sql);
                }
            }
        }
    }
    
    /**
     * Delete tags if where are deleted in the message. Used for update_message
     * @param int $message_ID
     * @param string $content 
     */
    private static function del_tags($message_ID, $content) {
        global $wpdb;
        global $admin_blog_tags;
        global $admin_blog_relations;
        global $admin_blog_posts;
        $row = $wpdb->get_results("SELECT DISTINCT r.rel_ID, t.name FROM `$admin_blog_posts` p
                            LEFT JOIN `$admin_blog_relations` r ON r.post_ID = p.post_ID
                            LEFT JOIN `$admin_blog_tags` t ON t.tag_ID = r.tag_ID
                            WHERE r.post_ID = '$message_ID'", ARRAY_A);
        foreach ($row as $row) {
            $haystack = '#' . $row['name'];
            $search = strpos($haystack, $content);
            if ( $search === false ) {
                $wpdb->query("DELETE FROM $admin_blog_relations WHERE `rel_ID` = '" . $row['rel_ID'] . "'");
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
        global $admin_blog_posts;
        global $admin_blog_relations;
        $wpdb->query("SET AUTOCOMMIT=0");
        $wpdb->query("START TRANSACTION");
        if ( $level === 2 ) {
            wpam_message::reset_sort_date($message_ID);
        }
        
        $wpdb->query("DELETE FROM $admin_blog_posts WHERE `post_ID` = '$message_ID'");
        $wpdb->query("DELETE FROM $admin_blog_relations WHERE `post_ID` = '$message_ID'");
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
        global $admin_blog_posts;
        // Select parent_id
        $parent_id = intval( $wpdb->get_var("SELECT `post_parent` FROM $admin_blog_posts WHERE `post_ID` = '$message_ID'") );
        if ( $parent_id === 0 ) {
            $wpdb->query("ROLLBACK");
            return false;
        }
        // load date from latest child or use original post date
        $date = $wpdb->get_var("SELECT `date` FROM $admin_blog_posts WHERE `post_parent` = '$parent_id' AND `post_ID` != '$message_ID' ORDER BY `post_ID` DESC LIMIT 0,1");
        if ( $date == '' ) {
            $date = $wpdb->get_var("SELECT `date` FROM $admin_blog_posts WHERE `post_ID` = '$parent_id'");
        }
        // update sort_date
        $wpdb->update($admin_blog_posts, array('sort_date' => $date ), array('post_ID' => $parent_id), array('%s'), array('%d') );
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
        global $admin_blog_posts;
        $text = nl2br($text);
        $post_time = current_time('mysql',0);
        $wpdb->update($admin_blog_posts, array('text' => $text, 'last_edit' => $post_time ), array('post_ID' => $message_ID), array('%s','%s'), array('%d') );
        wpam_message::del_tags($message_ID, $text);
        wpam_message::add_tags($message_ID, $text);
    }
    
    /**
    * Remove sticky message and make it to a normal one
    * @param int $message_ID
    * @param int $is_sticky - 0 or 1
    * @since version 1.2.0
    */
    static function update_sticky($message_ID, $is_sticky) {
        global $wpdb;
        global $admin_blog_posts;
        $wpdb->update($admin_blog_posts, array('is_sticky' => $is_sticky ), array('post_ID' => $message_ID), array('%d'), array('%d') );
    }

    /** 
    * Add a message as WordPress blog post
    * @param string $content - the content of the message
    * @param string $title - the title of the message
    * @param int $author_id - the user id
    */
    static function add_as_wp_post ($content, $title, $author_id) {
        if ($title == '') {
            $title = __('Short message','wp_admin_blog');
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
        $text = wpam_message::replace_bbcode($text);
        $text = wpam_message::replace_url($text);
        $text = wpam_message::replace_tags($text, $tags);
        $text = stripslashes($text);
        return $text;
    }
    
    /**
     * Replace bb-codes
     * @param string $text
     * @param string $mode (replace or delete)
     * @return string 
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
        }
        return $text;
    }
    
    /**
     * Replace urls in a string
     * @param string $text
     * @return string
     */
    private static function replace_url($text) {
        // correct a problem when <br /> stands behind an url
        $text = str_replace("<br />"," <br />",$text);
        if ( preg_match_all("((http://|https://|ftp://|file://|mailto:|news:)[^ ]+)", $text, $match) ) {
            for ($x = 0; $x < count($match[0]); $x++) {
                if ($match[1][$x] == 'file://') {
                    $link = str_replace('file://', 'http://', $match[0][$x]);
                    $text = str_replace($match[0][$x], ' <a href="' . $link . '" target="_blank" title="' . $link . '">' . basename($match[0][$x]) . '</a> ', $text);
                }
                else {
                    $link_text = $match[0][$x];
                    $length = strlen($link_text);
                    $link_text = substr($link_text, 0 , 50);
                    if ($length > 50) {
                        $link_text .= '[...]';
                    }
                    $text = str_replace($match[0][$x], ' <a href="' . $match[0][$x] . '" target="_blank" title="' . $match[0][$x] . '">' . $link_text . '</a> ', $text);
                }
            }
        }
        return $text;
    }
    
    /**
     * Replace hashtags
     * @param string $text
     * @param array $tags
     * @return string
     * @since version 2.1.0
     */
    private static function replace_tags ($text, $tags) {
        if ( preg_match_all("/[#]+[A-Za-z0-9-_]+/", $text, $match) ) {
            for ($x = 0; $x < count($match[0]); $x++) {
                $name = str_replace('#', '', $match[0][$x]);
                $name = trim($name);
                foreach ($tags as $tag) {
                    if ($tag['name'] == $name) {
                        $text = str_replace($match[0][$x], ' <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;tag=' . $tag['tag_id'] . '" title="' . __('Show related messages','wp_admin_blog') . '">#' . $tag['name'] . '</a> ', $text);
                    }
                }
            }
        }
        return $text;
    }
}
