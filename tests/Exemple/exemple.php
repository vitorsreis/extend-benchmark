<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

use D5WHUB\Extend\Benchmark\Benchmark;

require_once __DIR__ . '/../../vendor/autoload.php';

$agent = new Benchmark(
    'My Benchmark',
    'This is my benchmark.'
);

$agent
    ->createBenchmark(
        'My Test 1',
        'This is my test 1.'
    )->addTest(
        'Success1',
        [ 'return' => 'TEST' ],
        function () {
            usleep(50000);
            return 'TEST';
        },
        function () {
            usleep(50000);
            return 'TEST';
        }
    )->addTest(
        'Partial1',
        [ 'return' => 'TEST' ],
        function ($__iteraction) {
            return $__iteraction % 2 ? 'TEST' : 'AAA';
        }
    )->addTest(
        'Error1',
        [ 'return' => 'TEST' ],
        function () {
            return 'AAA';
        }
    )->addTest(
        'Success2',
        [ 'return' => 'TEST' ],
        function () {
            usleep(100000);
            return 'TEST';
        }
    )->addTest(
        'Error2',
        [ 'return' => 'TEST' ],
        function () {
            throw new Exception('TEST', 500);
        }
    )->addTest(
        'Partial2',
        [ 'return' => 'TEST' ],
        function ($__iteraction) {
            return $__iteraction % 2 ? 'TEST' : 'AAA';
        }
    )->addTest(
        'Skiped1',
        null
    )->addTest(
        'Skiped2',
        [ 'skiped' => 'Skiped custom message' ]
    );

$agent
    ->createBenchmark(
        'My Test 2',
        'This is my test 2.',
        3
    )->addTest(
        'Success1',
        [ 'return' => 'TEST' ],
        function () {
            usleep(110000);
            return 'TEST';
        }
    )->addTest(
        'Partial1',
        [ 'return' => 'TEST' ],
        function ($__iteraction) {
            return $__iteraction % 2 ? 'TEST' : 'AAA';
        }
    )->addTest(
        'Error1',
        [ 'return' => 'TEST' ],
        function () {
            return [ 'xxx' => 'zzz' ];
        }
    )->addTest(
        'Success2',
        [ 'return' => 'TEST' ],
        function () {
            usleep(100000);
            return 'TEST';
        }
    )->addTest(
        'Error2',
        [ 'throw' => null ],
        function () {
            throw new Exception('Error', 123);
        }
    )->addTest(
        'Partial2',
        [ 'return' => 'TEST' ],
        function ($__iteraction) {
            return $__iteraction % 2 ? 'TEST' : (object)['aa' => 'bb'];
        }
    )->addTest(
        'Skiped1',
        null
    )->addTest(
        'Skiped2',
        [ 'skiped' => 'Skiped custom message' ]
    );

$agent
    ->execute(5);
