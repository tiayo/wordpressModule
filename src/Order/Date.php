<?php

namespace Foru\Order;

use Foru\Model\Order;

class Date
{
    protected $order;
    public $filename;
    protected $handle;
    protected $page;

    public function __construct()
    {
        $this->page = 'foru_dropshipping';
        $this->order = new Order();
        $this->handle = new Handle();

    }

    /**
     * 获取所有符合条件的订单信息
     * 将值传到子级进行处理，返回给父级.
     *
     * @return array
     */
    public function byDate()
    {
        $date = htmlspecialchars($_GET['date'], ENT_QUOTES);
        if (empty($date)) {
            //执行失败
            $foru_message = urlencode('Date is null!');
            $foru_status = 2;
            wp_redirect(admin_url('admin.php')."?page=".$this->page."&foru_message=".$foru_message."&foru_status=".$foru_status."");
            die();
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
            $array = [];
            foreach ($result as $row) {
                $array[] = $this -> handle -> result($row['ID']);
            }

            return $array;
        }
    }
}
