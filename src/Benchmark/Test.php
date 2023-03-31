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
    private const MAX_STRING_LENGTH = 30;

    public function __construct(
        public string     $title,
        public array|null $experct,
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
    public function execute(int $iteraction, array $callbackArgs = [], array $constructArgs = []): array
    {
        $partial = [
            'type' => 'pending',
            'return' => null,
            'output' => null,
            'throw' => null
        ];

        $startTime = microtime(true);
        foreach ($this->callbacks as $callback) {
            $partial = $this->callback($callback, [
                ...$callbackArgs,
                '__iteraction' => $iteraction,
                '__partial' => $partial ?? ''
            ], [
                ...$constructArgs,
                '__iteraction' => $iteraction,
                '__partial' => $partial ?? ''
            ]);

            if ($partial['type'] === 'throw') {
                break;
            }
        }
        $runningTime = microtime(true) - $startTime;

        return [
            '_' => [
                'start' => $startTime,
                'running' => $runningTime
            ],
            ...$this->experct($partial),
            'hit' => $partial
        ];
    }

    /**
     * @return array{
     *     status:Status,
     *     error:string[]|null
     * }
     */
    private function experct(array $result): array
    {
        $status = Status::SUCCESS;
        $error = [];

        if (array_key_exists('type', $this->experct ?? []) && $result['type'] !== $this->experct['type']) {
            $status = Status::FAILED;
            $error[] = sprintf(
                "Experct type %s, actual %s",
                $this->strvalue($this->experct['type']),
                $this->strvalue($result['type'])
            );
        }

        if (array_key_exists('return', $this->experct ?? []) && $result['return'] !== $this->experct['return']) {
            $status = Status::FAILED;
            $error[] = sprintf(
                "Experct return %s, actual %s",
                $this->strvalue($this->experct['return']),
                $this->strvalue($result['return'])
            );
        }

        if (array_key_exists('output', $this->experct ?? []) && $result['output'] !== $this->experct['output']) {
            $status = Status::FAILED;
            $error[] = sprintf(
                "Experct output %s, actual %s",
                $this->strvalue($this->experct['output']),
                $this->strvalue($result['output'])
            );
        }

        if (array_key_exists('throw', $this->experct ?? [])) {
            $keys = [];

            if (is_null($this->experct['throw']) && $result['throw']) {
                $status = Status::FAILED;
                $keys = ['class', 'message', 'code', 'line', 'file'];
            } elseif (is_string($this->experct['throw'])) {
                if (isset($this->experct['throw']['class'])
                    && $this->experct['throw']['class'] !== ($result['throw']['class'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'class';
                }
            } elseif (is_array($this->experct['throw'])) {
                if (array_key_exists('class', $this->experct['throw'])
                    && $this->experct['throw']['class'] !== ($result['throw']['class'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'class';
                }
                if (array_key_exists('code', $this->experct['throw'])
                    && $this->experct['throw']['code'] !== ($result['throw']['code'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'code';
                }
                if (array_key_exists('message', $this->experct['throw'])
                    && $this->experct['throw']['message'] !== ($result['throw']['message'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'message';
                }
                if (array_key_exists('file', $this->experct['throw'])
                    && $this->experct['throw']['file'] !== ($result['throw']['file'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'file';
                }
                if (array_key_exists('line', $this->experct['throw'])
                    && $this->experct['throw']['line'] !== ($result['throw']['line'] ?? null)) {
                    $status = Status::FAILED;
                    $keys[] = 'line';
                }
            }

            if ($keys) {
                $error[] = sprintf(
                    "Experct throw %s, actual %s",
                    $this->strvalue($this->experct['throw'], $keys),
                    $this->strvalue($result['throw'] ?? null, $keys)
                );
            }
        }

        return [
            'status' => $status,
            'error' => $error ?: null
        ];
    }

    private static function strvalue(mixed $value, array $throw = []): string
    {
        if (is_null($value)) {
            return 'null';
        } elseif ($throw && is_array($value)) {
            return sprintf("throw{%s}", implode(',', array_filter([
                in_array('class', $throw) && array_key_exists('class', $value)
                    ? sprintf("class:\"%s\"", self::strlimited($value['class'], true)) : null,

                in_array('code', $throw) && array_key_exists('code', $value)
                    ? sprintf("code:\"%s\"", $value['code']) : null,

                in_array('message', $throw) && array_key_exists('message', $value)
                    ? sprintf("message:\"%s\"", self::strlimited($value['message'])) : null,

                in_array('file', $throw) && array_key_exists('file', $value)
                    ? sprintf("file:\"%s\"", self::strlimited($value['file'], true)) : null,

                in_array('line', $throw) && array_key_exists('line', $value)
                    ? sprintf("line:\"%s\"", $value['line']) : null
            ])));
        } elseif (is_array($value)) {
            return sprintf("array{%s}", self::strlimited($value));
        } elseif (is_object($value)) {
            return sprintf("%s{%s}", basename(get_class($value)), self::strlimited($value));
        } else {
            return sprintf("%s{%s}", gettype($value), $value);
        }
    }

    private static function strlimited(mixed $value, bool $inverse = false): string
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
