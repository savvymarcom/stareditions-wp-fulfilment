<?php

namespace SavvyWebFulfilment;

class Logger
{
    public static function log($orderId, string $method, string $endpoint, int $code, string $status, string $message): void
    {
        global $wpdb;

        $wpdb->insert("{$wpdb->prefix}savvy_web_logs", [
            'order_ref'         => $orderId,
            'request_method'    => strtoupper($method),
            'endpoint'          => $endpoint,
            'response_code'     => $code,
            'status'            => $status,
            'message'           => $message,
            'created_at'        => current_time('mysql'),
        ]);
    }
}
