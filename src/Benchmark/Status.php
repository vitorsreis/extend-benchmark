<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Extend\Benchmark\Benchmark;

enum Status: string
{
    case FAILED = 'failed';
    case PARTIAL = 'partial';
    case SUCCESS = 'success';
    case SKIPPED = 'skipped';
}
