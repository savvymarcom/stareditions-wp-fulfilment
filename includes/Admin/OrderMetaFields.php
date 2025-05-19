<?php

namespace SavvyWebFulfilment\Admin;

class OrderMetaFields
{
    private SavvyPluginConfig $savvyPluginConfig;
    private string $brandName;

    public function __construct(SavvyPluginConfig $savvyPluginConfig)
    {
        $this->savvyPluginConfig = $savvyPluginConfig;
        $this->brandName = $this->savvyPluginConfig->getSavvyBrandName();

        add_action('add_meta_boxes', [$this, 'addOrderPageMetaBox']);
    }

    public function addOrderPageMetaBox()
    {
        $screen = function_exists('wc_get_page_screen_id')
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'savvy_web_order_tracking',
            "{$this->brandName} Order Tracking",
            [$this, 'renderSavvyTrackingMetaBox'],
            $screen,
            'side',
            'high'
        );
    }

    public function renderSavvyTrackingMetaBox($post)
    {
        
        $order = wc_get_order($post->ID);

        if (!$order) {
            echo '<p><em>Order not found.</em></p>';
            return;
        }

        $status = $order->get_meta('_savvy_fulfilment_status', true) ?: 'Not Sent';
        $last_attempt = $order->get_meta('_savvy_fulfilment_last_attempt', true);
        $error = $order->get_meta('_savvy_fulfilment_error_message', true);
        $tracking = $order->get_meta('_savvy_fulfilment_tracking_number', true);
        $carrier = $order->get_meta('_savvy_fulfilment_carrier', true);
        

        echo "<p><strong>Status:</strong> " . esc_html(ucfirst($status)) . "</p>";

        if ($last_attempt) {
            echo "<p><strong>Last Attempt:</strong><br>" . esc_html($last_attempt) . "</p>";
        }

        if ($error) {
            echo "<p style='color: red;'><strong>Error:</strong><br>" . esc_html($error) . "</p>";

            $resend_url = admin_url("admin-post.php?action=savvy_resend_fulfilment&order_id={$order->get_id()}");
            echo '<a href="' . esc_url($resend_url) . '" class="button">Resend to Fulfilment</a>';
        }

        if ($tracking) {
            echo "<p><strong>Tracking Number:</strong><br>" . esc_html($tracking) . "</p>";
        }

        if ($carrier) {
            echo "<p><strong>Carrier Name:</strong><br>" . esc_html($carrier) . "</p>";
        }

    }
}
