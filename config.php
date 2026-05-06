<?php
if (!function_exists('app_load_env')) {
    function app_load_env($path)
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            $separator = strpos($line, '=');
            if ($separator === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separator));
            $value = trim(substr($line, $separator + 1));
            if ($key === '') {
                continue;
            }

            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = substr($value, -1);
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }

            $envValue = getenv($key);
            $_ENV[$key] = $envValue === false ? $value : $envValue;
            $_SERVER[$key] = $_ENV[$key];
        }
    }
}

if (!function_exists('app_env')) {
    function app_env($key, $default = '')
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

app_load_env(__DIR__ . '/.env');

if (!defined('YOUTUBE_API_KEY')) {
    define('YOUTUBE_API_KEY', app_env('YOUTUBE_API_KEY'));
}

if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', app_env('GEMINI_API_KEY'));
}

class config
{
    private static $pdo = null;

    public static function getConnexion()
    {
        if (!isset(self::$pdo)) {
            try {
                self::$pdo = new PDO(
                    'mysql:host=localhost;dbname=nutribudget;charset=utf8mb4',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (Exception $e) {
                die('Erreur: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>
