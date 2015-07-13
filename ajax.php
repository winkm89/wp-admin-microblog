<?php
/**
 * This file contains the server side part for the teachpress ajax interface
 * @package wpam
 * @since 2.3
 */
require_once( '../../../wp-load.php' );

if ( is_user_logged_in() && isset( $_GET['p_id'] ) ) {
    $p_id = htmlspecialchars($_GET['p_id']);
    global $admin_blog_posts;
    global $wpdb;
    $test = intval( $wpdb->get_var("SELECT COUNT(*) FROM $admin_blog_posts WHERE `post_ID` > '$p_id'") );
    if ( $test !== 0 ) {
        echo __('New messages available','wp_admin_blog');
    }
}