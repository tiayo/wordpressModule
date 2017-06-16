<?php

namespace Foru\Order;

use Foru\Model\Order;

class All
{
    protected $order;
    public $filename;
    protected $handle;

    public function __construct()
    {
        $this->handle = new Handle();
        $this->order = new Order();
    }

    /**
     * 获取所有符合条件的订单信息
     * 将值传到子级进行处理，返回给父级.
     *
     * @return array
     */
    public function all()
    {
        $array = [];
        $result = $this->order
                        ->where('post_status', 'wc-processing')
                        ->orwhere('post_status', 'wc-on-hold')
                        ->get()
                        ->toArray();

        foreach ($result as $row) {
            $array[] = $this->handle->result($row['ID']);
        }

        return $array;
    }
}
