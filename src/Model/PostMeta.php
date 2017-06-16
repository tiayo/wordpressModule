<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class PostMeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'postmeta';
    protected $primaryKey = 'meta_id';

    public $timestamps = false;
    protected $guarded = ['meta_id'];
}
