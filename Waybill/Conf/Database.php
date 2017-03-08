<?php

namespace Order\conf;

use Illuminate\Database\Capsule\Manager as Capsule;

require_once(__DIR__.'/../../../../vendor/autoload.php' );

$capsule = new Capsule;
/*
 * 多表切换
 * */
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '192.168.33.10',
    'database'  => 'wordpress',
    'username'  => 'root',
    'password'  => '123456',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
], 'mysql');
// Set the event dispatcher used by Eloquent models... (optional)
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
$capsule->setEventDispatcher(new Dispatcher(new Container));
// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();
// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();