<?php

namespace SavvyWebFulfilment\Service;

use SavvyWebFulfilment\Api\SavvyApiClient;

class SavvyApiService
{
    protected SavvyApiClient $client;

    public function __construct()
    {
        $this->client = new SavvyApiClient();
    }
 
    public function sendOrder(array $orderData): array
    {
        return $this->client->post('orders', $orderData, $orderData['order_ref']);
    }

    public function registerSite()
    {
        // $alreadyRegistered = get_option('savvy_web_registered', false);
        // if ($alreadyRegistered) {
        //     return;
        // }

        $siteData = $this->getSiteInfo();

        try {
            
            $response = $this->client->post('wordpress/register', $siteData, 'site-registration');

            if (
                (isset($response['error']) && $response['error']) || 
                (isset($response['status']) && $response['status'] === 'error')
            ){
                
                update_option('savvy_web_registered', false);
                return ['error' => true, 'message' =>  $response['message'] ];

            }else{

                if (
                    isset($response['updater']['wp_github_user']) &&
                    isset($response['updater']['wp_github_repo'])
                ) {
                    update_option('savvy_web_updater_user', sanitize_text_field($response['updater']['wp_github_user']));
                    update_option('savvy_web_updater_repo', sanitize_text_field($response['updater']['wp_github_repo']));
                }

                update_option('savvy_web_registered', true);

            }

            

        } catch (\Exception $e) {
            error_log('[SavvyWeb] Registration failed: ' . $e->getMessage());
        }
    }

    public function getSiteInfo(): array
    {
        return [
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'plugin_type' => 'stareditions',
            'plugin_version' => defined('SAVVY_WEB_FULFILMENT_VERSION') ? SAVVY_WEB_FULFILMENT_VERSION : 'unknown',
            'active_providers' => $this->getUsedFulfilmentProviders(),
            'php_version' => phpversion(),
            'timezone' => wp_timezone_string(),
            'wp_version' => get_bloginfo('version'),
        ];
    }

    private function getUsedFulfilmentProviders(): array
    {
        global $wpdb;

        $limit = 1000;

        $results = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_fulfilment_provider'
            AND pm.meta_value != ''
            AND p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
            ORDER BY p.ID DESC
            LIMIT %d
        ", $limit));

        return array_values(array_unique(array_filter($results)));
    }

}
