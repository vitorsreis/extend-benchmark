<?php

declare(strict_types=1);

namespace D5WHUB\Test\Extend\Benchmark;

use D5WHUB\Extend\Benchmark\Benchmark;
use D5WHUB\Extend\Benchmark\Benchmark\Collection;
use D5WHUB\Extend\Benchmark\Benchmark\Test;
use D5WHUB\Extend\Benchmark\Exception\BenchmarkException;
use D5WHUB\Extend\Benchmark\Printer\Console;
use D5WHUB\Extend\Benchmark\Utils\Status;
use D5WHUB\Test\Extend\Benchmark\UnitTest\MiddlewareByClassMethod;
use D5WHUB\Test\Extend\Benchmark\UnitTest\MiddlewareByClassMethodWithContruct;
use D5WHUB\Test\Extend\Benchmark\UnitTest\MiddlewareByClassStaticMethod;
use Exception;
use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{
    public function testSimple(): void
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ fn() => 'TEST' ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('TEST', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testExpectReturnSucces(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'return' => 'TEST' ],
            [ fn() => 'TEST' ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('TEST', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testExpectReturnPartial(): void
    {
        ($agent = new Benchmark(__FUNCTION__))
            ->createBenchmark(__FUNCTION__)
            ->addTest('TT', [ 'return' => 'TEST' ], fn($__iteraction) => $__iteraction % 2 ? 'TEST' : 111);
        $result = $agent->execute(2);

        $this->assertEquals(Status::PARTIAL, $result['TT']['_']['status']);
        $this->assertEquals([ 'Experct return "TEST", actual 111' ], $result['TT']['_']['error']);
    }

    public function testExpectReturnFailed(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'return' => 'TEST' ],
            [ fn() => 111 ]
        ))->execute(1);

        $this->assertEquals(Status::FAILED, $result['status']);
        $this->assertEquals('integer', $result['hit']['type']);
        $this->assertEquals(111, $result['hit']['return']);
        $this->assertEquals([ 'Experct return "TEST", actual 111' ], $result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testExpectReturnSkipped(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'return' => 'TEST' ],
            []
        ))->execute(1);

        $this->assertEquals(Status::SKIPPED, $result['status']);
        $this->assertEquals('skipped', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEquals([ 'Skipped, empty callbacks...' ], $result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testExpectOutputSuccess(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'output' => 'TEST' ],
            [ function () { echo 'TEST'; } ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('output', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEquals('TEST', $result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testExpectOutputFailed(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'output' => 'TEST' ],
            [ function () { echo 111; } ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::FAILED, $result['status']);
        $this->assertEquals('output', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEquals([ 'Experct output "TEST", actual "111"' ], $result['error']);
        $this->assertEquals(111, $result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testExpectThrowNullSuccess(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => null ],
            [ fn () => 'TEST' ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('TEST', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testExpectThrowNullFailed(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => null ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::FAILED, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEquals(
            [
                sprintf(
                    'Experct throw NULL, actual throw{class:"%s",code:"%s",message:"%s",file:"%s",line:"%s"}',
                    Exception::class,
                    E_ERROR,
                    'TEST',
                    strlen(__FILE__) > 50 ? "..." . substr(__FILE__, -47) : __FILE__,
                    __LINE__ - 14
                )
            ],
            $result['error']); // phpcs:ignore
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 23,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowClassSuccess(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'class' => Exception::class ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowClassFailed(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ "class" => "xxx" ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::FAILED, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEquals([ 'Experct throw throw{class:"xxx"}, actual throw{class:"Exception"}' ], $result['error']); // phpcs:ignore
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowMessageSuccess(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'message' => 'TEST' ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowMessageFailed(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'message' => 'xxx' ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::FAILED, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEquals([ 'Experct throw throw{message:"xxx"}, actual throw{message:"TEST"}' ], $result['error']); // phpcs:ignore
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowCodeSuccess(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'code' => E_ERROR ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowCodeFailed(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'code' => 111 ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::FAILED, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEquals([ 'Experct throw throw{code:"111"}, actual throw{code:"1"}' ], $result['error']); // phpcs:ignore
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowLineSuccess(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'line' => __LINE__ + 1 ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowLineFailed(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'line' => 1 ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::FAILED, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEquals([ 'Experct throw throw{line:"1"}, actual throw{line:"' . (__LINE__ - 6) . '"}' ], $result['error']); // phpcs:ignore
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowFileSuccess(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'file' => __FILE__ ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 12,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testExpectThrowFileFailed(): void
    {
        $result = (new Test(
            __FUNCTION__,
            [ 'throw' => [ 'file' => 'xxx' ] ],
            [ fn () => throw new Exception('TEST', E_ERROR) ] // phpcs:ignore
        ))->execute(1);

        $this->assertEquals(Status::FAILED, $result['status']);
        $this->assertEquals('throw', $result['hit']['type']);
        $this->assertEmpty($result['hit']['return']);
        $this->assertEquals([
            sprintf(
                "Experct throw throw{file:\"xxx\"}, actual throw{file:\"%s\"}",
                strlen(__FILE__) > 50 ? "..." . substr(__FILE__, -47) : __FILE__,
            )
        ], $result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEquals([
            'class' => Exception::class,
            'message' => 'TEST',
            'code' => E_ERROR,
            'line' => __LINE__ - 17,
            'file' => __FILE__
        ], $result['hit']['throw']);
    }

    public function testWithMiddlewareByFunctionName()
    {
        function middlewareNamedFunction($__iteraction, $__partial): string
        {
            return "$__iteraction:$__partial[return]:test";
        }

        $result = (new Test(
            __FUNCTION__,
            null,
            [ "\\" . __NAMESPACE__ . '\\middlewareNamedFunction' ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByNativeFunction()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ 'stripos' ]
        ))->execute(1, [ 'haystack' => 'ABCDEF', 'needle' => 'D' ]);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('integer', $result['hit']['type']);
        $this->assertEquals(3, $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByAnonymousFunction()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ function ($__iteraction, $__partial) {
                return "$__iteraction:$__partial[return]:test";
            } ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByArrowFunction()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ fn ($__iteraction, $__partial) => "$__iteraction:$__partial[return]:test" ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByVariableFunction()
    {
        $varfunc = fn ($__iteraction, $__partial) => "$__iteraction:$__partial[return]:test";

        $result = (new Test(
            __FUNCTION__,
            null,
            [ $varfunc ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByClassStaticMethodArray()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ [ MiddlewareByClassStaticMethod::class, 'params' ] ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByClassStaticMethodString()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ "\\" . __NAMESPACE__ . "\\UnitTest\\MiddlewareByClassStaticMethod::params" ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByClassStaticMethodObject()
    {
        $class = new MiddlewareByClassStaticMethod();

        $result = (new Test(
            __FUNCTION__,
            null,
            [ [ $class, 'params' ] ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByClassMethodArray()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ [ MiddlewareByClassMethod::class, 'execute' ] ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByClassMethodString()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ "\\" . __NAMESPACE__ . "\\UnitTest\\MiddlewareByClassMethod::execute" ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByClassMethodObject()
    {
        $class = new MiddlewareByClassMethod();

        $result = (new Test(
            __FUNCTION__,
            null,
            [ [ $class, 'execute' ] ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testWithMiddlewareByClassMethodWithConstructArray()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [ [ MiddlewareByClassMethodWithContruct::class, 'execute' ] ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1::test', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }

    public function testErrorCallbackNotFound()
    {
        $this->expectException(BenchmarkException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("Function xxx() does not exist");

        (new Test(
            __FUNCTION__,
            null,
            [ "xxx" ]
        ))->execute(1);
    }

    public function testErrorClassNotFound()
    {
        $this->expectException(BenchmarkException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("Class \"\\~\" does not exist");

        (new Test(
            __FUNCTION__,
            null,
            [ '\\~::~notFoundMethod' ]
        ))->execute(1);
    }

    public function testErrorClassMethodNotFound()
    {
        $this->expectException(BenchmarkException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("Method " . self::class . "::~notFoundMethod() does not exist");

        (new Test(
            __FUNCTION__,
            null,
            [ self::class . '::~notFoundMethod' ]
        ))->execute(1);
    }

    public function testErrorFunctionNotFound()
    {
        $this->expectException(BenchmarkException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("Function ~notFoundFunction() does not exist");

        (new Test(
            __FUNCTION__,
            null,
            [ '~notFoundFunction' ]
        ))->execute(1);
    }

    public function testMultiplesCallbacks()
    {
        $result = (new Test(
            __FUNCTION__,
            null,
            [
                fn($__iteraction) => "$__iteraction:TEST",
                fn($__iteraction, $__partial) => "$__partial[return]   $__iteraction:TEST",
                fn($__iteraction, $__partial) => "$__partial[return]   $__iteraction:TEST",
                fn($__iteraction, $__partial) => "$__partial[return]   $__iteraction:TEST",
                fn($__iteraction, $__partial) => "$__partial[return]   $__iteraction:TEST",
            ]
        ))->execute(1);

        $this->assertEquals(Status::SUCCESS, $result['status']);
        $this->assertEquals('string', $result['hit']['type']);
        $this->assertEquals('1:TEST   1:TEST   1:TEST   1:TEST   1:TEST', $result['hit']['return']);
        $this->assertEmpty($result['error']);
        $this->assertEmpty($result['hit']['output']);
        $this->assertEmpty($result['hit']['throw']);
    }
}
