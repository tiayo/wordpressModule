<?php
/*
Plugin Name: FORU Droshiping
Description: This is an WooCommerce plugin
Author: zxj
Version: 2.0
*/
require_once (ABSPATH . 'wp-admin/includes/plugin.php' );
require_once __DIR__ . '/database/database.php';
require_once __DIR__.'/vendor/autoload.php';

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    echo 'WooCommerce no Activate/install!';
    exit();
}

class Forudropshipping
{
    protected $run;
    protected $status;
    protected $message;
    protected $page;

    public function __construct()
    {
        $this->run = 0;
        $this->page = 'foru_dropshipping';
        $this->init_hooks();
        $this->monitor = new \Foru\Api\Monitor();
    }

    public function init_hooks()
    {
        add_action('init', array( $this, 'catch_request') , 9);

        //向订单列表添加快递单号
        add_filter( 'manage_edit-shop_order_columns', array($this, 'custom_shop_order_column'),11);
        add_action( 'manage_shop_order_posts_custom_column' , array($this, 'cbsp_credit_details'), 10, 2 );

        //支付成功，订单提交到API
        add_action('woocommerce_order_status_processing', array($this, 'foru_triggered_api'), 12, 10);

        //当订单变为退货、取消时
        add_action('woocommerce_order_status_cancelled', array($this, 'foru_order_status'), 12, 2);
        add_action('woocommerce_order_status_refunded', array($this, 'foru_order_status'), 12, 2);

        //修改订单时执行
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'foru_update_address'), 12, 2);

        //退款时调用
        add_action('woocommerce_order_refunded', array($this, 'foru_refunded_item'), 12, 2);

        //取消退款时调用
        add_action('woocommerce_refund_deleted', array($this, 'foru_cancelled_refunded'), 12, 2);

        //插入css,js
        add_action('admin_enqueue_scripts', array($this, 'wp_order_script'));

        //生成页面
        add_action('admin_menu', array($this, 'wporg_order_page'));
    }

    public function catch_request()
    {
        $file = __DIR__.'/Product/record.txt';
        if (file_exists($file)) {
            unlink($file);
        }
        $page = strip_tags(empty($_GET['page']) ? $_POST['page'] : $_GET['page']);
        $action = strip_tags($_GET['action']);

        //获取message信息
        if (!empty($_GET['foru_message'])) {
            $this->message = strip_tags(urldecode($_GET['foru_message']));
            $this->status = strip_tags($_GET['foru_status']);
        }

        if($action == 'forudropshipping-push-products'){
            $this->api_product();
            exit();
        } else if($action == 'forudropshipping-push-tracks')
        {
            $this->api_track();
            exit();
        } else if($action == 'forudropshipping-categories') {
            $this->api_category();
            exit();
        }
        if (current_user_can('administrator')) {
            switch ($page) {
                case 'order_export':
                    $action = htmlspecialchars($_GET['order']);
                    $this->order_export($action);
                    break;
                case 'wc_import_product':
                    $this->wc_import_product();
                    break;
                case 'traking_import':
                    $this->traking_import();
                    break;
                case 'api_key_update':
                    $this->api_key_update();
                    break;
            }

            return;
        }
    }

    public function order_export($action)
    {
        if ($action == 'all') {
            //Get data
            $result = new Foru\Order\All();
            $data = $result->all();
            //export CSV
            $export = new Foru\Order\Export();
            $export->csv($data);
            exit();
        } elseif ($action == 'date') {
            //Get data
            $result = new Foru\Order\Date();
            $data = $result->byDate();
            //export CSV
            $export = new Foru\Order\Export();
            $export->csv($data, $result->filename);
            exit();
        }
    }

    public function wc_import_product()
    {
        $handle = new Foru\Product\Handle();
        if ($handle->foruImportProducts()) {
            //执行成功
            $foru_message = urlencode('Product import complet!');
            $foru_status = 1;
            wp_redirect(admin_url('admin.php')."?page=".$this->page."&foru_message=".$foru_message."&foru_status=".$foru_status."");
        }
    }

    public function traking_import()
    {
        $handle = new Foru\Tracking\Handle();
        try {
            $data = $handle->get();
            $handle->database($data);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        //执行成功
        $foru_message = urlencode('Tracking import Success!');
        $foru_status = 1;
        wp_redirect(admin_url('admin.php')."?page=".$this->page."&foru_message=".$foru_message."&foru_status=".$foru_status."");
    }

    public function api_key_update()
    {
        $api = new Foru\Api\Api();
        if ($api->updateKey()) {
            //执行成功
            $foru_message = urlencode('Create token update success!');
            $foru_status = 1;
            return wp_redirect(admin_url('admin.php')."?page=".$this->page."&foru_message=".$foru_message."&foru_status=".$foru_status."");
        }

        //执行失败
        $foru_message = urlencode('Create token fail.Please check your account username、password or try again!');
        $foru_status = 2;
        return wp_redirect(admin_url('admin.php')."?page=".$this->page."&foru_message=".$foru_message."&foru_status=".$foru_status."");
    }

    public function api_product()
    {
        $import =  new Foru\Api\ImportProduct();
        $import->handle();
    }

    public function api_track()
    {
        $import =  new Foru\Api\ImportTrack();
        $import->handle();
    }

    public function api_category()
    {
        $categorys = new \Foru\Product\Category();
        echo json_encode($categorys->verification());
    }

    //add tracking column
    // ADDING COLUMN TITLES (Here 2 columns)
    public function custom_shop_order_column($columns)
    {
        //add columns
        $columns['tracking_number'] = __('Tracking number','theme_slug');
        $columns['express_type'] = __('Express type','theme_slug');
        return $columns;
    }

    // adding the data for each orders by column
    public function cbsp_credit_details( $column )
    {
        global $the_order;
        $order_id = $the_order->id;
        switch ( $column )
        {
            case 'tracking_number' :
                $new_handle = new Foru\Tracking\Handle();
                $myVarOne = $new_handle->colunm_value($order_id, '_traking_number') ? : null;
                echo $myVarOne;
                break;
            case 'express_type':
                $new_handle = new Foru\Tracking\Handle();
                $myVarOne = $new_handle->colunm_value($order_id, '_express_type') ? : null;
                echo $myVarOne;
                break;
        }
    }

    //支付成功，订单提交到API
    public function foru_triggered_api($order_id){
        if ($this->run != 1) {
            $this->monitor->strore($order_id);
        }
        $this->run = 1;
    }

    //当订单变为退货、取消时
    public function foru_order_status($order_id){
        if ($this->run != 1) {
            $this->monitor->delete($order_id);
        }
        $this->run = 1;
    }

    //修改订单地址时执行
    public function foru_update_address($order){
        if ($this->run != 1) {
            $info = json_decode($order, true);
            $this->monitor->updateAddress($info);
        }
        $this->run = 1;
    }

    //退款时调用
    public function foru_refunded_item($order_id, $refund_id)
    {
        $this->monitor->refunded($order_id, $refund_id);
    }

    //取消退款时调用
    public function foru_cancelled_refunded($refund_id, $order_id)
    {
        $this->monitor->cancelledRefunded($order_id);
    }

    public function wp_order_script()
    {
        if ($_GET['page'] == 'foru_dropshipping') {
           wp_enqueue_script('flatpickr', 'http://cdn.bootcss.com/flatpickr/2.4.3/flatpickr.min.js');
           wp_enqueue_style('flatpickr', 'http://cdn.bootcss.com/flatpickr/2.4.3/flatpickr.min.css');
           wp_enqueue_style('foru',plugins_url().'/forudropshipping/html/foru.css');
           wp_enqueue_script('jquery_product','//cdn.bootcss.com/jquery/3.2.0/jquery.min.js');
        }
    }

    public function wporg_order_page()
    {
        // add top level menu page
        add_menu_page(
            'FORU Dropshipping',
            'FORU Dropshipping',
            'manage_options',
            'foru_dropshipping',
            array($this, 'foruDropshippingPageHtml')
        );
    }

    //展示页面
    public function foruDropshippingPageHtml()
    {
        require_once __DIR__ . '/html/foruDropshippingPageHtml.php';
    }
}

//启动
new Forudropshipping();
