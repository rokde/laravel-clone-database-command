<?php

namespace Rokde\CloneDatabase\Actions;

use Illuminate\Support\Arr;
use Rokde\CloneDatabase\Events\MutationApplied;

readonly class MutateRecord
{
    public function __construct(protected string $table, protected array $mutations = [])
    {

    }

    public function __invoke(array $record): array
    {
        $mutationsApplied = 0;
        foreach ($record as $key => $value) {
            if (Arr::has($this->mutations, $key)) {
                $record[$key] = value($this->mutations[$key], $value);
                $mutationsApplied++;
            }
        }

        MutationApplied::dispatchIf($mutationsApplied > 0, $this->table, $mutationsApplied);

        return $record;
    }
}
