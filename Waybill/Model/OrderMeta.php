<?php

namespace Order\Model;

use Illuminate\Database\Eloquent\Model;

require_once(__DIR__.'/../../../../vendor/autoload.php' );

class OrderMeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'wp_postmeta';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['post_status', 'post_type'];

}
