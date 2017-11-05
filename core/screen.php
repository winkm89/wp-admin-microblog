<?php

/**
 * Load screen options
 * @since 2.3
 */
function wpam_screen_options () {
    add_filter('screen_settings', 'wpam_show_screen_options', 10, 2 );
}

/**
 * Add screen options
 * @param string status
 * @param array $args
 * @since 2.3
 */
function wpam_show_screen_options( $status, $args ) {
    global $wpam_admin_page;
    // Load data
    $tags_per_page = WPAM_DEFAULT_TAGS;
    $messages_per_page = WPAM_DEFAULT_NUMBER_MESSAGES;
    $sort_order = WPAM_DEFAULT_SORT_ORDER;
    $date_format = WPAM_DEFAULT_DATE_FORMAT;
    $user = get_current_user_id();
    $user_options = get_user_meta($user, 'wpam_screen_settings', true);
    if ( !empty($user_options) ) {
        $data = wpam_core::extract_column_data($user_options);
        $tags_per_page = $data['tags_per_page'];
        $messages_per_page = $data['messages_per_page'];
        $sort_order = $data['sort_order'];
        $date_format = $data['date_format'];
    }
    
    // Set sort_order radio buttons
    if ( $sort_order === 'date' ) {
        $sel_1 = 'checked="checked"';
        $sel_2 = '';
    }
    else {
        $sel_1 = '';
        $sel_2 = 'checked="checked"';
    }
    
    // Set date_format radio buttons
    if ( $date_format === 'time_difference' ) {
        $sel_3 = 'checked="checked"';
        $sel_4 = '';
    }
    else {
        $sel_3 = '';
        $sel_4 = 'checked="checked"';
    }
    
    $return = $status;
    if ( $args->base == $wpam_admin_page ) {    
        $button = get_submit_button( __( 'Apply', 'wp-admin-microblog' ), 'primary', 'screen-options-apply', false );
        $return .= '
        <h3>' . __('Screen Options', 'wp-admin-microblog') . '</h3>
        <input type="hidden" name="wp_screen_options[option]" value="wpam_screen_settings" />
        <input type="hidden" name="wp_screen_options[value]" value="default" />
        <p><input type="number" name="wpam_tags_per_page" id="wpam_tags_per_page" value="' . $tags_per_page . '" min="1" max="999" maxlength="3"/> <label for="wpam_tags_per_page">' . __('Number of tags', 'wp-admin-microblog') . '</label></p>
        <p><input type="number" name="messages_per_page" id="messages_per_page" value="' . $messages_per_page . '" min="1" max="999" maxlength="3"/> <label for="messages_per_page">' . __('Number of messages per page', 'wp-admin-microblog') . '</label></p>
        <h5>' . __('Sort order for messages','wp-admin-microblog') . '</h5>    
          <p>
            <input type="radio" name="wpam_sort_order" id="wpam_sort_order_1" value="date" ' . $sel_1 . '/><label for="wpam_sort_order_1">' . __('Show the latest messages first', 'wp-admin-microblog') . '</label>
            <input type="radio" name="wpam_sort_order" id="wpam_sort_order_2" value="date_last_comment" ' . $sel_2 . '/><label for="wpam_sort_order_2">' . __('Show messages with latest comments first', 'wp-admin-microblog') . '</label> 
          </p>
        <h5>' . __('Date format for messages', 'wp-admin-microblog') . '</h5>
           <p>
            <input type="radio" name="wpam_date_format" id="wpam_date_format_1" value="time_difference" ' . $sel_3 . '/><label for="wpam_date_format_1">' . __('Show time difference','wp-admin-microblog') . '</label>
            <input type="radio" name="wpam_date_format" id="wpam_date_format_2" value="date" ' . $sel_4 . '/><label for="wpam_date_format_2">' . __('Show date of publishing', 'wp-admin-microblog') . '</label> 
          </p>
        ' . $button;
    }
    return $return;
}

add_filter('set-screen-option', 'wpam_set_screen_options', 11, 3);

/**
 * Set screen options
 * @param string $status
 * @param string $option
 * @param string $value
 * @return string
 * @since 2.3
 */
function wpam_set_screen_options($status, $option, $value) {
    if ( 'wpam_screen_settings' == $option ) { 
        $tags_per_page = intval($_POST['wpam_tags_per_page']);
        $messages_per_page = intval($_POST['messages_per_page']);
        $sort_order = htmlspecialchars($_POST['wpam_sort_order']);
        $date_format = htmlspecialchars($_POST['wpam_date_format']);
        $value= 'tags_per_page = {' . $tags_per_page . '}, messages_per_page = {' . $messages_per_page . '}, sort_order = {' . $sort_order . '}, date_format = {' . $date_format . '}';
    }
    return $value;
}

/**
 * Add help tab to wp admin microblog main screen
 */
function wpam_add_help_tab () {
    $screen = get_current_screen();  
    $screen->add_help_tab( array(
        'id'        => 'wpam_help_tab',
        'title'     => __('Microblog','wp-admin-microblog'),
        'content'   => '<p><strong>' . __('E-mail notification','wp-admin-microblog') . '</strong> - ' . __('If you will send your message as an E-Mail to any user, so write @username (example: @admin)','wp-admin-microblog') . '
                        <p><strong>' . __('Text formatting','wp-admin-microblog') . '</strong> - ' . __('You can use simple bbcodes: [b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s], [red]red[/red], [blue]blue[/blue], [green]green[/green], [orange]orange[/orange], [code]code[/code]. Combinations like [red][s]text[/s][/red] are possible. The using of HTML tags is not possible.','wp-admin-microblog') . '</p>
                        <p><strong>' . __('Tags', 'wp-admin-microblog') . '</strong> - ' . __('You can add tags directly to your message, if you use hashtags. Examples: #monday #2012','wp-admin-microblog') . '</p>',
    ) );
} 

/**
 * @since 2.3
 */
class wpam_screen {
    
    /**
     * Returns the tag_cloud
     * @param string $tag
     * @param string $author
     * @param string $search
     * @param int $tags_per_page
     * @return string
     * @since 2.3
     */
    public static function get_tagcloud ($tag, $author, $search, $tags_per_page) {
        global $wpdb;
        $end = '';
        // font sizes
        $maxsize = 35;
        $minsize = 11;
        // Count all tags and find max, min
        $tagcloud_temp = $wpdb->get_row("SELECT MAX(anzahlTags) AS max, min(anzahlTags) AS min "
                . "                         FROM ( SELECT anzahlTags FROM ( SELECT COUNT(*) AS anzahlTags FROM " . WPAM_ADMIN_BLOG_RELATIONS . " GROUP BY " . WPAM_ADMIN_BLOG_RELATIONS . ".`tag_ID` ORDER BY anzahlTags DESC ) as temp1 GROUP BY anzahlTags ORDER BY anzahlTags DESC ) AS temp", ARRAY_A);
        $max = $tagcloud_temp['max'];
        $min = $tagcloud_temp['min'];
        // if there are no tags in database
        if ( $min == '' ) {
            return __('No tags available','wp-admin-microblog') ;
        }
        // compose tags and their numbers
        $sql = "SELECT tagPeak, name, tag_ID FROM ( SELECT COUNT(b.tag_ID) as tagPeak, t.name AS name, t.tag_ID as tag_ID FROM " . WPAM_ADMIN_BLOG_RELATIONS . " b LEFT JOIN " . WPAM_ADMIN_BLOG_TAGS . " t ON b.tag_ID = t.tag_ID GROUP BY b.tag_ID ORDER BY tagPeak DESC LIMIT $tags_per_page ) AS temp WHERE tagPeak>=$min ORDER BY name";
        $temp = $wpdb->get_results($sql, ARRAY_A);
        // create a cloud
        foreach ($temp as $tagcloud) {
            // compute font size
            // offset for min
            if ($min == 1) {
                $min = 0;
            }
            $div = $max - $min;
            if ($div == 0) {
                $div = 1;
            }
            // Formula: max. font size*(current number - min number)/ (max number - min number)
            $size = floor(($maxsize*($tagcloud['tagPeak']-$min)/($div)));
            // offset for font size
            if ($size < $minsize) {
                $size = $minsize ;
            }
            // active tag
            if ($tagcloud['tag_ID'] == $tag){
                $end .= '<span style="font-size:' . $size . 'px;" class="wpam-tag"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $author . '&amp;search=' . $search . '" title="' . __('Delete the tag from filter','wp-admin-microblog') . '" style="color:#FF9900; text-decoration:underline;">' . $tagcloud['name'] . '</a></span> '; 
            }
            else{
                $end .= '<span style="font-size:' . $size . 'px;" class="wpam-tag"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $author . '&amp;search=' . $search . '&amp;tag=' . $tagcloud['tag_ID'] . '" title="' . __('Show related messages','wp-admin-microblog') . '">' . $tagcloud['name'] . '</a></span> '; 
            }
        }
        return $end;
    }
    
    /**
     * Returns the content for the user_widget
     * @param string $tag
     * @param string $author
     * @param string $search
     * @param string $wpam_user_name
     * @return string
     * @since 2.3
     */
    public static function get_users ($tag, $author, $search, $wpam_user_name) {
        global $wpdb;
        $end = '<ul class="wpam-user-list">';
        $users = $wpdb->get_results("SELECT DISTINCT user FROM " . WPAM_ADMIN_BLOG_POSTS);
        foreach ($users as $users) {
            $user_info = get_userdata($users->user);
            $name = wpam_screen::get_username($user_info, $wpam_user_name);
            if ($author == $user_info->ID) {
                $end .= '<li class="wpam-user-list-select"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . __('Delete user as filter','wp-admin-microblog') . '">';
            }
            else {
                $end .= '<li><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $user_info->ID . '&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . $name . '">';
            }
            $end .= '<div class="wpam_user_image">' . get_avatar($user_info->ID, 35) . '</div>';
            $end .= '</a></li>';
        }
        return $end . '</ul>';      
    }
    
    /**
     * Defines the visisble user name
     * @param object $user_info
     * @param string $wpam_user_name
     * @since 3.1
     */
    public static function get_username ($user_info, $wpam_user_name) {
        // Nick name option
        if ($wpam_user_name == 'nickname') {
            return $user_info->nickname; 
        }
        
        // User login option
        if ($wpam_user_name == 'user_login') {
            return $user_info->user_login;
        }
        
        // Full name option (First name and last name)
        if ($wpam_user_name == 'full_name') {
            return $user_info->first_name . ' ' . $user_info->last_name;
        }
        
        // Default
        return $user_info->display_name;
    }
    
    /**
     * Prints javascript parameter for the screen
     * @param array args
     * @since 3.0
     */
    public static function print_javascript_paramenters ($args) {
        ?>
        <script type="text/javascript">
            var wpam_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ) . '?action=wp_admin_blog' ;?>';
            var wpam_latest_message_id = <?php echo intval(wpam_message::get_latest_message_id()); ?>;
            var wpam_auto_reload_interval = <?php echo intval($args['auto_reload_interval']); ?>;
            var wpam_i18n_refresh = '<?php _e('Click for refresh', 'wp-admin-microblog');  ?>';
            var wpam_i18n_like_title = '<?php _e('The following users like this message', 'wp-admin-microblog');?>';
            var wpam_i18n_like_button = '<?php _e('Like','wp-admin-microblog'); ?>';
            var wpam_i18n_unlike_button = '<?php _e('Unlike','wp-admin-microblog'); ?>';
            var wpam_i18n_cancel_button = '<?php _e('Cancel','wp-admin-microblog'); ?>';
            var wpam_i18n_save_button = '<?php _e('Submit','wp-admin-microblog'); ?>';
        </script>
        <?php
    }
    
    /**
     * Gets the new message form
     * @since 3.0
     */
    public static function get_new_message_form () {
        ?>
        <form name="new_post" method="post" action="admin.php?page=wp-admin-microblog/wp-admin-microblog.php" id="new_post_form">
        <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Your Message', 'wp-admin-microblog');?></th>
            </tr>
            <tr>
                <td>
                <div class="wpam_media_buttons" style="text-align:right;"><?php echo wpam_media_buttons(); ?></div>
                <div id="postdiv" class="postarea" style="display:block;">
                <textarea name="wpam_nm_text" id="wpam_nm_text" style="width:100%;" rows="4"></textarea>
                </div>
                <p style="text-align:right; float:right;"><input name="send" type="submit" class="button-primary" value="<?php _e('Send', 'wp-admin-microblog'); ?>" /><p>
                <?php if ( current_user_can( 'use_wp_admin_microblog_bp' ) || current_user_can( 'use_wp_admin_microblog_sticky' ) ) { ?>
                    <p style="float:left; padding: 5px;"><a onclick="wpam_showhide('wpam_message_options')" style="cursor:pointer; font-weight:bold;">+ <?php _e('Options', 'wp-admin-microblog'); ?></a></p>
                    <table style="width:100%; display: none; float:left;" id="wpam_message_options">
                        <?php if ( current_user_can( 'use_wp_admin_microblog_sticky' ) ) { ?> 
                            <tr>
                                 <td style="border-bottom-width:0px;"><input name="is_sticky" id="is_sticky" type="checkbox"/> <label for="is_sticky"><?php _e('Sticky this message','wp-admin-microblog'); ?></label></td>
                            </tr>
                        <?php } ?>
                        <?php if ( current_user_can( 'use_wp_admin_microblog_bp' ) ) { ?>
                            <tr>
                                 <td style="border-bottom-width:0px;">
                                     <input name="as_wp_post" id="as_wp_post" type="checkbox" onclick="wpam_showhide('span_headline')" />
                                     <label for="as_wp_post"><?php _e('as WordPress blog post', 'wp-admin-microblog');?></label>
                                     <span style="display:none;" id="span_headline">&rarr; <label for="headline"><?php _e('Title', 'wp-admin-microblog');?> </label>
                                         <input name="headline" id="headline" type="text" style="width:350px;" />
                                     </span>
                                 </td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } ?> 
               </td>
            </tr>
        </thead>
        </table>
        </form>
        <?php
    }
    
    /**
     * Gets the microblog headline
     * @param array $args
     * @since 3.0
     */
    public static function get_headline ($args) {
        if ( $args['search'] != '' || $args['author'] != '' || $args['tag'] != '' || $args['rpl'] != '' ) {
           echo __('Search Results', 'wp-admin-microblog') . ' | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php">' . __('Show all','wp-admin-microblog') . '</a>';
        }
        else {
           echo __('Messages', 'wp-admin-microblog');
        }
    }
    
    /**
     * Prints the messages
     * @param object $post
     * @param array $args
     * @since 3.0
     */
    public static function print_messages ($post, $args) {
        global $wpdb;
        
        // IF there are no messages
        if ( $args['num_all_messages'] == 0 ) {
           echo '<tr><td>' . __('Sorry, no entries mached your criteria','wp-admin-microblog') . '</td></tr>';
           return;
        }
        
        // The page menu
        echo '<tr>';
        echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . wpam_page_menu($args['num_all_messages'], $args['number_messages'], $args['curr_page'], $args['message_limit'], 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php', 'search=' . $args['search'] . '&amp;author=' . $args['author'] . '&amp;tag=' . $args['tag'] . '') . '</div></td>';
        echo '</tr>';
            
        // Search for replies
        $where = '';
        foreach ($post as $ids) {
            $where .= ($where === '') ? "`post_parent` = '" . $ids->post_ID . "'" : "  or `post_parent` = '" . $ids->post_ID . "'";
        }
        $sql = "SELECT * FROM " . WPAM_ADMIN_BLOG_POSTS . " WHERE $where ORDER BY `post_ID` ASC";
        $replies = $wpdb->get_results($sql);
            
        // Options for messages
        $options = array (
            'auto_reply' => $args['auto_reply'],
            'user' => $args['user'],
            'date_format' => $args['date_format'],
            'wp_date_format' => get_option('date_format'),
            'wp_time_format' => get_option('time_format'),
            'sticky_for_dash' => true,
            'is_widget' => false,
            'wpam_user_name' => $args['wpam_user_name']
        );
            
        foreach ($post as $post) {
            $user_info = get_userdata($post->user);
                
            // Load tags
            $tags = wpam_tags::get_tags($post->post_ID);

            // sticky post options
            // change background color for sticky posts
            $class = 'wpam-normal';
            if ( $post->is_sticky == 1  ) {
                 $class = 'wpam-sticky';
            }
               
            // print messages
            echo '<tr class="' . $class . '">';
            echo '<td style="padding:10px 0 10px 10px; width:40px;"><div class="wpam_user_image" title="' . self::get_username($user_info, $args['wpam_user_name']) . '">' . get_avatar($user_info->ID, 40) . '</div></td>';
            echo '<td style="padding:10px;">';
            echo wpam_templates::message($post, $tags, $user_info, $options, 1, 0);
            
            // print replies
            wpam_screen::print_replies($post, $replies, $args, $options);

            echo '</td>';
            echo '</tr>';
        }
    }
    
    /**
     * Prints the replies
     * @param object $post
     * @param object $replies
     * @param array $args
     * @param array $options
     * @since 3.0
     */
    public static function print_replies ($post, $replies, $args, $options) {
        
        if ( $args['search'] !== '' || $args['tag'] !== '' ) {
            return;
        }
        
        $r = '';
        (int) $count = 0;
        (int) $reply_number = 0;
        // count number of replies of this message
        foreach ($replies as $counts) {
            if ( $counts->post_parent == $post->post_ID ) {
                $reply_number++;
            }
        }
        // create replies
        foreach ( $replies as $reply ) {
            $tags_reply = wpam_tags::get_tags($reply->post_ID);
            if ( $reply->post_parent == $post->post_ID ) {
                 $count++;
                 $user_info = get_userdata($reply->user);
                 $style = ( $reply_number >= 3 && $reply_number - $count >= 3 && $rpl == 0 ) ? 'style="display:none;"' : '';
                 $r .= '<tr id="wpam-reply-' . $post->post_ID . '-' . $count . '" ' . $style . '>';
                 $r .= '<td style="padding:10px 0 10px 10px; width:40px;"><div class="wpam_user_image" title="' . self::get_username($user_info, $args['wpam_user_name']) . '">' . get_avatar($user_info->ID, 40) . '</div></td>';
                 $r .= '<td>' . wpam_templates::message($reply, $tags_reply, $user_info, $options, 2, 0) . '</td>';
                 $r .= '</tr>';
            }
        }
        // show the number of replies text
        if ( $count > 3 && $rpl == 0 ) {
             echo '<table class="wpam-replies" id="wpam-reply-sum-' . $post->post_ID . '">';
             echo '<tr><td style="padding:7px;"><a onclick="wpam_showAllReplies(' . "'" . $post->post_ID . "'" . ', ' . "'" . $reply_number . "'" . ')" style="cursor:pointer;" title="' . __('Show all replies','wp-admin-microblog') . '">' . $count . ' ' . __('Replies','wp-admin-microblog') . '</a></td></tr>';
             echo '</table>';
        }
        // echo table of replies
        echo '<table class="wpam-replies" id="wpam-replies-' . $post->post_ID . '">';
        echo $r;
        echo '</table>';
    }
    
}

/** 
 * Main Page
 * @global type $current_user
 * @global class $wpdb
 */
function wpam_page() {
    $current_user = wp_get_current_user();
    $user = $current_user->ID;

    // run the updater
    wpam_update::force_update();

    // edit post fields
    $text = isset( $_POST['wp_admin_blog_edit_text'] ) ? htmlspecialchars($_POST['wp_admin_blog_edit_text']) : '';
    $edit_message_ID = isset( $_POST['wp_admin_blog_message_ID'] ) ? intval($_POST['wp_admin_blog_message_ID']) : 0;
    $parent_ID = isset( $_POST['wp_admin_blog_parent_ID'] ) ? intval($_POST['wp_admin_blog_parent_ID']) : 0;
    $delete = isset( $_GET['delete'] ) ? intval($_GET['delete']) : 0;
    $level= isset( $_GET['level'] ) ? intval($_GET['level']) : 0;
    $remove = isset( $_GET['remove'] ) ? intval($_GET['remove']) : 0;
    $add = isset( $_GET['add'] ) ? intval($_GET['add']) : 0;

    // filter
    $author = isset( $_GET['author'] ) ? htmlspecialchars($_GET['author']) : '';
    $tag = isset( $_GET['tag'] ) ? htmlspecialchars($_GET['tag']) : '';
    $search = isset( $_GET['search'] ) ? htmlspecialchars($_GET['search']) : '';
    $rpl = isset( $_GET['rpl'] ) ? intval($_GET['rpl']) : 0;

    // load system settings
    $auto_reply = false;
    $auto_reload_interval = 60000;
    $blog_name = 'Microblog';
    $wpam_user_name = 'display_name';
    $tags_per_page = WPAM_DEFAULT_TAGS;
    $number_messages = WPAM_DEFAULT_NUMBER_MESSAGES;
    $sort_order = WPAM_DEFAULT_SORT_ORDER;
    $date_format = WPAM_DEFAULT_DATE_FORMAT;

    $system = wpam_get_options('','system');
    foreach ($system as $system) {
        if ( $system['variable'] == 'auto_reply' ) { $auto_reply = $system['value']; }
        if ( $system['variable'] == 'blog_name' ) { $blog_name = $system['value']; }
        if ( $system['variable'] == 'auto_reload_interval' ) { $auto_reload_interval = $system['value']; }
        if ( $system['variable'] == 'auto_reload_enabled' ) { $auto_reload_enabled = $system['value']; }
        if ( $system['variable'] == 'wpam_user_name' ) { $wpam_user_name = $system['value']; }
    }
   
    // Load user settings
    $user_options = get_user_meta($user, 'wpam_screen_settings', true);
    if ( !empty($user_options) ) {
        $data = wpam_core::extract_column_data($user_options);
        $tags_per_page = $data ['tags_per_page'];
        $number_messages = $data ['messages_per_page'];
        $sort_order = $data ['sort_order'];
        $date_format = $data['date_format'];
    }
   
    // Handles limits 
    if (isset($_REQUEST['limit'])) {
       $curr_page = intval($_REQUEST['limit']) ;
       if ( $curr_page <= 0 ) {
          $curr_page = 1;
       }
       $message_limit = ( $curr_page - 1 ) * $number_messages;
    }
    else {
       $message_limit = 0;
       $curr_page = 1;
    }
    
    // Handles actions
    if (isset($_POST['send'])) {
       // new_post fields
       $content = isset( $_POST['wpam_nm_text'] ) ? htmlspecialchars($_POST['wpam_nm_text']) : '';
       $headline = isset( $_POST['headline'] ) ? htmlspecialchars($_POST['headline']) : '';
       $is_sticky = isset( $_POST['is_sticky'] ) ? 1 : 0;

       // Add new message
       wpam_message::add_message($content, $user, 0, $is_sticky);
       if ( isset( $_POST['as_wp_post'] ) ) {
          wpam_message::add_as_wp_post($content, $headline, $user);
       }
       $content = "";
    }	
    if ( $delete != 0 ) {
       wpam_message::del_message($delete, $level);
    }
    if ( $remove != 0 ) {
       wpam_message::update_sticky($remove, 0);  
    }
    if ( $add != 0 ) {
       wpam_message::update_sticky($add, 1);    
    }
    if (isset($_POST['wp_admin_blog_edit_message_submit'])) {
       wpam_message::update_message($edit_message_ID, $text);
    }
    if (isset($_POST['wp_admin_blog_reply_message_submit'])) {
       wpam_message::add_message($text, $user, $parent_ID, 0);
    }
    
    // print js
    $args = array ('auto_reload_interval' => $auto_reload_interval);
    wpam_screen::print_javascript_paramenters($args);
    
    ?>
    <div class="wrap wpam-row">
    <h2 style="padding-bottom: 10px;"><?php echo $blog_name;?></h2>
    <div class="wpam-container-main">
    <?php wpam_screen::get_new_message_form(); ?>
    <form name="all_messages" method="post">
        <table class="widefat wpam-messages" style="margin-top: 25px;">
    <thead>
        <tr id="wpam_table_messages_headline">
            <th colspan="2">
             <?php
                $args = array ( 
                    'search' => $search,
                    'author' => $author,
                    'tag'    => $tag,
                    'rpl'    => $rpl);
                wpam_screen::get_headline($args);
               ?>
            </th>
        </tr>
        
        <?php
        // Check number messages
        $num_all_messages = wpam_message::get_messages($args, true);
        
        // Load messages
        $args = array(
            'search' => $search,
            'author' => $author,
            'tag' => $tag,
            'sort_order' => 'date',
            'message_limit' => $message_limit,
            'number_messages' => $number_messages,
            'rpl' => $rpl,
            'output_type' => OBJECT,
            // for print_messages
            'curr_page' => $curr_page,
            'num_all_messages' => $num_all_messages,
            'auto_reply' => $auto_reply,
            'user' => $user,
            'date_format' => $date_format,
            'wpam_user_name' => $wpam_user_name
        ); 
        $post = wpam_message::get_messages($args);
        
        // Print messages
        wpam_screen::print_messages($post, $args);

        // Page Menu
        if ( $num_all_messages > $number_messages ) {
           echo '<tr>';
           echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . wpam_page_menu($num_all_messages, $number_messages, $curr_page, $message_limit, 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php', 'search=' . $search . '&amp;author=' . $author . '&amp;tag=' . $tag . '', 'bottom') . '</td>';
           echo '</tr>';
        }
         
       ?>
    </thead>
    </table>
    </form>
    </div>
    
    
    <div class="wpam-container-filter">
    <form name="blog_selections" method="get" action="admin.php">
    <input name="page" type="hidden" value="wp-admin-microblog/wp-admin-microblog.php" />
    <input name="author" type="hidden" value="<?php echo $author; ?>" />
    <input name="tag" type="hidden" value="<?php echo $tag; ?>" />
    <table class="widefat">
    <thead>
        <tr>
            <th><?php
              if ($search != "") { ?>
            	<label for="suche_abbrechen" title="<?php _e('Delete the search from filter','wp-admin-microblog'); ?>">
                    <?php _e('Search', 'wp-admin-microblog'); ?><a id="suche_abbrechen" href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=<?php echo $author; ?>&amp;search=&amp;tag=<?php echo $tag;?>" style="text-decoration:none; color:#FF9900;" title="<?php _e('Delete the search from filter','wp-admin-microblog'); ?>"> X</a>
                </label><?php 
              }
              else {
                 _e('Search', 'wp-admin-microblog');
              }?>
            </th>
        </tr>
        <tr>
            <td>
            <input class="wpam-search" name="search" type="search"  value="<?php if ($search != "") { echo $search; } else { _e('Search word', 'wp-admin-microblog'); }?>" onblur="if(this.value==='') this.value='<?php _e('Search word', 'wp-admin-microblog'); ?>';" onfocus="if(this.value==='<?php _e('Search word', 'wp-admin-microblog'); ?>') this.value='';"/>
            <input name="search_init" type="submit" class="button-secondary" value="<?php _e('Go', 'wp-admin-microblog');?>"/>
            </td>
        </tr>    
    </thead>
    </table>
    <p style="margin:7px; font-size:2px;">&nbsp;</p>
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Tags', 'wp-admin-microblog');?></th>
        </tr>
        <tr>
            <td><div style="padding:5px; line-height: 2.0em;">
             <?php echo wpam_screen::get_tagcloud($tag, $author, $search, $tags_per_page); ?>
             </div>     
            </td>
        </tr>
    </thead>
    </table>
    <p style="margin:7px; font-size:2px;">&nbsp;</p>
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('User', 'wp-admin-microblog');?></th>
        </tr>
        <tr>
            <td>
                <?php echo wpam_screen::get_users($tag, $author, $search, $wpam_user_name); ?>
            </td>
         </tr>   
    </thead>
    </table>
    </form>
    </div>
    </div>
    <?php if ( $auto_reload_enabled == 'true' ) { ?>
        <script type="text/javascript" charset="utf-8" id="wpam_auto_reload_interval" src="<?php echo plugins_url() . '/wp-admin-microblog/js/auto-reload.js'; ?>"></script>
    <?php } ?>
    <script type="text/javascript" charset="utf-8" id="wpam_edit_message" src="<?php echo plugins_url() . '/wp-admin-microblog/js/messages.js'; ?>"></script>
    <?php
}