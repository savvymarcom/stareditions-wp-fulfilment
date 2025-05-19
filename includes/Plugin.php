<?php

namespace SavvyWebFulfilment;

use SavvyWebFulfilment\Orders\OrderFulfilmentHandler;
use SavvyWebFulfilment\Orders\OrderResendHandler;
use SavvyWebFulfilment\Admin\ProductMetaFields;
use SavvyWebFulfilment\Admin\OrderMetaFields;
use SavvyWebFulfilment\Admin\SavvyPluginConfig;
use SavvyWebFulfilment\Admin\SettingsPage;
use SavvyWebFulfilment\Api\OrderApiController;
use SavvyWebFulfilment\Api\RegisterApiRoutes;
use SavvyWebFulfilment\Logs\LogCleanupScheduler;
use SavvyWebFulfilment\Service\UpdaterService;

class Plugin
{

    private SavvyPluginConfig $savvyPluginConfig;

    public function __construct()
    {
        add_action('init', [$this, 'init']);  
    }

    public function init()
    {

        $this->savvyPluginConfig = new SavvyPluginConfig();
    
        if (is_admin()) {
            new SettingsPage($this->savvyPluginConfig);
            new ProductMetaFields($this->savvyPluginConfig);
            new OrderMetaFields($this->savvyPluginConfig);
            new OrderResendHandler($this->savvyPluginConfig); 
            new UpdaterService(SAVVY_WEB_FULFILMENT_FILE);
        }

        new LogCleanupScheduler();
        new OrderApiController();
        new RegisterApiRoutes();

        add_action('woocommerce_payment_complete', [$this, 'handleOrderPaymentComplete']);
        //add_action('woocommerce_new_order', [$this, 'handleOrderPaymentComplete']);

    }

    public function handleOrderPaymentComplete($orderId)
    {
        $order = wc_get_order($orderId);

        if (!$order) {
            return;
        }

        $handler = new OrderFulfilmentHandler($this->savvyPluginConfig);
        $handler->handle($order);

    }

    public static function activate()
    {
        global $wpdb;

        $logTable = $wpdb->prefix . 'savvy_web_logs';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $logTable (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            order_ref VARCHAR(20) DEFAULT NULL,
            request_method VARCHAR(10),
            endpoint VARCHAR(255),
            response_code SMALLINT,
            status VARCHAR(20),
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        LogCleanupScheduler::deactivate();
        update_option('savvy_web_registered', false);
        flush_rewrite_rules();
    }
}
