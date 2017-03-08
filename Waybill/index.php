<?php
/*
Plugin Name: Order export
Description: This is an order-derived plugin
Author: zxj
Version: 1.0
*/

add_action('init', 'catch_request', 9);
function catch_request()
{
    if ($_GET['page'] == 'order_export') {
        $action = $_GET['order'];

        if($action == 'all') {
            require_once __DIR__ . '/Services/all.php';
            require_once __DIR__ . '/Services/export.php';
            //Get results
            $result = new \Order\Controller\All();
            $data = $result->all();
            //export CSV
            $export = new \Order\Controller\Export();
            $export->csv($data);
            exit();

        } elseif ($action == 'date') {
            require_once __DIR__ . '/Services/date.php';
            require_once __DIR__ . '/Services/export.php';
            $result = new \Order\Controller\Date();//Get results
            try {
                $data = $result->byDate();
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            //export CSV
            $export = new \Order\Controller\Export();
            $export->csv($data, $result->filename);
            exit();
        }
    }
}

add_action('admin_enqueue_scripts', 'wp_order_script');
function wp_order_script()
{
    if ($_GET['page'] == 'wp_order_io') {
       wp_enqueue_script('flatpickr', 'http://cdn.bootcss.com/flatpickr/2.4.3/flatpickr.min.js');
       wp_enqueue_style('flatpickr', 'http://cdn.bootcss.com/flatpickr/2.4.3/flatpickr.min.css');
    }
}

add_action('admin_menu', 'wporg_order_page');
function wporg_order_page()
{
    // add top level menu page
    add_menu_page(
        'WP-ORDER-IO',
        'WP-ORDER-IO',
        'manage_options',
        'wp_order_io',
        'wp_order_io_page_html'
    );
}

function wp_order_io_page_html()
{
    ?>
    <style type="text/css">
        input.flatpickr{
            font-family: inherit;
            font-size: 1rem;
            padding: 0 .5rem;
            height: 40px;
            background: #fff;
            border: 1px solid #e3e3e3;
            border-radius: 2px;
            margin-bottom: 0;
            color: rgba(0,0,0,0.9);
            width:28%;
            display: block;
            transition: all 200ms ease-out;
            cursor: pointer;
        }
        input.flatpickr.active{
            outline: 0;
            background: #fff;
            border: 1px solid rgba(50,115,220,0.7);
        }

        }
    </style>
    <div class="container">
        <h2 class="form-signin-heading">Export all orders</h2>
        <a href="<?php echo admin_url('admin.php?page=order_export&order=all'); ?>"><input class="button button-primary button-large" type="button" value="Export all orders"></a>
    </div>

    <div class="container">
        <h2 class="form-signin-heading">Export orders by date</h2>
        <form class="form-signin" action="<?php echo admin_url('admin.php'); ?>" method="get">
            <input type="hidden" name="page" value="order_export">
            <input type="hidden" name="order" value="date">
            <input class="flatpickr" id="range" type="text" name="date" placeholder="Select Date..">
            <p></p>
            <input type="submit" class="button button-primary button-large" value="Confirm export">
        </form>
    </div>
    <script>
        window.onload = function () {
            flatpickr("#range", {
                "mode": "range",
                enableTime: true,
                altInput: true,
                altFormat: "Y-m-d H:i:S"
            });
        }
    </script>
    <?php
}