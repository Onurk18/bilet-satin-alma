<?php
// src/models/CompanyTripModel.php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';

final class CompanyTripModel {
    

    public static function getByCompany(string $companyId): array {
        $sql = "
            SELECT 
                t.id, t.departure_city, t.destination_city,
                t.departure_time, t.arrival_time, t.price, t.capacity,
                t.created_at,
                (t.capacity - COALESCE(sold.cnt, 0)) AS available_seats
            FROM Trips t
            LEFT JOIN (
                SELECT ti.trip_id, COUNT(*) AS cnt
                FROM Booked_Seats bs
                JOIN Tickets ti ON ti.id = bs.ticket_id
                WHERE ti.status = 'ACTIVE'
                GROUP BY ti.trip_id
            ) sold ON sold.trip_id = t.id
            WHERE t.company_id = :cid
            ORDER BY t.departure_time DESC
        ";
        
        $stmt = db()->prepare($sql);
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll();
    }
    
    
    public static function create(
        string $id,
        string $companyId,
        string $departureCity,
        string $destinationCity,
        string $departureTime,
        string $arrivalTime,
        int $price,
        int $capacity
    ): void {
        $stmt = db()->prepare("
            INSERT INTO Trips (
                id, company_id, departure_city, destination_city,
                departure_time, arrival_time, price, capacity, created_at
            ) VALUES (
                :id, :cid, :dep, :dest, :dtime, :atime, :price, :cap, datetime('now')
            )
        ");
        
        $stmt->execute([
            ':id'    => $id,
            ':cid'   => $companyId,
            ':dep'   => $departureCity,
            ':dest'  => $destinationCity,
            ':dtime' => $departureTime,
            ':atime' => $arrivalTime,
            ':price' => $price,
            ':cap'   => $capacity,
        ]);
    }
    
    
    public static function update(
        string $id,
        string $departureCity,
        string $destinationCity,
        string $departureTime,
        string $arrivalTime,
        int $price,
        int $capacity
    ): void {
        $stmt = db()->prepare("
            UPDATE Trips SET
                departure_city = :dep,
                destination_city = :dest,
                departure_time = :dtime,
                arrival_time = :atime,
                price = :price,
                capacity = :cap
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id'    => $id,
            ':dep'   => $departureCity,
            ':dest'  => $destinationCity,
            ':dtime' => $departureTime,
            ':atime' => $arrivalTime,
            ':price' => $price,
            ':cap'   => $capacity,
        ]);
    }
    
    
    public static function delete(string $id): bool {
        try {
            $stmt = db()->prepare("DELETE FROM Trips WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
   
    public static function findById(string $id): ?array {
        $stmt = db()->prepare("
            SELECT id, company_id, departure_city, destination_city,
                   departure_time, arrival_time, price, capacity
            FROM Trips
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}