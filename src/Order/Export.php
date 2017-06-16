<?php

namespace Foru\Order;

class Export
{
    /**
     * 按照csv格式生成字符串.
     *
     * @param $data
     * @param null $filename
     * @param null $remark
     */
    public function csv($data, $filename = null, $remark = null)
    {
        $str = "Order Number,SKU,Product Name,Quantity,First Name,Last Name,Address,City,State,Zip Code,Country,Phone Number,Email\n";
        $data = $remark == 'one' ? array(0 => $data) : $data;
        foreach ($data as $data_row) {
            if (is_array($data_row) || is_object($data_row)) {
                foreach ($data_row as $key => $row) {
                    $str .= '"'.$row['order_number'].'"'.','.
                        '"'.$row['sku'].'"'.','.
                        '"'.$row['product_name'].'"'.','.
                        '"'.$row['quantity'].'"'.','.
                        '"'.$row['first_name'].'"'.','.
                        '"'.$row['last_name'].'"'.','.
                        '"'.$row['street_1'].'"'.','.
                        '"'.$row['city'].'"'.','.
                        '"'.$row['state'].'"'.','.
                        '"'.$row['zip'].'"'.','.
                        '"'.$row['country'].'"'.','.
                        '"'.$row['phone'].'"'.','.
                        '"'.$row['email'].'"'.','
                        ."\r\n";
                }
            }
        }
        $filename = $filename == null ? date('Ymd').'.csv' : $filename.'.csv';
        $this->exportCsv($filename, $str);
    }

    /**
     * 输出csv.
     *
     * @param $filename
     * @param $str
     */
    public function exportCsv($filename, $str)
    {
        header('Content-type:text/csv');
        header('Content-Disposition:attachment;filename='.$filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $str;
    }
}
