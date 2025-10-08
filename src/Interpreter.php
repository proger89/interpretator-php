<?php

declare(strict_types=1);

namespace Interpreter;

use RuntimeException;

class Interpreter
{
    private FunctionRegistry $registry;

    public function __construct(?FunctionRegistry $registry = null)
    {
        $this->registry = $registry ?? new FunctionRegistry();
        $this->registerBuiltins();
    }

    public function registerFunction(string $name, callable $handler): void
    {
        $this->registry->register($name, $handler);
    }

    /**
     * @param list<mixed> $arguments
     */
    public function run(string $source, array $arguments = []): mixed
    {
        $lexer = new Lexer($source);
        $tokens = $lexer->tokenize();

        $parser = new Parser($tokens);
        $ast = $parser->parse();

        $context = new ExecutionContext($arguments);

        return $this->evaluate($ast, $context);
    }

    /**
     * @param array<string, mixed> $node
     */
    private function evaluate(array $node, ExecutionContext $context): mixed
    {
        return match ($node['type']) {
            'const' => $node['value'],
            'call' => $this->evaluateCall($node, $context),
            default => throw new RuntimeException('Unknown node type ' . $node['type']),
        };
    }

    /**
     * @param array<string, mixed> $node
     */
    private function evaluateCall(array $node, ExecutionContext $context): mixed
    {
        $args = [];
        foreach ($node['arguments'] as $argument) {
            $args[] = $this->evaluate($argument, $context);
        }

        return $this->registry->call($node['name'], $args, $context);
    }

    private function registerBuiltins(): void
    {
        $this->registerBuiltin('getArg', function (array $args, ExecutionContext $context): mixed {
            $this->assertArgumentCount('getArg', 1, $args);
            $index = $args[0];
            if (!is_int($index)) {
                throw new RuntimeException('getArg expects integer index');
            }

            return $context->getArgument($index);
        });

        $this->registerBuiltin('array', function (array $args, ExecutionContext $context): array {
            unset($context);
            return $args;
        });

        $this->registerBuiltin('map', function (array $args, ExecutionContext $context): array {
            unset($context);
            $this->assertArgumentCount('map', 2, $args);
            [$keys, $values] = $args;

            if (!is_array($keys) || !is_array($values)) {
                throw new RuntimeException('map expects two array arguments');
            }

            if (count($keys) !== count($values)) {
                throw new RuntimeException('map expects arrays of the same length');
            }

            $result = [];
            $keyList = array_values($keys);
            $valueList = array_values($values);
            foreach ($keyList as $offset => $key) {
                $value = $valueList[$offset] ?? null;
                $result[(string) $key] = $value;
            }

            return $result;
        });

        $this->registerBuiltin('json', function (array $args, ExecutionContext $context): string {
            unset($context);
            $this->assertArgumentCount('json', 1, $args);

            $encoded = json_encode($args[0], JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new RuntimeException('json encoding failed: ' . json_last_error_msg());
            }

            return $encoded;
        });

        $this->registerBuiltin('concat', function (array $args, ExecutionContext $context): string {
            unset($context);
            $this->assertArgumentCount('concat', 2, $args);

            return (string) $args[0] . (string) $args[1];
        });
    }

    private function registerBuiltin(string $name, callable $handler): void
    {
        if (!$this->registry->has($name)) {
            $this->registry->register($name, $handler);
        }
    }

    /**
     * @param list<mixed> $args
     */
    private function assertArgumentCount(string $function, int $expected, array $args): void
    {
        if (count($args) !== $expected) {
            throw new RuntimeException(sprintf(
                "%s expects %d arguments, got %d",
                $function,
                $expected,
                count($args)
            ));
        }
    }
}
