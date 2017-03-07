<?php

namespace Order\Model;

use Illuminate\Database\Eloquent\Model;

require_once(__DIR__.'/../../../../vendor/autoload.php' );

class OrderItemMeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'wp_woocommerce_order_itemmeta';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['post_status', 'post_type'];

}
