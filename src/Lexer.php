<?php

declare(strict_types=1);

namespace Interpreter;

use RuntimeException;

class Lexer
{
    private string $input;
    private int $length;
    private int $position = 0;
    private int $line = 1;
    private int $column = 1;

    public function __construct(string $input)
    {
        $this->input = $input;
        $this->length = strlen($input);
    }

    /**
     * @return Token[]
     */
    public function tokenize(): array
    {
        $tokens = [];

        while (!$this->isAtEnd()) {
            $this->skipWhitespace();
            if ($this->isAtEnd()) {
                break;
            }

            $char = $this->peek();

            if ($char === '(') {
                $tokens[] = $this->makeToken(Token::T_LPAREN, '(');
                $this->advance();
                continue;
            }

            if ($char === ')') {
                $tokens[] = $this->makeToken(Token::T_RPAREN, ')');
                $this->advance();
                continue;
            }

            if ($char === ',') {
                $tokens[] = $this->makeToken(Token::T_COMMA, ',');
                $this->advance();
                continue;
            }

            if ($char === '"') {
                $tokens[] = $this->readString();
                continue;
            }

            if (ctype_digit($char)) {
                $tokens[] = $this->readNumber();
                continue;
            }

            if ($this->isIdentifierStart($char)) {
                $tokens[] = $this->readIdentifier();
                continue;
            }

            throw new RuntimeException(sprintf(
                'Unexpected character "%s" at %d:%d',
                $char,
                $this->line,
                $this->column
            ));
        }

        $tokens[] = new Token(Token::T_EOF, null, $this->line, $this->column);

        return $tokens;
    }

    private function readString(): Token
    {
        $startLine = $this->line;
        $startColumn = $this->column;

        $this->advance();
        $value = '';

        while (!$this->isAtEnd()) {
            $char = $this->peek();

            if ($char === '"') {
                $this->advance(); 
                return new Token(Token::T_STRING, $value, $startLine, $startColumn);
            }

            if ($char === '\\') {
                $this->advance();
                if ($this->isAtEnd()) {
                    break;
                }

                $escaped = $this->peek();
                $value .= $this->unescape($escaped);
                $this->advance();
                continue;
            }

            $value .= $char;
            $this->advance();
        }

        throw new RuntimeException(sprintf(
            'Unterminated string literal at %d:%d',
            $startLine,
            $startColumn
        ));
    }

    private function readNumber(): Token
    {
        $startLine = $this->line;
        $startColumn = $this->column;

        $number = '';
        while (!$this->isAtEnd() && ctype_digit($this->peek())) {
            $number .= $this->peek();
            $this->advance();
        }

        if (!$this->isAtEnd() && $this->peek() === '.') {
            $number .= '.';
            $this->advance();

            if ($this->isAtEnd() || !ctype_digit($this->peek())) {
                throw new RuntimeException(sprintf(
                    'Invalid float literal at %d:%d',
                    $startLine,
                    $startColumn
                ));
            }

            while (!$this->isAtEnd() && ctype_digit($this->peek())) {
                $number .= $this->peek();
                $this->advance();
            }
        }

        return new Token(Token::T_NUMBER, $number, $startLine, $startColumn);
    }

    private function readIdentifier(): Token
    {
        $startLine = $this->line;
        $startColumn = $this->column;
        $identifier = '';

        while (!$this->isAtEnd() && $this->isIdentifierPart($this->peek())) {
            $identifier .= $this->peek();
            $this->advance();
        }

        $lower = strtolower($identifier);
        return match ($lower) {
            'true' => new Token(Token::T_TRUE, 'true', $startLine, $startColumn),
            'false' => new Token(Token::T_FALSE, 'false', $startLine, $startColumn),
            'null' => new Token(Token::T_NULL, 'null', $startLine, $startColumn),
            default => new Token(Token::T_IDENTIFIER, $identifier, $startLine, $startColumn),
        };
    }

    private function skipWhitespace(): void
    {
        while (!$this->isAtEnd()) {
            $char = $this->peek();
            if ($char !== ' ' && $char !== "\t" && $char !== "\r" && $char !== "\n") {
                break;
            }
            $this->advance();
        }
    }

    private function makeToken(string $type, string $value): Token
    {
        return new Token($type, $value, $this->line, $this->column);
    }

    private function isIdentifierStart(string $char): bool
    {
        return ctype_alpha($char) || $char === '_';
    }

    private function isIdentifierPart(string $char): bool
    {
        return ctype_alnum($char) || $char === '_';
    }

    private function unescape(string $char): string
    {
        return match ($char) {
            '"', '\\' => $char,
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            default => $char,
        };
    }

    private function advance(): void
    {
        if ($this->isAtEnd()) {
            return;
        }

        $char = $this->input[$this->position];
        $this->position++;

        if ($char === "\n") {
            $this->line++;
            $this->column = 1;
        } else {
            $this->column++;
        }
    }

    private function peek(): string
    {
        return $this->input[$this->position];
    }

    private function isAtEnd(): bool
    {
        return $this->position >= $this->length;
    }
}
