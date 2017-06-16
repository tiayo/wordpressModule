<?php

namespace Foru\Product;

use Foru\Model\Post;
use Foru\Model\PostMeta;
use Foru\Model\TermRelationships;
use Foru\Model\TermTaxonomy;

require_once ABSPATH.'wp-admin'.'/includes/image.php';
require_once ABSPATH.'wp-admin'.'/includes/file.php';
require_once ABSPATH.'wp-admin'.'/includes/media.php';

class Insert
{
    protected $post;
    protected $postMeta;
    protected $term_relationships;
    protected $term_taxonomy;

    public function __construct()
    {
        $this->post = new Post();
        $this->postMeta = new PostMeta();
        $this->term_relationships = new TermRelationships();
        $this->term_taxonomy = new TermTaxonomy();
    }

    /**
     * 插入产品
     *
     * @param $product
     * @param $category
     * @param int $schedule
     * @param int $average
     *
     * @return bool
     */
    public function insertProduct($product, $category, $schedule = 0, $average = 0)
    {
        /*handle product already exists start*/
        if (!empty($product['variations'][0][1])) {
            $sku_2 = $product['variations'][0][1];
            $all_sku = $this->postMeta
                ->select('meta_value', 'post_id')
                ->where('meta_key', '_sku')
                ->get()
                ->toArray();
            foreach ($all_sku as $all_sku_row) {
                $all_sku_ex = explode('-', explode('_', $all_sku_row['meta_value'])[1]);
                $if_sku = explode('-', $sku_2);
                if ($all_sku_ex[0] == $if_sku[0] && $all_sku_ex[1] == $if_sku[1]) {
                    $update_product = new Update();
                    $update_product->update($product, $all_sku_row, $category);

                    return true;
                }
            }
        }
        /*handle product already exists end*/

        $post['post_author'] = 1;
        $post['post_content'] = $product['description'];
        $post['post_status'] = 'publish';
        $post['post_title'] = $product['name'];
        $post['post_type'] = 'product';

        $post_id = wp_insert_post($post);

        //插入分类（数组）
        foreach ($category as $category_row) {
            $this->term_relationships
            ->create([
                'object_id' => $post_id,
                'term_taxonomy_id' => $category_row,
            ]);
        }

        $this->postMeta
            ->create([
                'post_id' => $post_id,
                'meta_key' => '_visibility',
                'meta_value' => 'visible',
            ]);

        $this->postMeta
            ->create([
                'post_id' => $post_id,
                'meta_key' => '_product_source',
                'meta_value' => 'foru_drop_shipping',
            ]);

        $this->insert_product_images($post_id, $product['images'], $schedule, $average);

        // wp_set_object_terms($post_id, explode(',',$product_data['category']), 'product_cat');
        if (count($product['variations']) > 1) {
            wp_set_object_terms($post_id, 'variable', 'product_type');
            $attributes = $variations = [];
            foreach ($product['variations'] as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                $variations[] = $value;
            }
            foreach ($product['variations'] as $key => $value) {
                if (isset($value[4]) && !empty($value[4])) {
                    $attributes[] = $value[4];
                }
            }
            $this->insertProductAttributes($post_id, $attributes);
            $this->insertProductVariations($post_id, $variations);
        } elseif (count($product['variations']) == 1) {
            $data = array(
                [
                    'post_id' => $post_id,
                    'meta_key' => '_sku',
                    'meta_value' => empty($sku = 'FORU_'.$product['variations'][0][1]) ? '' : $sku,
                ],
                [
                    'post_id' => $post_id,
                    'meta_key' => '_price',
                    'meta_value' => empty($price = $product['variations'][0][2]) ? '' : $price,
                ],
                [
                    'post_id' => $post_id,
                    'meta_key' => '_regular_price',
                    'meta_value' => empty($regular_price = $product['variations'][0][2]) ? '' : $regular_price,
                ],
                [
                    'post_id' => $post_id,
                    'meta_key' => '_weight',
                    'meta_value' => empty($weight = $product['variations'][0][3]) ? '' : $weight,
                ],
            );

            foreach ($data as $data_row) {
                $this->postMeta->create($data_row);
            }
        }
    }

    /**
     * 插入产品图片.
     *
     * @param $post_id
     * @param $images
     * @param $schedule
     * @param $average
     */
    public function insert_product_images($post_id, $images, $schedule, $average)
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

    /**
     * 插入产品元属性.
     *
     * @param $post_id
     * @param $attributes
     */
    public function insertProductAttributes($post_id, $attributes)
    {
        $product_attributes = [];
        foreach ($attributes as  $value) {
            $line = array_filter(explode(';', $value));
            foreach ($line as $key) {
                $pair = explode(':', $key);
                $product_attributes[$pair[0]][] = $pair[1];
            }
        }
        $product_attributes = array_map('array_unique', $product_attributes);
        $index = 0;
        foreach ($product_attributes as $key => $value) {
            $product_attributes[$key] = array(
                'name' => $key,
                'value' => implode('|', $value),
                'position' => $index,
                'is_visible' => 0,
                'is_variation' => 1,
                'is_taxonomy' => 0,
            );
            ++$index;
        }
        $this->postMeta
            ->create([
                'post_id' => $post_id,
                'meta_key' => '_product_attributes',
                'meta_value' => serialize($product_attributes),
            ]);
    }

    /**
     * 插入产品的子产品
     *
     * @param $post_id
     * @param $variations
     */
    public function insertProductVariations($post_id, $variations)
    {
        foreach ($variations as $index => $variation) {
            $variation_post = $this->post->initialization(); //Initialization data
            $variation_post['post_title'] = 'Variation #'.$index.' of '.count($variations).' for product#'.$post_id;
            $variation_post['post_name'] = 'product-'.$post_id.'-variation-'.$index;
            $variation_post['post_status'] = 'publish';
            $variation_post['post_parent'] = $post_id;
            $variation_post['post_type'] = 'product_variation';
            $variation_post['guid'] = home_url().'/?product_variation=product-'.$post_id.'-variation-'.$index;
            $variation_post_id = $this->post
                ->create($variation_post)
                ->ID;

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
                $this->postMeta->create($data_row);
            }

            if (isset($variation[4])) {
                $pairs = array_filter(explode(';', $variation[4]));
                foreach ($pairs as $key) {
                    $pair = explode(':', $key);
                    $pair[0] = strtolower($pair[0]);
                    $this->postMeta
                        ->create([
                            'post_id' => $variation_post_id,
                            'meta_key' => 'attribute_'.$pair[0],
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
}
