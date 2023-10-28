<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

require_once 'vendor/autoload.php';

$capsule = new Capsule;
$capsule->addConnection(config('database'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

$schema = Capsule::schema();

if (!$schema->hasTable('users')) {
    $schema->create('users', function(Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('username', 32)->nullable();
        $table->enum('language', ['ru', 'tg', 'en']);
        $table->string('encrypted_login')->nullable();
        $table->string('encrypted_password')->nullable();
        $table->timestamps();
    });
    echo "Created table 'users'";
}