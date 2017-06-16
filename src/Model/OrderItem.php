<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'woocommerce_order_items';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['post_status', 'post_type'];
}
