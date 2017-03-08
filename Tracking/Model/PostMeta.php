<?php

namespace Tracking\Model;

use Illuminate\Database\Eloquent\Model;

require_once(__DIR__ . '/../../../../vendor/autoload.php');

class PostMeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'test';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = ['meta_id', 'post_id', 'meta_key', 'meta_value'];

}
