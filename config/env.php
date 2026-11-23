<?php
declare(strict_types=1);

/**
 * Loader .env ringan (zero-dependency) untuk Rentify.
 * Prioritas nilai: OS/Docker environment variable > file .env > fallback default.
 */

if (!function_exists('rentify_load_env')) {
    function rentify_load_env(?string $path = null): void
    {
        static $loaded = [];
        $file = $path ?? (dirname(__DIR__) . '/.env');
        if (isset($loaded[$file]) || !is_file($file) || !is_readable($file)) {
            return;
        }
        $loaded[$file] = true;

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        if (!isset($GLOBALS['_RENTIFY_ENV_FILE_KEYS'])) {
            $GLOBALS['_RENTIFY_ENV_FILE_KEYS'] = [];
        }

        foreach ($lines as $index => $line) {
            if ($index === 0) {
                $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
            }
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = trim(substr($trimmed, 7));
            }
            $pos = strpos($trimmed, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($trimmed, 0, $pos));
            if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            $rawValue = trim(substr($trimmed, $pos + 1));
            $value = rentify_parse_env_value($rawValue);

            // Jangan timpa environment variable asli dari OS/Docker
            if (getenv($key) !== false || array_key_exists($key, $_ENV)) {
                continue;
            }

            $GLOBALS['_RENTIFY_ENV_FILE_KEYS'][$key] = true;
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    function rentify_parse_env_value(string $rawValue): string
    {
        if ($rawValue === '') {
            return '';
        }

        $first = $rawValue[0];
        if ($first === '"' || $first === "'") {
            $escaped = false;
            $len = strlen($rawValue);
            $result = '';
            for ($i = 1; $i < $len; $i++) {
                $ch = $rawValue[$i];
                if ($first === '"' && !$escaped && $ch === '\\') {
                    $escaped = true;
                    continue;
                }
                if (!$escaped && $ch === $first) {
                    return $result;
                }
                if ($escaped) {
                    $result .= match ($ch) {
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        '"' => '"',
                        '\\' => '\\',
                        default => '\\' . $ch,
                    };
                    $escaped = false;
                } else {
                    $result .= $ch;
                }
            }
            return $result;
        }

        $commentPos = strpos($rawValue, ' #');
        if ($commentPos !== false) {
            $rawValue = substr($rawValue, 0, $commentPos);
        }

        return trim($rawValue);
    }

    function env(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return (string) $val;
        }
        return $default;
    }

    function env_non_empty(string $key): ?string
    {
        $val = env($key);
        return ($val !== null && $val !== '') ? $val : null;
    }

    /**
     * Ambil nilai hanya jika diset dari OS/Docker (bukan dari file .env).
     */
    function env_os(string $key): ?string
    {
        if (!empty($GLOBALS['_RENTIFY_ENV_FILE_KEYS'][$key])) {
            return null;
        }
        $val = getenv($key);
        return ($val !== false && $val !== '') ? (string) $val : null;
    }
}

rentify_load_env();
