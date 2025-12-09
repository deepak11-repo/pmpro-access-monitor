<?php
/**
 * Plugin Name: PMPro Access Monitor
 * Description: Monitors Paid Memberships Pro membership purchases and periodically checks for access discrepancies. Sends email alerts for new member purchases and when course access is missing. Includes scheduled cron job checks to identify and report access issues for all active members.
 * Version: 1.2.0
 * Author: Team WisdmLabs
 * Author URI: https://wisdmlabs.com
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PMPRO_ACCESS_MONITOR_VERSION', '1.2.0');
define('PMPRO_ACCESS_MONITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PMPRO_ACCESS_MONITOR_FILE', __FILE__);

/**
 * Main plugin class loader
 */
function pmpro_access_monitor_load() {
    // Check if PMPro is active
    if (!class_exists('MemberOrder')) {
        add_action('admin_notices', 'pmpro_access_monitor_missing_pmpro_notice');
        return;
    }
    
    // Load required files
    require_once PMPRO_ACCESS_MONITOR_PLUGIN_DIR . 'includes/class-pmpro-access-monitor-helpers.php';
    require_once PMPRO_ACCESS_MONITOR_PLUGIN_DIR . 'includes/class-pmpro-access-monitor-email.php';
    require_once PMPRO_ACCESS_MONITOR_PLUGIN_DIR . 'includes/checks/class-pmpro-access-monitor-purchase-check.php';
    require_once PMPRO_ACCESS_MONITOR_PLUGIN_DIR . 'includes/checks/class-pmpro-access-monitor-course-access-alert.php';
    require_once PMPRO_ACCESS_MONITOR_PLUGIN_DIR . 'includes/checks/class-pmpro-access-monitor-scheduled-check.php';
    require_once PMPRO_ACCESS_MONITOR_PLUGIN_DIR . 'includes/class-pmpro-access-monitor.php';
    
    // Initialize the plugin
    PMPro_Access_Monitor::get_instance();
}

/**
 * Display admin notice if PMPro is not active
 */
function pmpro_access_monitor_missing_pmpro_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e('PMPro Access Monitor', 'pmpro-access-monitor'); ?></strong>: 
            <?php esc_html_e('This plugin requires Paid Memberships Pro to be installed and active.', 'pmpro-access-monitor'); ?>
        </p>
    </div>
    <?php
}

// Load the plugin
pmpro_access_monitor_load();

