<?php

namespace Foru\Api;

use Foru\Model\Options;
use Foru\Model\OrderItem;
use Foru\Model\OrderItemMeta;
use Foru\Model\Post;
use Foru\Model\PostMeta;
use Foru\Order\Handle;
use Requests;

class Monitor
{
    protected $option;
    protected $token;
    protected $post;
    protected $post_meta;
    protected $order_item;
    protected $order_item_meta;
    const URL = 'https://www.forudropshipping.com/api/orders/';

    public function __construct()
    {
        $this->post = new Post();
        $this->post_meta = new PostMeta();
        $this->order_item = new OrderItem();
        $this->order_item_meta = new OrderItemMeta();
        $this->option = new Options();
        $this->token = $this->option
            ->select('option_value')
            ->where('option_name', 'api_key')
            ->first()['option_value'];
    }

    /**
     * 向api提交新订单.
     *
     * @param $order_id
     */
    public function strore($order_id)
    {
        //get data
        $handle = new Handle();
        $data = $handle->result($order_id);

        if (empty($data)) {
            return;
        }

        //send api
        Requests::post(self::URL, array(
                'Accept' => 'application/json',
                'Token' => $this->token,
            ), array(
                'orders' => $data,
            ),
            array(
                'verify' => false,
            )
        );
    }

    /**
     * 向api提交更新地址请求
     *
     * @param $order_id
     *
     * @return bool
     */
    public function updateAddress($order)
    {
        $post_status = $this->post
            ->select('post_status')
            ->where('ID', $order['id'])
            ->first()['post_status'];

        if ($post_status != 'wc-processing') {
            return false;
        }

        if (empty($order)) {
            return false;
        }

        $data = array(
            'first_name' => $order['shipping']['first_name'],
            'last_name' => $order['shipping']['last_name'],
            'street_1' => $order['shipping']['address_1'].' '.$order['shipping']['address_2'],
            'city' => $order['shipping']['city'],
            'state' => $order['shipping']['state'],
            'zip' => $order['shipping']['postcode'],
            'country' => $order['shipping']['country'],
            'phone' => $order['billing']['phone'],
            'email' => $order['billing']['email'],
        );

        //send api
        Requests::put(self::URL.$order['id'], array(
            'Accept' => 'application/json',
            'Token' => $this->token,
        ), array(
            'json' => $data,
        ),
            array(
                'verify' => false,
            )
        );
    }

    /**
     * 向api请求删除订单.
     *
     * @param $order_id
     */
    public function delete($order_id)
    {
        Requests::delete(self::URL.$order_id, array(
            'Accept' => 'application/json',
            'Token' => $this->token,
        ),
            array(
                'verify' => false,
            )
        );
    }

    public function refunded($order_id, $refund_id)
    {
        $order_item_id = $this->order_item
            ->select('order_item_id')
            ->where('order_id', $refund_id)
            ->get()
            ->toArray();

        foreach ($order_item_id as $item) {
            $product_id = $this->order_item_meta
                ->select('meta_value')
                ->where('order_item_id', $item['order_item_id'])
                ->where('meta_key', '_product_id')
                ->first()['meta_value'];

            $sku = $this->post_meta
                ->select('meta_value')
                ->where('post_id', $product_id)
                ->where('meta_key', '_sku')
                ->first()['meta_value'];

            $sku = explode('_', $sku)[1];

            $quantity = $this->order_item_meta
                ->select('meta_value')
                ->where('order_item_id', $item['order_item_id'])
                ->where('meta_key', '_qty')
                ->first()['meta_value'];

            Requests::delete(self::URL.$order_id.'/items/'.$sku.'/'.abs($quantity), array(
                'Accept' => 'application/json',
                'Token' => $this->token,
            ),
                array(
                    'verify' => false,
                )
            );
        }
    }

    public function cancelledRefunded($order_id)
    {
        $this->strore($order_id);
    }
}
