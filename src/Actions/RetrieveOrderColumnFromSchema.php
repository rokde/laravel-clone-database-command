<?php

namespace Rokde\CloneDatabase\Actions;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Index;
use Rokde\CloneDatabase\Events\OrderColumnFound;

class RetrieveOrderColumnFromSchema
{
    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __invoke(AbstractSchemaManager $schema, string $table): string
    {
        $tableIntrospection = $schema->introspectTable($table);

        $pk = $tableIntrospection->getPrimaryKey();
        $orderByColumn = ($pk instanceof Index)
            ? $pk->getColumns()[0]
            : current($tableIntrospection->getColumns())->getName();

        OrderColumnFound::dispatch($table, $orderByColumn);

        return $orderByColumn;
    }
}
