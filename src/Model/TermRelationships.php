<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class TermRelationships extends Model
{
    protected $connection = 'mysql';
    protected $table = 'term_relationships';
    protected $primaryKey = 'object_id';

    public $timestamps = false;
    protected $guarded = [];
}
