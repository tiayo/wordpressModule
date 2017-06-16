<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class OrderItemMeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'woocommerce_order_itemmeta';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['post_status', 'post_type'];
}
