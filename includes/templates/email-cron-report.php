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
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PMPro Access Report</title>
    <style type="text/css">
        body { 
            background-color: #f7f7f7; 
            padding: 0; 
            text-align: center; 
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }
        
        #outer_wrapper { 
            background-color: #f7f7f7; 
        }
        
        #wrapper { 
            margin: 0 auto; 
            padding: 70px 0; 
            width: 100%; 
            max-width: 600px; 
            -webkit-text-size-adjust: none; 
        }
        
        #template_container { 
            background-color: #fff; 
            border: 1px solid #dedede; 
            box-shadow: 0 1px 4px rgba(0,0,0,.1); 
            border-radius: 3px; 
        }
        
        #template_header { 
            background: linear-gradient(135deg, #264584 0%, #1a3461 100%); 
            color: #fff; 
            border-bottom: 0; 
            font-weight: bold; 
            line-height: 100%; 
            vertical-align: middle; 
            font-family: 'Segoe UI', sans-serif; 
            border-radius: 3px 3px 0 0; 
        }
        
        #header_wrapper { 
            padding: 36px 48px; 
            display: block; 
        }
        
        #header_wrapper h1 { 
            font-family: 'Segoe UI', sans-serif; 
            font-size: 28px; 
            font-weight: 600; 
            line-height: 150%; 
            margin: 0; 
            color: #fff; 
            text-align: left; 
        }
        
        #header_wrapper p { 
            margin: 8px 0 0 0; 
            font-size: 13px; 
            color: #fff; 
            opacity: 0.85; 
            font-weight: normal; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .alert-banner { 
            background: linear-gradient(to right, #fff3cd 0%, #ffeaa7 100%); 
            border-bottom: 3px solid #f39c12; 
            padding: 20px 48px; 
        }
        
        .alert-banner p { 
            margin: 0; 
            color: #856404; 
            font-family: 'Segoe UI', sans-serif; 
            font-size: 14px; 
            line-height: 150%; 
        }
        
        #template_body { 
            background-color: #fff; 
        }
        
        #body_content { 
            background-color: #fff; 
        }
        
        .content-wrapper { 
            padding: 40px 48px 32px; 
        }
        
        .content-inner { 
            color: #636363; 
            font-family: 'Segoe UI', sans-serif; 
            font-size: 14px; 
            line-height: 150%; 
            text-align: left; 
        }
        
        .stat-box { 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center; 
            vertical-align: middle; 
        }
        
        .stat-box-red { 
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); 
        }
        
        .stat-box-blue { 
            background: linear-gradient(135deg, #264584 0%, #3d5a99 100%); 
        }
        
        .stat-number { 
            margin: 0; 
            font-size: 38px; 
            font-weight: 700; 
            color: #fff; 
            line-height: 1.2; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .stat-label { 
            margin: 8px 0 0 0; 
            font-size: 13px; 
            color: rgba(255,255,255,0.9); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            font-weight: 500; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .section-title { 
            color: #264584; 
            font-family: 'Segoe UI', sans-serif; 
            font-size: 18px; 
            font-weight: 700; 
            margin: 0 0 16px 0; 
        }
        
        .summary-table { 
            width: 100%; 
            margin-bottom: 35px; 
            border: 2px solid #e8e8e8; 
            border-radius: 8px; 
            overflow: hidden; 
        }
        
        .summary-table thead tr { 
            background: linear-gradient(to right, #f8f9fa 0%, #e9ecef 100%); 
        }
        
        .summary-table th { 
            padding: 14px 16px; 
            font-size: 12px; 
            color: #495057; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            border-bottom: 2px solid #dee2e6; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .summary-table tbody tr { 
            background-color: #fff; 
        }
        
        .summary-table td { 
            padding: 14px 16px; 
            border-bottom: 1px solid #f1f3f5; 
        }
        
        .member-name { 
            color: #264584; 
            font-weight: 700; 
            font-size: 14px; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .member-email { 
            font-size: 12px; 
            color: #6c757d; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .membership-name { 
            color: #212529; 
            font-size: 13px; 
            font-weight: 600; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .membership-date { 
            font-size: 12px; 
            color: #6c757d; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .status-badge { 
            display: inline-block; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-weight: 600; 
            font-size: 10px; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        .status-ok { 
            background-color: #d4edda; 
            color: #155724; 
        }
        
        .status-missing { 
            background-color: #f8d7da; 
            color: #721c24; 
        }
        
        .action-button { 
            display: inline-block; 
            padding: 6px 12px; 
            background-color: #264584; 
            color: #fff; 
            text-decoration: none; 
            border-radius: 4px; 
            font-size: 11px; 
            font-weight: 600; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        #template_footer { 
            padding: 24px 48px; 
            background-color: #f8f9fa; 
            border-top: 2px solid #e9ecef; 
        }
        
        #credit { 
            text-align: center; 
            color: #6c757d; 
            font-family: 'Segoe UI', sans-serif; 
            font-size: 12px; 
            line-height: 150%; 
        }
        
        #credit p { 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        #credit p:first-child { 
            margin: 0 0 8px 0; 
        }
        
        #credit p:last-child { 
            margin: 0; 
            font-size: 11px; 
            color: #adb5bd; 
        }
        
        @media screen and (max-width: 600px) {
            #header_wrapper { padding: 27px 36px !important; font-size: 24px; }
            #body_content table > tbody > tr > td { padding: 10px !important; }
            #body_content_inner { font-size: 10px !important; }
            .stat-box { width: 100% !important; display: block !important; margin-bottom: 15px !important; }
        }
    </style>
</head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
    <table width="100%" id="outer_wrapper" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td></td>
            <td width="600">
                <div id="wrapper" dir="ltr">
                    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper">
                        <tr>
                            <td align="center" valign="top">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container">
                                    <tr>
                                        <td align="center" valign="top">
                                            <!-- Header -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header">
                                                <tr>
                                                    <td id="header_wrapper">
                                                        <h1>PMPro Access Report</h1>
                                                        <p>Generated: <?php echo date('Y-m-d \a\t g:i A'); ?></p>
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- End Header -->
                                        </td>
                                    </tr>
                                    
                                    <!-- Alert Banner -->
                                    <tr>
                                        <td class="alert-banner">
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="padding: 0;">
                                                        <p><strong>⚠️ Action Required:</strong> Members below have access discrepancies requiring attention.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <td align="center" valign="top">
                                            <!-- Body -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
                                                <tr>
                                                    <td valign="top" id="body_content">
                                                        <!-- Content -->
                                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                            <tr>
                                                                <td valign="top" class="content-wrapper">
                                                                    <div class="content-inner">
                                                                        
                                                                        <!-- Dashboard Stats -->
                                                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 35px;">
                                                                            <tr>
                                                                                <td class="stat-box stat-box-red" width="49%" align="center" valign="middle">
                                                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                                                        <tr>
                                                                                            <td style="padding: 0;">
                                                                                                <p class="stat-number"><?php echo count($problems); ?></p>
                                                                                                <p class="stat-label">Problems<br>Found</p>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                                <td width="2%"></td>
                                                                                <td class="stat-box stat-box-blue" width="49%" align="center" valign="middle">
                                                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                                                        <tr>
                                                                                            <td style="padding: 0;">
                                                                                                <p class="stat-number"><?php echo $total_checked; ?></p>
                                                                                                <p class="stat-label">Members<br>Checked</p>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                        
                                                                        <!-- Quick Summary Table -->
                                                                        <h2 class="section-title">📊 Quick Overview</h2>
                                                                        
                                                                        <table cellspacing="0" cellpadding="0" border="0" class="summary-table">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th align="left">Member</th>
                                                                                    <th align="left">Membership</th>
                                                                                    <th align="center" width="120">Member Status</th>
                                                                                    <th align="center" width="120">Course Access</th>
                                                                                    <th align="center" width="80">Action</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <?php foreach ($problems as $problem): 
                                                                                    $level = pmpro_getLevel($problem['membership_id']);
                                                                                ?>
                                                                                <tr>
                                                                                    <td>
                                                                                        <span class="member-name"><?php echo esc_html($problem['display_name']); ?></span><br>
                                                                                        <span class="member-email"><?php echo esc_html($problem['email']); ?></span>
                                                                                    </td>
                                                                                    <td>
                                                                                        <span class="membership-name"><?php echo esc_html($level ? $level->name : 'ID ' . $problem['membership_id']); ?></span><br>
                                                                                        <span class="membership-date">Since: <?php echo date('M d, Y', strtotime($problem['start_date'])); ?></span>
                                                                                    </td>
                                                                                    <td align="center">
                                                                                        <span class="status-badge <?php echo $problem['has_membership'] ? 'status-ok' : 'status-missing'; ?>"><?php echo $problem['has_membership'] ? '✓ OK' : '✗ MISSING'; ?></span>
                                                                                    </td>
                                                                                    <td align="center">
                                                                                        <?php 
                                                                                        $missing_count = !empty($problem['missing_courses']) ? count($problem['missing_courses']) : 0;
                                                                                        $total_courses = isset($problem['total_courses']) ? intval($problem['total_courses']) : 0;
                                                                                        
                                                                                        if ($problem['has_course_access']) {
                                                                                            echo '<span class="status-badge status-ok">✓ OK</span>';
                                                                                        } else {
                                                                                            if ($total_courses > 0) {
                                                                                                echo '<span class="status-badge status-missing">✗ ' . esc_html($missing_count) . ' of ' . esc_html($total_courses) . ' unavailable</span>';
                                                                                            } else {
                                                                                                echo '<span class="status-badge status-missing">✗ MISSING</span>';
                                                                                            }
                                                                                        }
                                                                                        ?>
                                                                                    </td>
                                                                                    <td align="center">
                                                                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . intval($problem['user_id'])); ?>" class="action-button">Fix</a>
                                                                                    </td>
                                                                                </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                        
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <!-- End Content -->
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- End Body -->
                                        </td>
                                    </tr>
                                    
                                    <!-- Footer -->
                                    <tr>
                                        <td align="center" valign="top">
                                            <table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer">
                                                <tr>
                                                    <td valign="top">
                                                        <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                            <tr>
                                                                <td colspan="2" valign="middle" id="credit" align="center">
                                                                    <p><?php echo get_bloginfo('name'); ?></p>
                                                                    <p>This is an automated notification from PMPro Access Monitor. Please do not reply to this email.</p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- End Footer -->
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td></td>
        </tr>
    </table>
</body>
</html>