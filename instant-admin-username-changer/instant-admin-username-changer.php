<?php
/**
 * Plugin Name: Instant Admin Email & Username Changer
 * Description: Instantly change admin email and any user's username without email confirmation or database edits.
 * Version: 1.0.0
 * Author: Waqas Khan
 * Author URI: https://waqaskhan.com.pk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: instant-admin-username-changer
 * Domain Path: /languages
 * Requires at least: 5.5
 * Tested up to: 6.5
 * Stable tag: 1.0
 *
 * @package Instant_Admin_Username_Changer
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'IAUC_VERSION', '1.0.0' );
define( 'IAUC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IAUC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IAUC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
class Instant_Admin_Username_Changer {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'wp_ajax_iauc_change_admin_email', array( $this, 'ajax_change_admin_email' ) );
        add_action( 'wp_ajax_iauc_change_username', array( $this, 'ajax_change_username' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Load text domain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load core functions
        require_once IAUC_PLUGIN_DIR . 'includes/functions.php';
    }

    /**
     * Admin initialization
     */
    public function admin_init() {
        // Override admin email change confirmation
        add_filter( 'send_email_change_email', array( $this, 'prevent_admin_email_confirmation' ), 10, 3 );
    }

    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'instant-admin-username-changer', false, dirname( IAUC_PLUGIN_BASENAME ) . '/languages' );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Quick Identity Change', 'instant-admin-username-changer' ),
            __( 'Quick Identity Change', 'instant-admin-username-changer' ),
            'manage_options',
            'instant-admin-username-changer',
            array( $this, 'admin_page' )
        );
    }

    /**
     * Admin page callback
     */
    public function admin_page() {
        require_once IAUC_PLUGIN_DIR . 'admin/settings-page.php';
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'settings_page_instant-admin-username-changer' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'iauc-admin-script',
            IAUC_PLUGIN_URL . 'assets/js/script.js',
            array( 'jquery' ),
            IAUC_VERSION,
            true
        );

        wp_enqueue_style(
            'iauc-admin-style',
            IAUC_PLUGIN_URL . 'assets/css/style.css',
            array(),
            IAUC_VERSION
        );

        // Localize script for AJAX
        wp_localize_script( 'iauc-admin-script', 'iauc_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'iauc_nonce' ),
            'strings'  => array(
                'confirm_email_change' => __( 'Are you sure you want to change the admin email?', 'instant-admin-username-changer' ),
                'confirm_username_change' => __( 'Are you sure you want to change this username?', 'instant-admin-username-changer' ),
                'loading' => __( 'Processing...', 'instant-admin-username-changer' ),
            )
        ) );
    }

    /**
     * Prevent admin email change confirmation
     */
    public function prevent_admin_email_confirmation( $send, $user, $userdata ) {
        // If this is the admin user, don't send confirmation email
        if ( $user->ID === 1 || user_can( $user->ID, 'manage_options' ) ) {
            return false;
        }
        return $send;
    }

    /**
     * AJAX handler for changing admin email
     */
    public function ajax_change_admin_email() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'iauc_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'instant-admin-username-changer' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'instant-admin-username-changer' ) );
        }

        if ( ! isset( $_POST['new_email'] ) ) {
            wp_send_json_error( esc_html__( 'Email address is required.', 'instant-admin-username-changer' ) );
        }

        $new_email = sanitize_email( wp_unslash( $_POST['new_email'] ) );
        
        if ( ! is_email( $new_email ) ) {
            wp_send_json_error( esc_html__( 'Please enter a valid email address.', 'instant-admin-username-changer' ) );
        }

        // Change admin email
        $result = iauc_change_admin_email( $new_email );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( esc_html( $result->get_error_message() ) );
        }

        wp_send_json_success( esc_html__( 'Admin email changed successfully!', 'instant-admin-username-changer' ) );
    }

    /**
     * AJAX handler for changing username
     */
    public function ajax_change_username() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'iauc_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'instant-admin-username-changer' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'instant-admin-username-changer' ) );
        }

        if ( ! isset( $_POST['user_id'] ) ) {
            wp_send_json_error( esc_html__( 'User ID is required.', 'instant-admin-username-changer' ) );
        }

        if ( ! isset( $_POST['new_username'] ) ) {
            wp_send_json_error( esc_html__( 'Username is required.', 'instant-admin-username-changer' ) );
        }

        $user_id = intval( $_POST['user_id'] );
        $new_username = sanitize_user( wp_unslash( $_POST['new_username'] ) );

        if ( empty( $new_username ) ) {
            wp_send_json_error( esc_html__( 'Please enter a valid username.', 'instant-admin-username-changer' ) );
        }

        // Change username
        $result = iauc_change_username( $user_id, $new_username );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( esc_html( $result->get_error_message() ) );
        }

        wp_send_json_success( esc_html__( 'Username changed successfully!', 'instant-admin-username-changer' ) );
    }
}

// Initialize the plugin
new Instant_Admin_Username_Changer();

// Activation hook
register_activation_hook( __FILE__, 'iauc_activate' );

/**
 * Plugin activation
 */
function iauc_activate() {
    // Add activation tasks if needed
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'iauc_deactivate' );

/**
 * Plugin deactivation
 */
function iauc_deactivate() {
    // Add deactivation tasks if needed
    flush_rewrite_rules();
} 