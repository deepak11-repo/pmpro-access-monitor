<?php
/**
 * Purchase Check Class
 *
 * Handles checking access immediately after a new member purchase
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PMPro_Access_Monitor_Purchase_Check
 */
class PMPro_Access_Monitor_Purchase_Check {
    
    /**
     * Get instance (singleton pattern)
     *
     * @return PMPro_Access_Monitor_Purchase_Check
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
     * Check access immediately after purchase - only for new members
     *
     * @param int   $user_id User ID
     * @param object $morder  Order object
     */
    public function check($user_id, $morder) {
        $helpers = PMPro_Access_Monitor_Helpers::get_instance();
        $email = PMPro_Access_Monitor_Email::get_instance();
        
        // Validate parameters
        if (!$morder || !isset($morder->membership_id) || empty($user_id)) {
            return;
        }
        
        // Check if this is a new member
        $is_new = $helpers->is_new_member($user_id, isset($morder->id) ? $morder->id : null);
        
        if (!$is_new) {
            return;
        }
        
        // Get user data
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        // Get membership level
        $level = pmpro_getLevel($morder->membership_id);
        
        // Check membership level access
        $has_membership = pmpro_hasMembershipLevel($morder->membership_id, $user_id);
        
        // Check course access
        $has_course_access = true; // Default to true
        $missing_courses = array();
        try {
            $has_course_access = $helpers->check_course_access($user_id, $morder->membership_id);
            if (!$has_course_access) {
                $missing_courses = $helpers->get_missing_courses($user_id, $morder->membership_id);
            }
        } catch (Exception $e) {
            $has_course_access = false;
        }
        
        // Determine status
        $status = ($has_membership && $has_course_access) ? 'SUCCESS' : 'PROBLEM';
        
        // Build and send email
        try {
            $message = $email->build_purchase_email($user, $level, $morder, $has_membership, $has_course_access, $missing_courses);
            $subject = sprintf('[%s] New Member Purchase - %s', get_bloginfo('name'), $status);
            $email->send_alert($subject, $message);
        } catch (Exception $e) {
            // Silently fail - email sending errors are handled in send_alert method
        }
    }
}

