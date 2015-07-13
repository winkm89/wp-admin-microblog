<?php

/**
 * WPAM Update class
 * @since 2.3
 */
class wpam_update {
    
    /**
    * Updater
    * 
    * @global string $admin_blog_posts
    * @global class $wpdb
    * @since 1.2.0
    */
    public static function force_update() {
        global $wpdb;
        $db_version = get_option('wp_admin_blog_version');
        $software_version = wpam_get_version();
        $level = 0;

        // Do nothing
        if ( $db_version == $software_version ) {
            return;
        }
      
        // charset & collate like WordPress
        $charset_collate = '';
        if ( ! empty($wpdb->charset) ) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }	
        if ( ! empty($wpdb->collate) ) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }	

      
        // Update to database version 1.2.0
        if ( $db_version === false ) {
            wpam_update::update_to_12();
            $level = 1;
        }
      
        // Update to database version 2.1.0
        if ( $level === 1 || $db_version[0] === '1' ) {
            wpam_update::update_to_21($charset_collate);
            $level = 2;
        }
        
        // Update to database version 2.3.0
        if ( $level === 2 || $db_version[0] === '2' ) {
            wpam_update::update_to_23();
            $level = 3;
        }
        
        update_option('wp_admin_blog_version', $software_version);
    }
   
    /**
     * Update database to version 1.2
     * @since 2.3
     */
    private static function update_to_12 () {
        global $admin_blog_posts;
        global $wpdb;
        add_option('wp_admin_blog_version', '1.2.0', '', 'no');
        // Add is_sticky column
        if ($wpdb->query("SHOW COLUMNS FROM " . $admin_blog_posts . " LIKE 'is_sticky'") == '0') { 
            $wpdb->query("ALTER TABLE " . $admin_blog_posts . " ADD `is_sticky` INT NULL AFTER `user`");
        }
    }
    
    /**
     * Update database to version 2.1
     * @param string $charset_collate
     * @since 2.3
     */
    private static function update_to_21 ($charset_collate) {
        global $wpdb;
        global $admin_blog_meta;
        // Add meta table 
        if ($wpdb->get_var("SHOW TABLES LIKE '$admin_blog_meta'") != $admin_blog_meta) {
            $sql = "CREATE TABLE " . $admin_blog_meta . " (
                                `meta_ID` INT UNSIGNED AUTO_INCREMENT ,
                                `variable` VARCHAR (200) ,
                                `value` LONGTEXT ,
                                `category` VARCHAR (200) ,
                                PRIMARY KEY (meta_ID)
                            ) $charset_collate;";
            $wpdb->query($sql);
            $wpdb->query("INSERT INTO " . $admin_blog_meta . " (`variable`, `value`, `category`) VALUES ('blog_name', 'Microblog', 'system')");
            $wpdb->query("INSERT INTO " . $admin_blog_meta . " (`variable`, `value`, `category`) VALUES ('blog_name_widget', 'Microblog', 'system')");
            $wpdb->query("INSERT INTO " . $admin_blog_meta . " (`variable`, `value`, `category`) VALUES ('auto_reply', 'false', 'system')");
            $wpdb->query("INSERT INTO " . $admin_blog_meta . " (`variable`, `value`, `category`) VALUES ('media_upload', 'false', 'system')");
            $wpdb->query("INSERT INTO " . $admin_blog_meta . " (`variable`, `value`, `category`) VALUES ('sticky_for_dash', 'false', 'system')");
            $wpdb->query("INSERT INTO " . $admin_blog_meta . " (`variable`, `value`, `category`) VALUES ('auto_notifications', 'false', 'system')");
            delete_option('wp_admin_blog_name');
            delete_option('wp_admin_blog_name_widget');
            delete_option('wp_admin_number_tags');
            delete_option('wp_admin_number_messages');
            delete_option('wp_admin_auto_reply');
            delete_option('wp_admin_media_upload');
        }
    }
    
    /**
     * Update database to version 2.3
     * @param string $charset_collate
     * @since 2.3
     */
    private static function update_to_23 () {
        global $wpdb;
        global $admin_blog_posts;
        global $admin_blog_meta;
        global $admin_blog_relations;
        global $admin_blog_tags;
        // add column sort_date
        if ($wpdb->query("SHOW COLUMNS FROM " . $admin_blog_posts . " LIKE 'sort_date'") == '0') { 
            $wpdb->query("ALTER TABLE " . $admin_blog_posts . " ADD `sort_date` DATETIME NULL DEFAULT NULL AFTER `date`");
        }
        // add column last_edit
        if ($wpdb->query("SHOW COLUMNS FROM " . $admin_blog_posts . " LIKE 'last_edit'") == '0') { 
            $wpdb->query("ALTER TABLE " . $admin_blog_posts . " ADD `last_edit` DATETIME NULL DEFAULT NULL AFTER `sort_date`");
        }
        if ( wpam_get_options('auto_reload_interval') === false ) {
            $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (`variable`, `value`, `category`) VALUES ('auto_reload_interval', '60000', 'system')");
        }
        if ( wpam_get_options('auto_reload_enabled') === false ) {
            $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (`variable`, `value`, `category`) VALUES ('auto_reload_enabled', 'true', 'system')");
        }
        $wpdb->query("ALTER TABLE " . $admin_blog_posts . " ENGINE = INNODB");
        $wpdb->query("ALTER TABLE " . $admin_blog_meta . " ENGINE = INNODB");
        $wpdb->query("ALTER TABLE " . $admin_blog_relations . " ENGINE = INNODB");
        $wpdb->query("ALTER TABLE " . $admin_blog_tags . " ENGINE = INNODB");
    }
    
}

