<?php
/**
 * Helper Functions Class
 *
 * Contains utility functions for course access checking and member validation
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PMPro_Access_Monitor_Helpers
 */
class PMPro_Access_Monitor_Helpers {
    
    /**
     * Get instance (singleton pattern)
     *
     * @return PMPro_Access_Monitor_Helpers
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
     * Check if user is a new member (no previous membership history)
     *
     * @param int      $user_id         User ID
     * @param int|null $current_order_id Current order ID to exclude from check
     * @return bool True if new member, false otherwise
     */
    public function is_new_member($user_id, $current_order_id = null) {
        global $wpdb;
        
        try {
            // Check for previous successful orders (excluding current order)
            $previous_orders_sql = $wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->prefix}pmpro_membership_orders 
                WHERE user_id = %d 
                AND id != %d
                AND status NOT IN ('refunded', 'review', 'token', 'error', 'pending', 'cancelled')
                AND total > 0",
                $user_id,
                $current_order_id ? intval($current_order_id) : 0
            );
            
            $previous_orders = intval($wpdb->get_var($previous_orders_sql));
            
            return ($previous_orders == 0);
            
        } catch (Exception $e) {
            // Default to true (treat as new) if check fails to ensure alerts are sent
            return true;
        }
    }
    
    /**
     * Check if user has course access based on their membership
     *
     * @param int $user_id       User ID
     * @param int $membership_id Membership level ID
     * @return bool True if user has access, false otherwise
     */
    public function check_course_access($user_id, $membership_id) {
        try {
            // Get courses associated with this membership level
            $courses = $this->get_courses_for_level($membership_id);
            
            if (empty($courses)) {
                // No courses mapped to this level, assume access is fine
                return true;
            }
            
            foreach ($courses as $course_id) {
                if (!$this->user_has_course_access($user_id, $course_id)) {
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get list of courses user doesn't have access to
     *
     * @param int $user_id       User ID
     * @param int $membership_id Membership level ID
     * @return array Array of course IDs that user doesn't have access to
     */
    public function get_missing_courses($user_id, $membership_id) {
        $missing = array();
        
        try {
            // Get courses associated with this membership level
            $courses = $this->get_courses_for_level($membership_id);
            
            if (empty($courses)) {
                // No courses mapped to this level
                return $missing;
            }
            
            foreach ($courses as $course_id) {
                if (!$this->user_has_course_access($user_id, $course_id)) {
                    $missing[] = $course_id;
                }
            }
            
        } catch (Exception $e) {
            // Return empty array on error
        }
        
        return $missing;
    }
    
    /**
     * Get total number of courses associated with a membership level
     *
     * @param int $membership_id Membership level ID
     * @return int Total number of courses
     */
    public function get_total_courses_for_level($membership_id) {
        try {
            $courses = $this->get_courses_for_level($membership_id);
            return count($courses);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get courses associated with a membership level
     *
     * @param int $membership_id Membership level ID
     * @return array Array of course IDs
     */
    private function get_courses_for_level($membership_id) {
        // Option 1: Store mapping in options table
        $mappings = get_option('pmpro_course_mappings', array());
        if (isset($mappings[$membership_id])) {
            return $mappings[$membership_id];
        }
        
        // Option 2: If using LearnDash with PMPro integration
        if (function_exists('learndash_get_groups_courses_ids')) {
            // Get courses from LearnDash groups associated with this level
            $group_id = get_option('pmpro_learndash_group_' . $membership_id);
            if ($group_id) {
                return learndash_group_enrolled_courses($group_id);
            }
        }
        
        // Option 3: Custom post meta mapping
        $courses = get_posts(array(
            'post_type'      => 'sfwd-courses', // LearnDash course post type
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_pmpro_membership_level',
                    'value'   => $membership_id,
                    'compare' => '='
                )
            ),
            'fields'         => 'ids'
        ));
        
        return $courses;
    }
    
    /**
     * Check if user has access to a specific course
     *
     * @param int $user_id   User ID
     * @param int $course_id Course ID
     * @return bool True if user has access, false otherwise
     */
    private function user_has_course_access($user_id, $course_id) {
        // LearnDash
        if (function_exists('sfwd_lms_has_access')) {
            return sfwd_lms_has_access($course_id, $user_id);
        }
        
        // LifterLMS
        if (function_exists('llms_is_user_enrolled')) {
            return llms_is_user_enrolled($user_id, $course_id);
        }
        
        // LearnPress
        if (function_exists('learn_press_user_has_enrolled_course')) {
            return learn_press_user_has_enrolled_course($course_id, $user_id);
        }
        
        // Tutor LMS
        if (function_exists('tutor_utils')) {
            return tutor_utils()->is_enrolled($course_id, $user_id);
        }
        
        // Default: Check if user can read the post
        return user_can($user_id, 'read_post', $course_id);
    }
}

