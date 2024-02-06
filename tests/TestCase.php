<?php

namespace Rokde\CloneDatabase\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rokde\CloneDatabase\CloneDatabaseServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            CloneDatabaseServiceProvider::class,
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
