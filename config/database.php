<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

class Database
{
    private static ?PDO $connection = null;

    private string $host;
    private string $port;
    private string $dbName;
    private string $username;
    private string $password;

    public function __construct()
    {
        $inContainer = is_file('/.dockerenv');

        $this->host = $inContainer
            ? (env_os('DB_HOST') ?? env_non_empty('DB_DOCKER_HOST') ?? (string) env('DB_HOST'))
            : (string) env('DB_HOST');
        $this->port = (string) env('DB_PORT');
        $this->dbName = (string) env('DB_NAME');
        $this->username = (string) env('DB_USER');
        $this->password = (string) env('DB_PASSWORD');
    }

    public static function conn(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = new self();
        $dsn = 'mysql:host=' . $config->host . ';port=' . $config->port . ';dbname=' . $config->dbName . ';charset=utf8mb4';

        $initCommandAttribute = class_exists(\Pdo\Mysql::class)
            ? \Pdo\Mysql::ATTR_INIT_COMMAND
            : PDO::MYSQL_ATTR_INIT_COMMAND;

        try {
            self::$connection = new PDO($dsn, $config->username, $config->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                $initCommandAttribute => "SET time_zone = '+07:00'",
            ]);
        } catch (PDOException $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());
            if (PHP_SAPI !== 'cli' && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Koneksi database gagal. Pastikan container MySQL berjalan dan database rentify sudah dibuat.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            http_response_code(500);
            echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Database belum siap</title>';
            echo '<div style="font-family:system-ui;max-width:640px;margin:12vh auto;padding:24px;line-height:1.6">';
            echo '<h1 style="color:#032517">Database belum siap</h1>';
            echo '<p>Rentify tidak bisa terhubung ke MySQL. Jalankan container <strong>mysql-server</strong>, lalu buka <code>install.php</code> sekali untuk menyiapkan database <strong>rentify</strong>.</p>';
            echo '<p style="color:#5e7066;font-size:14px">Detail: ' . e($exception->getMessage()) . '</p>';
            echo '</div></html>';
            exit;
        }

        return self::$connection;
    }

    public function getConnection(): PDO
    {
        return self::conn();
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
