<?php

namespace Order\Controller;

use Order\Model\Order;
require_once __DIR__ . '/../Conf/Database.php';
require_once __DIR__ . '/../Model/Order.php';
require_once __DIR__ . '/export.php';
require_once __DIR__ . '/handle.php';

class Date{

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

    public function byDate()
    {
        $date = htmlspecialchars($_GET['date']);
        if (empty($date)) {
            throw new \Exception('date is null');
        }
        $this->filename = $date;
        $date_array = explode(' to ', $date);
        if (count($date_array) == 2) {
            $start_date = date('Y-m-d H:i:s', strtotime($date_array['0']));
            $end_date = date('Y-m-d H:i:s', strtotime($date_array['1']));
            $result = $this -> order
                            -> where('post_date', '>=', $start_date)
                            -> where('post_date', '<=', $end_date)
                            -> whereIn('post_status', ['wc-processing','wc-on-hold'])
                            -> get()
                            -> toArray();

            foreach ($result as $row) {
                $array[] = $this -> handle -> result($row['ID'], $row);
            }
            return $array ??[];
        }


    }


}

