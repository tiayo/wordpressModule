<?php

namespace Foru\Tracking;

use Foru\Model\PostMeta;

class Handle
{
    protected $meta;
    protected $page;

    public function __construct()
    {
        $new_meta = new PostMeta();
        $this->meta = $new_meta;
        $this->page = 'foru_dropshipping';
    }

    public function get()
    {
        if ($_FILES['file']['type'] != 'text/csv') {
            //执行失败
            $foru_message = urlencode('Import file must be CSV format！(import tracking)');
            $foru_status = 2;
            wp_redirect(admin_url('admin.php')."?page=".$this->page."&foru_message=".$foru_message."&foru_status=".$foru_status."");
            die();
        }
        $filename = $_FILES['file']['tmp_name']; //获取临时文件名
        if ($filename != null) {
            $file = fopen($filename, 'r'); //打开临时文件
            while (!feof($file)) {
                $value[] = fgetcsv($file); //按行读取数据
            }
            fclose($file); //关闭文件
            $value_key = $value[0];
            $key_num = count($value_key); //元素个数
            unset($value[0]);
            $one = array();
            foreach ($value as $row) {
                //循环构建数组
                for ($i = 0; $i < $key_num; ++$i) {
                    $one[$value_key[$i]] = $row[$i];
                }
                $data[] = [
                    'order_number' => $one['Order Number'],
                    'tracking_number' => $one['Tracking Number'],
                    'tracking_carrier' => $one['Tracking Carrier'],
                ];
            }

            return $data;
        }

        throw new \Exception('File upload error');
    }

    public function database($data)
    {
        foreach ($data as $row) {
            //Remove empty items
            if (
                !empty($oreder_id = htmlspecialchars($row['order_number'], ENT_QUOTES)) &&
                !empty($tacking_number = htmlspecialchars($row['tracking_number'], ENT_QUOTES)) &&
                !empty($express_type = htmlspecialchars($row['tracking_carrier'], ENT_QUOTES))
            ) {
                if (!is_numeric($oreder_id)) {
                    continue;
                }

                //删除当前订单的快递单号信息
                $this->meta
                    ->where('post_id', $oreder_id)
                    ->where(
                        function ($query) {
                            $query->where('meta_key', '_traking_number')
                            ->orwhere('meta_key', '_express_type');
                        }
                    )
                    ->delete();

                //插入当前订单的快递单号信息
                $this->meta
                    ->create([
                        'post_id' => $oreder_id,
                        'meta_key' => '_traking_number',
                        'meta_value' => $tacking_number,
                    ]);

                $this->meta
                    ->create([
                        'post_id' => $oreder_id,
                        'meta_key' => '_express_type',
                        'meta_value' => $express_type,
                    ]);
            }
        }
    }

    public function colunm_value($order_id, $data)
    {
        return $this->meta
            ->select('meta_value')
            ->where('post_id', $order_id)
            ->where('meta_key', $data)
            ->first()['meta_value'];
    }
}
