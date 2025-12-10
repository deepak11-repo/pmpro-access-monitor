<?php
/**
 * Scheduled Check Class
 *
 * Handles scheduled cron job checks for all members
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PMPro_Access_Monitor_Scheduled_Check
 */
class PMPro_Access_Monitor_Scheduled_Check {
    
    /**
     * Get instance (singleton pattern)
     *
     * @return PMPro_Access_Monitor_Scheduled_Check
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
     * Run scheduled cron check for all members
     */
    public function run() {
        global $wpdb;
        
        $helpers = PMPro_Access_Monitor_Helpers::get_instance();
        $email = PMPro_Access_Monitor_Email::get_instance();
        
        // Get all users with active memberships
        $sql = "
            SELECT 
                mu.user_id,
                mu.membership_id,
                mu.startdate,
                u.user_email,
                u.display_name
            FROM {$wpdb->prefix}pmpro_memberships_users mu
            INNER JOIN {$wpdb->users} u ON mu.user_id = u.ID
            WHERE mu.status = 'active'
            ORDER BY mu.user_id
        ";
        
        $members = $wpdb->get_results($sql);
        
        if (empty($members)) {
            return;
        }
        
        // Count unique users (not membership rows)
        $unique_user_ids = array_unique(array_column($members, 'user_id'));
        $total_unique_users = count($unique_user_ids);
        
        $problems = array();
        
        foreach ($members as $member) {
            // Verify membership level is properly assigned
            $has_membership = pmpro_hasMembershipLevel($member->membership_id, $member->user_id);
            
            // Check course access
            $has_course_access = $helpers->check_course_access($member->user_id, $member->membership_id);
            
            // Get list of missing courses if course access is missing
            $missing_courses = array();
            $total_courses = 0;
            if (!$has_course_access) {
                $missing_courses = $helpers->get_missing_courses($member->user_id, $member->membership_id);
            }
            
            // Get total courses for this membership level
            $total_courses = $helpers->get_total_courses_for_level($member->membership_id);
            
            if (!$has_membership || !$has_course_access) {
                $problems[] = array(
                    'user_id'           => $member->user_id,
                    'email'             => $member->user_email,
                    'display_name'      => $member->display_name,
                    'membership_id'     => $member->membership_id,
                    'has_membership'    => $has_membership,
                    'has_course_access' => $has_course_access,
                    'missing_courses'   => $missing_courses,
                    'total_courses'     => $total_courses,
                    'start_date'        => $member->startdate
                );
            }
        }
        
        // Send report if problems found
        if (!empty($problems)) {
            $this->send_report($problems, $total_unique_users);
        }
        
        // Log the check
        update_option('pmpro_access_monitor_last_check', array(
            'time'           => current_time('mysql'),
            'total_checked'  => $total_unique_users,
            'problems_found' => count($problems)
        ));
    }
    
    /**
     * Send scheduled check report
     *
     * @param array $problems      Array of problem data
     * @param int   $total_checked Total number of members checked
     */
    private function send_report($problems, $total_checked) {
        $email = PMPro_Access_Monitor_Email::get_instance();
        
        $subject = sprintf(
            '[%s] PMPro Access Report - %d Issues Found',
            get_bloginfo('name'),
            count($problems)
        );
        
        $message = $email->build_scheduled_report_email($problems, $total_checked);
        
        $email->send_alert($subject, $message);
    }
}

