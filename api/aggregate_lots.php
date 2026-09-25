<?php
declare(strict_types=1);

namespace Wasla\Api;

require_once __DIR__ . '/../config/db.php';

use Wasla\Config\Database;
use Exception;
use PDO;

// Set API Response Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = Database::getConnection();

    // Read input parameters (e.g., target_min_kg for pooling threshold)
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?: [];
    
    // Default threshold for pooling is 1000kg (1 Ton) to make it easy to trigger in prototypes, fully customizable
    $minTonnageThreshold = (float)($input['min_threshold_kg'] ?? $_REQUEST['min_threshold_kg'] ?? 1000.0);

    // 1. Fetch all pending offers
    $query = "
        SELECT 
            o.id,
            o.produit,
            o.quantite_kg,
            o.latitude,
            o.longitude,
            o.commune_id,
            c.nom AS commune_nom
        FROM offers o
        INNER JOIN communes c ON o.commune_id = c.id
        WHERE o.status = 'pending'
    ";
    
    $stmt = $db->query($query);
    $pendingOffers = $stmt->fetchAll();

    if (count($pendingOffers) === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No pending offers available for aggregation.',
            'lots_created' => 0,
            'lots' => []
        ]);
        exit;
    }

    // 2. Group pending offers by Product
    $groupsByProduct = [];
    foreach ($pendingOffers as $offer) {
        $productName = $offer['produit'];
        if (!isset($groupsByProduct[$productName])) {
            $groupsByProduct[$productName] = [];
        }
        $groupsByProduct[$productName][] = $offer;
    }

    $createdLots = [];

    // 3. Process aggregation inside a secure Transaction
    $db->beginTransaction();

    foreach ($groupsByProduct as $productName => $offers) {
        // Group offers of this product by commune to ensure spatial proximity
        $byCommune = [];
        foreach ($offers as $offer) {
            $communeId = (int)$offer['commune_id'];
            if (!isset($byCommune[$communeId])) {
                $byCommune[$communeId] = [];
            }
            $byCommune[$communeId][] = $offer;
        }

        foreach ($byCommune as $communeId => $communeOffers) {
            // Calculate total weight of offers for this product in this commune
            $totalWeight = 0.0;
            foreach ($communeOffers as $offer) {
                $totalWeight += (float)$offer['quantite_kg'];
            }

            // Check if weight meets the threshold for pooling
            if ($totalWeight >= $minTonnageThreshold) {
                // Calculate spatial Centroid (average latitude and longitude)
                $sumLat = 0.0;
                $sumLon = 0.0;
                $offerIds = [];

                foreach ($communeOffers as $offer) {
                    $sumLat += (float)$offer['latitude'];
                    $sumLon += (float)$offer['longitude'];
                    $offerIds[] = (int)$offer['id'];
                }

                $count = count($communeOffers);
                $centroidLat = $sumLat / $count;
                $centroidLon = $sumLon / $count;

                // CO2 avoided calculation:
                // Pooling multiple offers into a single trip avoids redundant transport routes.
                // Estimating 0.12 kg of CO2 saved per kg of crop due to efficient direct routing
                $co2Avoided = $totalWeight * 0.12;

                // Create the Lot
                $stmtInsertLot = $db->prepare("
                    INSERT INTO lots (tonnage_total_kg, centroid_lat, centroid_lon, co2_evite_kg, status)
                    VALUES (?, ?, ?, ?, 'open')
                ");
                $stmtInsertLot->execute([$totalWeight, $centroidLat, $centroidLon, $co2Avoided]);
                $lotId = (int)$db->lastInsertId();

                // Connect offers to Lot via Pivot and Update Offer status to 'assigned'
                $stmtInsertPivot = $db->prepare("INSERT INTO lot_offers (lot_id, offer_id) VALUES (?, ?)");
                $stmtUpdateOffer = $db->prepare("UPDATE offers SET status = 'assigned' WHERE id = ?");

                foreach ($offerIds as $id) {
                    $stmtInsertPivot->execute([$lotId, $id]);
                    $stmtUpdateOffer->execute([$id]);
                }

                $createdLots[] = [
                    'lot_id' => $lotId,
                    'product' => $productName,
                    'commune_id' => $communeId,
                    'commune_nom' => $communeOffers[0]['commune_nom'],
                    'total_weight_kg' => $totalWeight,
                    'co2_saved_kg' => $co2Avoided,
                    'centroid' => [
                        'latitude' => $centroidLat,
                        'longitude' => $centroidLon
                    ],
                    'offers_pooled_count' => $count,
                    'offer_ids' => $offerIds
                ];
            }
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Aggregation compile successful.',
        'lots_created' => count($createdLots),
        'lots' => $createdLots
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    // Friendly error fallback if DB is not initialized
    if (strpos($e->getMessage(), 'doesn\'t exist') !== false || strpos($e->getMessage(), 'not found') !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Database tables have not been fully initialized yet. Run schema.sql.',
            'lots_created' => 0,
            'lots' => []
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server Error: ' . $e->getMessage()
    ]);
}
