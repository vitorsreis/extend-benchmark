<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Extend\Benchmark\Benchmark;

use Closure;
use D5WHUB\Extend\Benchmark\Exception\BenchmarkException;
use D5WHUB\Extend\Benchmark\Utils\Status;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

readonly class Test
{
    private const MAX_STRING_LENGTH = 50;

    public function __construct(
        public string     $title,
        public array|null $expect,
        public array      $callbacks
    ) {
    }

    /**
     * @return array{
     *     _:array{
     *         start:float,
     *         running:float,
     *         experct:mixed
     *     },
     *     status:Status,
     *     error:string[]|null,
     *     hit:array{
     *         type:string,
     *         return:mixed,
     *         output:string,
     *         throw:array{
     *             class:string,
     *             message:string,
     *             code:mixed,
     *             line:int,
     *             file:string,
     *         }|null
     *     }
     * }
     * @throws BenchmarkException
     */
    public function execute(int $interaction, array $callbackArgs = [], array $constructArgs = []): array
    {
        $partial = [
            'type' => 'pending',
            'return' => null,
            'output' => null,
            'throw' => null
        ];

        $startTime = microtime(true);
        if (empty($this->callbacks)) {
            $partial = [
                'type' => 'skipped',
                'return' => null,
                'output' => null,
                'throw' => null
            ];
        } else {
            foreach ($this->callbacks as $callback) {
                $partial = $this->callback($callback, [
                    ...$callbackArgs,
                    '__interaction' => $interaction,
                    '__partial' => $partial
                ], [
                    ...$constructArgs,
                    '__interaction' => $interaction,
                    '__partial' => $partial
                ]);

                if ($partial['type'] === 'throw') {
                    break;
                }
            }
        }
        $runningTime = microtime(true) - $startTime;

        return [
            '_' => [
                'start' => $startTime,
                'running' => $runningTime
            ],
            ...$this->expect($partial),
            'hit' => $partial
        ];
    }

    /**
     * @return array{
     *     status:Status,
     *     error:string[]|null
     * }
     */
    private function expect(array $result): array
    {
        if ($result['type'] === 'skipped') {
            return [
                'status' => Status::SKIPPED,
                'error' => [ $this->expect['skipped'] ?? "Skipped, empty callbacks..." ]
            ];
        }

        $status = Status::SUCCESS;
        $error = [];

        if (array_key_exists('throw', $this->expect ?? [])) {
            $keys = [];

            if (is_null($this->expect['throw']) && $result['throw']) {
                $status = Status::FAILED;
                $keys = [ 'class', 'message', 'code', 'line', 'file' ];
            } elseif (is_string($this->expect['throw'])) {
                if (isset($this->expect['throw']['class'])
                    && $this->expect['throw']['class'] !== ($result['throw']['class'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'class';
                }
            } elseif (is_array($this->expect['throw'])) {
                if (array_key_exists('class', $this->expect['throw'])
                    && $this->expect['throw']['class'] !== ($result['throw']['class'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'class';
                }
                if (array_key_exists('code', $this->expect['throw'])
                    && $this->expect['throw']['code'] !== ($result['throw']['code'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'code';
                }
                if (array_key_exists('message', $this->expect['throw'])
                    && $this->expect['throw']['message'] !== ($result['throw']['message'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'message';
                }
                if (array_key_exists('file', $this->expect['throw'])
                    && $this->expect['throw']['file'] !== ($result['throw']['file'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'file';
                }
                if (array_key_exists('line', $this->expect['throw'])
                    && $this->expect['throw']['line'] !== ($result['throw']['line'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'line';
                }
            }

            if ($keys) {
                $error[] = sprintf(
                    "Expect throw %s, actual %s",
                    $this->tostr($this->expect['throw'], $keys),
                    $this->tostr($result['throw'] ?? null, $keys)
                );
            }
        }

        if (array_key_exists('output', $this->expect ?? []) && $result['output'] !== $this->expect['output']) {
            $status = Status::FAILED;
            $error[] = sprintf(
                "Expect output %s, actual %s",
                $this->tostr($this->expect['output']),
                empty($result['throw']) || array_key_exists('throw', $this->expect ?? [])
                    ? $this->tostr($result['output'])
                    : $this->tostr($result['throw'], [ 'class', 'message', 'code', 'line', 'file' ])
            );
        }

        if (array_key_exists('return', $this->expect ?? []) && $result['return'] !== $this->expect['return']) {
            $status = Status::FAILED;
            $error[] = sprintf(
                "Expect return %s, actual %s",
                $this->tostr($this->expect['return']),
                empty($result['throw']) || array_key_exists('throw', $this->expect ?? [])
                    ? $this->tostr($result['return'])
                    : $this->tostr($result['throw'], [ 'class', 'message', 'code', 'line', 'file' ])
            );
        }

        if (array_key_exists('type', $this->expect ?? []) && $result['type'] !== $this->expect['type']) {
            $status = Status::FAILED;
            $error[] = sprintf(
                "Expect type %s, actual %s",
                $this->tostr($this->expect['type']),
                $this->tostr($result['type'])
            );
        }

        return [
            'status' => $status,
            'error' => $error ?: null
        ];
    }

    /**
     * Convert mixed to string
     */
    private static function tostr(mixed $value, array $throw = []): string
    {
        if (is_null($value) || (is_string($value) && strtoupper("$value") === "NULL")) {
            return 'NULL';
        } elseif (is_string($value)) {
            return "\"$value\"";
        } elseif (is_bool($value)) {
            return $value ? "TRUE" : 'FALSE';
        } elseif (is_int($value)) {
            return "$value" ?: "0";
        } elseif (is_float($value)) {
            return "$value" ?: "0.0";
        } elseif ($throw && is_array($value)) {
            return sprintf("throw{%s}", implode(',', array_filter([
                in_array('class', $throw) && array_key_exists('class', $value)
                    ? sprintf("class:\"%s\"", self::limstr($value['class'], true)) : null,

                in_array('code', $throw) && array_key_exists('code', $value)
                    ? sprintf("code:\"%s\"", $value['code']) : null,

                in_array('message', $throw) && array_key_exists('message', $value)
                    ? sprintf("message:\"%s\"", $value['message']) : null,

                in_array('file', $throw) && array_key_exists('file', $value)
                    ? sprintf("file:\"%s\"", self::limstr($value['file'], true)) : null,

                in_array('line', $throw) && array_key_exists('line', $value)
                    ? sprintf("line:\"%s\"", $value['line']) : null
            ])));
        } elseif (is_array($value)) {
            return sprintf("array{%s}", self::limstr($value));
        } elseif (is_object($value)) {
            return sprintf("%s{%s}", basename(get_class($value)), self::limstr($value));
        } else {
            return sprintf("%s{%s}", gettype($value), $value);
        }
    }

    /**
     * Limit string size
     */
    private static function limstr(mixed $value, bool $inverse = false): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
            $value = substr($value, 1, -1);
        } else {
            $value = strval($value);
        }

        if (strlen($value) < self::MAX_STRING_LENGTH) {
            return $value;
        }

        return $inverse
            ? "..." . substr($value, - self::MAX_STRING_LENGTH + 3)
            : substr($value, 0, self::MAX_STRING_LENGTH - 3) . "...";
    }

    /**
     * @throws BenchmarkException
     */
    private static function callback(mixed $callback, array $callbackArgs = [], array $constructArgs = []): array
    {
        if (is_object($callback) && !($callback instanceof Closure)) {
            # anonymous class
            $callback = [$callback, '__invoke'];
        } elseif (is_string($callback)) {
            if (str_contains($callback, '::')) {
                # class::method string
                $callback = explode('::', $callback);
            } elseif (!function_exists($callback) && class_exists($callback)) {
                echo 'a' . PHP_EOL;
                # class::__invoke
                $callback = [$callback, '__invoke'];
            }
        }

        if (is_array($callback)) {
            # method / static method
            try {
                $reflection = new ReflectionMethod($callback[0], $callback[1]);
            } catch (ReflectionException $e) {
                throw new BenchmarkException($e->getMessage(), 500, $e);
            }

            if (!$reflection->isStatic()) {
                $arguments = method_exists($callback[0], '__construct')
                    ? self::populate(new ReflectionMethod($callback[0], '__construct'), $constructArgs)
                    : [];

                $callback[0] = new $callback[0](...$arguments);
            }
        } else {
            # function / anonymous function / arrow function / string function
            try {
                $reflection = new ReflectionFunction($callback);
            } catch (ReflectionException $e) {
                throw new BenchmarkException($e->getMessage(), 500, $e);
            }
        }

        @ob_start();
        try {
            $return = call_user_func_array($callback, self::populate($reflection, $callbackArgs));
            $throw = null;
            $output = @ob_get_clean();
            $type = is_null($return ?? null) && !empty($output) ? 'output' : gettype($return ?? null);
        } catch (Throwable $e) {
            $return = null;
            $output = null;
            ob_end_clean();
            $throw = [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ];
            $type = 'throw';
        }

        return [
            'type' => $type ,
            'return' => $return,
            'output' => $output,
            'throw' => $throw
        ];
    }

    /**
     * @throws BenchmarkException
     */
    private static function populate(ReflectionMethod|ReflectionFunction $reflection, array $params = []): array
    {
        $arguments = [];

        foreach ($reflection->getParameters() as $param) {
            if (isset($params[$param->getName()])) {
                $arguments[$param->getName()] = $params[$param->getName()]; # URL named params
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $arguments[$param->getName()] = $param->getDefaultValue();
                continue;
            }

            throw new BenchmarkException(
                sprintf("Required argument \"%s\" for invoke \"%s\"!", $param->getName(), $reflection->getName()),
                500
            );
        }

        return $arguments;
    }
}
