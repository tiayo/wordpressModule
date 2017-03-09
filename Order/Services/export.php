<?php

namespace Order\Controller;

class Export
{
    public function csv($data, $filename = null, $remark = null)
    {
        $str = "Order Number, SKU, Product Name, Quantity, First Name, Last Name, Address, City, State, Zip Code, Country, Phone Number, Email\n";
        $data = $remark == 'one' ? array(0 => $data):$data;
        foreach ($data as $data_row) {
            if (is_array($data_row) || is_object($data_row)) {
                foreach ($data_row as $key => $row) {
                    $str .= '"' . $row['id'] . '"' . ',' .
                        '"' . $row['SKU'] . '"' . ',' .
                        '"' . $row['product_name'] . '"' . ',' .
                        '"' . $row['quantity'] . '"' . ',' .
                        '"' . $row['first_name'] . '"' . ',' .
                        '"' . $row['last_name'] . '"' . ',' .
                        '"' . $row['address'] . '"' . ',' .
                        '"' . $row['city'] . '"' . ',' .
                        '"' . $row['state'] . '"' . ',' .
                        '"' . $row['zip_code'] . '"' . ',' .
                        '"' . $row['country'] . '"' . ',' .
                        '"' . $row['phone_number'] . '"' . ',' .
                        '"' . $row['email'] . '"' . ','
                        . "\r\n";
                }
            }
        }
        $filename = $filename == null ? date('Ymd').'.csv' : $filename.'.csv';
        $this->export_csv($filename, $str);
    }

    public function export_csv($filename, $str)
    {
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $str;
    }

}
