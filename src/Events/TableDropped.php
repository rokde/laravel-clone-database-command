<?php

namespace Rokde\CloneDatabase\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableDropped
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly string $table)
    {
    }
}
