<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class Terms extends Model
{
    protected $connection = 'mysql';
    protected $table = 'terms';
    protected $primaryKey = 'terms_id';

    public $timestamps = false;
    protected $guarded = [];
}
