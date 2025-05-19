<?php

namespace SavvyWebFulfilment\Traits;

use WP_REST_Request;

trait TokenProtected
{
    public function checkAccessToken(WP_REST_Request $request): bool
    {
        $providedToken = $request->get_header('X-Savvy-Token');
        $storedToken = get_option('savvy_web_access_token');

        return $storedToken && hash_equals($storedToken, $providedToken);
    }
}
