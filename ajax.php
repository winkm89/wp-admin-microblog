<?php
/**
 * This file contains the server side part for the teachpress ajax interface
 * @package wpam
 * @since 2.3
 */
require_once( '../../../wp-load.php' );

// New message check
if ( is_user_logged_in() && isset( $_GET['p_id'] ) ) {
    wpam_ajax::new_message_check($_GET['p_id']);
}

// Edit message
if ( is_user_logged_in() && isset( $_GET['edit_id'] ) ) {
    wpam_ajax::get_message_text_for_edit($_GET['edit_id']);
}

// Add like
if ( is_user_logged_in() && isset( $_GET['add_like_id'] ) ) {
    wpam_ajax::add_like($_GET['add_like_id']);
}

// Show likes
if ( is_user_logged_in() && isset( $_GET['like_id'] ) ) {
    wpam_ajax::show_likes($_GET['like_id']);
}