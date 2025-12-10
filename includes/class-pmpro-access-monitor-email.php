<?php
/**
 * Email Templates Class
 *
 * Handles all email template building for PMPro Access Monitor
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PMPro_Access_Monitor_Email
 */
class PMPro_Access_Monitor_Email {
    
    /**
     * Get instance (singleton pattern)
     *
     * @return PMPro_Access_Monitor_Email
     */
    public static function get_instance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }
    
    /**
     * Get template path
     *
     * @param string $template_name Template filename
     * @return string Full path to template file
     */
    private function get_template_path($template_name) {
        $template_path = PMPRO_ACCESS_MONITOR_PLUGIN_DIR . 'includes/templates/' . $template_name;
        
        // Allow theme override
        $theme_template = get_stylesheet_directory() . '/pmpro-access-monitor/' . $template_name;
        if (file_exists($theme_template)) {
            return $theme_template;
        }
        
        return $template_path;
    }
    
    /**
     * Load email template
     *
     * @param string $template_name Template filename
     * @param array  $args          Variables to pass to template
     * @return string HTML email content
     */
    private function load_template($template_name, $args = array()) {
        $template_path = $this->get_template_path($template_name);
        
        if (!file_exists($template_path)) {
            return sprintf('Template file not found: %s', $template_name);
        }
        
        // Extract variables for template
        extract($args);
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Build HTML email for immediate purchase alert
     *
     * @param WP_User $user            User object
     * @param object  $level           Membership level object
     * @param object  $morder          Order object
     * @param bool    $has_membership Whether user has membership access
     * @param bool    $has_course_access Whether user has course access
     * @param array   $missing_courses Optional array of course IDs that are missing
     * @return string HTML email content
     */
    public function build_purchase_email($user, $level, $morder, $has_membership, $has_course_access, $missing_courses = array()) {
        $status = ($has_membership && $has_course_access) ? 'SUCCESS' : 'PROBLEM';
        $status_color = ($status === 'SUCCESS') ? '#28a745' : '#dc3545';
        
        return $this->load_template('email-purchase-alert.php', array(
            'user'            => $user,
            'level'           => $level,
            'morder'          => $morder,
            'has_membership'  => $has_membership,
            'has_course_access' => $has_course_access,
            'missing_courses' => $missing_courses,
            'status'          => $status,
            'status_color'    => $status_color
        ));
    }
    
    /**
     * Build HTML email for scheduled check report (cron)
     *
     * @param array $problems      Array of problem data
     * @param int   $total_checked Total number of members checked
     * @return string HTML email content
     */
    public function build_scheduled_report_email($problems, $total_checked) {
        return $this->load_template('email-cron-report.php', array(
            'problems'      => $problems,
            'total_checked' => $total_checked
        ));
    }
    
    /**
     * Send email alert
     *
     * @param string $subject Email subject
     * @param string $message Email message (HTML)
     * @return bool True if sent successfully, false otherwise
     */
    public function send_alert($subject, $message) {
        $recipients = $this->get_recipients();
        
        if (empty($recipients)) {
            return false;
        }
        
        // Build from email
        $from_email = 'noreply@' . sanitize_text_field(wp_parse_url(home_url(), PHP_URL_HOST));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . sanitize_email($from_email) . '>',
        );
        
        return wp_mail($recipients, $subject, $message, $headers);
    }
    
    /**
     * Get all recipient email addresses
     *
     * @return array Array of email addresses
     */
    public function get_recipients() {
        $recipients = array();
        
        // Get admin email settings
        $send_to_all_admins = get_option('pmpro_access_monitor_send_to_all_admins', false);
        $selected_admins = get_option('pmpro_access_monitor_selected_admins', array());
        
        // Add admin emails based on settings
        if ($send_to_all_admins) {
            // Send to all admins
            $recipients = array_merge($recipients, $this->get_all_admin_email_addresses());
        } elseif (!empty($selected_admins)) {
            // Send to selected admins only
            $recipients = array_merge($recipients, $this->get_selected_admin_email_addresses($selected_admins));
        }
        
        // Add additional custom recipients
        $saved_recipients = get_option('pmpro_access_monitor_recipients', array());
        if (!empty($saved_recipients)) {
            $recipients = array_merge($recipients, $saved_recipients);
        }
        
        // Remove duplicates and empty values
        return array_filter(array_unique($recipients));
    }
    
    /**
     * Get all admin email addresses
     *
     * @return array Array of admin email addresses
     */
    private function get_all_admin_email_addresses() {
        $admins = get_users(array('role' => 'administrator'));
        
        $emails = array();
        
        foreach ($admins as $admin) {
            if (!empty($admin->user_email)) {
                $emails[] = $admin->user_email;
            }
        }
        
        return $emails;
    }
    
    /**
     * Get selected admin email addresses
     *
     * @param array $admin_ids Array of admin user IDs
     * @return array Array of admin email addresses
     */
    private function get_selected_admin_email_addresses($admin_ids) {
        if (empty($admin_ids)) {
            return array();
        }
        
        $admins = get_users(array(
            'role'   => 'administrator',
            'include' => array_map('absint', $admin_ids)
        ));
        
        $emails = array();
        
        foreach ($admins as $admin) {
            if (!empty($admin->user_email)) {
                $emails[] = $admin->user_email;
            }
        }
        
        return $emails;
    }
}

