<?php
/**
 * Update settings
 * @param array $option
 * @param array $roles
 * @param array $blog_post
 * @param array $sticky 
 */
function wpam_update_options ($option, $roles, $blog_post, $sticky) {
   global $wp_roles;
   global $wpdb;
   
   // Roles
   if ( empty($roles) || ! is_array($roles) ) { 
      $roles = array(); 
   }
   $who_can = $roles;
   $who_cannot = array_diff( array_keys($wp_roles->role_names), $roles);
   foreach ($who_can as $role) {
      $wp_roles->add_cap($role, 'use_wp_admin_microblog');
   }
   foreach ($who_cannot as $role) {
      $wp_roles->remove_cap($role, 'use_wp_admin_microblog');
   }
   
   // Roles for message as a blop post
   if ( empty($blog_post) || ! is_array($blog_post) ) { 
      $blog_post = array(); 
   }
   $who_can = $blog_post;
   $who_cannot = array_diff( array_keys($wp_roles->role_names), $blog_post);
   foreach ($who_can as $role) {
      $wp_roles->add_cap($role, 'use_wp_admin_microblog_bp');
   }
   foreach ($who_cannot as $role) {
      $wp_roles->remove_cap($role, 'use_wp_admin_microblog_bp');
   }
   
   // Roles for sticky message options
   if ( empty($sticky) || ! is_array($sticky) ) { 
      $sticky = array(); 
   }
   $who_can = $sticky;
   $who_cannot = array_diff( array_keys($wp_roles->role_names), $sticky);
   foreach ($who_can as $role) {
      $wp_roles->add_cap($role, 'use_wp_admin_microblog_sticky');
   }
   foreach ($who_cannot as $role) {
      $wp_roles->remove_cap($role, 'use_wp_admin_microblog_sticky');
   }
   
   // Update system values
   $update = "UPDATE " . WPAM_ADMIN_BLOG_META . " SET `value` = '" . $option['name_blog'] . "' WHERE `variable` = 'blog_name'";
   $wpdb->query( $update );
   $update = "UPDATE " . WPAM_ADMIN_BLOG_META . " SET `value` = '" . $option['name_widget'] . "' WHERE `variable` = 'blog_name_widget'";
   $wpdb->query( $update );
   $update = "UPDATE " . WPAM_ADMIN_BLOG_META . " SET `value` = '" . $option['auto_reply'] . "' WHERE `variable` = 'auto_reply'";
   $wpdb->query( $update );
   $update = "UPDATE " . WPAM_ADMIN_BLOG_META . " SET `value` = '" . $option['auto_reload_interval'] . "' WHERE `variable` = 'auto_reload_interval'";
   $wpdb->query( $update );
   $update = "UPDATE " . WPAM_ADMIN_BLOG_META . " SET `value` = '" . $option['auto_reload_enabled'] . "' WHERE `variable` = 'auto_reload_enabled'";
   $wpdb->query( $update );
   $update = "UPDATE " . WPAM_ADMIN_BLOG_META . " SET `value` = '" . $option['media_upload'] . "' WHERE `variable` = 'media_upload'";
   $wpdb->query( $update );
   $update = "UPDATE " . WPAM_ADMIN_BLOG_META . " SET `value` = '" . $option['sticky_for_dash'] . "' WHERE `variable` = 'sticky_for_dash'";
   $wpdb->query( $update );
   $update = "UPDATE " . WPAM_ADMIN_BLOG_META . " SET `value` = '" . $option['auto_notifications'] . "' WHERE `variable` = 'auto_notifications'";
   $wpdb->query( $update );
} 

/**
 * Settings Page
 */
function wpam_settings () {
     // run the updater
     wpam_update::force_update();
     
     if ( isset($_POST['save']) ) {
          $option = array(
            'auto_reply' => htmlspecialchars($_POST['auto_reply']),
            'auto_reload_interval' => htmlspecialchars($_POST['auto_reload_interval']),
            'auto_reload_enabled' => ( isset( $_POST['auto_reload_enabled'] ) == 'true' ) ? 'true' : 'false',
            'media_upload' => htmlspecialchars($_POST['media_upload']),
            'name_blog' => htmlspecialchars($_POST['name_blog']),
            'name_widget' => htmlspecialchars($_POST['name_widget']),
            'sticky_for_dash' => htmlspecialchars($_POST['sticky_for_dash']),
            'auto_notifications' => htmlspecialchars($_POST['auto_notifications'])  
          );
          $userrole = $_POST['userrole'];
          $blog_post = $_POST['blog_post'];
          $sticky = $_POST['sticky'];
          wpam_update_options($option, $userrole, $blog_post, $sticky);
          echo '<div class="updated"><p>' . __('Settings are changed. Please note that access changes are visible, until you have reloaded this page a secont time.','wp-admin-microblog') . '</p></div>';
     }

     // load system settings
     $auto_reply = false;
     $auto_reload_interval = 60000;
     $auto_reload_enabled = true;
     $name_blog = 'Microblog';
     $name_widget = 'Microblog';
     $media_upload = false;
     $sticky_for_dash = false;
     $auto_notifications = '';
     
     $system = wpam_get_options('','system');
     foreach ($system as $system) {
        if ( $system['variable'] == 'auto_reply' ) { $auto_reply = $system['value']; }
        if ( $system['variable'] == 'auto_reload_interval' ) { $auto_reload_interval = $system['value']; }
        if ( $system['variable'] == 'auto_reload_enabled' ) { $auto_reload_enabled = $system['value']; }
        if ( $system['variable'] == 'blog_name' ) { $name_blog = $system['value']; }
        if ( $system['variable'] == 'blog_name_widget' ) { $name_widget = $system['value']; }
        if ( $system['variable'] == 'media_upload' ) { $media_upload = $system['value']; }
        if ( $system['variable'] == 'sticky_for_dash' ) { $sticky_for_dash = $system['value']; }
        if ( $system['variable'] == 'auto_notifications' ) { $auto_notifications = $system['value']; }
     }
     ?>
     <div class="wrap">
     <h2><?php _e('WP Admin Microblog Settings','wp-admin-microblog'); ?></h2>
     <form name="form1" id="form1" method="post" action="admin.php?page=wp-admin-microblog/settings.php">
     <input name="page" type="hidden" value="wp-admin-blog" />
     <h3><?php _e('General','wp-admin-microblog'); ?></h3>
     <table class="form-table">
        <tr>
             <th scope="row"><?php _e('Name of the Microblog','wp-admin-microblog'); ?></th>
             <td style="width: 330px;"><input name="name_blog" type="text" value="<?php echo $name_blog; ?>" size="35" /></td>
             <td><em><?php _e('Default: Microblog','wp-admin-microblog'); ?></em></td>
        </tr>
        <tr>
             <th scope="row"><?php _e('Name of the dashboard widget','wp-admin-microblog'); ?></th>
             <td><input name="name_widget" type="text" value="<?php echo $name_widget; ?>" size="35" /></td>
             <td><em><?php _e('Default: Microblog','wp-admin-microblog'); ?></em></td>
        </tr>
         <tr>
             <th scope="row"><?php _e('Media upload for the dashboard widget','wp-admin-microblog'); ?></th>
             <td><select name="media_upload">
                <?php
                if ($media_upload != false) {
                    echo '<option value="true" selected="selected">' . __('yes','wp-admin-microblog') . '</option>';
                    echo '<option value="false">' . __('no','wp-admin-microblog') . '</option>';
                }
                else {
                    echo '<option value="true">' . __('yes','wp-admin-microblog') . '</option>';
                    echo '<option value="false" selected="selected">' . __('no','wp-admin-microblog') . '</option>';
                } 
                ?>
             </select></td>
             <td><em><?php _e('Activate this option to use the media upload for the WP Admin Microblog dashboard widget. If you use it, please notify, that the media upload will not work correctly for QuickPress.','wp-admin-microblog'); ?></em></td>
         </tr>
         <tr>
             <th scope="row"><?php _e('Auto check interval','wp-admin-microblog'); ?></th>
             <td><input name="auto_reload_interval" type="text" value="<?php echo $auto_reload_interval; ?>" size="35" /></td>
             <td><em><?php _e('Use this option to modify the interval in which the plugin checks for new messages. A smaller value needs more server performance. The default value is 60000 ms.','wp-admin-microblog'); ?></em></td>
        </tr>
        <tr>
             <th scope="row"></th>
             <?php $checked = ( $auto_reload_enabled == 'true' ) ? 'checked="checked"' : '';?>
             <td><input name="auto_reload_enabled" id="auto_reload_enabled" type="checkbox" value="true" <?php echo $checked;?> /><label for="auto_reload_enabled"><?php _e('Enable auto check','wp-admin-microblog'); ?></label></td>
             <td></td>
        </tr>
     </table>
     <h3><?php _e('Access','wp-admin-microblog'); ?></h3>
     <table class="form-table">
         <tr>
              <th scope="row"><?php _e('Access for','wp-admin-microblog'); ?></th>
              <td style="width: 330px;">
              <select name="userrole[]" id="userrole" multiple="multiple" style="height:80px;">
                  <?php
                   global $wp_roles;
                   foreach ($wp_roles->role_names as $roledex => $rolename) {
                       $role = $wp_roles->get_role($roledex);
                       $select = $role->has_cap('use_wp_admin_microblog') ? 'selected="selected"' : '';
                       echo '<option value="'.$roledex.'" '.$select.'>'.$rolename.'</option>';
                   }
                   ?>
              </select>
              </td>
              <td><em><?php _e('Select each user role which has access to WP Admin Microblog.','wp-admin-microblog'); ?><br /><?php _e('Use &lt;Ctrl&gt; key to select more than one.','wp-admin-microblog'); ?></em></td>
         </tr>
         <tr>
              <th scope="row"><?php _e('"Message as a blog post"-function for','wp-admin-microblog'); ?></th>
              <td>
              <select name="blog_post[]" id="blog_post" multiple="multiple" style="height:80px;">
                  <?php
                   foreach ($wp_roles->role_names as $roledex => $rolename) {
                       $role = $wp_roles->get_role($roledex);
                       $select = $role->has_cap('use_wp_admin_microblog_bp') ? 'selected="selected"' : '';
                       echo '<option value="'.$roledex.'" '.$select.'>'.$rolename.'</option>';
                   }
                   ?>
              </select>
              </td>
              <td><em><?php _e('Select each user role which can use the "Message as a blog post"-function.','wp-admin-microblog'); ?><br /><?php _e('Use &lt;Ctrl&gt; key to select more than one.','wp-admin-microblog'); ?></em></td>
         </tr>
     </table>
     <h3><?php _e('Notifications','wp-admin-microblog'); ?></h3>
     <table class="form-table">
          <tr>
             <th scope="row"><?php _e('Auto replies','wp-admin-microblog'); ?></th>
             <td style="width: 330px;"><select name="auto_reply">
                <?php
                if ($auto_reply == 'true') {
                echo '<option value="true" selected="selected">' . __('yes','wp-admin-microblog') . '</option>';
                     echo '<option value="false">' . __('no','wp-admin-microblog') . '</option>';
                }
                else {
                     echo '<option value="true">' . __('yes','wp-admin-microblog') . '</option>';
                     echo '<option value="false" selected="selected">' . __('no','wp-admin-microblog') . '</option>';
                } 
                ?>
             </select></td>
             <td><em><?php _e('Activate this option and the plugin insert in every reply the string for an e-mail notification to the message author.','wp-admin-microblog'); ?></em></td>
         </tr>
         <tr>
             <th><?php _e('Auto notifications','wp-admin-microblog'); ?></th>
             <td><textarea id="auto_notifications" name="auto_notifications" style="width: 100%;" rows="5"><?php echo $auto_notifications; ?></textarea></td>
             <td><em><?php _e('Insert your email address, if you want to receive notifications for every new message in the microblog. Insert one email address per line.','wp-admin-microblog'); ?></em></td></td>
         </tr>
     </table>
     <h3><?php _e('Sticky messages','wp-admin-microblog'); ?></h3>
     <table class="form-table">
         <tr>
              <th scope="row"><?php _e('"Sticky messages"-function for','wp-admin-microblog'); ?></th>
              <td style="width: 330px;">
                   <select name="sticky[]" id="sticky" multiple="multiple" style="height:80px;">
                        <?php
                        foreach ($wp_roles->role_names as $roledex => $rolename) {
                            $role = $wp_roles->get_role($roledex);
                            $select = $role->has_cap('use_wp_admin_microblog_sticky') ? 'selected="selected"' : '';
                            echo '<option value="'.$roledex.'" '.$select.'>'.$rolename.'</option>';
                        }
                        ?>
                   </select>
              </td>
              <td><em><?php _e('Select each user role which can add sticky messages.', 'wp-admin-microblog'); ?><br /><?php _e('Use &lt;Ctrl&gt; key to select more than one.','wp-admin-microblog'); ?></em></td>
         </tr> 
         <tr>
              <th scope="row"><?php _e('Sticky messages for the dashboard widget','wp-admin-microblog'); ?></th>
              <td>
                   <select name="sticky_for_dash">
                     <?php
                     if ($sticky_for_dash == 'true') {
                         echo '<option value="true" selected="selected">' . __('yes','wp-admin-microblog') . '</option>';
                         echo '<option value="false">' . __('no','wp-admin-microblog') . '</option>';
                     }
                     else {
                         echo '<option value="true">' . __('yes','wp-admin-microblog') . '</option>';
                         echo '<option value="false" selected="selected">' . __('no','wp-admin-microblog') . '</option>';
                     } 
                     ?>
                   </select>
              </td>
              <td><em><?php _e('Select `yes` to display sticky messages in the dashboard widget.','wp-admin-microblog'); ?></em></td>
         </tr>
     </table>
     <p class="submit">
     <input type="submit" name="save" id="save" class="button-primary" value="<?php _e('Save Changes', 'wp-admin-microblog') ?>" />
     </p>
     </form>
     </div>
     <?php
}
