<?php

namespace Foru\Product;

use League\Csv\Reader;

class Handle
{
    protected $page;

    public function __construct()
    {
        $this->page = 'foru_dropshipping';
    }

    function foruImportProducts()
    {
        if ($_FILES['import']['type'] != 'text/csv') {
            //执行失败
            $foru_message = urlencode('Import file must be CSV format！(import product)');
            $foru_status = 2;
            wp_redirect(admin_url('admin.php')."?page=".$this->page."&foru_message=".$foru_message."&foru_status=".$foru_status."");
            die();
        }
        $csv = Reader::createFromPath($_FILES['import']['tmp_name']);
        $this->extractProducts($csv->fetchAll());
        usleep(1010000);
        return true;
    }

    /**
     * 处理csv数据，转为易读数组，并传到子级处理.
     *
     * @param $csv
     */
    public function extractProducts($csv)
    {
        $products = [];
        $current_product = [];
        foreach ($csv as $key => $product) {
            if (strtolower($product[0]) == 'product') {
                if (!empty($current_product) && isset($current_product['name'])) {
                    $products[] = $current_product;
                }
                $current_product['name'] = empty($product[1]) ? '' : $product[1];
                $current_product['description'] = empty($product[2]) ? '' : $product[2];
                $current_product['images'] = $this->getImageArray($product[3]);
                $current_product['variations'] = [];
                continue;
            }
            if (empty($product)) {
                continue;
            }
            $current_product['variations'][] = $product;
        }
        if (!empty($current_product) && isset($current_product['name'])) {
            $products[] = $current_product;
        }

        $category = [
            0 => htmlspecialchars($_POST['category'], ENT_QUOTES),
        ];

        //Insert one by one,and record
        $count = count($products);
        $insert = new Insert();
        foreach ($products as $key => $data) {

            // Write progress
            $record = fopen(__DIR__.'/record.txt', 'w');
            $schedule = floor((($key) / $count) * 100);
            fwrite($record, $schedule);
            fclose($record);
            $average = floor((1 / $count) * 100);

            //insert to database
            $insert->insertProduct($data, $category, $schedule, $average);
        }
        /*
         *insert success
         * */
        $record = fopen(__DIR__.'/record.txt', 'w');
        $schedule = 100;
        fwrite($record, $schedule);
        fclose($record);
    }

    public function getImageArray($images)
    {
        $image_array = preg_split("/(\r\n|\n|\r)/", $images);
        foreach ($image_array as $item) {
            preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $item, $matches);
            if (empty($matches)) {
                continue;
            }
        }

        return $image_array;
    }
}
