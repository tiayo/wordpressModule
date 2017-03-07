<?php

namespace Order\Model;

use Illuminate\Database\Eloquent\Model;

require_once(__DIR__.'/../../../../vendor/autoload.php' );

class OrderItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'wp_woocommerce_order_items';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['post_status', 'post_type'];

    public function meta()
    {
        return $this->hasMany('App\OrderItemMeta', 'order_item_id');
    }

}
