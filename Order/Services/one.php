<?php

namespace Order\Controller;

use Order\Model\Order;
require_once __DIR__ . '/../Conf/Database.php';
require_once __DIR__ . '/../Model/Order.php';
require_once __DIR__ . '/export.php';
require_once __DIR__ . '/handle.php';

class One{

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

    public function one()
    {
        $id = htmlspecialchars($_GET['id']);
        if (empty($id)) {
            return 'id is null';
            exit();
        }
        $this->filename = $id;
        $result = $this -> order
                        -> find($id)
                        -> toArray();

        return $this -> handle -> result($id, $result) ??[];
    }



}
