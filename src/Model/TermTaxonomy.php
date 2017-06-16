<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class TermTaxonomy extends Model
{
    protected $connection = 'mysql';
    protected $table = 'term_taxonomy';
    protected $primaryKey = 'term_taxonomy_id';

    public $timestamps = false;
    protected $guarded = [];
}
