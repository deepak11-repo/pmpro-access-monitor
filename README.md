# PMPro Access Monitor

A WordPress plugin that monitors membership purchases and periodically checks for access discrepancies.

## Plugin Structure

This plugin follows WordPress best practices with a modular, organized file structure:

```
pmpro-access-monitor/
├── pmpro-access-monitor.php          # Main plugin file
├── README.md                          # This file
└── includes/
    ├── class-pmpro-access-monitor.php              # Core plugin class
    ├── class-pmpro-access-monitor-email.php        # Email sending and template loader
    ├── class-pmpro-access-monitor-helpers.php      # Utility functions
    ├── templates/                                  # Email templates folder
    │   ├── email-purchase-alert.php                # Purchase alert email template
    │   └── email-cron-report.php                   # Cron report email template
    └── checks/
        ├── class-pmpro-access-monitor-purchase-check.php    # Purchase check (trigger)
        ├── class-pmpro-access-monitor-course-access-alert.php # Course access alert check
        └── class-pmpro-access-monitor-scheduled-check.php  # Scheduled/cron check
```

## File Descriptions

### Main Plugin File
- **pmpro-access-monitor.php**: Main entry point with plugin headers, constants, and class loader

### Core Classes
- **class-pmpro-access-monitor.php**: Main orchestrator class that handles initialization, hooks, admin menu, and settings
- **class-pmpro-access-monitor-email.php**: Email sending functionality and template loader
- **class-pmpro-access-monitor-helpers.php**: Utility functions for course access checking, new member detection, etc.

### Email Templates
- **email-purchase-alert.php**: Template for new member purchase alerts and course access alerts
- **email-cron-report.php**: Template for scheduled cron job access check reports

> **Note**: Templates can be overridden by placing them in your theme's `pmpro-access-monitor/` folder

### Check Classes
- **class-pmpro-access-monitor-purchase-check.php**: Handles immediate check after new member purchase (triggered via `pmpro_after_checkout` hook)
- **class-pmpro-access-monitor-course-access-alert.php**: Handles alerts when course access is missing after membership purchase
- **class-pmpro-access-monitor-scheduled-check.php**: Handles scheduled cron job checks for all active members

## Features

1. **New Member Purchase Alerts**: Triggers alerts only for first-time member purchases
2. **Scheduled Access Checks**: Periodic cron job checks all active members for access issues
3. **Email Notifications**: HTML email templates for purchase alerts and scheduled reports
4. **Admin Interface**: Settings page for configuring email recipients and course mappings

## Requirements

- WordPress 5.0+
- PHP 7.2+
- Paid Memberships Pro plugin (active)

## Installation

1. Upload the `pmpro-access-monitor` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure email settings in PMPro → Access Monitor

## Version History

- **1.2.0**: Refactored into modular structure following WordPress best practices
- **1.1.0**: Added new member detection
- **1.0.0**: Initial release

