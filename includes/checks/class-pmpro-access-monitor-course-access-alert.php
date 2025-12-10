<?php
/**
 * Course Access Alert Class
 *
 * Handles alerts when a user purchases membership but doesn't gain course access
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PMPro_Access_Monitor_Course_Access_Alert
 */
class PMPro_Access_Monitor_Course_Access_Alert {
    
    /**
     * Get instance (singleton pattern)
     *
     * @return PMPro_Access_Monitor_Course_Access_Alert
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
     * Check and alert if course access is missing after purchase
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
        
        // Get user data
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        // Get membership level
        $level = pmpro_getLevel($morder->membership_id);
        
        // Check membership level access
        $has_membership = pmpro_hasMembershipLevel($morder->membership_id, $user_id);
        
        // Only proceed if membership is granted
        if (!$has_membership) {
            return;
        }
        
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
        
        // Only send alert if membership is granted BUT course access is missing
        if (!$has_course_access) {
            // Build and send alert email
            try {
                $message = $email->build_purchase_email($user, $level, $morder, $has_membership, $has_course_access, $missing_courses);
                $subject = sprintf('[%s] Course Access Alert - Membership Purchased but Course Access Missing', get_bloginfo('name'));
                $email->send_alert($subject, $message);
            } catch (Exception $e) {
                // Silently fail - email sending errors are handled in send_alert method
            }
        }
    }
}

