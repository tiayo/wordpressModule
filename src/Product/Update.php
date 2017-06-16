<?php

namespace Foru\Product;

use Foru\Model\Post;
use Foru\Model\PostMeta;
use Foru\Model\TermRelationships;

require_once ABSPATH.'wp-admin'.'/includes/image.php';
require_once ABSPATH.'wp-admin'.'/includes/file.php';
require_once ABSPATH.'wp-admin'.'/includes/media.php';

class Update
{
    protected $post;
    protected $postMeta;
    protected $term_relationships;

    public function __construct()
    {
        $this->post = new Post();
        $this->postMeta = new PostMeta();
        $this->term_relationships = new TermRelationships();
    }

    public function update($product, $all_sku, $category)
    {
        $post_parent = $this->post
            ->select('post_parent')
            ->where('ID', $all_sku['post_id'])
            ->first()['post_parent'];

        $post_id = $all_sku['post_id'];

        //single product
        if ($post_parent == 0) {
            $post_parent = $post_id;
        }

        //判断是否需要删除属性
        $this->handleDelete($product, $post_parent);

        $post['post_content'] = strip_tags($product['description'], '<div><ul><li><p><h1><h2><img><ol>');
        $post['post_title'] = htmlspecialchars($product['name'], ENT_QUOTES);
        $post['post_status'] = 'publish';
        $this->post
            ->where('ID', $post_parent)
            ->update($post);

        //删除商品分类
        $this->term_relationships
            ->where('object_id', $post_parent)
            ->delete();

        //循环插入商品分类
        foreach ($category as $category_row) {
            $this->term_relationships
                ->create([
                    'object_id' => $post_parent,
                    'term_taxonomy_id' => $category_row,
                ]);
        }

        if (isset($product['images'])) {
            //delete picture first
            $this->postMeta
                ->where('post_id', $post_parent)
                ->where('meta_key', '_thumbnail_id')
                ->delete();
            $this->postMeta
                ->where('post_id', $post_parent)
                ->where('meta_key', '_product_image_gallery')
                ->delete();
            //RE add picture
            $this->insert_product_images($post_parent, $product['images']);
        }

        //update meta info
        if (count($product['variations']) > 1) {
            wp_set_object_terms($post_parent, 'variable', 'product_type', false);
            $attributes = $variations = [];
            foreach ($product['variations'] as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                $variations[] = $value;
            }
            foreach ($variations as $key => $value) {
                if (isset($value[4]) && !empty($value[4])) {
                    $attributes[] = $value[4];
                }
            }

            $this->insert_product_attributes($post_parent, $attributes);
            $this->insert_product_variations($post_id, $post_parent, $variations);
        } elseif (count($product['variations']) == 1) {

            //删除原商品属性
            $this->postMeta
                ->where('post_id', $post_parent)
                ->where(function ($q) {
                    $q->where('meta_key', '_sku')
                        ->orWhere('meta_key', '_price')
                        ->orWhere('meta_key', '_regular_price')
                        ->orWhere('meta_key', '_weight');
                })
                ->delete();

            //更新或重建商品属性信息
            $this->post
                ->where('ID', $post_parent)
                ->update([
                    'post_status' => 'publish',
                ]);

            $data = array(
                [
                    'post_id' => $post_parent,
                    'meta_key' => '_sku',
                    'meta_value' => empty($sku = 'FORU_'.$product['variations'][0][1]) ? '' : $sku,
                ],
                [
                    'post_id' => $post_parent,
                    'meta_key' => '_price',
                    'meta_value' => empty($price = $product['variations'][0][2]) ? '' : $price,
                ],
                [
                    'post_id' => $post_parent,
                    'meta_key' => '_regular_price',
                    'meta_value' => empty($regular_price = $product['variations'][0][2]) ? '' : $regular_price,
                ],
                [
                    'post_id' => $post_parent,
                    'meta_key' => '_weight',
                    'meta_value' => empty($weight = $product['variations'][0][3]) ? '' : $weight,
                ],
            );
            foreach ($data as $data_row) {
                $this->postMeta->create($data_row);
            }
        }
    }

    public function insert_product_images($post_id, $images, $schedule = 0, $average = 0)
    {
        $gallery = [];
        $count = count($images);
        foreach ($images as $key => $value) {
            $image = get_posts(array(
                'numberposts' => 1,
                'post_type' => 'attachment',
                'name' => $value,
                'fields' => 'ids',
            ));
            if (empty($image)) {
                $image = $this->product_media_sideload_image($value, 0, $value);
                if (!is_wp_error($image)) {
                    $gallery[] = $image;
                }
            } else {
                $gallery[] = empty($image[0]) ? 0 : $image[0];
            }
            /*
             * Write progress
             * */
            $record = fopen(__DIR__.'/record.txt', 'w');
            $schedule = $schedule + floor($average / $count);
            fwrite($record, $schedule);
            fclose($record);
        }
        $featured = array_shift($gallery);
        $gallery = implode(',', $gallery);
        $this->postMeta
            ->create([
                'post_id' => $post_id,
                'meta_key' => '_thumbnail_id',
                'meta_value' => empty($featured) ? '' : $featured,
            ]);
        $this->postMeta
            ->create([
                'post_id' => $post_id,
                'meta_key' => '_product_image_gallery',
                'meta_value' => empty($gallery) ? '' : $gallery,
            ]);
    }

    public function insert_product_attributes($post_parent, $attributes)
    {
        $product_attributes = $this->postMeta
            ->select('meta_value')
            ->where('post_id', $post_parent)
            ->where('meta_key', '_product_attributes')
            ->first()['meta_value'];

        $product_attributes = unserialize($product_attributes);

        foreach ($attributes as  $value) {
            $line = array_filter(explode(';', $value));
            foreach ($line as $key) {
                $pair = explode(':', $key);
                $pair[0] = strtolower($pair[0]);
                $explode = explode('|', $product_attributes[$pair[0]]['value']);
                foreach ($explode as $item) {
                    if ($item == $pair[1]) {
                        $is_ex = 0;
                        break;
                    }
                    $is_ex = 1;
                }
                if ($is_ex == 1) {
                    $product_attributes[$pair[0]]['value'] .= '|'.$pair[1];
                }
            }
        }

        $this->postMeta
            ->where('post_id', $post_parent)
            ->where('meta_key', '_product_attributes')
            ->update([
                'meta_value' => serialize($product_attributes),
            ]);
    }

    public function insert_product_variations($post_id, $post_parent, $variations)
    {
        foreach ($variations as $index => $variation) {
            $variation_post_id = $this->postMeta
                ->where('meta_value', 'FORU_'.$variation[1])
                ->first()['post_id'];

            if (empty($variation_post_id)) {
                $variation_post['post_title'] = 'Variation #'.$index.' of '.count($variations).' for product#'.$post_id;
                $variation_post['post_name'] = 'product-'.$post_id.'-variation-'.$index;
                $variation_post['post_status'] = 'publish';
                $variation_post['post_parent'] = $post_parent;
                $variation_post['post_type'] = 'product_variation';
                $variation_post['guid'] = home_url().'/?product_variation=product-'.$post_id.'-variation-'.$index;
                $variation_post_id = wp_insert_post($variation_post);
            } else {
                //如果商品在垃圾桶，修改商品状态
                $variation_post['post_status'] = 'publish';
                $this->post
                    ->where('ID', $variation_post_id)
                    ->update($variation_post);
            }

            //Delete first
            $this->postMeta
                ->where('post_id', $variation_post_id)
                ->where(function ($q) {
                    $q->where('meta_key', '_sku')
                        ->orWhere('meta_key', '_price')
                        ->orWhere('meta_key', '_regular_price')
                        ->orWhere('meta_key', '_weight');
                })
                ->delete();
            //Re create
            $data = array(
                [
                    'post_id' => $variation_post_id,
                    'meta_key' => '_sku',
                    'meta_value' => empty($sku = 'FORU_'.$variation[1]) ? '' : $sku,
                ],
                [
                    'post_id' => $variation_post_id,
                    'meta_key' => '_price',
                    'meta_value' => empty($variation[2]) ? '' : $variation[2],
                ],
                [
                    'post_id' => $variation_post_id,
                    'meta_key' => '_regular_price',
                    'meta_value' => empty($variation[2]) ? '' : $variation[2],
                ],
                [
                    'post_id' => $variation_post_id,
                    'meta_key' => '_weight',
                    'meta_value' => empty($variation[3]) ? '' : $variation[3],
                ],
            );
            foreach ($data as $data_row) {
                $this->postMeta
                    ->create($data_row);
            }

            if (isset($variation[4])) {
                $pairs = array_filter(explode(';', $variation[4]));
                foreach ($pairs as $key) {
                    $pair = explode(':', $key);
                    $pair[0] = strtolower($pair[0]);
                    //Delete first
                    $this->postMeta
                        ->where('post_id', $variation_post_id)
                        ->where('meta_key', 'attribute_'.strtolower($pair[0]))
                        ->delete();
                    //Re create
                    $this->postMeta
                        ->create([
                            'post_id' => $variation_post_id,
                            'meta_key' => 'attribute_'.strtolower($pair[0]),
                            'meta_value' => empty($pair[1]) ? '' : $pair[1],
                        ]);
                }
            }
        }
    }

    public function product_media_sideload_image($file, $post_id, $desc = null, $return = 'html')
    {
        if (!empty($file)) {
            // Set variables for storage, fix file filename for query strings.
            preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches);
            if (!$matches) {
                return new \WP_Error('image_sideload_failed', __('Invalid image URL'));
            }

            $file_array = array();
            $file_array['name'] = basename($matches[0]);

            // Download file to temp location.
            $file_array['tmp_name'] = $this->product_download_url($file);
            // If error storing temporarily, return the error.
            if (is_wp_error($file_array['tmp_name'])) {
                return $file_array['tmp_name'];
            }

            // Do the validation and storage stuff.
            $id = media_handle_sideload($file_array, $post_id, $desc);

            // If error storing permanently, unlink.
            if (is_wp_error($id)) {
                @unlink($file_array['tmp_name']);
            }

            return $id;
        }
    }

    public function product_download_url($url, $timeout = 300)
    {
        //WARNING: The file is not automatically deleted, The script must unlink() the file.
        if (!$url) {
            return new \WP_Error('http_no_url', __('Invalid URL Provided.'));
        }

        $url_filename = basename(parse_url($url, PHP_URL_PATH));

        $tmpfname = wp_tempnam($url_filename);
        if (!$tmpfname) {
            return new \WP_Error('http_no_file', __('Could not create Temporary file.'));
        }

        $response = wp_remote_get($url, array('timeout' => $timeout, 'stream' => true, 'filename' => $tmpfname));

        if (is_wp_error($response)) {
            unlink($tmpfname);

            return $response;
        }

        if (200 != wp_remote_retrieve_response_code($response)) {
            unlink($tmpfname);

            return new \WP_Error('http_404', trim(wp_remote_retrieve_response_message($response)));
        }

        $content_md5 = wp_remote_retrieve_header($response, 'content-md5');
        if ($content_md5) {
            $md5_check = verify_file_md5($tmpfname, $content_md5);
            if (is_wp_error($md5_check)) {
                unlink($tmpfname);

                return $md5_check;
            }
        }

        return $tmpfname;
    }

    public function handleDelete($product, $post_parent)
    {
        $exists = 0;
        $database_sku = [];

        //获取该商品所有sku
        $all_post_id = $this->post
            ->select('ID')
            ->where('post_parent', $post_parent)
            ->get()
            ->toArray();
        foreach ($all_post_id as $item) {
            $database_sku[] = $this->postMeta
                ->select('meta_value')
                ->where('post_id', $item['ID'])
                ->first()['meta_value'];
        }

        foreach ($database_sku as $sku) {
            //检查sku是否存在，不存在删除
            foreach ($product['variations'] as $value) {
                if ($value[0] != 'Variant') {
                    continue;
                }
                if ('FORU_'.$value[1] == $sku) {
                    $exists = 1;
                    break;
                }
                $exists = 0;
            }
            //不存在，删除属性
            if ($exists == 0) {
                //查询分类id
                $post_id = $this->postMeta
                    ->select('post_id')
                    ->where('meta_key', '_sku')
                    ->where('meta_value', $sku)
                    ->first()['post_id'];

                //删除分类
                $this->postMeta
                    ->where('post_id', $post_id)
                    ->delete();

                $this->post
                    ->where('ID', $post_id)
                    ->delete();
            }
        }
    }
}
