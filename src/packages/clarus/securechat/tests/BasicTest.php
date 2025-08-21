<?php

namespace Clarus\SecureChat\Tests;

use PHPUnit_Framework_TestCase;
use Illuminate\Database\Capsule\Manager as DB;

//require 'vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

class BasicTest extends PHPUnit_Framework_TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->migrateTables();
    }

    /** @test */
    public function tests_run__ok()
    {
        return true;
    }

    protected function migrateTables(): void
    {
//        DB::schema()->create('posts', function ($table) {
//            $table->increments('id');
//            $table->integer('author_id')->unsigned();
//            $table->string('title');
//            $table->timestamps();
//        });
//
//        DB::schema()->create('comments', function ($table) {
//            $table->increments('id');
//            $table->integer('post_id')->unsigned();
//            $table->string('body');
//            $table->timestamps();
//        });
//
//        DB::schema()->create('people', function ($table) {
//            $table->increments('id');
//            $table->string('name');
//            $table->timestamps();
//        });
//
//        DB::schema()->create('messages', function ($table) {
//            $table->increments('id');
//            $table->integer('sender_id')->unsigned();
//            $table->integer('receiver_id')->unsigned();
//            $table->string('contents');
//            $table->timestamps();
//        });
    }

    protected function setUpDatabase(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();
    }
}
