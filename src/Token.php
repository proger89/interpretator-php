<?php

declare(strict_types=1);

namespace Interpreter;

class Token
{
    public const T_LPAREN = 'LPAREN';
    public const T_RPAREN = 'RPAREN';
    public const T_COMMA = 'COMMA';
    public const T_IDENTIFIER = 'IDENTIFIER';
    public const T_STRING = 'STRING';
    public const T_NUMBER = 'NUMBER';
    public const T_TRUE = 'TRUE';
    public const T_FALSE = 'FALSE';
    public const T_NULL = 'NULL';
    public const T_EOF = 'EOF';

    public function __construct(
        public readonly string $type,
        public readonly ?string $value,
        public readonly int $line,
        public readonly int $column
    ) {
    }
}
