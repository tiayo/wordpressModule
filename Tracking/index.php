<?php
/*
Plugin Name: Traking import
Description: This is an Traking import plugin
Author: zxj
Version: 1.0
*/

add_action('init', 'catch_request_waybill', 9);
function catch_request_waybill()
{
    if( htmlspecialchars($_POST['page']) == 'traking'){
        require_once __DIR__ . '/Services/handle.php';
        $handle = new handle();
        try {
            $data = $handle->get();
            $handle->database($data);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        echo "<script language=\"javascript\">alert('Import Success!Click OK to return.');location.href = history.go(-1)</script>";
    }
}

add_action('admin_enqueue_scripts', 'wp_wporg_Traking_page_script');
function wp_wporg_Traking_page_script()
{
}

add_action('admin_menu', 'wporg_Traking_page');
function wporg_Traking_page()
{
    // add top level menu page
    add_menu_page(
        'Traking_import',
        'Traking_import',
        'manage_options',
        'Traking_import',
        'wp_Traking_io_page_html'
    );
}

function wp_Traking_io_page_html()
{
    ?>
    <div class="container">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form enctype="multipart/form-data" action="<?php echo admin_url('admin.php'); ?>" method="post">
            <input type="hidden" name="page" value="traking">
            <input type="file" name="file" placeholder="Select Date..">
            <p></p>
            <input type="submit" class="button button-primary button-large" value="Confirm import">
        </form>
    </div>
    <?php
}