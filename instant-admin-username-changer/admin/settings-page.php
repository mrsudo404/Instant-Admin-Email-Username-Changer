<?php
/**
 * Admin settings page for Instant Admin Email & Username Changer
 *
 * @package Instant_Admin_Username_Changer
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check user permissions
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'instant-admin-username-changer' ) );
}

// Get current admin email
$current_admin_email = iauc_get_current_admin_email();

// Get users for dropdown
$users = iauc_get_users_for_dropdown();
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <div class="iauc-admin-container">
        <!-- Admin Email Change Section -->
        <div class="iauc-section">
            <h2><?php esc_html_e( 'Change Admin Email', 'instant-admin-username-changer' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Instantly change the WordPress admin email without confirmation emails.', 'instant-admin-username-changer' ); ?>
            </p>
            
            <form id="iauc-admin-email-form" class="iauc-form">
                <?php wp_nonce_field( 'iauc_nonce', 'iauc_email_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="current_admin_email"><?php esc_html_e( 'Current Admin Email', 'instant-admin-username-changer' ); ?></label>
                        </th>
                        <td>
                            <input type="email" 
                                   id="current_admin_email" 
                                   name="current_admin_email" 
                                   value="<?php echo esc_attr( $current_admin_email ); ?>" 
                                   class="regular-text" 
                                   readonly />
                            <p class="description">
                                <?php esc_html_e( 'This is the current admin email address.', 'instant-admin-username-changer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="new_admin_email"><?php esc_html_e( 'New Admin Email', 'instant-admin-username-changer' ); ?></label>
                        </th>
                        <td>
                            <input type="email" 
                                   id="new_admin_email" 
                                   name="new_admin_email" 
                                   class="regular-text" 
                                   required />
                            <p class="description">
                                <?php esc_html_e( 'Enter the new admin email address.', 'instant-admin-username-changer' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="iauc-change-email-btn">
                        <?php esc_html_e( 'Change Admin Email', 'instant-admin-username-changer' ); ?>
                    </button>
                    <span class="spinner" style="float: none; margin-left: 10px;"></span>
                </p>
            </form>
            
            <div id="iauc-email-message"></div>
        </div>

        <!-- Username Change Section -->
        <div class="iauc-section">
            <h2><?php esc_html_e( 'Change Username', 'instant-admin-username-changer' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Change any user\'s username directly from the dashboard.', 'instant-admin-username-changer' ); ?>
            </p>
            
            <form id="iauc-username-form" class="iauc-form">
                <?php wp_nonce_field( 'iauc_nonce', 'iauc_username_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_id"><?php esc_html_e( 'Select User', 'instant-admin-username-changer' ); ?></label>
                        </th>
                        <td>
                            <select id="user_id" name="user_id" class="regular-text" required>
                                <option value=""><?php esc_html_e( '-- Select a user --', 'instant-admin-username-changer' ); ?></option>
                                <?php foreach ( $users as $user_id => $user_info ) : ?>
                                    <option value="<?php echo esc_attr( $user_id ); ?>">
                                        <?php echo esc_html( $user_info ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Choose the user whose username you want to change.', 'instant-admin-username-changer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="new_username"><?php esc_html_e( 'New Username', 'instant-admin-username-changer' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="new_username" 
                                   name="new_username" 
                                   class="regular-text" 
                                   required 
                                   pattern="[a-zA-Z0-9_-]+" 
                                   title="<?php esc_attr_e( 'Username can only contain letters, numbers, underscores, and hyphens.', 'instant-admin-username-changer' ); ?>" />
                            <p class="description">
                                <?php esc_html_e( 'Enter the new username (letters, numbers, underscores, and hyphens only).', 'instant-admin-username-changer' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="iauc-change-username-btn">
                        <?php esc_html_e( 'Change Username', 'instant-admin-username-changer' ); ?>
                    </button>
                    <span class="spinner" style="float: none; margin-left: 10px;"></span>
                </p>
            </form>
            
            <div id="iauc-username-message"></div>
        </div>

        <!-- Information Section -->
        <div class="iauc-section iauc-info-section">
            <h2><?php esc_html_e( 'Important Information', 'instant-admin-username-changer' ); ?></h2>
            <div class="iauc-info-content">
                <h3><?php esc_html_e( 'Security Notes:', 'instant-admin-username-changer' ); ?></h3>
                <ul>
                    <li><?php esc_html_e( 'Only administrators can access this page.', 'instant-admin-username-changer' ); ?></li>
                    <li><?php esc_html_e( 'All changes are logged for security purposes.', 'instant-admin-username-changer' ); ?></li>
                    <li><?php esc_html_e( 'Username changes are irreversible - choose carefully.', 'instant-admin-username-changer' ); ?></li>
                    <li><?php esc_html_e( 'Admin email changes bypass the standard WordPress confirmation process.', 'instant-admin-username-changer' ); ?></li>
                </ul>
                
                <h3><?php esc_html_e( 'Usage Tips:', 'instant-admin-username-changer' ); ?></h3>
                <ul>
                    <li><?php esc_html_e( 'Usernames must be unique across the entire site.', 'instant-admin-username-changer' ); ?></li>
                    <li><?php esc_html_e( 'Email addresses must be valid and unique.', 'instant-admin-username-changer' ); ?></li>
                    <li><?php esc_html_e( 'Changes take effect immediately.', 'instant-admin-username-changer' ); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Auto-populate current username when user is selected
    $('#user_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var userInfo = selectedOption.text();
        
        if (userInfo && userInfo !== '-- Select a user --') {
            // Extract username from the format "Display Name (username) - email"
            var usernameMatch = userInfo.match(/\(([^)]+)\)/);
            if (usernameMatch) {
                $('#new_username').val(usernameMatch[1]);
            }
        }
    });
});
</script> 