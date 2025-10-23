<?php
declare(strict_types=1);
require __DIR__ . '/../../src/db.php';

header('Content-Type: application/json');

$pdo = db();

$sql = "
    SELECT DISTINCT departure_city AS city FROM Trips
    UNION
    SELECT DISTINCT destination_city AS city FROM Trips
    ORDER BY city
";

$stmt = $pdo->query($sql);
$cities = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($cities, JSON_UNESCAPED_UNICODE);