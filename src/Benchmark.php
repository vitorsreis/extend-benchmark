<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Extend\Benchmark;

use D5WHUB\Extend\Benchmark\Benchmark\Collection;
use D5WHUB\Extend\Benchmark\Exception\BenchmarkException;
use D5WHUB\Extend\Benchmark\Printer\Console;
use D5WHUB\Extend\Benchmark\Printer\Html;
use D5WHUB\Extend\Benchmark\Utils\Printer;
use D5WHUB\Extend\Benchmark\Utils\Status;

class Benchmark
{
    /**
     * @var Collection[]
     */
    private array $benchmarks = [];

    private readonly Printer|null $printer;

    public function __construct(
        public readonly string      $title,
        public readonly string|null $comment = null,
        Printer|null                $printer = null
    ) {
        if (func_num_args() < 3) {
            $printer = isset($_SERVER['HTTP_USER_AGENT']) ? new Html() : new Console();
        }

        $this->printer = $printer;
    }

    public function createBenchmark(string $title, string|null $comment = null, int|null $iterations = null): Collection
    {
        return $this->benchmarks[] = new Collection($title, $comment, $iterations, $this->printer);
    }

    /**
     * @throws BenchmarkException
     */
    public function execute(int|null $iterations = null): array
    {
        $startTime = microtime(true);
        $this->printer?->start();
        $this->printer?->title($this->title, $this->comment);
        $this->printer?->skipline();

        $endResult = [];
        $totalBenchmarks = count($this->benchmarks);
        $totalIterations = 0;

        foreach ($this->benchmarks as $benckmark) {
            $benckmark_iterations = $benckmark->iterations ?: $iterations ?: 1;

            $this->printer?->subtitle($benckmark->title, $benckmark->comment, $benckmark_iterations);

            $results = $benckmark->execute($benckmark_iterations);

            foreach ($results as $testTitle => &$testResult) {
                $totalIterations += $benckmark_iterations;
                $testResult = $this->end($testResult);
                $endResult[$testTitle][] = $testResult;
            }

            $this->sort($results);
            $this->printer?->results($results);

            $this->printer?->skipline();
        }

        $this->printer?->subtitle('End result', "This is the final average considering all benchmarks previously run");

        $endResult = array_map(
            function ($results) {
                $results = array_map(fn($i) => array_slice($i, 1), $results);
                $results = array_merge(...$results);
                return $this->end($results);
            },
            $endResult
        );

        $this->sort($endResult);

        $this->printer?->results($endResult, true);

        $this->printer?->skipline();

        $this->printer?->end(microtime(true) - $startTime, $totalBenchmarks, $totalIterations);

        return $endResult;
    }

    private function end(array $results): array
    {
        $resultsCount = count($results);

        $skiped = array_filter($results, fn($i) => $i['status'] === Status::SKIPED);
        $success = array_filter($results, fn($i) => $i['status'] === Status::SUCCESS);
        $successCount = count($success);

        $status = $skiped ? Status::SKIPED : ($successCount && $successCount === $resultsCount
            ? Status::SUCCESS
            : ($successCount ? Status::PARTIAL : Status::FAILED)
        );

        $best = null;
        $average = null;
        $error = null;

        if ($status === Status::SUCCESS || $status === Status::PARTIAL) {
            $runnings = array_column(array_column($success, '_'), 'running');

            asort($runnings);
            $best = [
                current($runnings),
                key($runnings)
            ];

            $average = array_sum($runnings) / count($runnings);
        }

        if ($status === Status::FAILED || $status === Status::PARTIAL) {
            $failed = array_filter($results, fn($i) => $i['status'] === Status::FAILED);
            $error = current($failed)['error'];
        }

        if ($status === Status::FAILED || $status === Status::PARTIAL) {
            $failed = array_filter($results, fn($i) => $i['status'] === Status::FAILED);
            $error = current($failed)['error'];
        }

        return [
            '_' => [
                'status' => $status,
                'best' => $best,
                'average' => $average,
                'error' => $error
            ],
            ...$results
        ];
    }

    private function sort(array &$results): void
    {
        uasort($results, function ($a, $b) {
            if ($a['_']['status'] === Status::PARTIAL && $b['_']['status'] === Status::FAILED) {
                return -1;
            }
            if ($b['_']['status'] === Status::PARTIAL && $b['_']['status'] === Status::FAILED) {
                return 1;
            }

            if ($a['_']['status'] !== Status::SUCCESS) {
                return 1;
            }
            if ($b['_']['status'] !== Status::SUCCESS) {
                return -1;
            }

            return $a['_']['average'] < $b['_']['average'] ? -1 : 1;
        });
    }
}
