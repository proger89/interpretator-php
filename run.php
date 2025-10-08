#!/usr/bin/env php
<?php

declare(strict_types=1);

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

spl_autoload_register(function (string $class): void {
    $prefix = 'Interpreter\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

use Interpreter\Interpreter;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php run.php <program-file> [arguments]\n");
    exit(1);
}

$programPath = $argv[1];
$source = @file_get_contents($programPath);
if ($source === false) {
    fwrite(STDERR, "Unable to read program file: {$programPath}\n");
    exit(1);
}

$arguments = array_slice($argv, 2);

$interpreter = new Interpreter();

try {
    $result = $interpreter->run($source, $arguments);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

echo valueToString($result) . PHP_EOL;

function valueToString(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if ($value === null) {
        return 'null';
    }

    if (is_array($value)) {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($encoded !== false) {
            return $encoded;
        }
    }

    return (string) $value;
}
