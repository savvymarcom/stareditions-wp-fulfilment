<?php 

namespace SavvyWebFulfilment\Api;

use WP_REST_Request;
use WP_REST_Response;
use SavvyWebFulfilment\Formatter\OrderFormatter;
use SavvyWebFulfilment\Service\SavvyApiService;
use SavvyWebFulfilment\Traits\TokenProtected;

class OrderApiController
{
    use TokenProtected;

    public function __construct()
    {
        //
    }

    public function getUnfulfilledOrders(WP_REST_Request $request): WP_REST_Response
    {
        $from = $request->get_param('from');
        $to = $request->get_param('to');
        $limit = min((int) $request->get_param('limit') ?: 50);

        $args = [
            'limit' => $limit,
            'status' => ['processing', 'on-hold', 'pending', 'failed'],
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($from) {
            $args['date_after'] = date('Y-m-d H:i:s', strtotime($from));
        }

        if ($to) {
            $args['date_before'] = date('Y-m-d H:i:s', strtotime($to));
        }

        $orders = wc_get_orders($args);

        $formatter = new \SavvyWebFulfilment\Formatter\OrderFormatter();
        $formatted = [];

        foreach ($orders as $order) {
            $formatted[] = $formatter->format($order);
        }

        return new WP_REST_Response($formatted, 200);
    }

    public function getOrderData(WP_REST_Request $request): WP_REST_Response
    {
        $orderId = (int) $request->get_param('id');
        $order = wc_get_order($orderId);

        if (!$order) {
            return new WP_REST_Response(['error' => 'Order not found'], 404);
        }

        $formatter = new OrderFormatter();
        return new WP_REST_Response($formatter->format($order), 200);
    }

    public function getSiteInfo(WP_REST_Request $request): WP_REST_Response
    {
        $service = new SavvyApiService();
        return new WP_REST_Response($service->getSiteInfo(), 200);
    }

}
