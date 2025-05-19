<?php

namespace SavvyWebFulfilment\Formatter;

use SavvyWebFulfilment\Service\FulfilmentProviderService;
use WC_Order_Item_Product;

class OrderFormatter
{
    public function format(\WC_Order $order): array
    {
        $items = [];

        foreach ($order->get_items() as $item) {

            if (!($item instanceof WC_Order_Item_Product)) {
                continue;
            }
            
            $product = $item->get_product();

            // Only include items that should be fulfilled by Savvy Web
            $fulfilmentMethod = $product ? $product->get_meta('_fulfilment_provider') : '';

            //error_log('_fulfilment_provider ' . $product->get_meta('_fulfilment_provider'));

            $fulfilmentProviderService = new FulfilmentProviderService();

            if (!$fulfilmentProviderService->checkValidFulfilmentProvider($fulfilmentMethod)) {
                continue;
            }

            $image_id  = $product->get_image_id();
            $image_url = wp_get_attachment_image_url( $image_id, 'full' );

            $items[] = [
                'fulfillment_service' => $fulfilmentMethod,
                'item_name' => $item->get_name(),
                'item_price' => $product->get_price(),
                'item_sku' => $product ? $product->get_sku() : '',
                'item_product_id' => $product->get_id(),
                'item_qty' => $item->get_quantity(),
                'total' => $item->get_total(),
                'item_image' => $image_url ?? '',
            ];
        }

        $billing = [
            'billing_first_name' => ucwords(strtolower($order->get_billing_first_name())),
            'billing_last_name' => ucwords(strtolower($order->get_billing_last_name())),
            'billing_phone' => $order->get_billing_phone(),
            'billing_email' => strtolower($order->get_billing_email()),
            'billing_address_1' => $order->get_billing_address_1(),
            'billing_address_2' => $order->get_billing_address_2(),
            'billing_city' => $order->get_billing_city(),
            'billing_postcode' => $order->get_billing_postcode(),
            'billing_country' => $order->get_billing_country(),
            'billing_country_code' => $order->get_billing_country(),
        ];

        $shipping = [
            'shipping_first_name' => ucwords(strtolower($order->get_shipping_first_name())),
            'shipping_last_name' => ucwords(strtolower($order->get_shipping_last_name())),
            'shipping_phone' => $order->get_shipping_phone(),
            'shipping_email' => strtolower($order->get_billing_email()),
            'shipping_address_1' => $order->get_shipping_address_1(),
            'shipping_address_2' => $order->get_shipping_address_2(),
            'shipping_city' => $order->get_shipping_city(),
            'shipping_postcode' => $order->get_shipping_postcode(),
            'shipping_country' => $order->get_shipping_country(),
            'shipping_country_code' => $order->get_shipping_country(),
        ];

        return [
            'order_ref' => $order->get_id(),
            'order_placed_at' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'subtotal_price' => $order->get_subtotal(),
            'total_price' => $order->get_total(),
            'financial_status' => "paid",
            'currency' => $order->get_currency(),
            'total_tax' => $order->get_total_tax(),

            'shipping_cost' => $order->get_shipping_total(),
            'shipping_code' => $order->get_shipping_method(),
            'shipping_method' => $order->get_shipping_method(),

            'billing' => $billing,
            'shipping' => $shipping,
            'line_items' => $items,
            'status_callback_url' => $this->generateCallbackUrl($order),
        ];
    }

    protected function generateCallbackUrl(\WC_Order $order): string
    {
        return add_query_arg([
            'order_id' => $order->get_id(),
            'key'      => $order->get_order_key(),
            'token'    => $this->generateCallbackToken($order),
        ], rest_url('savvy-web/v1/callback'));
    }

    protected function generateCallbackToken(\WC_Order $order): string
    {
        $secret = get_option('savvy_web_access_token');

        return hash_hmac('sha256', $order->get_id() . '|' . $order->get_order_key(), $secret);
    }

}
