<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Extend\Benchmark\Utils;

interface Printer
{
    public function start(): self;

    public function title(string $title, ?string $comment): self;

    public function subtitle(string $title, ?string $comment = null, ?int $iterations = null): self;

    public function results(array $results, bool $end = false): self;

    public function skipline(int $times = 1): self;

    public function tmp(string $text): self;

    public function tmpclear(): self;

    public function end(float $runningTime, int $totalBenchmark, int $totalInteractions): self;
}
