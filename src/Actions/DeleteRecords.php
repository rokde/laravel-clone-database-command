<?php

namespace Rokde\CloneDatabase\Actions;

use Illuminate\Support\Facades\DB;
use Rokde\CloneDatabase\Events\RecordsDeleted;

class DeleteRecords
{
    public function __invoke(string $table, string $connectionName = 'target'): void
    {
        DB::connection($connectionName)
            ->delete('DELETE FROM `'.$table.'`;');

        RecordsDeleted::dispatch($table);
    }
}
