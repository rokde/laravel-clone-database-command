<?php

namespace Rokde\CloneDatabase\Actions;

use Illuminate\Support\Facades\DB;

class CountRecords
{
    public function __invoke(string $table, string $connectionName = 'source'): int
    {
        return DB::connection($connectionName)
            ->table($table)
            ->count();
    }
}
