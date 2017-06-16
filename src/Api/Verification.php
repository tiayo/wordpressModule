<?php

namespace Foru\Api;

use Foru\Model\Options;

class Verification
{
    public static function verification()
    {
        $options = new Options();
        $response = new ImportProduct();
        $token = $options
            ->select('option_value')
            ->where('option_name', 'api_key')
            ->first()['option_value'];

        $get_token = $_SERVER['HTTP_TOKEN'];

        if ($token != $get_token) {
            $response->response('Token error!');
        }
    }
}
