<?php

namespace Order\Controller;

class Export
{
    public function csv($data, $filename = null, $remark = null)
    {
        $str = "order number, SKU, quantity, name, address, city, state, zip code, country, phone number\n";
        $data = $remark == 'one' ? array(0 => $data):$data;
        foreach ($data as $data_row) {
            foreach ($data_row as $num=>$row){
                $str_item = null;
                $str .=  '"'.$row['id']."_".($num+1).'"'.','.
                         '"'.$row['SKU'].'"'.','.
                         '"'.$row['quantity'].'"'.','.
                         '"'.$row['name'].'"'.','.
                         '"'.$row['address'].'"'.','.
                         '"'.$row['city'].'"'.','.
                         '"'.$row['state'].'"'.','.
                         '"'.$row['zip_code'].'"'.','.
                         '"'.$row['country'].'"'.','.
                         '"'.$row['phone_number'].'"'.','
                         ."\r\n";
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