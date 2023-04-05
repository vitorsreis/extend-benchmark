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

    public function createBenchmark(
        string $title,
        string|null $comment = null,
        int|null $iterations = null,
        bool $ignoreResults = false
    ): Collection {
        return $this->benchmarks[] = new Collection($title, $comment, $iterations, $this->printer, $ignoreResults);
    }

    /**
     * @throws BenchmarkException
     */
    public function execute(int|null $iterations = null, bool $ignoreEndResults = false): array
    {
        $startTime = microtime(true);
        $this->printer?->start();
        $this->printer?->title($this->title, $this->comment);
        $this->printer?->skipline();

        $endResult = [];
        $totalBenchmarks = count($this->benchmarks);
        $totalIterations = 0;

        foreach ($this->benchmarks as $benchmark) {
            $benchmark_iterations = $benchmark->iterations ?: $iterations ?: 1;

            $this->printer?->subtitle($benchmark->title, $benchmark->comment, $benchmark_iterations);

            $results = $benchmark->execute($benchmark_iterations);

            foreach ($results as $testTitle => &$testResult) {
                $totalIterations += $benchmark_iterations;
                if (!$benchmark->ignoreResults) {
                    $testResult = $this->end($testResult);
                    $endResult[$testTitle][] = $testResult;
                }
            }

            if (!$benchmark->ignoreResults) {
                $this->sort($results);
                $this->printer?->results($results);

            } else {
                $this->printer?->withTime("| Ignored\n");
            }
            $this->printer?->skipline();
        }

        if (!$ignoreEndResults) {
            $this->printer?->subtitle('End result', "Final average considering all benchmarks previously run");

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
        }

        $this->printer?->end(microtime(true) - $startTime, $totalBenchmarks, $totalIterations);

        return $endResult;
    }

    private function end(array $results): array
    {
        $resultsCount = count($results);

        $skipped = array_filter($results, fn($i) => $i['status'] === Status::SKIPPED);
        $success = array_filter($results, fn($i) => $i['status'] === Status::SUCCESS);
        $successCount = count($success);

        $status = $skipped ? Status::SKIPPED : ($successCount && $successCount === $resultsCount
            ? Status::SUCCESS
            : ($successCount ? Status::PARTIAL : Status::FAILED)
        );

        $best = null;
        $average = null;
        $error = null;

        if ($status === Status::SUCCESS || $status === Status::PARTIAL) {
            $result = array_column(array_column($success, '_'), 'running');

            asort($result);
            $best = [
                current($result),
                key($result)
            ];

            $average = array_sum($result) / count($result);
        }

        if ($status === Status::FAILED || $status === Status::PARTIAL) {
            $failed = array_filter($results, fn($i) => $i['status'] === Status::FAILED);
            $error = current($failed)['error'];
        }

        if ($status === Status::FAILED || $status === Status::PARTIAL) {
            $failed = array_filter($results, fn($i) => $i['status'] === Status::FAILED);
            $error = current($failed)['error'];
        }

        if ($status === Status::SKIPPED) {
            $skipped = array_filter($results, fn($i) => $i['status'] === Status::SKIPPED);
            $error = current($skipped)['error'];
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
