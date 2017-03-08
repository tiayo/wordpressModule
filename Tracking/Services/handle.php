<?php

use Tracking\Model\PostMeta;

require_once __DIR__ . '/../Conf/Database.php';
require_once __DIR__ . '/../Model/PostMeta.php';

class handle
{
    protected $meta;

    public function __construct()
    {
        $new_meta = new PostMeta();
        $this->meta = $new_meta;
    }

    public function get()
    {
        $filename = $_FILES['file']['tmp_name'];//获取临时文件名
        if( $filename != null ) {
            $file = fopen($filename,"r");//打开临时文件
            while(!feof($file))
            {
                $value[] = fgetcsv($file);//按行读取数据
            }
            fclose($file);//关闭文件
            $value_key = $value[0];
            $key_num = count($value_key);//元素个数
            unset($value[0]);
            $one = array();
            foreach ($value as $row) {//循环构建数组
                for ($i=0;$i<$key_num;$i++) {
                    $one[$value_key[$i]] = $row[$i];
                }
                $data[] = $one;
            }
            return $data;
        }

        throw new Exception('File upload error');
    }

    public function database($data)
    {
        foreach ($data as $row) {
            //Remove empty items
            if (!empty($oreder_id = $row['Order ID']) && !empty($ttacking_number = $row['Traking Number'])) {
                //To determine whether the number already exists
                $all_post_id = $this->meta
                    ->select('post_id')
                    ->where('meta_key', '_traking_number')
                    ->get()
                    ->toArray();
                foreach ($all_post_id as $all_post_id_row) {
                    if ($all_post_id_row['post_id'] == $oreder_id) {
                        $status = true;
                        break;
                    }
                    $status = false;
                }

                //Non-existent,insert
                if($status == false) {
                    $this->meta
                        ->create([
                            'post_id' => $row['Order ID'],
                            'meta_key' => '_traking_number',
                            'meta_value' => $row['Traking Number']
                        ]);
                }

                //existent,update
                if($status == true) {
                    $this->meta
                        ->where('post_id', $row['Order ID'])
                        ->where('meta_key', '_traking_number')
                        ->update([
                            'meta_value' => $row['Traking Number']
                        ]);
                }
            }
        }
    }
}
