<?php

namespace Foru\Model;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $connection = 'mysql';
    protected $table = 'posts';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $guarded = ['ID'];

    public function initialization()
    {
        return array(
            'post_date' => date('Y-m-d H:i:s'),
            'post_date_gmt' => date('Y-m-d H:i:s'),
            'post_content' => '',
            'post_title' => '',
            'post_excerpt' => '',
            'post_status' => '',
            'comment_status' => '',
            'ping_status' => '',
            'post_password' => '',
            'post_name' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => date('Y-m-d H:i:s'),
            'post_modified_gmt' => date('Y-m-d H:i:s'),
            'post_content_filtered' => '',
            'guid' => '',
            'post_type' => '',
            'post_mime_type' => '',

        );
    }
}
