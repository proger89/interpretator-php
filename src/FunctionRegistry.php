<?php

declare(strict_types=1);

namespace Interpreter;

use RuntimeException;

class FunctionRegistry
{
    /** @var array<string, callable> */
    private array $functions = [];

    public function register(string $name, callable $handler): void
    {
        $this->functions[$this->normalize($name)] = $handler;
    }

    public function has(string $name): bool
    {
        return isset($this->functions[$this->normalize($name)]);
    }

    /**
     * @param string $name
     * @param list<mixed> $arguments
     */
    public function call(string $name, array $arguments, ExecutionContext $context): mixed
    {
        $key = $this->normalize($name);
        if (!isset($this->functions[$key])) {
            throw new RuntimeException(sprintf("Function '%s' is not defined", $name));
        }

        return ($this->functions[$key])($arguments, $context);
    }

    private function normalize(string $name): string
    {
        return strtolower($name);
    }
}
