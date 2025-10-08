<?php

declare(strict_types=1);

namespace Interpreter;

use RuntimeException;

class Parser
{
    /** @var Token[] */
    private array $tokens;
    private int $position = 0;

    /**
     * @param Token[] $tokens
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(): array
    {
        $expression = $this->parseExpression();
        $this->consume(Token::T_EOF, 'end of input');

        return $expression;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseExpression(): array
    {
        $token = $this->peek();

        return match ($token->type) {
            Token::T_LPAREN => $this->parseFunctionCall(),
            Token::T_STRING,
            Token::T_NUMBER,
            Token::T_TRUE,
            Token::T_FALSE,
            Token::T_NULL => $this->parseConstant(),
            default => $this->error($token, 'expression'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFunctionCall(): array
    {
        $this->consume(Token::T_LPAREN, "'('");
        $nameToken = $this->consume(Token::T_IDENTIFIER, 'function name');
        $arguments = [];

        if ($this->match(Token::T_COMMA)) {
            do {
                $arguments[] = $this->parseExpression();
            } while ($this->match(Token::T_COMMA));
        }

        $this->consume(Token::T_RPAREN, "')'");

        return [
            'type' => 'call',
            'name' => $nameToken->value,
            'arguments' => $arguments,
            'line' => $nameToken->line,
            'column' => $nameToken->column,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseConstant(): array
    {
        $token = $this->advance();

        return match ($token->type) {
            Token::T_STRING => [
                'type' => 'const',
                'value' => $token->value,
                'line' => $token->line,
                'column' => $token->column,
            ],
            Token::T_NUMBER => [
                'type' => 'const',
                'value' => $this->normalizeNumber($token->value),
                'line' => $token->line,
                'column' => $token->column,
            ],
            Token::T_TRUE => [
                'type' => 'const',
                'value' => true,
                'line' => $token->line,
                'column' => $token->column,
            ],
            Token::T_FALSE => [
                'type' => 'const',
                'value' => false,
                'line' => $token->line,
                'column' => $token->column,
            ],
            Token::T_NULL => [
                'type' => 'const',
                'value' => null,
                'line' => $token->line,
                'column' => $token->column,
            ],
            default => $this->error($token, 'constant'),
        };
    }

    private function normalizeNumber(string $raw): int|float
    {
        return str_contains($raw, '.') ? (float) $raw : (int) $raw;
    }

    private function match(string $type): bool
    {
        if ($this->check($type)) {
            $this->advance();
            return true;
        }

        return false;
    }

    private function consume(string $type, string $expectedDescription): Token
    {
        $token = $this->peek();
        if ($token->type !== $type) {
            $this->error($token, $expectedDescription);
        }

        return $this->advance();
    }

    private function check(string $type): bool
    {
        return $this->peek()->type === $type;
    }

    private function peek(): Token
    {
        return $this->tokens[$this->position];
    }

    private function advance(): Token
    {
        $token = $this->tokens[$this->position];

        if ($token->type !== Token::T_EOF) {
            $this->position++;
        }

        return $token;
    }

    private function isAtEnd(): bool
    {
        return $this->peek()->type === Token::T_EOF;
    }

    /**
     * @return never
     */
    private function error(Token $token, string $expected): never
    {
        $message = sprintf(
            'Parse error at %d:%d: expected %s, got %s',
            $token->line,
            $token->column,
            $expected,
            $token->type
        );

        throw new RuntimeException($message);
    }
}
