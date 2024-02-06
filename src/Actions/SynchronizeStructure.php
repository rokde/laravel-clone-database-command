<?php

namespace Rokde\CloneDatabase\Actions;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Database\Connection;
use Rokde\CloneDatabase\Events\TableCreated;
use Rokde\CloneDatabase\Events\TableDropped;

readonly class SynchronizeStructure
{
    public function __construct(
        protected bool $dropExistingTables = false,
        protected bool $keepUnhandledTablesOnTarget = true
    ) {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __invoke(
        Connection $sourceConnection,
        Connection $targetConnection
    ): void {
        // prevent "Unknown database type enum requested, Doctrine\DBAL\Platforms\*Platform may not support it."
        $sourceConnection->getDoctrineConnection()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        $sourceSchema = $sourceConnection->getDoctrineSchemaManager();
        $targetSchema = $targetConnection->getDoctrineSchemaManager();

        $this->processUnhandledTablesOnTargetWhenNecessary($sourceSchema, $targetSchema);
        $this->dropExistingTablesWhenNecessary($sourceSchema, $targetSchema);
        $this->createTablesWhenNecessary($sourceSchema, $targetSchema);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function processUnhandledTablesOnTargetWhenNecessary(
        AbstractSchemaManager $sourceSchema,
        AbstractSchemaManager $targetSchema,
    ): void {
        if ($this->keepUnhandledTablesOnTarget) {
            return;
        }

        $targetTableNames = $targetSchema->listTableNames();
        $sourceTableNames = $sourceSchema->listTableNames();
        $unhandledTableNames = array_diff($targetTableNames, $sourceTableNames);

        foreach ($unhandledTableNames as $unhandledTableName) {
            $this->dropTable($targetSchema, $unhandledTableName);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function dropExistingTablesWhenNecessary(
        AbstractSchemaManager $sourceSchema,
        AbstractSchemaManager $targetSchema
    ): void {
        if (! $this->dropExistingTables) {
            return;
        }

        foreach ($sourceSchema->listTables() as $table) {
            if (! $targetSchema->tablesExist([$table->getName()])) {
                continue;
            }

            $this->dropTable($targetSchema, $table->getName());
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function dropTable(AbstractSchemaManager $schema, string $tableName): void
    {
        $schema->dropTable($tableName);
        TableDropped::dispatch($tableName);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function createTablesWhenNecessary(
        AbstractSchemaManager $sourceSchema,
        AbstractSchemaManager $targetSchema
    ): void {
        foreach ($sourceSchema->listTables() as $table) {
            if ($targetSchema->tablesExist([$table->getName()])) {
                return;
            }

            $targetSchema->createTable($table);
            TableCreated::dispatch($table->getName());
        }
    }
}
