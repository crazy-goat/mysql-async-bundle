<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Connection;

use Amp\Future;
use Amp\Mysql\MysqlResult;

class Result
{
    /**
     * The future is untyped on purpose: `Amp\async()` is annotated `@template T`, but PHPStan
     * cannot bind that template, so a `Future<MysqlResult>` here would reject every value the
     * pool can actually produce. `fetchScalar()` checks the awaited value instead.
     *
     * @param Future<mixed> $future
     */
    public function __construct(private readonly Future $future)
    {
    }

    public function fetchScalar(): int|float|string|null
    {
        $result = $this->future->await();

        if (!$result instanceof MysqlResult) {
            throw new \RuntimeException(\sprintf('Expected %s, got %s.', MysqlResult::class, get_debug_type($result)));
        }

        $row = $result->fetchRow();

        if (null === $row) {
            return null;
        }

        $key = array_key_first($row);

        if (null !== $key) {
            return $row[$key] ?? null;
        }

        return null;
    }
}
