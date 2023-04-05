<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Test\Extend\Benchmark\UnitTest;

class MiddlewareByClassMethod
{
    public function execute($__interaction, $__partial): string
    {
        return "$__interaction:$__partial[return]:test";
    }
}
