<?php

namespace Rokde\CloneDatabase;

use Rokde\CloneDatabase\Commands\DatabaseCloneCommandCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CloneDatabaseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-clone-database-command')
            ->hasConsoleCommand(DatabaseCloneCommandCommand::class);
    }
}
