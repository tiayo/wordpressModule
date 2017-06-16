<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class OrderMeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'postmeta';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['post_status', 'post_type'];
}
