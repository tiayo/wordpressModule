<?php

namespace Foru\Api;

use Foru\Model\TermRelationships;
use Foru\Model\Terms;
use Foru\Model\TermTaxonomy;
use Foru\Product\Insert;

class ImportProduct
{
    protected $token;
    protected $term_relationships;
    protected $term_taxonomy;
    protected $terms;

    public function __construct()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $this->term_relationships = new TermRelationships();
        $this->terms = new Terms();
        $this->term_taxonomy = new TermTaxonomy();
    }

    /**
     * 接收数据并处理为数组发到下一级继续处理.
     */
    public function handle()
    {
        Verification::verification();
        $csv = json_decode(file_get_contents('php://input'), true);
        if (empty($csv) || !is_array($csv)) {
            $this->response('Product must be a array()!');
        }

        //handle data
        $products = [];
        $current_product = [];
        $category = $csv['categories'];

        foreach ($csv['products'] as $key => $product) {
            if (strtolower($product[0]) == 'product') {
                if (!empty($current_product) && isset($current_product['name'])) {
                    $products[] = $current_product;
                }
                $current_product['name'] = empty($product[1]) ? '' : $product[1];
                $current_product['description'] = empty($product[2]) ? '' : $product[2];
                if (!empty($product[3])) {
                    $current_product['images'] = $this->get_image_array($product[3]);
                }
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

        //add product
        $insert = new Insert();

        try {
            foreach ($products as $product_item) {
                $insert->insertProduct($product_item, $category);
            }
        } catch (\Exception $e) {
            $this->response($e->getMessage(), $e->getCode());
        }

        $this->response('success!', 200);
    }

    /**
     * 返回状态码和信息.
     *
     * @param $info
     * @param int $code
     */
    public function response($info, $code = 403)
    {
        http_response_code($code);
        echo $info;
        exit();
    }

    /**
     * 将图片字符串切成数组.
     *
     * @param $images
     *
     * @return array
     */
    public function get_image_array($images)
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
