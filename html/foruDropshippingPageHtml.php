
<div class="container" style="padding:0 0 0 1em;">
    <img src="<?php echo plugin_dir_url(__FILE__) ?>../logo.png" width="300">
</div>

<?php
if (!empty($this->message)) {
    $class = 'border-left:solid 3px #eab3b7;background-color:#FFE2E4;color:#D27C82';
    if ($this->status == 1) {
        $class = 'border-left:solid 3px #92d097;background-color:#ddf0de;color:#72C279';
    }
    echo '
                    <div class="container" style="'.$class.'">
                        <p style="margin:0; padding:1em 0 1em 0; font-size: 1.2em;">'.$this->message.'</p>
                    </div>
                ';
}
?>
<div class="container">
    <h2 class="form-signin-heading">API(Recommended)</h2>

    <?php
        $api = new \Foru\Api\Api();
        $token = $api->view();
    ?>

        <form enctype="multipart/form-data"
              action="<?php echo admin_url('admin.php'); ?>"
              method="post"
              id="create_token_form"
              style="display: <?php if (!empty($token)) echo 'none';?>"
        >
        <input type="hidden" name="page" value="api_key_update">
        <label>Email:</label>
        <input type="text" name="username" value="" placeholder="username">
        <label>Password:</label>
        <input type="password" name="password" value="" placeholder="password">
        <div class="create_token_form_div">
            <input type="submit" class="button button-primary button-large" value="Create Token">
        </div>
    </form>

    <form id="view_token_form" style="width: 37%;display: <?php if (empty($token)) echo 'none';?>">
        <input type="text" name="api_key" style="height:30px;width:70%;" value="<?php echo $token; ?>" placeholder="Enter token" readonly>
        <input type="button" id="view_token_form_submit" class="button button-primary button-large" value="Refresh Token">
    
    <div class="howto">
        <p>Add Store and enter token listed on <a href="https://www.forudropshipping.com/stores">https://www.forudropshipping.com/stores</a></p>
    </div>
</div>

<div class="container">
    <h2 class="form-signin-heading">Import products</h2>
    <form enctype="multipart/form-data" class="form-group" method="post" action="<?php echo admin_url('admin.php');?>">
        <input type="hidden" name="page" value="wc_import_product">
        <input class="form-control" type="file" id="upload" name="import"/>
        <div style="width:100%;float:left;margin: 1em 0 1em;">
            <select name="category" class="form-control">
                <option value="0">Select a category</option>
                <?php
                $category = new \Foru\Product\Category();
                echo $category->get();
                ?>
            </select>
            <a class="button button-defalut pl-2" href="edit-tags.php?taxonomy=product_cat&post_type=product">New category</a>
        </div>
        <input style="float:left;" class="button-primary" type="submit" value='Import' id="m-callback-start"/>
    </form>
</div>

<script>
    $(document).ready(function() {

        $('#view_token_form_submit').click(function () {
            $('#create_token_form')[0].style.display = 'block';
            $('#view_token_form')[0].style.display = 'none';
        });

        $('#m-callback-start').click(function() {
            html = '<div class="bgc"></div>\
                        <div class="float">\
                            <a href="javascript:history.go(0);" class="bgc_close">X</a>\
                            <h2>product import process </h2>\
                            <div class="bs-example m-callback">\
                                <div class="progress"><div class="progress-bar" role="progressbar" data-transitiongoal-backup="100">\
                                    </div>\
                                </div>\
                                    <p>progress: <span class="label label-info" id="m-callback-update"></span></p>\
                                    <p>done: <span class="label label-success" id="m-callback-done"></span></p>\
                            </div>\
                        </div>\
                    ';
            $('#wpcontent').append(html);
            setTimeout("Push()",0);
            setInterval("Push()",1000);
        });
    });

    function Push() {
        $.ajax({
            type: "get",
            url: "/wp-content/plugins/forudropshipping/src/Product/Record.php",
            dataType: "json",
            success: function (data) {
                var $pb = $('.m-callback .progress-bar');
                $pb.attr('data-transitiongoal', data);
                $pb.attr('aria-valuenow', data);
                $pb.attr('style', 'width:' + data + '%');
                $('#m-callback-update').html(data);
                if (data == 100) {
                    $('#m-callback-done').html('Import complete');
                } else {
                    $('#m-callback-done').html('It is important to take a few minutes to import the product!');
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    }
</script>

<div class="container">
    <h2 class="form-signin-heading">Export all orders</h2>
    <a href="<?php echo admin_url('admin.php?page=order_export&order=all'); ?>"><input class="button button-primary button-large" type="button" value="Export all orders"></a>
    <h2 class="form-signin-heading">Export orders by date</h2>
    <form class="form-group" action="<?php echo admin_url('admin.php'); ?>" method="get">
        <input type="hidden" name="page" value="order_export">
        <input type="hidden" name="order" value="date">
        <input class="flatpickr" id="range" type="text" name="date" placeholder="Select a date range">
        <br />
        <input type="submit" class="button button-primary button-large" value="Export">
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


<div class="container">
    <h2 class="form-signin-heading">Import tracking numbers</h2>
    <form class="form-group" enctype="multipart/form-data" style="width:35%"  action="<?php echo admin_url('admin.php'); ?>" method="post" >
        <input type="hidden" name="page" value="traking_import">
        <input class="form-control" type="file" name="file">
        <br> <br>
        <input type="submit" class="button button-primary button-large" value="Import">
    </form>
    <div class="howto">
        <p>
            Export from <a href="https://www.forudropshipping.com/order-groups">https://www.forudropshipping.com/order-groups</a> to your computer, then upload downloaded csv file to WooCommerce.
        </p>


    </div>
</div>