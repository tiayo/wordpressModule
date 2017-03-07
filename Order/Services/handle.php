<?php

namespace Order\Controller;

use Order\Model\Order;
use Order\Model\OrderItem;
use Order\Model\OrderItemMeta;
use Order\Model\OrderMeta;

require_once __DIR__ . '/../Conf/Database.php';
require_once __DIR__ . '/../Model/Order.php';
require_once __DIR__ . '/../Model/OrderItem.php';
require_once __DIR__ . '/../Model/OrderItemMeta.php';
require_once __DIR__ . '/../Model/OrderMeta.php';
require_once __DIR__ . '/export.php';

class handle{

    protected $order;
    protected $meta;
    protected $order_item;
    protected $order_item_meta;
    public $filename;

    public function __construct()
    {
        $new_order= new Order();
        $new_meta = new OrderMeta();
        $new_order_item = new OrderItem();
        $new_order_item_meta = new OrderItemMeta();

        $this->order = $new_order;
        $this->meta = $new_meta;
        $this->order_item = $new_order_item;
        $this->order_item_meta = $new_order_item_meta;
    }

    public function result($id, $result)
    {
        $line_items = $this -> order_item
            -> where('order_id', $id)
            -> get()
            -> toArray();

        foreach ($line_items as $row) {
            $order_item_id = $row['order_item_id'];
            $product_id = $this->oderItemMeta($order_item_id, '_product_id');
            $order_meta =  $this->oderMeta($id)->toArray();
            $order_meta_array = array();
            foreach ($order_meta as $order_meta_row) {
                $order_meta_array[$order_meta_row['meta_key']] = $order_meta_row['meta_value'];
            }
            $line_items_result[] = array(
                'id' => $result['ID'],
                'SKU' => $this->oderMeta($product_id, '_sku'),
                'quantity' => $this->oderItemMeta($order_item_id, '_qty'),
                'name' => $order_meta_array['_shipping_first_name'].' '.$order_meta_array['_shipping_last_name'],
                'address' => $order_meta_array['_shipping_address_1'].' '.$order_meta_array['_shipping_address_2'],
                'city' => $order_meta_array['_shipping_city'],
                'state' => $order_meta_array['_shipping_state'],
                'zip_code' => $order_meta_array['_shipping_postcode'],
                'country' => $order_meta_array['_shipping_country'],
                'phone_number' => $order_meta_array['_billing_phone'],
                );
        }
        return $line_items_result;
    }

    public function oderMeta($id, $meta_key = null)
    {
        if ($meta_key != null) {
            return (string)$this -> meta
                                 -> select('meta_value')
                                 -> where('post_id', $id)
                                 -> where('meta_key', $meta_key)
                                 -> first()['meta_value'];
        } else {
            return $this -> meta
                         -> select('meta_key', 'meta_value')
                         -> where('post_id', $id)
                         -> get();
        }

    }

    public function oderItemMeta($id, $meta_key)
    {
        return (string)$this -> order_item_meta
                                 -> select('meta_value')
                                 -> where('order_item_id', $id)
                                 -> where('meta_key', $meta_key)
                                 -> first()['meta_value'];
    }

}
