<?php

namespace Order\Model;

use Illuminate\Database\Eloquent\Model;

require_once(__DIR__.'/../../../../vendor/autoload.php' );

class Order extends Model
{
    protected $connection = 'mysql';
    protected $table = 'wp_posts';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['post_status', 'post_type'];

    public function meta()
    {
        return $this->hasMany('App\OrderMeta', 'post_id');
    }

}
