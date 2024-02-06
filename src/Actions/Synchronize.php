<?php

namespace Rokde\CloneDatabase\Actions;

use Illuminate\Database\DatabaseManager;
use Rokde\CloneDatabase\Models\DatabaseSyncConfiguration;

readonly class Synchronize
{
    public function __construct(
        protected DatabaseManager $connections,
        protected DatabaseSyncConfiguration $config
    ) {

    }

    public function __invoke(): void
    {
        /** @var \Illuminate\Database\Connection $sourceConnection */
        $sourceConnection = $this->connections->connectUsing(
            $this->config->sourceConnectionName(),
            $this->config->sourceConnectionConfig(),
            true,
        );

        /** @var \Illuminate\Database\Connection $targetConnection */
        $targetConnection = $this->connections->connectUsing(
            $this->config->targetConnectionName(),
            $this->config->targetConnectionConfig(),
            true,
        );

        // start process
        try {
            $targetConnection->getSchemaBuilder()->disableForeignKeyConstraints();

            //  structure
            (new SynchronizeStructure(
                dropExistingTables: $this->config->shouldDropTables(),
                keepUnhandledTablesOnTarget: $this->config->shouldKeepUnhandledTablesOnTarget())
            )($sourceConnection, $targetConnection);

            $sourceSchema = $sourceConnection->getDoctrineSchemaManager();
            //  copy data
            collect($sourceSchema->listTableNames())
                ->each(function (string $tableName) use ($sourceSchema) {

                    (new SynchronizeTable(
                        table: $tableName,
                        sourceConnectionName: $this->config->sourceConnectionName(),
                        targetConnectionName: $this->config->targetConnectionName(),
                        deleteRecords: $this->config->shouldDeleteRecords(),
                    ))(
                        mutations: $this->config->mutationsFor($tableName),
                        chunks: $this->config->chunksFor($tableName),
                        limit: $this->config->limitFor($tableName),
                        sourceSchema: $sourceSchema,
                    );

                });

            $targetConnection->getSchemaBuilder()->enableForeignKeyConstraints();

            $targetSchema = $targetConnection->getDoctrineSchemaManager();
            //  views
            (new SynchronizeViews())($sourceSchema, $targetSchema);
        } catch (\Exception $exception) {
            $targetConnection->getSchemaBuilder()->enableForeignKeyConstraints();
        }
    }
}
