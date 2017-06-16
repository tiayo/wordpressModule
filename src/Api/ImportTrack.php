<?php

namespace Foru\Api;

use Foru\Tracking\Handle;

class ImportTrack
{
    protected $token;
    protected $term_relationships;
    protected $term_taxonomy;
    protected $terms;

    public function __construct()
    {
        set_time_limit(0);
        ignore_user_abort(true);
    }

    /**
     * 接收数据并处理为数组发到下一级继续处理.
     */
    public function handle()
    {
        $data = [];
        Verification::verification();
        $csv = json_decode(file_get_contents('php://input'), true);

        if (empty($csv) || !is_array($csv)) {
            $this->response('Tracking must be a array()!');
        }

        //delete order number suffix
        foreach ($csv as $item) {
            //            $item['order_number'] = explode('_', $item['order_number'])[0];
            $data[] = $item;
        }
        //send to handle
        $handle = new Handle();

        try {
            $handle->database($data);
        } catch (\Exception $e) {
            $this->response($e->getMessage(), $e->getCode());
        }

        $this->response('success!', 200);
    }

    /**
     * 返回状态码和信息.
     *
     * @param $info
     * @param int $code
     */
    public function response($info, $code = 403)
    {
        http_response_code($code);
        echo $info;
        exit();
    }
}
