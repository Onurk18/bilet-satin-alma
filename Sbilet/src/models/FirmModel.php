<?php
// src/models/FirmModel.php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';

final class FirmModel {
    public static function all(): array {
        return db()->query("SELECT id,name,logo_path,created_at FROM Bus_Company ORDER BY name")->fetchAll();
    }
    public static function create(string $id, string $name, ?string $logo): void {
        $st = db()->prepare("INSERT INTO Bus_Company (id,name,logo_path,created_at) VALUES (:i,:n,:l,datetime('now'))");
        $st->execute([':i'=>$id, ':n'=>$name, ':l'=>$logo]);
    }
    public static function update(string $id, string $name, ?string $logo): void {
        $st = db()->prepare("UPDATE Bus_Company SET name=:n, logo_path=:l WHERE id=:i");
        $st->execute([':i'=>$id, ':n'=>$name, ':l'=>$logo]);
    }
    public static function delete(string $id): void {
        $st = db()->prepare("DELETE FROM Bus_Company WHERE id=:i");
        $st->execute([':i'=>$id]);
    }
}
