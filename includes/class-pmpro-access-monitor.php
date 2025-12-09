<?php
/**
 * Core Plugin Class
 *
 * Main plugin class that orchestrates all functionality
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PMPro_Access_Monitor
 */
class PMPro_Access_Monitor {
    
    /**
     * Plugin instance
     *
     * @var PMPro_Access_Monitor
     */
    private static $instance = null;
    
    /**
     * Configuration
     *
     * @var array
     */
    private $config = array(
        'cron_interval' => 'hourly', // Options: hourly, twicedaily, daily
        'cron_hook'     => 'pmpro_access_monitor_cron',
    );
    
    /**
     * Get instance (singleton pattern)
     *
     * @return PMPro_Access_Monitor
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Ensure PMPro is loaded before hooking into its actions
        add_action('plugins_loaded', array($this, 'init'), 20);
        
        // Activation/Deactivation (these can run early)
        register_activation_hook(PMPRO_ACCESS_MONITOR_FILE, array($this, 'activate'));
        register_deactivation_hook(PMPRO_ACCESS_MONITOR_FILE, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin hooks after dependencies are loaded
     */
    public function init() {
        // Check if PMPro is active
        if (!function_exists('pmpro_hasMembershipLevel')) {
            return;
        }
        
        // Add custom cron interval if needed
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Manual check action and settings save
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // Conditionally register hooks based on settings
        $this->register_conditional_hooks();
    }
    
    /**
     * Register hooks conditionally based on settings
     */
    private function register_conditional_hooks() {
        // Check if purchase check is enabled
        if ($this->is_purchase_check_enabled()) {
            $purchase_check = PMPro_Access_Monitor_Purchase_Check::get_instance();
            add_action('pmpro_after_checkout', array($purchase_check, 'check'), 10, 2);
        }
        
        // Check if course access alert is enabled
        if ($this->is_course_access_alert_enabled()) {
            $course_access_alert = PMPro_Access_Monitor_Course_Access_Alert::get_instance();
            add_action('pmpro_after_checkout', array($course_access_alert, 'check'), 10, 2);
        }
        
        // Check if scheduled check is enabled
        if ($this->is_scheduled_check_enabled()) {
            $scheduled_check = PMPro_Access_Monitor_Scheduled_Check::get_instance();
            add_action($this->config['cron_hook'], array($scheduled_check, 'run'));
            
            // Ensure cron is scheduled if enabled
            if (!wp_next_scheduled($this->config['cron_hook'])) {
                wp_schedule_event(time(), $this->config['cron_interval'], $this->config['cron_hook']);
            }
        } else {
            // Clear cron if disabled
            wp_clear_scheduled_hook($this->config['cron_hook']);
        }
    }
    
    /**
     * Check if purchase check is enabled
     *
     * @return bool
     */
    private function is_purchase_check_enabled() {
        return get_option('pmpro_access_monitor_enable_purchase_check', true);
    }
    
    /**
     * Check if scheduled check is enabled
     *
     * @return bool
     */
    private function is_scheduled_check_enabled() {
        return get_option('pmpro_access_monitor_enable_scheduled_check', true);
    }
    
    /**
     * Check if course access alert is enabled
     *
     * @return bool
     */
    private function is_course_access_alert_enabled() {
        return get_option('pmpro_access_monitor_enable_course_access_alert', true);
    }
    
    /**
     * Plugin activation - schedule cron
     */
    public function activate() {
        if (!wp_next_scheduled($this->config['cron_hook'])) {
            wp_schedule_event(time(), $this->config['cron_interval'], $this->config['cron_hook']);
        }
    }
    
    /**
     * Plugin deactivation - clear cron
     */
    public function deactivate() {
        wp_clear_scheduled_hook($this->config['cron_hook']);
    }
    
    /**
     * Add custom cron intervals
     *
     * @param array $schedules Existing cron schedules
     * @return array Modified cron schedules
     */
    public function add_cron_intervals($schedules) {
        $schedules['every_six_hours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => __('Every 6 Hours', 'pmpro-access-monitor')
        );
        $schedules['every_fifteen_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __('Every 15 Minutes', 'pmpro-access-monitor')
        );
        return $schedules;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('PMPro Access Monitor Settings', 'pmpro-access-monitor'),
            __('PMPro Access Monitor', 'pmpro-access-monitor'),
            'manage_options',
            'pmpro-access-monitor',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Handle admin actions (settings save and manual check)
     */
    public function handle_admin_actions() {
        // Handle manual check trigger
        if (isset($_POST['pmpro_run_access_check']) && check_admin_referer('pmpro_access_monitor')) {
            $scheduled_check = PMPro_Access_Monitor_Scheduled_Check::get_instance();
            $scheduled_check->run();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . esc_html__('Access check completed. Check your email for results.', 'pmpro-access-monitor') . '</p></div>';
            });
        }
        
        // Handle settings save
        if (isset($_POST['pmpro_save_settings']) && check_admin_referer('pmpro_access_monitor_settings')) {
            // Save trigger enable/disable settings
            update_option('pmpro_access_monitor_enable_purchase_check', isset($_POST['enable_purchase_check']));
            update_option('pmpro_access_monitor_enable_course_access_alert', isset($_POST['enable_course_access_alert']));
            update_option('pmpro_access_monitor_enable_scheduled_check', isset($_POST['enable_scheduled_check']));
            
            // Save admin email settings
            if (isset($_POST['send_to_all_admins'])) {
                update_option('pmpro_access_monitor_send_to_all_admins', true);
                update_option('pmpro_access_monitor_selected_admins', array());
            } else {
                update_option('pmpro_access_monitor_send_to_all_admins', false);
                $selected_admins = isset($_POST['selected_admins']) ? array_map('absint', $_POST['selected_admins']) : array();
                update_option('pmpro_access_monitor_selected_admins', $selected_admins);
            }
            
            // Save additional recipients
            if (isset($_POST['additional_recipients'])) {
                $emails = array_map('trim', explode(',', sanitize_textarea_field($_POST['additional_recipients'])));
                $emails = array_filter($emails, 'is_email');
                update_option('pmpro_access_monitor_recipients', $emails);
            }
            
            // Save course mappings
            if (isset($_POST['course_mappings'])) {
                update_option('pmpro_course_mappings', $this->sanitize_mappings($_POST['course_mappings']));
            }
            
            // Re-register hooks with new settings
            $this->register_conditional_hooks();
            
            // Set a flag to show toast notification after page reload
            update_option('pmpro_access_monitor_show_success_toast', true);
            
            // Also show the standard WordPress notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible pmpro-access-monitor-notice"><p><strong>' . esc_html__('Success!', 'pmpro-access-monitor') . '</strong> ' . esc_html__('Settings saved successfully.', 'pmpro-access-monitor') . '</p></div>';
            });
        }
    }
    
    /**
     * Sanitize course mappings
     *
     * @param array $mappings Course mappings to sanitize
     * @return array Sanitized mappings
     */
    private function sanitize_mappings($mappings) {
        $clean = array();
        foreach ($mappings as $level_id => $courses) {
            $level_id = absint($level_id);
            $course_ids = array_map('absint', array_filter(explode(',', $courses)));
            if (!empty($course_ids)) {
                $clean[$level_id] = $course_ids;
            }
        }
        return $clean;
    }
    
    /**
     * Render settings page with all settings on one page
     */
    public function render_settings_page() {
        
        // Get current settings
        $enable_purchase_check = get_option('pmpro_access_monitor_enable_purchase_check', true);
        $enable_course_access_alert = get_option('pmpro_access_monitor_enable_course_access_alert', true);
        $enable_scheduled_check = get_option('pmpro_access_monitor_enable_scheduled_check', true);
        $send_to_all_admins = get_option('pmpro_access_monitor_send_to_all_admins', false);
        $selected_admins = get_option('pmpro_access_monitor_selected_admins', array());
        $additional_recipients = get_option('pmpro_access_monitor_recipients', array());
        $recipients_string = !empty($additional_recipients) ? implode(', ', $additional_recipients) : '';
        
        // Get all administrators
        $all_admins = get_users(array('role' => 'administrator', 'orderby' => 'display_name'));
        
        // Get status info
        $last_check = get_option('pmpro_access_monitor_last_check', array());
        $next_scheduled = wp_next_scheduled($this->config['cron_hook']);
        
        // Get course mappings
        $levels = pmpro_getAllLevels(true);
        $mappings = get_option('pmpro_course_mappings', array());
        
        // Get current recipients for display
        $email_helper = PMPro_Access_Monitor_Email::get_instance();
        $current_recipients = $email_helper->get_recipients();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('PMPro Access Monitor', 'pmpro-access-monitor'); ?></h1>
            
            <form method="post" action="<?php echo esc_url(admin_url('options-general.php?page=pmpro-access-monitor')); ?>" id="pmpro-access-monitor-settings-form">
                <?php wp_nonce_field('pmpro_access_monitor_settings'); ?>
                
                <!-- Trigger Settings Section -->
                <div class="card" style="max-width: 900px; padding: 20px; margin-bottom: 20px;">
                    <h2><?php echo esc_html__('Trigger Settings', 'pmpro-access-monitor'); ?></h2>
                    <p class="description"><?php echo esc_html__('Enable or disable monitoring triggers and scheduled checks.', 'pmpro-access-monitor'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="enable_purchase_check"><?php echo esc_html__('Purchase Check Trigger', 'pmpro-access-monitor'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_purchase_check" id="enable_purchase_check" value="1" 
                                           <?php checked($enable_purchase_check, true); ?>>
                                    <?php echo esc_html__('Enable alerts for new member purchases', 'pmpro-access-monitor'); ?>
                                </label>
                                <p class="description"><?php echo esc_html__('When enabled, sends alerts immediately when a new member completes a purchase.', 'pmpro-access-monitor'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="enable_course_access_alert"><?php echo esc_html__('Course Access Alert Trigger', 'pmpro-access-monitor'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_course_access_alert" id="enable_course_access_alert" value="1" 
                                           <?php checked($enable_course_access_alert, true); ?>>
                                    <?php echo esc_html__('Enable alerts when course access is missing after purchase', 'pmpro-access-monitor'); ?>
                                </label>
                                <p class="description"><?php echo esc_html__('When enabled, sends an immediate alert if a user successfully purchases a membership but does not gain access to the course.', 'pmpro-access-monitor'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="enable_scheduled_check"><?php echo esc_html__('Scheduled Check (Cron Job)', 'pmpro-access-monitor'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_scheduled_check" id="enable_scheduled_check" value="1" 
                                           <?php checked($enable_scheduled_check, true); ?>>
                                    <?php echo esc_html__('Enable scheduled access checks', 'pmpro-access-monitor'); ?>
                                </label>
                                <p class="description"><?php echo esc_html__('When enabled, periodically checks all active members for access issues.', 'pmpro-access-monitor'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Email Recipients Section -->
                <div class="card" style="max-width: 900px; padding: 20px; margin-bottom: 20px;">
                    <h2><?php echo esc_html__('Email Recipients', 'pmpro-access-monitor'); ?></h2>
                    <p class="description"><?php echo esc_html__('Configure who receives alert emails.', 'pmpro-access-monitor'); ?></p>
                        
                        <style>
                            .pmpro-access-monitor-admin-select {
                                max-height: 200px;
                                overflow-y: auto;
                                border: 1px solid #ddd;
                                padding: 12px 15px;
                                border-radius: 4px;
                                background-color: #fafafa;
                                margin: 0;
                                box-sizing: border-box;
                            }
                            .pmpro-access-monitor-admin-select label {
                                display: block;
                                margin-bottom: 10px;
                                padding: 4px 0;
                                line-height: 1.6;
                                cursor: pointer;
                            }
                            .pmpro-access-monitor-admin-select label:last-child {
                                margin-bottom: 0;
                            }
                            .pmpro-access-monitor-admin-select input[type="checkbox"] {
                                margin-right: 8px;
                                vertical-align: middle;
                                margin-top: 0;
                            }
                            #selected_admins_container.hidden {
                                display: none !important;
                            }
                            .form-table th {
                                width: 200px;
                                vertical-align: top;
                                padding-top: 8px;
                            }
                            .form-table td {
                                vertical-align: top;
                                padding-top: 8px;
                            }
                        </style>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="send_to_all_admins"><?php echo esc_html__('Send to All Admins', 'pmpro-access-monitor'); ?></label>
                                </th>
                                <td>
                                    <label for="send_to_all_admins" style="display: block; margin-bottom: 8px; cursor: pointer;">
                                        <input type="checkbox" name="send_to_all_admins" id="send_to_all_admins" value="1" 
                                               <?php checked($send_to_all_admins, true); ?>
                                               style="margin-right: 6px; vertical-align: middle; margin-top: 0;">
                                        <span style="vertical-align: middle;"><?php echo esc_html__('Send to all administrator accounts', 'pmpro-access-monitor'); ?></span>
                                    </label>
                                    <p class="description" style="margin-top: 0; margin-bottom: 0;"><?php echo esc_html__('When checked, all users with Administrator role will receive alerts.', 'pmpro-access-monitor'); ?></p>
                                </td>
                            </tr>
                            <tr id="selected_admins_container" class="<?php echo $send_to_all_admins ? 'hidden' : ''; ?>">
                                <th scope="row">
                                    <label for="selected_admins"><?php echo esc_html__('Select Specific Admins', 'pmpro-access-monitor'); ?></label>
                                </th>
                                <td>
                                    <?php if (!empty($all_admins)): ?>
                                        <fieldset class="pmpro-access-monitor-admin-select">
                                            <?php foreach ($all_admins as $admin): ?>
                                                <label>
                                                    <input type="checkbox" name="selected_admins[]" value="<?php echo esc_attr($admin->ID); ?>" 
                                                           <?php checked(in_array($admin->ID, $selected_admins), true); ?>>
                                                    <span><?php echo esc_html($admin->display_name . ' (' . $admin->user_email . ')'); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </fieldset>
                                        <p class="description" style="margin-top: 10px; margin-bottom: 0;"><?php echo esc_html__('Select specific administrators to receive alerts. Only shown when "Send to All Admins" is unchecked.', 'pmpro-access-monitor'); ?></p>
                                    <?php else: ?>
                                        <p><?php echo esc_html__('No administrators found.', 'pmpro-access-monitor'); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="additional_recipients"><?php echo esc_html__('Other than admin, any additional recipients', 'pmpro-access-monitor'); ?></label>
                                </th>
                                <td>
                                    <textarea name="additional_recipients" id="additional_recipients" 
                                              rows="3" class="large-text" 
                                              placeholder="<?php echo esc_attr__('email1@example.com, email2@example.com', 'pmpro-access-monitor'); ?>"
                                              style="width: 100%; max-width: 500px;"><?php echo esc_textarea($recipients_string); ?></textarea>
                                    <p class="description" style="margin-top: 8px; margin-bottom: 0;"><?php echo esc_html__('Enter additional email addresses (comma-separated) to receive alerts. These recipients are in addition to the selected administrators.', 'pmpro-access-monitor'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <script type="text/javascript">
                        (function() {
                            var checkbox = document.getElementById('send_to_all_admins');
                            var container = document.getElementById('selected_admins_container');
                            
                            if (checkbox && container) {
                                checkbox.addEventListener('change', function() {
                                    if (this.checked) {
                                        container.classList.add('hidden');
                                    } else {
                                        container.classList.remove('hidden');
                                    }
                                });
                            }
                        })();
                        </script>
                        
                    <h3 style="margin-top: 20px;"><?php echo esc_html__('Current Recipients', 'pmpro-access-monitor'); ?></h3>
                    <?php if (!empty($current_recipients)): ?>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <?php foreach ($current_recipients as $email_address): ?>
                                <li><?php echo esc_html($email_address); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color: #dc3545;"><strong>⚠️ <?php echo esc_html__('No recipients configured!', 'pmpro-access-monitor'); ?></strong> <?php echo esc_html__('Alerts will not be sent.', 'pmpro-access-monitor'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Course Mappings Section -->
                <div class="card" style="max-width: 900px; padding: 20px; margin-bottom: 20px;">
                    <h2><?php echo esc_html__('Course Mappings', 'pmpro-access-monitor'); ?></h2>
                    <p class="description"><?php echo esc_html__('Enter course IDs (comma-separated) for each membership level. Leave blank if no specific courses are required.', 'pmpro-access-monitor'); ?></p>
                        
                        <table class="widefat" style="max-width: 700px;">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Membership Level', 'pmpro-access-monitor'); ?></th>
                                    <th><?php echo esc_html__('Course IDs', 'pmpro-access-monitor'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($levels)): ?>
                                    <?php foreach ($levels as $level): ?>
                                    <tr>
                                        <td><?php echo esc_html($level->name); ?></td>
                                        <td>
                                            <input type="text" 
                                                   name="course_mappings[<?php echo esc_attr($level->id); ?>]" 
                                                   value="<?php echo esc_attr(isset($mappings[$level->id]) ? implode(',', $mappings[$level->id]) : ''); ?>"
                                                   class="regular-text"
                                                   placeholder="<?php echo esc_attr__('e.g., 101, 102, 103', 'pmpro-access-monitor'); ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2"><?php echo esc_html__('No membership levels found.', 'pmpro-access-monitor'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                    </table>
                </div>
                
                <!-- Status Section (Read-only) -->
                <div class="card" style="max-width: 900px; padding: 20px; margin-bottom: 20px;">
                    <h2><?php echo esc_html__('Status & Monitoring', 'pmpro-access-monitor'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th><?php echo esc_html__('Last Check', 'pmpro-access-monitor'); ?></th>
                                <td>
                                    <?php if (!empty($last_check)): ?>
                                        <?php echo esc_html($last_check['time']); ?><br>
                                        <small>
                                            <?php 
                                            printf(
                                                esc_html__('Checked %d members, found %d issues', 'pmpro-access-monitor'),
                                                esc_html($last_check['total_checked']),
                                                esc_html($last_check['problems_found'])
                                            );
                                            ?>
                                        </small>
                                    <?php else: ?>
                                        <?php echo esc_html__('Never run', 'pmpro-access-monitor'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Next Scheduled Check', 'pmpro-access-monitor'); ?></th>
                                <td>
                                    <?php 
                                    if ($next_scheduled && $enable_scheduled_check) {
                                        echo esc_html(date('F j, Y g:i a', $next_scheduled + (get_option('gmt_offset') * HOUR_IN_SECONDS)));
                                    } else {
                                        echo esc_html__('Not scheduled', 'pmpro-access-monitor');
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Check Frequency', 'pmpro-access-monitor'); ?></th>
                                <td><?php echo esc_html(ucfirst($this->config['cron_interval'])); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Purchase Check Status', 'pmpro-access-monitor'); ?></th>
                                <td>
                                    <?php if ($enable_purchase_check): ?>
                                        <span style="color: #28a745;">✓ <?php echo esc_html__('Enabled', 'pmpro-access-monitor'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">✗ <?php echo esc_html__('Disabled', 'pmpro-access-monitor'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Course Access Alert Status', 'pmpro-access-monitor'); ?></th>
                                <td>
                                    <?php if ($enable_course_access_alert): ?>
                                        <span style="color: #28a745;">✓ <?php echo esc_html__('Enabled', 'pmpro-access-monitor'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">✗ <?php echo esc_html__('Disabled', 'pmpro-access-monitor'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Scheduled Check Status', 'pmpro-access-monitor'); ?></th>
                                <td>
                                    <?php if ($enable_scheduled_check): ?>
                                        <span style="color: #28a745;">✓ <?php echo esc_html__('Enabled', 'pmpro-access-monitor'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">✗ <?php echo esc_html__('Disabled', 'pmpro-access-monitor'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        
                    <div style="margin-top: 20px;">
                        <button type="button" id="pmpro-run-access-check-btn" class="button button-secondary">
                            <?php echo esc_html__('Run Access Check Now', 'pmpro-access-monitor'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Single Save Button for All Settings -->
                <p class="submit" style="margin-top: 20px;">
                    <button type="submit" name="pmpro_save_settings" id="pmpro-save-settings-btn" class="button button-primary button-large">
                        <span class="save-text"><?php echo esc_html__('Save All Settings', 'pmpro-access-monitor'); ?></span>
                        <span class="spinner"></span>
                    </button>
                </p>
            </form>
            
            <!-- Separate form for manual check (outside main form to avoid nesting) -->
            <form method="post" id="pmpro-run-check-form" style="display: none;">
                <?php wp_nonce_field('pmpro_access_monitor'); ?>
                <input type="hidden" name="pmpro_run_access_check" value="1">
            </form>
        </div>
        
        <style>
            /* Save Button Styling */
            #pmpro-save-settings-btn {
                position: relative;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            #pmpro-save-settings-btn:hover:not(:disabled) {
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                transform: translateY(-1px);
            }
            #pmpro-save-settings-btn:active:not(:disabled) {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            #pmpro-save-settings-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            #pmpro-save-settings-btn .spinner {
                display: none;
                width: 16px;
                height: 16px;
                border: 2px solid #ffffff;
                border-top-color: transparent;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
                vertical-align: middle;
                margin-left: 8px;
            }
            #pmpro-save-settings-btn .spinner.visible {
                display: inline-block;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            /* Toast Notification */
            .pmpro-access-monitor-toast {
                position: fixed;
                top: 32px;
                right: 20px;
                background: #46b450;
                color: #fff;
                padding: 12px 20px;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                z-index: 100000;
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 300px;
                max-width: 500px;
                animation: slideInRight 0.3s ease-out;
                font-size: 14px;
                line-height: 1.5;
            }
            .pmpro-access-monitor-toast.hide {
                animation: slideOutRight 0.3s ease-in forwards;
            }
            .pmpro-access-monitor-toast-icon {
                font-size: 20px;
                line-height: 1;
            }
            .pmpro-access-monitor-toast-message {
                flex: 1;
            }
            .pmpro-access-monitor-toast-close {
                background: none;
                border: none;
                color: #fff;
                font-size: 18px;
                cursor: pointer;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0.8;
                transition: opacity 0.2s;
            }
            .pmpro-access-monitor-toast-close:hover {
                opacity: 1;
            }
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            /* Mobile responsive */
            @media screen and (max-width: 782px) {
                .pmpro-access-monitor-toast {
                    top: 46px;
                    right: 10px;
                    left: 10px;
                    min-width: auto;
                }
            }
        </style>
        
        <?php
        // Check if we should show success toast
        $show_toast = get_option('pmpro_access_monitor_show_success_toast', false);
        if ($show_toast) {
            delete_option('pmpro_access_monitor_show_success_toast');
        }
        ?>
        
        <?php if ($show_toast): ?>
        <!-- Toast Notification -->
        <div id="pmpro-access-monitor-toast" class="pmpro-access-monitor-toast">
            <span class="pmpro-access-monitor-toast-icon">✓</span>
            <span class="pmpro-access-monitor-toast-message">
                <strong><?php echo esc_html__('Settings Saved!', 'pmpro-access-monitor'); ?></strong><br>
                <?php echo esc_html__('Your changes have been saved successfully.', 'pmpro-access-monitor'); ?>
            </span>
            <button type="button" class="pmpro-access-monitor-toast-close" aria-label="<?php echo esc_attr__('Dismiss', 'pmpro-access-monitor'); ?>">×</button>
        </div>
        <?php endif; ?>
        
        <script type="text/javascript">
        (function() {
            var form = document.getElementById('pmpro-access-monitor-settings-form');
            var saveButton = document.getElementById('pmpro-save-settings-btn');
            var runCheckButton = document.getElementById('pmpro-run-access-check-btn');
            var runCheckForm = document.getElementById('pmpro-run-check-form');
            var toast = document.getElementById('pmpro-access-monitor-toast');
            
            // Handle toast notification
            if (toast) {
                var closeBtn = toast.querySelector('.pmpro-access-monitor-toast-close');
                
                // Auto-hide after 5 seconds
                setTimeout(function() {
                    if (toast && !toast.classList.contains('hide')) {
                        toast.classList.add('hide');
                        setTimeout(function() {
                            if (toast && toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }
                }, 5000);
                
                // Close button handler
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        toast.classList.add('hide');
                        setTimeout(function() {
                            if (toast && toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    });
                }
                
                // Click outside to dismiss (optional)
                toast.addEventListener('click', function(e) {
                    if (e.target === toast) {
                        toast.classList.add('hide');
                        setTimeout(function() {
                            if (toast && toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }
                });
            }
            
            // Handle manual check button
            if (runCheckButton && runCheckForm) {
                runCheckButton.addEventListener('click', function() {
                    runCheckForm.submit();
                });
            }
            
            // Handle settings save button
            if (form && saveButton) {
                // Handle form submission - show loading state
                form.addEventListener('submit', function(e) {
                    // Only show loading for save settings button
                    var clickedButton = e.submitter || document.activeElement;
                    if (clickedButton && clickedButton.name === 'pmpro_save_settings') {
                        var spinner = saveButton.querySelector('.spinner');
                        var saveText = saveButton.querySelector('.save-text');
                        
                        // Show loading state
                        if (spinner) {
                            spinner.classList.add('visible');
                        }
                        if (saveText) {
                            saveText.textContent = '<?php echo esc_js(__('Saving...', 'pmpro-access-monitor')); ?>';
                        }
                        
                        // Don't prevent default - allow form to submit normally
                    }
                });
            }
        })();
        </script>
        <?php
    }
}

