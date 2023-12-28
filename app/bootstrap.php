<?php

use Illuminate\Database\Capsule\Manager as Capsule;
$capsule = new Capsule;
$capsule->addConnection(config('database'));
$capsule->setAsGlobal();
$capsule->bootEloquent();
