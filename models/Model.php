<?php
declare(strict_types=1);

/**
 * Base model sederhana. Semua model memakai koneksi PDO tunggal dari Database.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::conn();
    }

    protected static function pdo(): PDO
    {
        return Database::conn();
    }

    protected static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    protected static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected static function fetchValue(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Decode JSON pada field item (features, specs, attributes, dll).
     */
    protected static function decodeJsonFields(array $row, array $fields): array
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = safe_json_decode($row[$field], []);
            }
        }
        return $row;
    }
}
