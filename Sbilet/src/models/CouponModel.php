<?php
// src/models/CouponModel.php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';

final class CouponModel {
    public static function all(): array {
        $sql = "SELECT c.id, c.code, c.discount, c.company_id, c.usage_limit, c.expire_date, c.created_at,
                       bc.name AS company_name
                FROM Coupons c
                LEFT JOIN Bus_Company bc ON bc.id = c.company_id
                ORDER BY c.created_at DESC";
        return db()->query($sql)->fetchAll();
    }
    public static function create(string $id, string $code, int $discount, ?string $companyId, int $limit, string $expire): void {
        $st = db()->prepare("INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date, created_at)
                             VALUES (:i,:code,:d,:cid,:lim,:exp,datetime('now'))");
        $st->execute([':i'=>$id, ':code'=>$code, ':d'=>$discount, ':cid'=>$companyId, ':lim'=>$limit, ':exp'=>$expire]);
    }
    public static function update(string $id, string $code, int $discount, ?string $companyId, int $limit, string $expire): void {
        $st = db()->prepare("UPDATE Coupons SET code=:code, discount=:d, company_id=:cid, usage_limit=:lim, expire_date=:exp WHERE id=:i");
        $st->execute([':i'=>$id, ':code'=>$code, ':d'=>$discount, ':cid'=>$companyId, ':lim'=>$limit, ':exp'=>$expire]);
    }
    public static function delete(string $id): void {
        $st = db()->prepare("DELETE FROM Coupons WHERE id=:i");
        $st->execute([':i'=>$id]);
    }
}
