<?php

namespace Rokde\CloneDatabase\Actions;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Rokde\CloneDatabase\Events\RecordInserted;
use Rokde\CloneDatabase\Models\DatabaseSyncConfiguration;

class InsertingRecords
{
    protected MutateRecord $mutateRecordAction;

    public function __construct(
        protected string $table,
        protected array $mutations = [],
        protected int $chunks = 100,
        protected int $limit = DatabaseSyncConfiguration::LIMIT_UNLIMITED,
        protected string $sourceConnectionName = 'source',
        protected string $targetConnectionName = 'target'
    ) {
        $this->mutateRecordAction = new MutateRecord($table, $this->mutations);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __invoke(AbstractSchemaManager $sourceSchema, ?callable $stepWiseCallback = null): void
    {
        $orderByColumn = (new RetrieveOrderColumnFromSchema())($sourceSchema, $this->table);

        $callback = function (Collection $records) use ($stepWiseCallback) {
            $records->each(function (\stdClass $result, int $index) use ($stepWiseCallback) {
                $record = get_object_vars($result);

                // mutate data
                $record = $this->mutateRecordAction->__invoke($record);

                DB::connection($this->targetConnectionName)
                    ->table($this->table)
                    ->insert($record);

                RecordInserted::dispatch($this->table, $index, $this->chunks);

                if ($stepWiseCallback) {
                    call_user_func($stepWiseCallback);
                }

            });
        };

        $query = DB::connection($this->sourceConnectionName)
            ->table($this->table)
            ->orderBy($orderByColumn);

        if ($this->limit === DatabaseSyncConfiguration::LIMIT_UNLIMITED) {
            $query->chunk($this->chunks, $callback);
        } else {
            $recordsFetched = 0;
            $page = 1;
            do {
                $records = $query
                    ->offset((++$page - 1) * $this->chunks)
                    ->limit(min($this->chunks, $this->limit - $recordsFetched))
                    ->get();

                $callback($records);

                $recordsFetched += $records->count();
            } while ($recordsFetched < $this->limit && $records->count() > 0);
        }
    }
}
