<?php
/**
 * Purchase Alert Email Template
 *
 * Template for new member purchase alerts
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 *
 * @var WP_User $user            User object
 * @var object  $level           Membership level object
 * @var object  $morder          Order object
 * @var bool    $has_membership Whether user has membership access
 * @var bool    $has_course_access Whether user has course access
 * @var string  $status          Status (SUCCESS or PROBLEM)
 * @var string  $status_color    Status color code
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 4px; 
                        font-weight: bold; margin: 10px 0; }
        .section { background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa; }
        .section h3 { margin-top: 0; color: #0073aa; }
        .detail-row { padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: bold; display: inline-block; width: 150px; }
        .access-status { padding: 5px 10px; border-radius: 3px; display: inline-block; }
        .access-granted { background: #d4edda; color: #155724; }
        .access-denied { background: #f8d7da; color: #721c24; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; 
                       margin: 15px 0; border-radius: 4px; }
        .button { display: inline-block; padding: 10px 20px; background: #0073aa; 
                 color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 New Member Purchase Alert</h1>
            <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">First-time membership purchase detected</p>
            <div class="status-badge" style="background: <?php echo esc_attr($status_color); ?>; color: white;">
                <?php echo esc_html($status); ?>
            </div>
        </div>
        
        <div class="section">
            <h3>👤 Customer Details</h3>
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <?php echo esc_html($user->display_name); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                    <?php echo esc_html($user->user_email); ?>
                </a>
            </div>
            <div class="detail-row">
                <span class="detail-label">User ID:</span>
                <?php echo esc_html($user->ID); ?>
            </div>
        </div>
        
        <div class="section">
            <h3>📋 Order Details</h3>
            <div class="detail-row">
                <span class="detail-label">Order ID:</span>
                <?php echo esc_html($morder->code); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Membership Level:</span>
                <?php echo esc_html($level ? $level->name : 'Unknown'); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount:</span>
                <?php echo pmpro_formatPrice($morder->total); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Gateway:</span>
                <?php echo esc_html(ucfirst($morder->gateway)); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <?php echo current_time('F j, Y g:i a'); ?>
            </div>
        </div>
        
        <div class="section">
            <h3>🔐 Access Status</h3>
            <div class="detail-row">
                <span class="detail-label">Membership Level:</span>
                <span class="access-status <?php echo $has_membership ? 'access-granted' : 'access-denied'; ?>">
                    <?php echo $has_membership ? '✓ Granted' : '✗ NOT GRANTED'; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Course Access:</span>
                <span class="access-status <?php echo $has_course_access ? 'access-granted' : 'access-denied'; ?>">
                    <?php echo $has_course_access ? '✓ Granted' : '✗ NOT GRANTED'; ?>
                </span>
            </div>
        </div>
        
        <?php if (!$has_membership || !$has_course_access): ?>
        <div class="warning-box">
            <strong>⚠️ ACTION REQUIRED:</strong> Access was not properly granted. 
            Please investigate and grant access manually.
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="<?php echo admin_url('admin.php?page=pmpro-member&user_id=' . $user->ID); ?>" 
               class="button">View Member Profile</a>
            <a href="<?php echo admin_url('admin.php?page=pmpro-orders&order=' . $morder->id); ?>" 
               class="button">View Order</a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; 
                    text-align: center; color: #666; font-size: 12px;">
            <p>This is an automated notification from PMPro Access Monitor</p>
            <p><?php echo get_bloginfo('name'); ?> | <?php echo home_url(); ?></p>
        </div>
    </div>
</body>
</html>

