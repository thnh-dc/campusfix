<?php
require_once __DIR__ . '/../config/functions.php';

class BaseModel {
    protected string $table;
    protected string $pk;

    public function find(int $id): ?array {
        $st = db()->prepare("SELECT * FROM {$this->table} WHERE {$this->pk} = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function all(string $orderBy = ''): array {
        $sql = "SELECT * FROM {$this->table}" . ($orderBy ? " ORDER BY $orderBy" : '');
        return db()->query($sql)->fetchAll();
    }

    public function delete(int $id): bool {
        $st = db()->prepare("DELETE FROM {$this->table} WHERE {$this->pk} = ?");
        return $st->execute([$id]);
    }
}
