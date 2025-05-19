<?php

namespace SavvyWebFulfilment\Logs;

class LogService
{
    protected $model;

    public function __construct()
    {
        $this->model = new LogModel();
    }

    public function getPaginatedErrors(int $page, int $perPage, string $search = ''): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'savvy_web_logs';
        $offset = ($page - 1) * $perPage;

        $where = "status = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $params = [];

        if ($search) {
            $where .= " AND (message LIKE %s OR endpoint LIKE %s OR order_ref LIKE %s)";
            $searchWildcard = '%' . $wpdb->esc_like($search) . '%';
            $params = [$searchWildcard, $searchWildcard, $searchWildcard];
        }

        // Build the main logs query
        $query = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $allParams = array_merge($params, [$perPage, $offset]);

        if (!empty($params)) {
            $logs = $wpdb->get_results($wpdb->prepare($query, ...$allParams));
        } else {
            $logs = $wpdb->get_results($wpdb->prepare($query, $perPage, $offset));
        }

        // Build the total count query
        $countQuery = "SELECT COUNT(*) FROM $table WHERE $where";

        if (!empty($params)) {
            $total = $wpdb->get_var($wpdb->prepare($countQuery, ...$params));
        } else {
            $total = $wpdb->get_var($countQuery);
        }

        return [
            'logs' => $logs,
            'total' => (int) $total,
        ];
    }

    public function clearLogs()
    {
        return $this->model->clearAll();
    }

    public function cleanupOldLogs($days = 14)
    {
        return $this->model->deleteOlderThanDays($days);
    }
}
