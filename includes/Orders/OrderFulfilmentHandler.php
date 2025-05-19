<?php

namespace SavvyWebFulfilment\Orders;

use SavvyWebFulfilment\Admin\SavvyPluginConfig;
use WC_Order;
use SavvyWebFulfilment\Service\SavvyApiService;
use SavvyWebFulfilment\Formatter\OrderFormatter;
use SavvyWebFulfilment\Service\EmailService;

class OrderFulfilmentHandler
{
    private SavvyPluginConfig $savvyPluginConfig;
    private string $brandName;

    public function __construct(SavvyPluginConfig $savvyPluginConfig)
    {
        $this->savvyPluginConfig = $savvyPluginConfig;
        $this->brandName = $this->savvyPluginConfig->getSavvyBrandName();    
    }

    public function handle(WC_Order $order): void
    {
        $orderData = (new OrderFormatter())->format($order);

        if (empty($orderData['line_items'])) {
            $order->add_order_note("⚠️ No items fulfilled by {$this->brandName} - order skipped.");
            return;
        }

        try {
            $service = new SavvyApiService();
            $service->sendOrder($orderData);
            $order->add_order_note("✅ Order sent to {$this->brandName} Fulfilment.");

            $order->update_meta_data('_savvy_fulfilment_status', 'sent');
            $order->update_meta_data('_savvy_fulfilment_last_attempt', current_time('mysql'));
            $order->update_meta_data('_savvy_fulfilment_error_message', null);
            $order->save();

        } catch (\Exception $e) {

            $error = $e->getMessage();

            $order->add_order_note("❌ Error sending to {$this->brandName}: {$error}");

            $order->update_meta_data('_savvy_fulfilment_status', 'error');
            $order->update_meta_data('_savvy_fulfilment_error_message', $error);
            $order->update_meta_data('_savvy_fulfilment_last_attempt', current_time('mysql'));
            $order->save();

            //error_log("{$this->brandName} Fulfilment error: {$error}");

            (new EmailService())->sendFulfilmentErrorEmail($order, $error);
        }
    }

}