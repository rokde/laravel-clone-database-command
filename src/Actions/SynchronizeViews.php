<?php

namespace Rokde\CloneDatabase\Actions;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\View;
use Rokde\CloneDatabase\Events\ViewCreated;
use Rokde\CloneDatabase\Events\ViewDropped;

class SynchronizeViews
{
    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __invoke(AbstractSchemaManager $sourceSchema, AbstractSchemaManager $targetSchema): void
    {
        foreach ($sourceSchema->listViews() as $view) {
            if ($this->viewExists($targetSchema, $view)) {
                $targetSchema->dropView($view->getName());
                ViewDropped::dispatch($view->getName());
            }

            $targetSchema->createView($view);
            ViewCreated::dispatch($view->getName());
        }
    }

    private function viewExists(AbstractSchemaManager $schema, View $view): bool
    {
        try {
            return in_array($view->getName(), array_keys($schema->listViews()));
        } catch (\Exception) {
        }

        return false;
    }
}
