<?php

namespace Rokde\CloneDatabase\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use InvalidArgumentException;

final class DatabaseSyncConfiguration implements Arrayable
{
    const DROP_TABLES = 'dropTables';

    const DELETE_RECORDS = 'deleteRecords';

    const LIMIT_UNLIMITED = -1;

    protected array $config = [
        'source_connection_name' => 'source',
        'target_connection_name' => 'target',
        'connection' => [
            'source' => [],
            'target' => [],
        ],
        'chunks' => [
            '*' => 100,
        ],
        'limits' => [
            '*' => self::LIMIT_UNLIMITED,
        ],
        'strategies' => [
            'keep_unhandled_tables_on_target' => true,
        ],
        'mode' => self::DELETE_RECORDS,
        'mutations' => [
            '*' => [],
        ],
    ];

    public static function make(array $config = []): static
    {
        return new self($config);
    }

    public function __construct(array $config)
    {
        if (isset($config['mode']) && ! in_array($config['mode'], [self::DROP_TABLES, self::DELETE_RECORDS])) {
            unset($config['mode']);
        }

        // @TODO merge just the known keys and ignore the others
        $this->config = array_merge($this->config, $config);
    }

    public function configureSourceConnectionName(string $name): self
    {
        $this->config['source_connection_name'] = $name;

        return $this;
    }

    public function configureSourceConnection(array $config, ?string $name = null): self
    {
        $this->config['connection']['source'] = $config;

        if (Arr::has($this->config, 'connection.target.prefix')
            && Arr::get($this->config, 'connection.source.prefix')
            !== Arr::get($this->config, 'connection.target.prefix')) {
            throw new InvalidArgumentException('prefix can not be unequal in source and target connection');
        }

        if ($name) {
            $this->configureSourceConnectionName($name);
        }

        return $this;
    }

    public function configureTargetConnectionName(string $name): self
    {
        $this->config['target_connection_name'] = $name;

        return $this;
    }

    public function configureTargetConnection(array $config, ?string $name = null): self
    {
        $this->config['connection']['target'] = $config;

        if (Arr::has($this->config, 'connection.source.prefix')
            && Arr::get($this->config, 'connection.source.prefix')
            !== Arr::get($this->config, 'connection.target.prefix')) {
            throw new InvalidArgumentException('prefix can not be unequal in source and target connection');
        }

        if ($name) {
            $this->configureTargetConnectionName($name);
        }

        return $this;
    }

    public function configureChunkSize(int $chunkSize, string $table = '*'): self
    {
        $this->config['chunks'][$table] = $chunkSize;

        return $this;
    }

    public function resetChunkSize(): self
    {
        $this->config['chunks'] = [
            '*' => 100,
        ];

        return $this;
    }

    public function configureLimit(int $limit, string $table = '*'): self
    {
        $this->config['limits'][$table] = $limit;

        return $this;
    }

    public function resetLimits(): self
    {
        $this->config['limits'] = [
            '*' => self::LIMIT_UNLIMITED,
        ];

        return $this;
    }

    public function addMutation(string $column, callable|string $closureOrValue, string $table = '*'): self
    {
        $this->config['mutations'][$table][$column] = $closureOrValue;

        return $this;
    }

    /**
     * @param  array<string, string|callable>  $columnMutations
     */
    public function setMutations(array $columnMutations, string $table = '*'): self
    {
        $this->config['mutations'][$table] = $columnMutations;

        return $this;
    }

    public function clearMutations(): self
    {
        $this->config['mutations'] = [
            '*' => [],
        ];

        return $this;
    }

    public function keepUnhandledTablesOnTarget(): self
    {
        $this->config['strategies']['keep_unhandled_tables_on_target'] = true;

        return $this;
    }

    public function dropUnhandledTablesOnTarget(): self
    {
        $this->config['strategies']['keep_unhandled_tables_on_target'] = false;

        return $this;
    }

    public function deleteRecords(): self
    {
        $this->config['mode'] = 'deleteRecords';

        return $this;
    }

    public function dropTables(): self
    {
        $this->config['mode'] = 'dropTables';

        return $this;
    }

    public function toArray(): array
    {
        return $this->config;
    }

    public function sourceConnectionName(): string
    {
        return $this->config['source_connection_name'];
    }

    public function sourceConnectionConfig(): array
    {
        return $this->config['connection']['source'];
    }

    public function targetConnectionName(): string
    {
        return $this->config['target_connection_name'];
    }

    public function targetConnectionConfig(): array
    {
        return $this->config['connection']['target'];
    }

    public function shouldKeepUnhandledTablesOnTarget(): bool
    {
        return $this->config['strategies']['keep_unhandled_tables_on_target'];
    }

    public function shouldDropTables(): bool
    {
        return $this->config['mode'] === self::DROP_TABLES;
    }

    public function shouldDeleteRecords(): bool
    {
        return $this->config['mode'] === self::DELETE_RECORDS;
    }

    public function chunksFor(string $tableName, int $default = 100): int
    {
        return Arr::get(
            $this->config, 'chunks.'.$tableName,
            Arr::get($this->config, 'chunks.*', $default)
        );
    }

    public function limitFor(string $tableName, int $default = self::LIMIT_UNLIMITED): int
    {
        return Arr::get(
            $this->config, 'limits.'.$tableName,
            Arr::get($this->config, 'limits.*', $default)
        );
    }

    /**
     * @return array<string, string|callable>
     */
    public function mutationsFor(string $tableName): array
    {
        return array_merge(
            Arr::get($this->config, 'mutations.*', []),
            Arr::get($this->config, 'mutations.'.$tableName, []),
        );
    }
}
