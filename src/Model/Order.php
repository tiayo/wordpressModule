<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $connection = 'mysql';
    protected $table = 'posts';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['post_status', 'post_type'];
}
