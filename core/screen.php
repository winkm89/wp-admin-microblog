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
    $user = get_current_user_id();
    $user_options = get_user_meta($user, 'wpam_screen_settings', true);
    if ( !empty($user_options) ) {
        $data = wpam_core::extract_column_data($user_options);
        $tags_per_page = $data ['tags_per_page'];
        $messages_per_page = $data ['messages_per_page'];
        $sort_order = $data ['sort_order'];
    }
    
    if ( $sort_order === 'date' ) {
        $sel_1 = 'checked="checked"';
        $sel_2 = '';
    }
    else {
        $sel_1 = '';
        $sel_2 = 'checked="checked"';
    }
    
    $return = $status;
    if ( $args->base == $wpam_admin_page ) {    
        $button = get_submit_button( __( 'Apply' ), 'button', 'screen-options-apply', false );
        $return .= '
        <h5>' . __('Show on screen') . '</h5>
        <input type="hidden" name="wp_screen_options[option]" value="wpam_screen_settings" />
        <input type="hidden" name="wp_screen_options[value]" value="default" />
        <p><input type="number" name="wpam_tags_per_page" id="wpam_tags_per_page" value="' . $tags_per_page . '" min="1" max="999" maxlength="3"/> <label for="wpam_tags_per_page">' . __('Number of tags','wp_admin_blog') . '</label></p>
        <p><input type="number" name="messages_per_page" id="messages_per_page" value="' . $messages_per_page . '" min="1" max="999" maxlength="3"/> <label for="messages_per_page">' . __('Number of messages per page','wp_admin_blog') . '</label></p>
        <h5>' . __('Sort order for messages','wp_admin_blog') . '</h5>    
          <p>
            <input type="radio" name="wpam_sort_order" id="wpam_sort_order_1" value="date" ' . $sel_1 . '/><label for="wpam_sort_order_1">' . __('Show the latest messages first','wp_admin_blog') . '</label>
            <input type="radio" name="wpam_sort_order" id="wpam_sort_order_2" value="date_last_comment" ' . $sel_2 . '/><label for="wpam_sort_order_2">' . __('Show messages with latest comments first','wp_admin_blog') . '</label> 
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
        $value= 'tags_per_page = {' . $tags_per_page . '}, messages_per_page = {' . $messages_per_page . '}, sort_order = {' . $sort_order . '}';
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
        'title'     => __('Microblog','wp_admin_blog'),
        'content'   => '<p><strong>' . __('E-mail notification','wp_admin_blog') . '</strong> - ' . __('If you will send your message as an E-Mail to any user, so write @username (example: @admin)','wp_admin_blog') . '
                        <p><strong>' . __('Text formatting','wp_admin_blog') . '</strong> - ' . __('You can use simple bbcodes: [b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s], [red]red[/red], [blue]blue[/blue], [green]green[/green], [orange]orange[/orange]. Combinations like [red][s]text[/s][/red] are possible. The using of HTML tags is not possible.','wp_admin_blog') . '</p>
                        <p><strong>' . __('Tags') . '</strong> - ' . __('You can add tags directly to your message, if you use hashtags. Examples: #monday #2012','wp_admin_blog') . '</p>',
    ) );
} 

/**
 * @since 2.3
 */
class wpam_screen {
    /**
     * get single message
     * @param string $post
     * @param array $tags
     * @param array $user_info
     * @param array $options
     * @param int $level
     * @return string
     * @since 2.3
     */
    public static function get_message ($post, $tags, $user_info, $options, $level = 1) {
        $edit_button = '';
        $time = wpam_core::datesplit($post->date);
        $message_text = wpam_message::prepare( $post->text, $tags );

        // Handles post parent
        if ($post->post_parent == '0') {
            $post->post_parent = $post->post_ID;
        }

        // sticky menu options
        $sticky_option = '';
        if ( current_user_can( 'use_wp_admin_microblog_sticky' ) && $level == 1 ) {
            if ( $post->is_sticky == 0 ) {
                $sticky_option = '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&add=' . $post->post_ID . '"" title="' . __('Sticky this message','wp_admin_blog') . '">' . __('Sticky','wp_admin_blog') . '</a> | ';
            }
            else {
                $sticky_option = '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&remove=' . $post->post_ID . '"" title="' . __('Unsticky this message','wp_admin_blog') . '">' . __('Unsticky','wp_admin_blog') . '</a> | ';
            }
        }

        // Show message edit options if the user is the author of the message or the blog admin
        if ( $post->user == $options['user'] || current_user_can('manage_options') ) {
            $edit_button = $edit_button . '<a onclick="javascript:wpam_editMessage(' . $post->post_ID . ')" style="cursor:pointer;" title="' . __('Edit this message','wp_admin_blog') . '">' . __('Edit','wp_admin_blog') . '</a> | ' . $sticky_option . '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;delete=' . $post->post_ID . '&amp;level=' . $level . '" title="' . __('Click to delete this message','wp_admin_blog') . '" style="color:#FF0000">' . __('Delete','wp_admin_blog') . '</a> | ';
        }
        $edit_button = $edit_button . '<a onclick="javascript:wpam_replyMessage(' . $post->post_ID . ',' . $post->post_parent . ',' . "'" . $options['auto_reply'] . "'" . ',' . "'" . $user_info->user_login . "'" . ')" style="cursor:pointer; color:#009900;" title="' . __('Write a reply','wp_admin_blog') . '">' . __('Reply','wp_admin_blog') . '</a>';

        // get human time difference
        $message_time = human_time_diff( mktime($time[0][3], $time[0][4], $time[0][5], $time[0][1], $time[0][2], $time[0][0] ), current_time('timestamp') ) . ' ' . __( 'ago', 'wp_admin_blog' );

        // handle date formats
        if ( __('en','wp_admin_blog') == 'de') {
            $message_date = $time[0][2]. '.' . $time[0][1] . '.' . $time[0][0];
        }
        else {
            $message_date = $time[0][0]. '-' . $time[0][1] . '-' . $time[0][2];
        }
        if ( date('d.m.Y') == $message_date || date('Y-m-d') == $message_date ) {
            $message_date = __('Today','wp_admin_blog');
        }     

        // print messages
        $r =  '<div id="wp_admin_blog_message_' . $post->post_ID . '" class="wpam-blog-message">
                <p style="color:#AAAAAA;"><span title="' . $message_date . '">' . $message_time . '</span> | ' . __('by','wp_admin_blog') . ' ' . $user_info->display_name . '</p>
                <p>' . $message_text . '</p>
                <div class="wpam-row-actions">' . $edit_button . '</div>
            </div>
            <input name="wp_admin_blog_message_text" id="wp_admin_blog_message_text_' . $post->post_ID . '" type="hidden" value="' . stripslashes($post->text) . '" />';
        return $r;
    }
    
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
        global $admin_blog_relations;
        global $admin_blog_tags;
        global $wpdb;
        $end = '';
        // font sizes
        $maxsize = 35;
        $minsize = 11;
        // Count all tags and find max, min
        $tagcloud_temp = $wpdb->get_row("SELECT MAX(anzahlTags) AS max, min(anzahlTags) AS min, COUNT(anzahlTags) as gesamt "
                . "                         FROM ( SELECT anzahlTags FROM ( SELECT COUNT(*) AS anzahlTags FROM $admin_blog_relations GROUP BY $admin_blog_relations.`tag_ID` ORDER BY anzahlTags DESC ) as temp1 GROUP BY anzahlTags ORDER BY anzahlTags DESC ) AS temp", ARRAY_A);
        $max = $tagcloud_temp['max'];
        $min = $tagcloud_temp['min'];
        $insgesamt = $tagcloud_temp['gesamt'];
        // if there are tags in database
        if ( $insgesamt === 0 ) {
            return __('No tags available','wp_admin_blog') ;
        }
        // compose tags and their numbers
        $sql = "SELECT tagPeak, name, tag_ID FROM ( SELECT COUNT(b.tag_ID) as tagPeak, t.name AS name,  t.tag_ID as tag_ID FROM $admin_blog_relations b LEFT JOIN $admin_blog_tags t ON b.tag_ID = t.tag_ID GROUP BY b.tag_ID ORDER BY tagPeak DESC LIMIT $tags_per_page ) AS temp WHERE tagPeak>=$min ORDER BY name";
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
                $end .= '<span style="font-size:' . $size . 'px;" class="wpam-tag"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $author . '&amp;search=' . $search . '" title="' . __('Delete the tag from filter','wp_admin_blog') . '" style="color:#FF9900; text-decoration:underline;">' . $tagcloud['name'] . '</a></span> '; 
            }
            else{
                $end .= '<span style="font-size:' . $size . 'px;" class="wpam-tag"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $author . '&amp;search=' . $search . '&amp;tag=' . $tagcloud['tag_ID'] . '" title="' . __('Show related messages','wp_admin_blog') . '">' . $tagcloud['name'] . '</a></span> '; 
            }
        }
        return $end;
    }
    
    /**
     * Returns the content for the user_widget
     * @param string $tag
     * @param string $author
     * @param string $search
     * @return string
     * @since 2.3
     */
    public static function get_users ($tag, $author, $search) {
        global $admin_blog_posts;
        global $wpdb;
        $end = '<ul class="wpam-user-list">';
        $users = $wpdb->get_results("SELECT DISTINCT user FROM $admin_blog_posts");
        foreach ($users as $users) {
            $user_info = get_userdata($users->user);
            $name = '' . $user_info->display_name . ' (' . $user_info->user_login . ')';
            if ($author == $user_info->ID) {
                $end .= '<li class="wpam-user-list-select"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . __('Delete user as filter','wp_admin_blog') . '">';
            }
            else {
                $end .= '<li><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $user_info->ID . '&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . $name . '">';
            }
            $end .= get_avatar($user_info->ID, 35);
            $end .= '</a></li>';
        }
        return $end . '</ul>';      
    }
    
}

/** 
 * Main Page
 * @global type $current_user
 * @global class $wpdb
 * @global string $admin_blog_posts
 * @global string $admin_blog_tags
 * @global string $admin_blog_relations
 */
function wpam_page() {
    global $current_user;
    global $wpdb;
    global $admin_blog_posts;
    global $admin_blog_tags;
    global $admin_blog_relations;
    get_currentuserinfo();
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
    $tags_per_page = WPAM_DEFAULT_TAGS;
    $number_messages = WPAM_DEFAULT_NUMBER_MESSAGES;
    $sort_order = WPAM_DEFAULT_SORT_ORDER;

    $system = wpam_get_options('','system');
    foreach ($system as $system) {
        if ( $system['variable'] == 'auto_reply' ) { $auto_reply = $system['value']; }
        if ( $system['variable'] == 'blog_name' ) { $blog_name = $system['value']; }
        if ( $system['variable'] == 'auto_reload_interval' ) { $auto_reload_interval = $system['value']; }
        if ( $system['variable'] == 'auto_reload_enabled' ) { $auto_reload_enabled = $system['value']; }
    }
   
    // Load user settings
    $user_options = get_user_meta($user, 'wpam_screen_settings', true);
    if ( !empty($user_options) ) {
        $data = wpam_core::extract_column_data($user_options);
        $tags_per_page = $data ['tags_per_page'];
        $number_messages = $data ['messages_per_page'];
        $sort_order = $data ['sort_order'];
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
    ?>
    <div class="wrap" style="max-width:1200px; min-width:780px;">
    <h2 style="padding-bottom: 10px;"><?php echo $blog_name;?></h2>
    <div style="width:31%; float:right; padding-right:1%;">
    <form name="blog_selections" method="get" action="admin.php">
    <input name="page" type="hidden" value="wp-admin-microblog/wp-admin-microblog.php" />
    <input name="author" type="hidden" value="<?php echo $author; ?>" />
    <input name="tag" type="hidden" value="<?php echo $tag; ?>" />
    <table class="widefat">
    <thead>
        <tr>
            <th><?php
              if ($search != "") { ?>
            	<label for="suche_abbrechen" title="<?php _e('Delete the search from filter','wp_admin_blog'); ?>">
                    <?php _e('Search', 'wp_admin_blog'); ?><a id="suche_abbrechen" href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=<?php echo $author; ?>&amp;search=&amp;tag=<?php echo $tag;?>" style="text-decoration:none; color:#FF9900;" title="<?php _e('Delete the search from filter','wp_admin_blog'); ?>"> X</a>
                </label><?php 
              }
              else {
                 _e('Search', 'wp_admin_blog');
              }?>
            </th>
        </tr>
        <tr>
            <td>
            <input name="search" type="text"  value="<?php if ($search != "") { echo $search; } else { _e('Search word', 'wp_admin_blog'); }?>" onblur="if(this.value==='') this.value='<?php _e('Search word', 'wp_admin_blog'); ?>';" onfocus="if(this.value==='<?php _e('Search word', 'wp_admin_blog'); ?>') this.value='';"/>
            <input name="search_init" type="submit" class="button-secondary" value="<?php _e('Go', 'wp_admin_blog');?>"/>
            </td>
        </tr>    
    </thead>
    </table>
    <p style="margin:7px; font-size:2px;">&nbsp;</p>
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Tags', 'wp_admin_blog');?></th>
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
            <th><?php _e('User', 'wp_admin_blog');?></th>
        </tr>
        <tr>
            <td>
                <?php echo wpam_screen::get_users($tag, $author, $search); ?>
            </td>
         </tr>   
    </thead>
    </table>
    </form>
    </div>
    <div style="width:66%; float:left; padding-right:1%;">
    <form name="new_post" method="post" action="admin.php?page=wp-admin-microblog/wp-admin-microblog.php" id="new_post_form">
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Your Message', 'wp_admin_blog');?></th>
        </tr>
        <tr>
            <td>
            <div class="wpam_media_buttons" style="text-align:right;"><?php echo wpam_media_buttons(); ?></div>
            <div id="postdiv" class="postarea" style="display:block;">
            <textarea name="wpam_nm_text" id="wpam_nm_text" style="width:100%;" rows="4"></textarea>
            </div>
            <p style="text-align:right; float:right;"><input name="send" type="submit" class="button-primary" value="<?php _e('Send', 'wp_admin_blog'); ?>" /><p>
            <?php if ( current_user_can( 'use_wp_admin_microblog_bp' ) || current_user_can( 'use_wp_admin_microblog_sticky' ) ) { ?>
            <p style="float:left; padding: 5px;"><a onclick="javascript:wpam_showhide('wpam_message_options')" style="cursor:pointer; font-weight:bold;">+ <?php _e('Options', 'wp_admin_blog'); ?></a></p>
            <table style="width:100%; display: none; float:left;" id="wpam_message_options">
                <?php if ( current_user_can( 'use_wp_admin_microblog_sticky' ) ) { ?> 
            	<tr>
                     <td style="border-bottom-width:0px;"><input name="is_sticky" id="is_sticky" type="checkbox"/> <label for="is_sticky"><?php _e('Sticky this message','wp_admin_blog'); ?></label></td>
                </tr>
                <?php } ?>
                <?php if ( current_user_can( 'use_wp_admin_microblog_bp' ) ) { ?>
                <tr>
                     <td style="border-bottom-width:0px;">
                         <input name="as_wp_post" id="as_wp_post" type="checkbox" onclick="javascript:wpam_showhide('span_headline')" />
                         <label for="as_wp_post"><?php _e('as WordPress blog post', 'wp_admin_blog');?></label>
                         <span style="display:none;" id="span_headline">&rarr; <label for="headline"><?php _e('Title', 'wp_admin_blog');?> </label>
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
    <p style="margin:7px; font-size:2px;">&nbsp;</p>
    <form name="all_messages" method="post">
    <table class="widefat">
    <thead>
        <tr id="wpam_table_messages_headline">
            <th colspan="2">
             <?php
              if ( $search != '' || $author != '' || $tag != '' || $rpl != '' ) {
                 echo __('Search Results', 'wp_admin_blog') . ' | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php">' . __('Show all','wp_admin_blog') . '</a>';
              }
              else {
                 echo __('Messages', 'wp_admin_blog');
              }
               ?>
            </th>
        </tr>
        
         <?php
         // Define sort_order
         if ( $sort_order === 'date' ) {
             $order_by = '`date` DESC';
         }
         else {
             $order_by = '`sort_date` DESC, `date` DESC';
         }
         // Load tags
         $tags = $wpdb->get_results("SELECT `tag_id`, `name` FROM `$admin_blog_tags`", ARRAY_A);
         // build SQL requests
         if ( $search != '' || $author != '' || $tag != '' ) {
            $select = "SELECT DISTINCT p.post_ID, p.post_parent, p.text, p.date, p.sort_date, p.last_edit, p.user, p.is_sticky FROM $admin_blog_posts p
                          LEFT JOIN $admin_blog_relations r ON r.post_ID = p.post_ID
                          LEFT JOIN $admin_blog_tags t ON t.tag_ID = r.tag_ID";
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
            $sql = "SELECT * FROM $admin_blog_posts WHERE `post_ID` = '$rpl' ORDER BY `post_ID` DESC LIMIT $message_limit, $number_messages";
            $test_sql = "SELECT `post_ID` FROM $admin_blog_posts WHERE `post_ID` = '$rpl'";
         }
         // Normal SQL
         else {
            if ( $rpl == 0 ) {
               $sql = "SELECT * FROM $admin_blog_posts WHERE `post_parent` = '0' ORDER BY `is_sticky` DESC, $order_by LIMIT $message_limit, $number_messages";
               $test_sql = "SELECT `post_ID` FROM $admin_blog_posts WHERE `post_parent` = '0'";  
            }
            else {
               $sql = "SELECT * FROM $admin_blog_posts ORDER BY `is_sticky` DESC, $order_by LIMIT $message_limit, $number_messages";
               $test_sql = "SELECT `post_ID` FROM $admin_blog_posts";
            }
         }
         // Find number of entries
         $test = $wpdb->query($test_sql);
         if ($test == 0) {
            echo '<tr><td>' . __('Sorry, no entries mached your criteria','wp_admin_blog') . '</td></tr>';
         }
         else {
            echo '<tr>';
            echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . wpam_page_menu($test, $number_messages, $curr_page, $message_limit, 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php', 'search=' . $search . '&amp;author=' . $author . '&amp;tag=' . $tag . '') . '</div></td>';
            echo '</tr>';
            // Entries
            $post = $wpdb->get_results($sql);
            if ( $test > 0 && $search == '' ) {
                 $where = '';
                 foreach ($post as $ids) {
                      $where = $where . "`post_parent` = '" . $ids->post_ID . "' or";
                 }
                 $where = substr($where, 0, -3);
                 $sql = "SELECT * FROM $admin_blog_posts WHERE $where ORDER BY `post_ID` ASC";
                 $replies = $wpdb->get_results($sql);
            }
            
            foreach ($post as $post) {
               $user_info = get_userdata($post->user);
               
               // sticky post options
               // change background color for sticky posts
               $class = 'wpam_normal';
               if ( $post->is_sticky == 1  ) {
                    $class = 'wpam_sticky';
               }
               
               // print messages
               $options['auto_reply'] = $auto_reply;
               $options['user'] = $user;
               echo '<tr class="' . $class . '">';
               echo '<td style="padding:10px 0 10px 10px; width:40px;"><span title="' . $user_info->display_name . ' (' . $user_info->user_login . ')">' . get_avatar($user_info->ID, 40) . '</span></td>';
               echo '<td style="padding:10px;">';
               echo wpam_screen::get_message($post, $tags, $user_info, $options);
               // print replies
               if ($search == '' && $tag == '') {
                    $r = '';
                    $str = "'";
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
                         if ( $reply->post_parent == $post->post_ID ) {
                              $count++;
                              $user_info = get_userdata($reply->user);
                              if ( $reply_number >= 3 && $reply_number - $count >= 3 && $rpl == 0 ) {
                                   $style = 'style="display:none;"';
                              }
                              else {
                                   $style = '';
                              }
                              $r = $r . '<tr id="wpam-reply-' . $post->post_ID . '-' . $count . '" ' . $style . '>';
                              $r = $r . '<td style="padding:10px 0 10px 10px; width:40px;"><span title="' . $user_info->display_name . ' (' . $user_info->user_login . ')">' . get_avatar($user_info->ID, 40) . '</span></td>';
                              $r = $r . '<td>' . wpam_screen::get_message($reply, $tags, $user_info, $options, 2) . '</td>';
                              $r = $r . '</tr>';
                         }
                    }
                    // show the number of replies text
                    if ( $count > 3 && $rpl == 0 ) {
                         echo '<table class="wpam-replies" id="wpam-reply-sum-' . $post->post_ID . '">';
                         echo '<tr><td style="padding:7px;"><a onclick="wpam_showAllReplies(' . $str . $post->post_ID . $str . ', ' . $str . $reply_number . $str . ')" style="cursor:pointer;" title="' . __('Show all replies','wp_admin_blog') . '">' . $count . ' ' . __('Replies','wp_admin_blog') . '</a></td></tr>';
                         echo '</table>';
                    }
                    // echo table of replies
                    echo '<table class="wpam-replies" id="wpam-replies-' . $post->post_ID . '">';
                    echo $r;
                    echo '</table>';
               }
               echo '</td>';
               echo '</tr>';
            }
            // Page Menu
            if ($test > $number_messages) {
               echo '<tr>';
               echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . wpam_page_menu($test, $number_messages, $curr_page, $message_limit, 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php', 'search=' . $search . '&amp;author=' . $author . '&amp;tag=' . $tag . '', 'bottom') . '</td>';
               echo '</tr>';
            }
         }
       ?>
    </thead>
    </table>
    </form>
    <?php if ( $auto_reload_enabled == 'true' ) { ?>
    <script type="text/javascript" charset="utf-8" id="wpam_auto_reload_interval">
        jQuery(document).ready(function($) {
            $.ajaxSetup({ cache: false });
            setInterval(function() {
                var p_id = <?php echo $wpdb->get_var("SELECT `post_ID` FROM $admin_blog_posts ORDER BY `post_ID` DESC LIMIT 0,1"); ?>;
                $.get("<?php echo WP_PLUGIN_URL . '/wp-admin-microblog/ajax.php' ;?>?p_id=" + p_id, 
                function(text){
                    if ( text !== '' ) {
                        var ret;
                        var current = $('#wpam_table_messages_headline').next();
                        current.attr('id', 'new_messages_info');
                        ret = ret + '<td id="wpam_new_messages_info" colspan="4"><a href="" title="<?php _e('Click for refresh','wp_admin_blog'); ?>">' + text + '</a></td>';
                        current.html(ret);
                    }
                });
            }, <?php echo $auto_reload_interval; ?>);
        });
    </script>
    <?php } ?>
    </div>
    </div>
    <?php
}
?>