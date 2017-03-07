<?php

namespace Order\Controller;

use Order\Model\Order;
require_once __DIR__ . '/../Conf/Database.php';
require_once __DIR__ . '/../Model/Order.php';
require_once __DIR__ . '/export.php';
require_once __DIR__ . '/handle.php';

class All{

    protected $order;
    public $filename;
    protected $handle;

    public function __construct()
    {
        $new_order= new Order();
        $new_handle = new handle();

        $this->order = $new_order;
        $this->handle = $new_handle;

    }

    public function all()
    {
        $result = $this -> order
                        -> where('post_status', 'wc-processing')
                        -> orwhere('post_status', 'wc-on-hold')
                        -> get()
                        -> toArray();

        foreach ($result as $row) {
            $array[] = $this -> handle -> result($row['ID'], $row);
        }
        return $array ??[];
    }

}

