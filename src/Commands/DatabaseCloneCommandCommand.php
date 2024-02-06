<?php

namespace Rokde\CloneDatabase\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Event;
use Rokde\CloneDatabase\Actions\CountRecords;
use Rokde\CloneDatabase\Actions\DeleteRecords;
use Rokde\CloneDatabase\Actions\InsertingRecords;
use Rokde\CloneDatabase\Actions\SynchronizeStructure;
use Rokde\CloneDatabase\Actions\SynchronizeViews;
use Rokde\CloneDatabase\Events\RecordsDeleted;
use Rokde\CloneDatabase\Events\TableCreated;
use Rokde\CloneDatabase\Events\TableDropped;
use Rokde\CloneDatabase\Events\ViewCreated;
use Rokde\CloneDatabase\Events\ViewDropped;
use Rokde\CloneDatabase\Models\DatabaseSyncConfiguration;
use Rokde\LaravelUtilities\Utilities\Memory;
use Rokde\LaravelUtilities\Utilities\Stopwatch;

use function Laravel\Prompts\progress;

class DatabaseCloneCommandCommand extends Command
{
    protected $signature = 'db:clone {--limit=500 : limit the records to be cloned (-1 for all records)}';

    protected $description = 'Cloning a database to another';

    private ?Stopwatch $stopwatch = null;

    public function handle(ConnectionResolverInterface $connections): int
    {
        $this->stopwatch = Stopwatch::make();

        Event::listen(TableCreated::class, fn ($event) => $this->log(' table '.$event->table.' created'));
        Event::listen(TableDropped::class, fn ($event) => $this->log(' table '.$event->table.' dropped'));
        Event::listen(ViewCreated::class, fn ($event) => $this->log(' view '.$event->table.' created'));
        Event::listen(ViewDropped::class, fn ($event) => $this->log(' view '.$event->table.' dropped'));
        Event::listen(RecordsDeleted::class, fn ($event) => $this->log(' records in '.$event->table.' deleted'));
        //        Event::listen(OrderColumnFound::class,
        //            fn($event) => $this->info(' try to order ' . $event->table . ' by ' . $event->column));
        //        Event::listen(MutationApplied::class,
        //            fn($event) => $this->info('  record was ' . $event->applied . 'x mutated'));
        //        Event::listen(RecordInserted::class,
        //            fn($event) => $this->info('  record ' . $event->index . ' inserted'));

        $config = DatabaseSyncConfiguration::make()
            ->configureSourceConnection(config('database.connections.source'))
            ->configureTargetConnection(config('database.connections.target'))
            ->dropUnhandledTablesOnTarget()
            ->dropTables();
        /**
        ->addMutation('email', fn() => fake()->email)
        ->addMutation('ip_address', fn($value) => fake()->ipv4, 'sessions')
        ->addMutation('name', fn($value) => fake()->company, 'teams')
        ->setMutations([
            'name' => fn() => fake()->name,
            'password' => fn() => bcrypt('password'),
        ], 'users')
         */
        $config->configureLimit(intval($this->option('limit')));

        /** @var \Illuminate\Database\Connection $sourceConnection */
        $sourceConnection = $connections->connectUsing($config->sourceConnectionName(),
            $config->sourceConnectionConfig(), true);

        /** @var \Illuminate\Database\Connection $targetConnection */
        $targetConnection = $connections->connectUsing($config->targetConnectionName(),
            $config->targetConnectionConfig(), true);

        /** @var \Doctrine\DBAL\Schema\MySQLSchemaManager $sourceSchema */
        $sourceSchema = $sourceConnection->getDoctrineSchemaManager();
        /** @var \Doctrine\DBAL\Schema\MySQLSchemaManager $targetSchema */
        $targetSchema = $targetConnection->getDoctrineSchemaManager();

        // start process
        try {
            $targetConnection->getSchemaBuilder()->disableForeignKeyConstraints();

            //  structure
            $this->log('Synchronizing structure...');
            (new SynchronizeStructure(
                dropExistingTables: $config->shouldDropTables(),
                keepUnhandledTablesOnTarget: $config->shouldKeepUnhandledTablesOnTarget())
            )($sourceConnection, $targetConnection);

            //  copy data

            $this->log('Syncing records...');
            collect($sourceSchema->listTableNames())
                ->each(function (string $tableName) use ($sourceSchema, $config) {

                    if ($config->shouldDeleteRecords()) {
                        $this->log('Deleting records in '.$tableName.'...');
                        (new DeleteRecords)($tableName, $config->targetConnectionName());
                    }

                    $records = (new CountRecords())($tableName, $config->sourceConnectionName());
                    if ($records > 0) {
                        $this->log('Inserting '.$records.' records in '.$tableName.'...');
                        $progress = progress('Inserting records', $records);
                        $progress->start();

                        (new InsertingRecords(
                            table: $tableName,
                            mutations: $config->mutationsFor($tableName),
                            chunks: $config->chunksFor($tableName),
                            limit: $config->limitFor($tableName),
                            sourceConnectionName: $config->sourceConnectionName(),
                            targetConnectionName: $config->targetConnectionName(),
                        ))($sourceSchema, function () use ($progress) {
                            $progress->advance();
                        });

                        $progress->finish();
                    } else {
                        $this->log('No records to sync in '.$tableName);
                    }
                });

            $targetConnection->getSchemaBuilder()->enableForeignKeyConstraints();

            //  views
            try {
                $this->log('Synchronizing views...');
                (new SynchronizeViews())($sourceSchema, $targetSchema);
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        } catch (\Exception $exception) {
            $targetConnection->getSchemaBuilder()->enableForeignKeyConstraints();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->log('Done');

        return self::SUCCESS;
    }

    private function log(string $message): void
    {
        $this->info(
            $this->stopwatch->measure()
            .' '
            .'['.Memory::usage().' / '.Memory::peak().']'
            .' '
            .$message
        );
    }
}
