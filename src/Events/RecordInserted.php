<?php

namespace Rokde\CloneDatabase\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecordInserted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly string $table, public readonly int $index, public readonly int $chunks)
    {
    }
}
