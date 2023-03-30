<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Extend\Benchmark\Benchmark;

use D5WHUB\Extend\Benchmark\Exception\BenchmarkException;
use D5WHUB\Extend\Benchmark\Utils\Printer;

class Collection
{
    /**
     * @var Test[]
     */
    private array $collection = [];

    private array $callbackArgs = [];

    private array $constructArgs = [];

    public function __construct(
        public readonly string       $title,
        public readonly string|null  $comment = null,
        public readonly int|null     $iterations = null,
        public readonly Printer|null $printer = null
    ) {
    }

    public function addTest(
        string                $title,
        array|null            $experct,
        array|callable|string ...$callback
    ): self {
        $this->collection[] = new Test($title, $experct, $callback);
        return $this;
    }

    public function argsByMethod(array $args): self
    {
        $this->callbackArgs = $args;
        return $this;
    }

    public function argsByConstruct(array $args): self
    {
        $this->constructArgs = $args;
        return $this;
    }

    /**
     * @throws BenchmarkException
     */
    public function execute(int|null $iterations): array
    {
        $first = true;
        $cursor = 1;
        $total = $iterations * count($this->collection);

        $results = [];

        foreach ($this->collection as $test) {
            $results[$test->title] = [];

            for ($iteraction = 1; $iteraction <= $iterations; $iteraction++, $cursor++) {
                if (!$first) {
                    $this->printer->tmpclear();
                }

                $this->printer->tmp("→ [Running test $cursor/$total] $test->title $iteraction/$iterations");
                $results[$test->title][] = $test->execute($iteraction, $this->callbackArgs, $this->constructArgs);
                $first = false;
            }
        }
        $this->printer->tmpclear();

        return $results;
    }
}
