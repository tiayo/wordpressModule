<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class Options extends Model
{
    protected $connection = 'mysql';
    protected $table = 'options';
    protected $primaryKey = 'option_id';

    public $timestamps = false;
    protected $guarded = [];
}
