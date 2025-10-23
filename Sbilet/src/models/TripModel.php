<?php
// src/models/TripModel.php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

final class TripModel
{
    /**
     * $filters = [
     *   'dep'  => 'İstan',   // kalkış şehir (kısmi)
     *   'arr'  => 'Anka',    // varış şehir (kısmi)
     *   'date' => '2025-10-21' // YYYY-MM-DD
     * ]
     */
    public static function search(array $filters = []): array
    {
        $pdo = db();

        $sql = "
            SELECT
              t.id,
              t.company_id,
              t.departure_city,
              t.destination_city,
              t.departure_time,
              t.arrival_time,
              t.price       AS price_tl,
              t.capacity,
              bc.name       AS company_name,
              (t.capacity - COALESCE(sold.cnt, 0)) AS available_seats
            FROM Trips t
            JOIN Bus_Company bc ON bc.id = t.company_id
            LEFT JOIN (
              SELECT ti.trip_id, COUNT(*) AS cnt
              FROM Booked_Seats bs
              JOIN Tickets ti ON ti.id = bs.ticket_id
              WHERE ti.status = 'ACTIVE'
              GROUP BY ti.trip_id
            ) sold ON sold.trip_id = t.id
            WHERE datetime(t.departure_time) > datetime('now')
        ";

        $params = [];

        // Serbest metin: kısmi ve case-insensitive (LIKE)
        if (!empty($filters['dep'])) {
            $sql .= " AND LOWER(t.departure_city) LIKE LOWER(:dep) ";
            $params[':dep'] = '%' . trim((string)$filters['dep']) . '%';
        }
        if (!empty($filters['arr'])) {
            $sql .= " AND LOWER(t.destination_city) LIKE LOWER(:arr) ";
            $params[':arr'] = '%' . trim((string)$filters['arr']) . '%';
        }

        // Gün bazlı tarih fitresi: o günün tüm kalkışları
        if (!empty($filters['date'])) {
            $sql .= " AND date(t.departure_time) = :d ";
            $params[':d'] = (string)$filters['date'];
        }

        // Varsayılan sıralama: kalkış saati
        $sql .= " ORDER BY t.departure_time ASC ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findWithSeats(string $tripId): ?array
    {
        $pdo = db();

        // Sefer ve firma bilgisi + GEÇMİŞ KONTROL
        $stmt = $pdo->prepare("
            SELECT
              t.id, t.company_id, t.departure_city, t.destination_city,
              t.departure_time, t.arrival_time, t.price AS price_tl, t.capacity,
              bc.name AS company_name,
              CASE 
                WHEN datetime(t.departure_time) <= datetime('now') THEN 1 
                ELSE 0 
              END AS is_past
            FROM Trips t
            JOIN Bus_Company bc ON bc.id = t.company_id
            WHERE t.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $tripId]);
        $trip = $stmt->fetch();
        if (!$trip) return null;

        // Dolu koltuklar (sadece ACTIVE biletler)
        $s = $pdo->prepare("
            SELECT bs.seat_number
            FROM Booked_Seats bs
            JOIN Tickets ti ON ti.id = bs.ticket_id
            WHERE ti.trip_id = :id AND ti.status = 'ACTIVE'
            ORDER BY bs.seat_number
        ");
        $s->execute([':id' => $tripId]);
        $booked = array_map(fn($r) => (int)$r['seat_number'], $s->fetchAll());

        $trip['booked_seats'] = $booked;
        $trip['available_seats'] = (int)$trip['capacity'] - count($booked);
        $trip['is_past'] = (bool)$trip['is_past'];

        return $trip;
    }
}