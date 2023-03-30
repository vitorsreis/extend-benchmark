<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

use D5WHUB\Extend\Benchmark\Benchmark;

require_once __DIR__ . '/../vendor/autoload.php';

$agent = new Benchmark(
    'Router libraries benchmark',
    'The purpose of this benchmark is to compare various php-router libraries in different scenarios.'
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
        [ 'return' => 'TEST' ],
        function () {
            return 1;
        }
    )->addTest(
        'Partial2',
        [ 'return' => 'TEST' ],
        function ($__iteraction) {
            return $__iteraction % 2 ? 'TEST' : (object)['aa' => 'bb'];
        }
    );

$agent
    ->execute(5);
