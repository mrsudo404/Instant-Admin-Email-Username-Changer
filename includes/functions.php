<?php
/**
 * Core functions for Instant Admin Email & Username Changer
 *
 * @package Instant_Admin_Username_Changer
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Change admin email instantly
 *
 * @param string $new_email The new admin email address
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function iauc_change_admin_email( $new_email ) {
    global $wpdb;

    // Validate email
    if ( ! is_email( $new_email ) ) {
        return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'instant-admin-username-changer' ) );
    }

    // Check if email is already in use
    $existing_user = get_user_by( 'email', $new_email );
    if ( $existing_user && $existing_user->ID !== 1 ) {
        return new WP_Error( 'email_exists', __( 'This email address is already in use by another user.', 'instant-admin-username-changer' ) );
    }

    // Update admin email option
    $result = update_option( 'admin_email', $new_email );
    
    if ( ! $result ) {
        return new WP_Error( 'update_failed', __( 'Failed to update admin email.', 'instant-admin-username-changer' ) );
    }

    // Clear any pending email change
    delete_option( 'new_admin_email' );

    // Update the admin user's email if it's user ID 1
    $admin_user = get_user_by( 'id', 1 );
    if ( $admin_user ) {
        wp_update_user( array(
            'ID'    => 1,
            'user_email' => $new_email
        ) );
    }

    // Log the change
    iauc_log_admin_email_change( $new_email );

    return true;
}

/**
 * Change username for a specific user
 *
 * @param int    $user_id     The user ID
 * @param string $new_username The new username
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function iauc_change_username( $user_id, $new_username ) {
    // Validate user ID
    $user = get_user_by( 'id', $user_id );
    if ( ! $user ) {
        return new WP_Error( 'invalid_user', __( 'Invalid user ID.', 'instant-admin-username-changer' ) );
    }

    // Validate username
    if ( empty( $new_username ) || ! validate_username( $new_username ) ) {
        return new WP_Error( 'invalid_username', __( 'Invalid username format.', 'instant-admin-username-changer' ) );
    }

    // Check if username already exists
    if ( username_exists( $new_username ) && get_user_by( 'login', $new_username )->ID !== $user_id ) {
        return new WP_Error( 'username_exists', __( 'This username is already in use.', 'instant-admin-username-changer' ) );
    }

    // Store old username for logging
    $old_username = $user->user_login;

    // Update username using WordPress functions
    $result = wp_update_user( array(
        'ID' => $user_id,
        'user_login' => $new_username
    ) );

    if ( is_wp_error( $result ) ) {
        return new WP_Error( 'update_failed', __( 'Failed to update username in database.', 'instant-admin-username-changer' ) );
    }

    // Clear user cache
    clean_user_cache( $user_id );

    // Log the change
    iauc_log_username_change( $user_id, $old_username, $new_username );

    return true;
}

/**
 * Get all users for dropdown
 *
 * @return array Array of users with ID and display name
 */
function iauc_get_users_for_dropdown() {
    $users = get_users( array(
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'fields'  => array( 'ID', 'user_login', 'display_name', 'user_email' )
    ) );

    $user_list = array();
    foreach ( $users as $user ) {
        $user_list[ $user->ID ] = sprintf(
            '%s (%s) - %s',
            $user->display_name,
            $user->user_login,
            $user->user_email
        );
    }

    return $user_list;
}

/**
 * Log admin email change
 *
 * @param string $new_email The new email address
 */
function iauc_log_admin_email_change( $new_email ) {
    $current_user = wp_get_current_user();
    $log_entry = sprintf(
        '[%s] Admin email changed from %s to %s by user %s (ID: %d)',
        current_time( 'Y-m-d H:i:s' ),
        get_option( 'admin_email' ),
        $new_email,
        $current_user->user_login,
        $current_user->ID
    );

    // Log only in debug mode
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        error_log( 'IAUC: ' . $log_entry );
    }
}

/**
 * Log username change
 *
 * @param int    $user_id      The user ID
 * @param string $old_username The old username
 * @param string $new_username The new username
 */
function iauc_log_username_change( $user_id, $old_username, $new_username ) {
    $current_user = wp_get_current_user();
    $target_user = get_user_by( 'id', $user_id );
    
    $log_entry = sprintf(
        '[%s] Username changed from %s to %s for user %s (ID: %d) by admin %s (ID: %d)',
        current_time( 'Y-m-d H:i:s' ),
        $old_username,
        $new_username,
        $target_user ? $target_user->display_name : 'Unknown',
        $user_id,
        $current_user->user_login,
        $current_user->ID
    );

    // Log only in debug mode
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        error_log( 'IAUC: ' . $log_entry );
    }
}

/**
 * Sanitize and validate username
 *
 * @param string $username The username to validate
 * @return string|false Sanitized username or false if invalid
 */
function iauc_sanitize_username( $username ) {
    $username = sanitize_user( $username );
    
    if ( empty( $username ) || ! validate_username( $username ) ) {
        return false;
    }

    return $username;
}

/**
 * Get current admin email
 *
 * @return string Current admin email
 */
function iauc_get_current_admin_email() {
    return get_option( 'admin_email' );
}

/**
 * Check if user can perform admin operations
 *
 * @return bool True if user can manage options
 */
function iauc_user_can_manage() {
    return current_user_can( 'manage_options' );
}

/**
 * Display admin notice
 *
 * @param string $message The message to display
 * @param string $type    The notice type (success, error, warning, info)
 */
function iauc_admin_notice( $message, $type = 'info' ) {
    $class = 'notice notice-' . $type;
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
} 