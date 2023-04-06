<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Extend\Benchmark\Benchmark;

use D5WHUB\Extend\Benchmark\Exception\BenchmarkException;
use D5WHUB\Extend\Benchmark\Printer\Printer;

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
        public readonly Printer|null $printer = null,
        public readonly bool $ignoreResults = false
    ) {
    }

    /**
     * @param array{
     *     type:string,
     *     return:mixed,
     *     output:null|string,
     *     throw:null|array{
     *         class:string,
     *         message:string,
     *         code:int,
     *         line:int,
     *         file:string
     *     }
     * }|null $expect
     */
    public function addTest(
        string                $title,
        array|null            $expect,
        array|callable|string ...$callback
    ): self {
        $this->collection[] = new Test($title, $expect, $callback);
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
    public function execute(int $iterations): array
    {
        $first = true;
        $cursor = 1;
        $total = $iterations * count($this->collection);

        $results = [];

        foreach ($this->collection as $test) {
            $results[$test->title] = [];

            for ($interaction = 1; $interaction <= $iterations; $interaction++, $cursor++) {
                if (!$first) {
                    $this->printer?->tmpclear();
                }

                $this->printer?->tmpwrite("→ [Running test $cursor/$total] $test->title $interaction/$iterations");
                $results[$test->title][] = $test->execute($interaction, $this->callbackArgs, $this->constructArgs);
                $first = false;
            }
        }
        $this->printer?->tmpclear();

        return $results;
    }
}
