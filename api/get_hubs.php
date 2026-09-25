<?php
require_once __DIR__ . '/../config/db.php';
use Wasla\Config\Database;

header('Content-Type: application/json');

try {
    $db = Database::getConnection();
    $stmt = $db->query('SELECT id, nom as name, latitude as lat, longitude as lon FROM communes');
    $communes = $stmt->fetchAll();

    // Mapping communes to coopHubs to keep the UI working
    $coopHubs = [];
    foreach ($communes as $commune) {
        $coopHubs[] = [
            'id' => 'c' . $commune['id'],
            'name' => 'Coopérative ' . $commune['name'],
            'lat' => $commune['lat'],
            'lon' => $commune['lon'],
            'members' => rand(50, 200),
            'products' => 'Produits locaux'
        ];
    }

    echo json_encode([
        'success' => true,
        'communesData' => $communes,
        'coopHubs' => $coopHubs
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
