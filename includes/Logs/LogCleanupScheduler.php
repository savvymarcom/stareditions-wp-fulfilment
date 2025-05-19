<?php 

namespace SavvyWebFulfilment\Logs;

class LogCleanupScheduler
{
    public function __construct()
    {
        add_action('init', [$this, 'scheduleCleanup']);
        add_action('savvy_web_delete_old_logs', [$this, 'deleteOldLogs']);
    }

    public function scheduleCleanup()
    {
        if (!wp_next_scheduled('savvy_web_delete_old_logs')) {
            wp_schedule_event(time(), 'daily', 'savvy_web_delete_old_logs');
        }
    }

    public function deleteOldLogs()
    {
        $logModel = new LogModel();
        $deleted = $logModel->deleteOlderThanDays(14);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Savvy Web] Deleted $deleted log(s) older than 14 days.");
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('savvy_web_delete_old_logs');
    }
}
