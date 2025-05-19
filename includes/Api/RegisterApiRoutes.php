<?php 

namespace SavvyWebFulfilment\Api;

use SavvyWebFulfilment\Callback\CallbackHandler;

class RegisterApiRoutes
{
    protected OrderApiController $orderController;
    protected CallbackHandler $callbackHandler;
    protected UpdaterController $updaterController;

    public function __construct()
    {
        $this->orderController = new OrderApiController();
        $this->callbackHandler = new CallbackHandler();
        $this->updaterController = new UpdaterController();
        
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes()
    {
        // Order routes
        register_rest_route('savvy-web/v1', '/order/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this->orderController, 'getOrderData'],
            'permission_callback' => [$this->orderController, 'checkAccessToken'],
        ]);

        register_rest_route('savvy-web/v1', '/orders/unfulfilled', [
            'methods' => 'GET',
            'callback' => [$this->orderController, 'getUnfulfilledOrders'],
            'permission_callback' => [$this->orderController, 'checkAccessToken'],
        ]);

        register_rest_route('savvy-web/v1', '/site-info', [
            'methods' => 'GET',
            'callback' => [$this->orderController, 'getSiteInfo'],
            'permission_callback' => [$this->orderController, 'checkAccessToken'],
        ]);

        // Callback routes
        register_rest_route('savvy-web/v1', '/callback', [
            'methods' => ['POST', 'GET'],
            'callback' => [$this->callbackHandler, 'handleCallback'],
            'permission_callback' => '__return_true',
        ]);

        // Updater routes
        register_rest_route('savvy-web/v1', '/updater/token', [
            'methods' => 'POST',
            'callback' => [$this->updaterController, 'updateGithubDetails'],
            'permission_callback' => [$this->updaterController, 'checkAccessToken'],
        ]);

    }

}