<?php

namespace App;

class Database
{
    private static $instance = null;
    private $pdo;
    private $dbPath;

    private function __construct()
    {
        $this->dbPath = __DIR__ . '/../database/happiness.db';
        $this->connect();
        $this->initializeSchema();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        try {
            $this->pdo = new \PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    private function initializeSchema(): void
    {
        $schemaPath = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaPath)) {
            $schema = file_get_contents($schemaPath);
            $this->pdo->exec($schema);
        }
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $allParams = [];
        
        // Build SET clause with unique parameter names
        $paramIndex = 0;
        foreach ($data as $column => $value) {
            $paramName = "set_param_{$paramIndex}";
            $setParts[] = "{$column} = :{$paramName}";
            $allParams[$paramName] = $value;
            $paramIndex++;
        }
        $setClause = implode(', ', $setParts);
        
        // Add WHERE parameters with unique names
        if (!empty($whereParams)) {
            $whereParamNames = [];
            for ($i = 0; $i < count($whereParams); $i++) {
                $paramName = "where_param_{$i}";
                $whereParamNames[] = ":{$paramName}";
                $allParams[$paramName] = $whereParams[$i];
            }
            // Replace ? with named parameters
            $where = preg_replace_callback('/\?/', function($matches) use (&$whereParamNames) {
                return array_shift($whereParamNames);
            }, $where);
        }
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $this->query($sql, $allParams);
        
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }

    public function generateUniqueId(string $table, string $column, int $length = 16): string
    {
        do {
            $id = bin2hex(random_bytes($length / 2));
            $exists = $this->fetchOne(
                "SELECT 1 FROM {$table} WHERE {$column} = ?",
                [$id]
            );
        } while ($exists);
        
        return $id;
    }
}