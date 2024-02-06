<?php

namespace Rokde\LaravelCloneDatabaseCommand\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Rokde\LaravelCloneDatabaseCommand\LaravelCloneDatabaseCommandServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelCloneDatabaseCommandServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-clone-database-command_table.php.stub';
        $migration->up();
        */
    }
}
