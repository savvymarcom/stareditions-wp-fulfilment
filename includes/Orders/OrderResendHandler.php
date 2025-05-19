<?php 

namespace SavvyWebFulfilment\Orders;

use SavvyWebFulfilment\Admin\SavvyPluginConfig;
use SavvyWebFulfilment\Service\EmailService;
use SavvyWebFulfilment\Service\SavvyApiService;

class OrderResendHandler
{
    private SavvyPluginConfig $savvyPluginConfig;
    private string $brandName;

    public function __construct(SavvyPluginConfig $savvyPluginConfig)
    {
        $this->savvyPluginConfig = $savvyPluginConfig;
        $this->brandName = $this->savvyPluginConfig->getSavvyBrandName();

        add_action('admin_post_savvy_resend_fulfilment', [$this, 'handle']);
    }

    public function handle()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied');
        }

        $order_id = absint($_GET['order_id'] ?? 0);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_die('Order not found');
        }

        try {
            $orderData = (new \SavvyWebFulfilment\Formatter\OrderFormatter())->format($order);
            (new SavvyApiService())->sendOrder($orderData);

            $order->update_meta_data('_savvy_fulfilment_status', 'sent');
            $order->update_meta_data('_savvy_fulfilment_last_attempt', current_time('mysql'));
            $order->update_meta_data('_savvy_fulfilment_error_message', null);
            $order->save();

            $order->add_order_note("🔁 Order resent to {$this->brandName} Fulfilment.");
        } catch (\Throwable $e) {

            $error = $e->getMessage();

            $order->update_meta_data('_savvy_fulfilment_status', 'error');
            $order->update_meta_data('_savvy_fulfilment_error_message', $error);
            $order->update_meta_data('_savvy_fulfilment_last_attempt', current_time('mysql'));
            $order->save();

            // Email admin
            (new EmailService())->sendFulfilmentErrorEmail($order, $error);

            $order->add_order_note("❌ Error resending to {$this->brandName}: {$error}");
        }

        wp_safe_redirect(admin_url("post.php?post={$order->get_id()}&action=edit"));
        exit;
    }
}
