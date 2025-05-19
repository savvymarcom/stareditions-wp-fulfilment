<?php

namespace SavvyWebFulfilment\Api;

use WP_REST_Request;
use WP_REST_Response;
use SavvyWebFulfilment\Service\UpdaterService;
use SavvyWebFulfilment\Traits\TokenProtected;

class UpdaterController
{
    use TokenProtected;
    
    public function updateGithubDetails(WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        try {
            $pluginFile = defined('SAVVY_WEB_FULFILMENT_FILE') ? SAVVY_WEB_FULFILMENT_FILE : __FILE__;
            $updater = new UpdaterService($pluginFile);
            $updater->updateGithubToken($data);

            return new WP_REST_Response([
                'status' => 'success',
                'message' => 'Updater details updated successfully.',
            ], 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
