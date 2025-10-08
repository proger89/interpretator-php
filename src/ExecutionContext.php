<?php

declare(strict_types=1);

namespace Interpreter;

use RuntimeException;

class ExecutionContext
{
    /**
     * @param list<mixed> $arguments
     */
    public function __construct(private readonly array $arguments)
    {
    }

    /**
     * @return list<mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(int $index): mixed
    {
        if (!array_key_exists($index, $this->arguments)) {
            throw new RuntimeException(sprintf('Argument %d is not provided', $index));
        }

        return $this->arguments[$index];
    }
}
