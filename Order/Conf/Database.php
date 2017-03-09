<?php

namespace Order\conf;

use Illuminate\Database\Capsule\Manager as Capsule;

require_once(__DIR__.'/../../../../vendor/autoload.php' );
require_once (__DIR__.'/../../../../wp-config.php');

$capsule = new Capsule;
/*
 * 多表切换
 * */
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => constant('DB_HOST'),
    'database'  => constant('DB_NAME'),
    'username'  => constant('DB_USER'),
    'password'  => constant('DB_PASSWORD'),
    'charset'   => constant('DB_CHARSET'),
    'collation' => empty(constant('DB_COLLATE')) ? 'utf8mb4_general_ci':constant('DB_COLLATE'),
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