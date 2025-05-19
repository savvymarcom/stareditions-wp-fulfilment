<?php

namespace SavvyWebFulfilment\Logs;

class LogModel
{
    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'savvy_web_logs';
    }

    public function getErrors($limit = 20, $offset = 0)
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table}
            WHERE response_code >= 400
            AND created_at >= NOW() - INTERVAL 7 DAY
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $limit, $offset
        );

        return $wpdb->get_results($sql);
    }

    public function countErrors()
    {
        global $wpdb;
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE response_code >= 400
             AND created_at >= NOW() - INTERVAL 7 DAY"
        );
    }

    public function searchErrors($term, $limit = 20, $offset = 0)
    {
        global $wpdb;
        $like = '%' . $wpdb->esc_like($term) . '%';

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table}
            WHERE response_code >= 400
            AND created_at >= NOW() - INTERVAL 7 DAY
            AND (
                endpoint LIKE %s OR
                message LIKE %s OR
                status LIKE %s OR
                order_ref LIKE %s
            )
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $like, $like, $like, $like, $limit, $offset
        );

        return $wpdb->get_results($sql);
    }

    public function countSearchResults($term)
    {
        global $wpdb;
        $like = '%' . $wpdb->esc_like($term) . '%';

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table}
            WHERE response_code >= 400
            AND created_at >= NOW() - INTERVAL 7 DAY
            AND (
                endpoint LIKE %s OR
                message LIKE %s OR
                status LIKE %s OR
                order_ref LIKE %s
            )",
            $like, $like, $like, $like
        );

        return $wpdb->get_var($sql);
    }

    public function deleteOlderThanDays($days)
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "DELETE FROM {$this->table} WHERE created_at < NOW() - INTERVAL %d DAY",
            $days
        );

        return $wpdb->query($sql);
    }

    public function clearAll()
    {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$this->table}");
    }
}
