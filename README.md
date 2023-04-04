# Simple benchmark for PHP 8.2
[![Latest Stable Version](https://poser.pugx.org/d5whub/extend-benchmark/v/stable)](https://packagist.org/packages/d5whub/extend-benchmark)
[![PHP Version Require](http://poser.pugx.org/d5whub/extend-benchmark/require/php)](https://packagist.org/packages/d5whub/extend-benchmark)
[![Monthly Downloads](https://poser.pugx.org/d5whub/extend-benchmark/d/monthly)](https://packagist.org/packages/d5whub/extend-benchmark)
[![Total Downloads](https://poser.pugx.org/d5whub/extend-benchmark/downloads)](https://packagist.org/packages/d5whub/extend-benchmark)
[![License](https://poser.pugx.org/d5whub/extend-benchmark/license)](https://packagist.org/packages/d5whub/extend-benchmark)

With this simple and elegant benchmark, you can create multiple tests with multiple callbacks at once and output tests in real time.

## Install
```shell
composer require "d5whub/extend-benchmark"
```

## Usage
```php
require_once __DIR__ . '/vendor/autoload.php';

use D5WHUB\Extend\Benchmark\Benchmark;

// 1. Benchmark agent
$agent = new Benchmark(
    'My benchmark tests',
    // [optional] 'My Comment',
    // [optional] Printer, if not informad will be used the default printers ( Printer/Console | Printer/Html ) 
);

// 2. Create benchmarks
$benchmark1 = $agent
    ->createBenchmark(
        'My Benchmark 1',
        // [optional] 'Test 1 Comment',
        // [optional] <iterations>, force number of interactions for this test
    );

// 3. Add tests
$benchmark1->addTest(
    'Test 1',
    [ 'return' => 'RESULT1-OK' ], // Expected result "RESULT1-OK"
    fn() => 'RESULT1-OK', // Callback 1
);
$benchmark1->addTest(
    'Test 2',
    [ 'return' => 'RESULT2-OK' ], // Expected result "RESULT1-OK"
    fn() => 'RESULT2', // Callback 1 - returns "RESULT2"
    fn($__partial) => "$__partial[return]-OK", // Callback 2 - Merge with callback 1, returns "RESULT2-OK"
);

// 4. Execute benchmarks and tests
$agent->execute(
    // [optional, default 1] <iterations>, number of interactions for all tests
);
```

###### • Expect test types
```php
• null // Use null to any result
• array( // or array of expected results, if omitted, the test will be considered successful
    "skipped"   => string, // [optional] custom skipped message print

    "type"   => string, // [optional] output|throw|skipped or return value type
    
    "return" => mixed, // [optional] accept callback return value
    
    "output" => null, // [optional] not accept callback output

    "output" => string, // [optional] accept callback output
    
    "throw"  => null, // [optional] not accept callback throw
    
    "throw"  => string, // [optional] accept callback throw class
    
    "throw"  => array( // [optional] accept callback throw by class, message, code, file and line
        "class" => string,   # [optional] accept callback throw class
        "message" => string, # [optional] accept callback throw message
        "code" => int,       # [optional] accept callback throw code
        "file" => string,    # [optional] accept callback throw file
        "line" => int        # [optional] accept callback throw line
    )
)
```
###### • There are 2 magic arguments that are automatically filled when detected in the call.
```php
// Current interaction number
$__iteraction = int

// Partial test returns
$__partial = array(
    "type"   => string, // pending|output|throw|skipped or return value type
    "return" => mixed,
    "output" => null|string,
    "throw"  => null|array(
        "class"   => string,
        "message" => string,
        "code"    => int,
        "line"    => int,
        "file"    => string
    )
)
```

###### If necessary, arguments can be added to the mapping to call the "__construct" and method. The smart populate looks for required arguments and fills them in recursively, regardless of position, so there's no argument overflow.

```php
$benchmark = $agent->createBenchmark('Test 1');

// by methods
$benchmark->argsByMethods([
    'arg1' => 'value1',
    'arg2' => 'value2',
    'arg3' => 'value3' // ignored
]);

$benchmark->addTest(
    '<title>',
    '<expected result>', // Expected result
    fn ($arg1, $__iteraction, $arg2, $__partial) => ..., // arrow function
)
```
###### Supported callbacks
```php
$benchmark->test(
    '<title>',
    '<expected result>',
    
    # Supported callbacks ↓↓↓
    
    "strlen", // native function name
    
    "myFunction", // function name
    
    function () { ... }, // anonymous function
    
    fn () => ..., // arrow function
    
    "MyClass::myMethod", // class method, "__construct" will be called before the method
    
    [ MyClass::class, "myMethod" ], # class method, "__construct" will be called before the method
    
    "MyClass::myStaticMethod", // class static method
    
    [ MyClass::class, "myStaticMethod" ], # class static method
    
    [ new MyClass(), "myMethod" ], // object, myMethod
    
    [ $myObject, "myMethod" ], # object, myMethod
    
    new MyClass(), # object, __invoke
    
    $myObject, # object, __invoke
    
    [ new MyClass(), "myStaticMethod" ], // object, myStaticMethod
    
    [ $myObject, "myStaticMethod" ], # object, myStaticMethod
);
```
