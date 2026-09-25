<?php
declare(strict_types=1);

namespace Wasla\Api;

require_once __DIR__ . '/../config/db.php';

use Wasla\Config\Database;
use Exception;

// Set API Response Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method Not Allowed. Use GET.'
    ]);
    exit;
}

try {
    $db = Database::getConnection();

    // Query active/pending offers and join with their respective communes for coordinate lookup
    $query = "
        SELECT 
            o.id,
            o.phone,
            o.produit,
            o.quantite_kg,
            o.qualite,
            o.latitude AS offer_lat,
            o.longitude AS offer_lon,
            o.transcription_darija,
            o.status,
            o.created_at,
            c.id AS commune_id,
            c.nom AS commune_nom,
            c.latitude AS commune_lat,
            c.longitude AS commune_lon
        FROM offers o
        INNER JOIN communes c ON o.commune_id = c.id
        ORDER BY o.created_at DESC
    ";

    $stmt = $db->query($query);
    $offers = $stmt->fetchAll();

    // Format the response payload elegantly
    $formattedOffers = [];
    foreach ($offers as $row) {
        $formattedOffers[] = [
            'id' => (int)$row['id'],
            'phone' => $row['phone'],
            'produit' => $row['produit'],
            'quantite_kg' => (float)$row['quantite_kg'],
            'qualite' => $row['qualite'],
            'location' => [
                'latitude' => (float)$row['offer_lat'],
                'longitude' => (float)$row['offer_lon']
            ],
            'commune' => [
                'id' => (int)$row['commune_id'],
                'nom' => $row['commune_nom'],
                'latitude' => (float)$row['commune_lat'],
                'longitude' => (float)$row['commune_lon']
            ],
            'transcription_darija' => $row['transcription_darija'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($formattedOffers),
        'offers' => $formattedOffers
    ]);

} catch (Exception $e) {
    // If the database has not been initialized or table offers does not exist, return a friendly mock/empty payload for onboarding
    if (strpos($e->getMessage(), 'doesn\'t exist') !== false || strpos($e->getMessage(), 'not found') !== false) {
        echo json_encode([
            'success' => true,
            'count' => 0,
            'offers' => [],
            'warning' => 'Database tables have not been fully initialized yet. Run schema.sql.'
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server Error: ' . $e->getMessage()
    ]);
}
