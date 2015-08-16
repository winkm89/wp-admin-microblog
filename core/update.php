<?php

/**
 * WPAM Update class
 * @since 2.3
 */
class wpam_update {
    
    /**
    * Updater
    * 
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
            self::update_to_12();
            $level = 1;
        }
      
        // Update to database version 2.1.0
        if ( $level === 1 || $db_version[0] === '1' ) {
            self::update_to_21($charset_collate);
            $level = 2;
        }
        
        // Update to database version 2.3.0
        if ( $level === 2 || $db_version[0] === '2' ) {
            self::update_to_23();
            $level = 3;
        }
        
        // Update to database version 3.0
        if ( $level === 3 || $db_version[0] === '3' ) {
            self::update_to_30($charset_collate);
            $level = 4;
        }
        
        update_option('wp_admin_blog_version', $software_version);
    }
   
    /**
     * Update database to version 1.2
     * @since 2.3
     */
    private static function update_to_12 () {
        global $wpdb;
        add_option('wp_admin_blog_version', '1.2.0', '', 'no');
        // Add is_sticky column
        if ($wpdb->query("SHOW COLUMNS FROM " . WPAM_ADMIN_BLOG_POSTS . " LIKE 'is_sticky'") == '0') { 
            $wpdb->query("ALTER TABLE " . WPAM_ADMIN_BLOG_POSTS . " ADD `is_sticky` INT NULL AFTER `user`");
        }
    }
    
    /**
     * Update database to version 2.1
     * @param string $charset_collate
     * @since 2.3
     */
    private static function update_to_21 ($charset_collate) {
        // Add meta table 
        wpam_tables::add_meta_table($charset_collate);

        // Delete old options
        delete_option('wp_admin_blog_name');
        delete_option('wp_admin_blog_name_widget');
        delete_option('wp_admin_number_tags');
        delete_option('wp_admin_number_messages');
        delete_option('wp_admin_auto_reply');
        delete_option('wp_admin_media_upload');

    }
    
    /**
     * Update database to version 2.3
     * @param string $charset_collate
     * @since 2.3
     */
    private static function update_to_23 () {
        global $wpdb;
        // add column sort_date
        if ($wpdb->query("SHOW COLUMNS FROM " . WPAM_ADMIN_BLOG_POSTS . " LIKE 'sort_date'") == '0') { 
            $wpdb->query("ALTER TABLE " . WPAM_ADMIN_BLOG_POSTS . " ADD `sort_date` DATETIME NULL DEFAULT NULL AFTER `date`");
        }
        // add column last_edit
        if ($wpdb->query("SHOW COLUMNS FROM " . WPAM_ADMIN_BLOG_POSTS . " LIKE 'last_edit'") == '0') { 
            $wpdb->query("ALTER TABLE " . WPAM_ADMIN_BLOG_POSTS . " ADD `last_edit` DATETIME NULL DEFAULT NULL AFTER `sort_date`");
        }
        if ( wpam_get_options('auto_reload_interval') === false ) {
            $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`, `value`, `category`) VALUES ('auto_reload_interval', '60000', 'system')");
        }
        if ( wpam_get_options('auto_reload_enabled') === false ) {
            $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`, `value`, `category`) VALUES ('auto_reload_enabled', 'true', 'system')");
        }
        $wpdb->query("ALTER TABLE " . WPAM_ADMIN_BLOG_POSTS . " ENGINE = INNODB");
        $wpdb->query("ALTER TABLE " . WPAM_ADMIN_BLOG_META . " ENGINE = INNODB");
        $wpdb->query("ALTER TABLE " . WPAM_ADMIN_BLOG_RELATIONS . " ENGINE = INNODB");
        $wpdb->query("ALTER TABLE " . WPAM_ADMIN_BLOG_TAGS . " ENGINE = INNODB");
    }
    
    /**
     * Update database to version 3.0
     * @param string $charset_collate
     * @since 3.0
     */
    private static function update_to_30 ($charset_collate) {
        wpam_tables::add_like_table($charset_collate);
    }
    
}

