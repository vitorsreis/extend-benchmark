<?php

/**
 * This file is part of vsr extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

use VSR\Extend\Benchmark;

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
        ['return' => 'TEST'],
        function () {
            usleep(50000);
            return 'TEST';
        },
        static function () {
            usleep(50000);
            return 'TEST';
        }
    )->addTest(
        'Partial1',
        ['return' => 'TEST'],
        function ($__interaction) {
            return $__interaction % 2 ? 'TEST' : 'AAA';
        }
    )->addTest(
        'Error1',
        ['return' => 'TEST'],
        static function () {
            return 'AAA';
        }
    )->addTest(
        'Success2',
        ['return' => 'TEST'],
        function () {
            usleep(100000);
            return 'TEST';
        }
    )->addTest(
        'Error2',
        ['return' => 'TEST'],
        static function () {
            throw new Exception('TEST', 500);
        }
    )->addTest(
        'Partial2',
        ['return' => 'TEST'],
        function ($__interaction) {
            return $__interaction % 2 ? 'TEST' : 'AAA';
        }
    )->addTest(
        'Skipped1',
        null
    )->addTest(
        'Skipped2',
        ['skipped' => 'Skipped custom message']
    );

$agent
    ->createBenchmark(
        'My Test 2',
        'This is my test 2.',
        3
    )->addTest(
        'Success1',
        ['return' => 'TEST'],
        function () {
            usleep(110000);
            return 'TEST';
        }
    )->addTest(
        'Partial1',
        ['return' => 'TEST'],
        static function ($__interaction) {
            return $__interaction % 2 ? 'TEST' : 'AAA';
        }
    )->addTest(
        'Error1',
        ['return' => 'TEST'],
        function () {
            return ['xxx' => 'zzz'];
        }
    )->addTest(
        'Success2',
        ['return' => 'TEST'],
        static function () {
            usleep(100000);
            return 'TEST';
        }
    )->addTest(
        'Error2',
        ['throw' => null],
        function () {
            throw new Exception('Error', 123);
        }
    )->addTest(
        'Partial2',
        ['return' => 'TEST'],
        static function ($__interaction) {
            return $__interaction % 2 ? 'TEST' : (object)['aa' => 'bb'];
        }
    )->addTest(
        'Skipped1',
        null
    )->addTest(
        'Skipped2',
        ['skipped' => 'Skipped custom message']
    );

$agent
    ->execute(5);
