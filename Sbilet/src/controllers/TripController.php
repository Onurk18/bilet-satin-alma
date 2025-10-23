<?php
// src/controllers/TripController.php
declare(strict_types=1);

require_once __DIR__ . '/../models/TripModel.php';

final class TripController
{
    public static function searchFromQuery(array $query): array
    {
        // Basit normalizasyon
        $filters = [
            'dep'  => isset($query['dep'])  ? trim((string)$query['dep'])  : '',
            'arr'  => isset($query['arr'])  ? trim((string)$query['arr'])  : '',
            'date' => isset($query['date']) ? trim((string)$query['date']) : '',
        ];

        // tarih formatına hafif kontrol (YYYY-MM-DD)
        if ($filters['date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date'])) {
            $filters['date'] = '';
        }

        return TripModel::search($filters);
    }
    public static function getDetail(string $id): ?array
    {
        return TripModel::findWithSeats($id);
    }

}
