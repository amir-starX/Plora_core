<?php

declare(strict_types=1);

namespace Core;

class YamlParser
{
    public static function parseFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        return self::parse(file_get_contents($path));
    }

    public static function parse(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = self::stripCommentsAndBlanks($lines);

        if (empty($lines)) {
            return [];
        }

        [$result, ] = self::parseBlock($lines, 0, 0);
        return $result;
    }

    private static function stripCommentsAndBlanks(array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            if (trim($line) === '' || ltrim($line)[0] === '#') {
                continue;
            }
            $out[] = rtrim($line);
        }
        return array_values($out);
    }

    private static function indentOf(string $line): int
    {
        return strlen($line) - strlen(ltrim($line, ' '));
    }

    private static function parseBlock(array $lines, int $index, int $baseIndent): array
    {
        $result = [];
        $isList = false;
        $count = count($lines);

        while ($index < $count) {
            $line = $lines[$index];
            $indent = self::indentOf($line);

            if ($indent < $baseIndent) {
                break;
            }

            if ($indent > $baseIndent) {
                $index++;
                continue;
            }

            $trimmed = trim($line);

            if (str_starts_with($trimmed, '- ')) {
                $isList = true;
                $itemContent = substr($trimmed, 2);

                if (str_contains($itemContent, ':') && !self::isInlineScalarOnly($itemContent)) {
                    $syntheticLine = str_repeat(' ', $indent + 2) . $itemContent;
                    $subLines = array_merge([$syntheticLine], self::collectChildren($lines, $index + 1, $indent));
                    [$item, $consumed] = self::parseBlock($subLines, 0, $indent + 2);
                    $result[] = $item;
                    $index += 1 + $consumed;
                } else {
                    $result[] = self::castScalar($itemContent);
                    $index++;
                }
                continue;
            }

            if ($trimmed === '-') {
                $isList = true;
                $childLines = self::collectChildren($lines, $index + 1, $indent);
                [$item, $consumed] = self::parseBlock($childLines, 0, $indent + 2);
                $result[] = $item;
                $index += 1 + $consumed;
                continue;
            }

            $colonPos = self::findColon($trimmed);
            if ($colonPos === false) {
                $index++;
                continue;
            }

            $key = trim(substr($trimmed, 0, $colonPos));
            $key = self::unquote($key);
            $value = trim(substr($trimmed, $colonPos + 1));

            $index++;

            if ($value === '') {
                $childLines = [];
                $childStart = $index;
                while ($index < $count && (trim($lines[$index]) === '' || self::indentOf($lines[$index]) > $indent)) {
                    $childLines[] = $lines[$index];
                    $index++;
                }

                if (empty($childLines)) {
                    $result[$key] = null;
                } else {
                    $childIndent = self::indentOf($childLines[0]);
                    [$childResult, ] = self::parseBlock($childLines, 0, $childIndent);
                    $result[$key] = $childResult;
                }
            } else {
                $result[$key] = self::castScalar($value);
            }
        }

        $consumedLines = $index;
        return [$isList ? array_values($result) : $result, $consumedLines];
    }

    private static function collectChildren(array $lines, int $start, int $parentIndent): array
    {
        $children = [];
        $count = count($lines);
        for ($i = $start; $i < $count; $i++) {
            if (self::indentOf($lines[$i]) <= $parentIndent) {
                break;
            }
            $children[] = $lines[$i];
        }
        return $children;
    }

    private static function isInlineScalarOnly(string $content): bool
    {
        if (preg_match('/^https?:\/\//', $content)) {
            return true;
        }
        return false;
    }

    private static function findColon(string $line): int|false
    {
        $inQuote = false;
        $quoteChar = '';
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            if ($inQuote) {
                if ($char === $quoteChar) {
                    $inQuote = false;
                }
                continue;
            }
            if ($char === '"' || $char === "'") {
                $inQuote = true;
                $quoteChar = $char;
                continue;
            }
            if ($char === ':' && (($i + 1 === strlen($line)) || $line[$i + 1] === ' ')) {
                return $i;
            }
        }
        return false;
    }

    private static function unquote(string $value): string
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }
        return $value;
    }

    private static function castScalar(string $value): mixed
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\[(.*)\]$/', $value, $m)) {
            $inner = trim($m[1]);
            if ($inner === '') {
                return [];
            }
            $parts = str_getcsv($inner, ',', "'");
            return array_map(fn ($p) => self::castScalar(trim($p)), $parts);
        }

        $unquoted = self::unquote($value);
        if ($unquoted !== $value) {
            return $unquoted;
        }

        return match (strtolower($value)) {
            'true', 'yes', 'on' => true,
            'false', 'no', 'off' => false,
            'null', '~' => null,
            default => self::castNumberOrString($value),
        };
    }

    private static function castNumberOrString(string $value): mixed
    {
        if (preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }
        if (preg_match('/^-?\d+\.\d+$/', $value)) {
            return (float) $value;
        }
        return $value;
    }
}
