<?php

namespace Foru\Order;

use Foru\Model\Order;
use Foru\Model\OrderItem;
use Foru\Model\OrderItemMeta;
use Foru\Model\OrderMeta;

class Handle
{
    protected $order;
    protected $meta;
    protected $order_item;
    protected $order_item_meta;
    public $filename;

    public function __construct()
    {
        $this->order = new Order();
        $this->meta = new OrderMeta();
        $this->order_item = new OrderItem();
        $this->order_item_meta = new OrderItemMeta();
    }

    /**
     * 生成符合条件的订单信息数组.
     *
     * @param $id
     *
     * @return array
     */
    public function result($id)
    {
        $refund = $this->refund($id);

        $line_items = $this->order_item
            ->where('order_id', $id)
            ->where('order_item_type', 'line_item')
            ->get()
            ->toArray();

        $line_items_result = [];
        foreach ($line_items as $row) {
            $order_item_id = $row['order_item_id'];
            if ($this->judgeRefund($refund, $order_item_id)) {
                continue;
            }
            $product_id = $this->oderItemMeta($order_item_id, '_variation_id');
            $product_id = empty($product_id) ? $this->oderItemMeta($order_item_id, '_product_id') : $product_id;
            $sku = $this->orderMeta($product_id, '_sku');
            $sku_ex = explode('_', $sku);
            if ($sku_ex[0] != 'FORU') {
                continue;
            }
            $sku = $sku_ex[1];
            $order_meta = $this->orderMeta($id)->toArray();
            $order_meta_array = array();
            foreach ($order_meta as $order_meta_row) {
                $order_meta_array[$order_meta_row['meta_key']] = $order_meta_row['meta_value'];
            }

            $line_items_result[] = array(
                'order_number' => $id,
                'sku' => $sku,
                'product_name' => $row['order_item_name'],
                'quantity' => $this->oderItemMeta($order_item_id, '_qty'),
                'first_name' => $order_meta_array['_shipping_first_name'],
                'last_name' => $order_meta_array['_shipping_last_name'],
                'street_1' => $order_meta_array['_shipping_address_1']."\r".$order_meta_array['_shipping_address_2'],
                'city' => $order_meta_array['_shipping_city'],
                'state' => $order_meta_array['_shipping_state'],
                'zip' => $order_meta_array['_shipping_postcode'],
                'country' => $order_meta_array['_shipping_country'],
                'phone' => $order_meta_array['_billing_phone'],
                'email' => $order_meta_array['_billing_email'],
                );
        }

        return $line_items_result;
    }

    /**
     *查询orderMeta表信息.
     *
     * @param $id
     * @param null $meta_key
     *
     * @return string/object
     */
    public function orderMeta($id, $meta_key = null)
    {
        if ($meta_key != null) {
            return (string) $this->meta
                ->select('meta_value')
                ->where('post_id', $id)
                ->where('meta_key', $meta_key)
                ->first()['meta_value'];
        }

        return $this->meta
            ->select('meta_key', 'meta_value')
            ->where('post_id', $id)
            ->get();
    }

    /**
     * 查询oderItemMeta表信息.
     *
     * @param $id
     * @param $meta_key
     *
     * @return string
     */
    public function oderItemMeta($id, $meta_key)
    {
        return (string) $this->order_item_meta
            ->select('meta_value')
            ->where('order_item_id', $id)
            ->where('meta_key', $meta_key)
            ->first()['meta_value'];
    }

    /**
     * 获取订单中退款的商品
     *
     * @param $order_id
     */
    public function refund($order_id)
    {
        $order_item_id = $result = [];
        $product_id = $this->order
            ->join('woocommerce_order_items', 'posts.ID', '=', 'woocommerce_order_items.order_id')
            ->join('woocommerce_order_itemmeta', 'woocommerce_order_itemmeta.order_item_id', '=', 'woocommerce_order_items.order_item_id')
            ->select('woocommerce_order_itemmeta.meta_value')
            ->where('post_parent', $order_id)
            ->where('post_type', 'shop_order_refund')
            ->where('woocommerce_order_itemmeta.meta_key', '_product_id')
            ->get()
            ->toArray();

        foreach ($product_id as $item) {
            $result[] = $this->order_item_meta
                ->select('order_item_id')
                ->where('meta_value', $item['meta_value'])
                ->get()
                ->toArray();
        }

        foreach ($result as $items) {
            foreach ($items as $item) {
                $order_item_id[] = $item['order_item_id'];
            }
        }

        return $order_item_id;
    }

    /**
     * 判断是否为退款商品
     *
     * @param $refund
     * @param $order_item_id
     */
    public function judgeRefund($refund, $order_item_id)
    {
        foreach ($refund as $item) {
            if ($order_item_id == $item) {
                return true;
            }
        }

        return false;
    }
}
