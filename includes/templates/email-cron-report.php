<?php
/**
 * Cron Report Email Template
 *
 * Template for scheduled cron job access check reports
 *
 * @package PMPro_Access_Monitor
 * @since 1.2.0
 *
 * @var array $problems      Array of problem data
 * @var int   $total_checked Total number of members checked
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
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .summary-box { background: #f8f9fa; padding: 20px; margin: 20px 0; 
                       border-radius: 4px; text-align: center; }
        .summary-stat { display: inline-block; margin: 0 20px; }
        .summary-stat .number { font-size: 36px; font-weight: bold; color: #0073aa; }
        .summary-stat .label { color: #666; font-size: 14px; }
        .problem-item { background: white; border: 1px solid #dee2e6; 
                        padding: 15px; margin: 15px 0; border-radius: 4px; }
        .problem-item h4 { margin-top: 0; color: #dc3545; }
        .detail-row { padding: 5px 0; }
        .detail-label { font-weight: bold; display: inline-block; width: 150px; }
        .status-indicator { padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-missing { background: #f8d7da; color: #721c24; }
        .button { display: inline-block; padding: 8px 16px; background: #0073aa; 
                 color: white; text-decoration: none; border-radius: 4px; 
                 font-size: 14px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ PMPro Access Report</h1>
            <p style="margin: 0;">Scheduled Access Check Results</p>
        </div>
        
        <div class="summary-box">
            <div class="summary-stat">
                <div class="number"><?php echo count($problems); ?></div>
                <div class="label">Problems Found</div>
            </div>
            <div class="summary-stat">
                <div class="number"><?php echo $total_checked; ?></div>
                <div class="label">Members Checked</div>
            </div>
            <div class="summary-stat">
                <div class="number"><?php echo current_time('g:i a'); ?></div>
                <div class="label">Check Time</div>
            </div>
        </div>
        
        <h2 style="color: #dc3545;">Users With Access Issues</h2>
        
        <?php foreach ($problems as $problem): 
            $level = pmpro_getLevel($problem['membership_id']);
        ?>
        <div class="problem-item">
            <h4><?php echo esc_html($problem['display_name']); ?> 
                (<?php echo esc_html($problem['email']); ?>)</h4>
            
            <div class="detail-row">
                <span class="detail-label">User ID:</span>
                <?php echo esc_html($problem['user_id']); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Membership:</span>
                <?php echo esc_html($level ? $level->name : 'ID ' . $problem['membership_id']); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Member Since:</span>
                <?php echo date('F j, Y', strtotime($problem['start_date'])); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Membership Status:</span>
                <span class="status-indicator <?php echo $problem['has_membership'] ? 'status-ok' : 'status-missing'; ?>">
                    <?php echo $problem['has_membership'] ? 'OK' : 'MISSING'; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Course Access:</span>
                <span class="status-indicator <?php echo $problem['has_course_access'] ? 'status-ok' : 'status-missing'; ?>">
                    <?php echo $problem['has_course_access'] ? 'OK' : 'MISSING'; ?>
                </span>
            </div>
            
            <a href="<?php echo admin_url('admin.php?page=pmpro-member&user_id=' . $problem['user_id']); ?>" 
               class="button">Fix Access</a>
        </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; 
                    text-align: center; color: #666; font-size: 12px;">
            <p>This is an automated report from PMPro Access Monitor</p>
            <p><?php echo get_bloginfo('name'); ?> | <?php echo home_url(); ?></p>
        </div>
    </div>
</body>
</html>

