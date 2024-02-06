<?php

namespace Rokde\CloneDatabase\Actions;

use Doctrine\DBAL\Schema\AbstractSchemaManager;

class SynchronizeTable
{
    public function __construct(
        protected string $table,
        protected string $sourceConnectionName,
        protected string $targetConnectionName,
        protected bool $deleteRecords,
    ) {

    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __invoke(
        array $mutations,
        int $chunks,
        int $limit,
        AbstractSchemaManager $sourceSchema,
    ): void {
        if ($this->deleteRecords) {
            (new DeleteRecords)($this->table, $this->targetConnectionName);
        }

        $records = (new CountRecords())($this->table, $this->sourceConnectionName);
        if ($records <= 0) {
            return;
        }

        (new InsertingRecords(
            table: $this->table,
            mutations: $mutations,
            chunks: $chunks,
            limit: $limit,
            sourceConnectionName: $this->sourceConnectionName,
            targetConnectionName: $this->targetConnectionName,
        ))($sourceSchema);
    }
}
