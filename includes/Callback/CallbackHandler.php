<?php

namespace SavvyWebFulfilment\Callback;

use WP_REST_Request;
use WP_REST_Response;
use SavvyWebFulfilment\Service\EmailService;
use SavvyWebFulfilment\Service\FulfilmentProviderService;
use WC_Order;
use WC_Order_Item_Product;

class CallbackHandler
{
    public function __construct()
    {
        // 
    }

    public function handleCallback(WP_REST_Request $request): WP_REST_Response
    {
        $order_id = absint($request->get_param('order_id'));
        $key = sanitize_text_field($request->get_param('key'));
        $token = sanitize_text_field($request->get_param('token'));

        $order = wc_get_order($order_id);

        if (!$order || $order->get_order_key() !== $key) {
            return new WP_REST_Response(['message' => 'Invalid order or key'], 403);
        }

        $expected_token = hash_hmac('sha256', $order_id . '|' . $order->get_order_key(), get_option('savvy_web_access_token'));
        if (!hash_equals($expected_token, $token)) {
            return new WP_REST_Response(['message' => 'Invalid token'], 403);
        }

        // Determine content type and parse data
        $data = $request->get_json_params() ?: $request->get_params();

        $status   = sanitize_text_field($data['fulfilment']['status'] ?? '');
        $tracking = sanitize_text_field($data['fulfilment']['tracking_number'] ?? '');
        $carrier = sanitize_text_field($data['fulfilment']['carrier'] ?? '');

        $noteLines = [];

        if ($status) {
            $order->update_meta_data('_savvy_fulfilment_status', $status);
            $noteLines[] = "📦 Fulfilment status: {$status}";
        }

        if ($tracking) {
            $order->update_meta_data('_savvy_fulfilment_tracking_number', $tracking);
            $noteLines[] = "🔁 Tracking number: {$tracking}";
        }

        if ($carrier) {
            $order->update_meta_data('_savvy_fulfilment_carrier', $carrier);
            $noteLines[] = "🚚 Carrier: {$carrier}";
        }

        if (!empty($noteLines)) {
            $order->add_order_note(implode("\n", $noteLines));
        }

        // if ($status === 'fulfilled') {
        //     (new EmailService())->sendFulfilmentStatusUpdateEmail($order->get_id(), $status, $tracking, $carrier);

        //     if ($this->checkOrderItemsToComplete($order) && $order->get_status() !== 'completed') {
        //         $order->update_status('completed');
        //         $order->add_order_note('✅ Order automatically marked as Completed.');
        //     }
        // }
        (new EmailService())->sendFulfilmentStatusUpdateEmail($order->get_id(), $status, $tracking, $carrier);

        $order->save();

        return new WP_REST_Response(['message' => 'Order updated']);
    }

    private function checkOrderItemsToComplete(WC_Order $order): bool
    {
        foreach ($order->get_items() as $item) {
            if (!($item instanceof WC_Order_Item_Product)) {
                continue;
            }

            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $fulfilmentMethod = $product->get_meta('_fulfilment_provider');
            $fulfilmentProviderService = new FulfilmentProviderService();

            if (!$fulfilmentProviderService->checkValidFulfilmentProvider($fulfilmentMethod)) {
                return false;
            }
        }

        return true;
    }
}
