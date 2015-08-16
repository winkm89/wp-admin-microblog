<?php
/**
 * This file contains all functions for creating a database for WP Admin Microblog
 * 
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 3.0
 */

/**
 * This class contains all functions for creating a database for WP Admin Microblog
 * @since 3.0
 */
class wpam_tables {
    
    /**
     * Install WPAM database tables
     * @since 3.0
     */
    public static function create() {
        $charset_collate = self::get_charset();
        
        self::add_capabilities();
        
        self::add_posts_table($charset_collate);
        self::add_tags_table($charset_collate);
        self::add_relation_table($charset_collate);
        self::add_like_table($charset_collate);
        self::add_meta_table($charset_collate);
        
    }
    
    /**
     * Creates the table wp_admin_microblog_posts
     * @global class $wpdb
     * @param string $charset_collate
     * @since 3.0
     */
    public static function add_posts_table ($charset_collate) {
        global $wpdb;
        
        if($wpdb->get_var("SHOW TABLES LIKE '" . WPAM_ADMIN_BLOG_POSTS . "'") == WPAM_ADMIN_BLOG_POSTS) {
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta( "CREATE TABLE " . WPAM_ADMIN_BLOG_POSTS . " (
                    `post_ID` INT UNSIGNED AUTO_INCREMENT ,
                    `post_parent` INT ,
                    `text` LONGTEXT ,
                    `date` DATETIME ,
                    `sort_date` DATETIME,
                    `last_edit` DATETIME,
                    `user` INT ,
                    `is_sticky` INT ,
                    PRIMARY KEY (post_ID)
              ) $charset_collate;");
        
        // test engine
        self::change_engine(WPAM_ADMIN_BLOG_POSTS);

    }
    
    /**
     * Creates the table wp_admin_microblog_tags
     * @global class $wpdb
     * @param string $charset_collate
     * @since 3.0
     */
    public static function add_tags_table ($charset_collate) {
        global $wpdb;
        
        if($wpdb->get_var("SHOW TABLES LIKE '" . WPAM_ADMIN_BLOG_TAGS . "'") == WPAM_ADMIN_BLOG_TAGS) {
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta( "CREATE TABLE " . WPAM_ADMIN_BLOG_TAGS . " (
                    `tag_ID` INT UNSIGNED AUTO_INCREMENT ,
                    `name` VARCHAR (200) ,
                    PRIMARY KEY (tag_ID)
                ) $charset_collate;");
        
        // test engine
        self::change_engine(WPAM_ADMIN_BLOG_TAGS);
    }
    
    /**
     * Creates the table wp_admin_microblog_relation
     * @global class $wpdb
     * @param string $charset_collate
     * @since 3.0
     */
    public static function add_relation_table ($charset_collate) {
        global $wpdb;
        
        if($wpdb->get_var("SHOW TABLES LIKE '" . WPAM_ADMIN_BLOG_RELATIONS . "'") == WPAM_ADMIN_BLOG_RELATIONS) {
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta( "CREATE TABLE " . WPAM_ADMIN_BLOG_RELATIONS . " (
                    `rel_ID` INT UNSIGNED AUTO_INCREMENT ,
                    `post_ID` INT ,
                    `tag_ID` INT ,
                    PRIMARY KEY (rel_ID)
                ) $charset_collate;");
        
        // test engine
        self::change_engine(WPAM_ADMIN_BLOG_RELATIONS);
    }
    
     /**
     * Creates the table wp_admin_microblog_likes
     * @global class $wpdb
     * @param string $charset_collate
     * @since 3.0
     */
    public static function add_like_table ($charset_collate) {
        global $wpdb;
        
        
        if($wpdb->get_var("SHOW TABLES LIKE '" . WPAM_ADMIN_BLOG_LIKES . "'") == WPAM_ADMIN_BLOG_LIKES) {
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql = "CREATE TABLE " . WPAM_ADMIN_BLOG_LIKES . " (
                    `rel_ID` INT UNSIGNED AUTO_INCREMENT ,
                    `post_ID` INT ,
                    `user_ID` INT ,
                    PRIMARY KEY (rel_ID)
                ) $charset_collate;");
        
        // test engine
        self::change_engine(WPAM_ADMIN_BLOG_LIKES);
    }
    
    /**
     * Creates the table wp_admin_microblog_meta
     * @global class $wpdb
     * @param string $charset_collate
     * @since 3.0
     */
    public static function add_meta_table ($charset_collate) {
        global $wpdb;
        
        if($wpdb->get_var("SHOW TABLES LIKE '" . WPAM_ADMIN_BLOG_META . "'") == WPAM_ADMIN_BLOG_META) {
            return;
        }
        
        $version = wpam_get_version();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta( "CREATE TABLE " . WPAM_ADMIN_BLOG_META . " (
                    `meta_ID` INT UNSIGNED AUTO_INCREMENT ,
                    `variable` VARCHAR (200) ,
                    `value` LONGTEXT ,
                    `category` VARCHAR (200) ,
                    PRIMARY KEY (meta_ID)
                ) $charset_collate;");		
        
        $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`,`value`,`category`) VALUES ('blog_name','Microblog','system')");
        $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`,`value`,`category`) VALUES ('blog_name_widget','Microblog','system')");
        $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`,`value`,`category`) VALUES ('auto_reply','false','system')");
        $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`,`value`,`category`) VALUES ('auto_reload_interval','60000','system')");
        $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`,`value`,`category`) VALUES ('auto_reload_enabled','true','system')");
        $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`,`value`,`category`) VALUES ('media_upload','false','system')");
        $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`,`value`,`category`) VALUES ('sticky_for_dash','','system')");
        $wpdb->query("INSERT INTO " . WPAM_ADMIN_BLOG_META . " (`variable`,`value`,`category`) VALUES ('auto_notifications','','system')");
   
        // test engine
        self::change_engine(WPAM_ADMIN_BLOG_META);
        
        if ( !get_option('wp_admin_blog_version') ) {
            add_option('wp_admin_blog_version', $version, '', 'no');
        }
    }
    
    /**
     * charset & collate like WordPress
     * @since 3.0
     */
    public static function get_charset() {
        global $wpdb; 
        $charset_collate = '';
        if ( ! empty($wpdb->charset) ) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }	
        if ( ! empty($wpdb->collate) ) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }
        $charset_collate .= " ENGINE = INNODB";
        return $charset_collate;
    }
    
    /**
     * Adds capabilities
     * @since 3.0
     */
    private static function add_capabilities() {
        // Add capabilities
        global $wp_roles;
        $role = $wp_roles->get_role('administrator');
        if ( !$role->has_cap('use_wp_admin_microblog') ) {
           $wp_roles->add_cap('administrator', 'use_wp_admin_microblog');
        }
        if ( !$role->has_cap('use_wp_admin_microblog_bp') ) {
           $wp_roles->add_cap('administrator', 'use_wp_admin_microblog_bp');
        }
        if ( !$role->has_cap('use_wp_admin_microblog_sticky') ) {
           $wp_roles->add_cap('administrator', 'use_wp_admin_microblog_sticky');
        }
    }

        /**
     * Returns an associative array with table status informations (Name, Engine, Version, Rows,...)
     * @param string $table
     * @return array
     * @since 3.0
     */
    public static function check_table_status($table){
        global $wpdb;
        return $wpdb->get_row("SHOW TABLE STATUS FROM " . DB_NAME . " WHERE `Name` = '$table'", ARRAY_A);
    }
    
    /**
     * Tests if the engine for the selected table is InnoDB. If not, the function changes the engine.
     * @param string $table
     * @since 3.0
     * @access private
     */
    private static function change_engine($table){
        global $wpdb;
        $db_info = self::check_table_status($table);
        if ( $db_info['Engine'] != 'InnoDB' ) {
            $wpdb->query("ALTER TABLE " . $table . " ENGINE = INNODB");
        }
    }
    
    /**
     * Remoces the database tables
     * @global class $wpdb
     * @since 3.0
     */
    public static function remove() {
        global $wpdb;
        $wpdb->query("SET FOREIGN_KEY_CHECKS=0");
        $wpdb->query("DROP TABLE " . WPAM_ADMIN_BLOG_POSTS . ", " . WPAM_ADMIN_BLOG_TAGS . ", " . WPAM_ADMIN_BLOG_RELATIONS . ", " . WPAM_ADMIN_BLOG_LIKES . ", " . WPAM_ADMIN_BLOG_META . "");
        $wpdb->query("SET FOREIGN_KEY_CHECKS=1");
        delete_option('wp_admin_blog_version');
    }
}


