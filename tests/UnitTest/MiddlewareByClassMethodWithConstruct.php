<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Test\Extend\Benchmark\UnitTest;

class MiddlewareByClassMethodWithConstruct
{
    public function __construct(
        protected $__interaction,
        protected $__partial
    ) {
    }

    public function execute(): string
    {
        return "$this->__interaction:{$this->__partial['return']}:test";
    }
}
