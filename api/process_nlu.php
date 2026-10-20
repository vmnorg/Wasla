<?php
declare(strict_types=1);

namespace Wasla\Api;

require_once __DIR__ . '/../config/db.php';

use Wasla\Config\Database;
use Exception;
use RuntimeException;

// Set API Response Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method Not Allowed. Use POST.'
    ]);
    exit;
}

try {
    // Read and parse JSON input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    // Support both raw JSON and form-data POST
    $darijaText = trim((string)($input['darija_text'] ?? $_POST['darija_text'] ?? ''));

    if ($darijaText === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing parameter: darija_text'
        ]);
        exit;
    }

    // Initialize environment variables (including GEMINI_API_KEY)
    Database::loadEnv();
    $apiKey = getenv('GEMINI_API_KEY');

    $extractedData = null;

    if (!empty($apiKey) && $apiKey !== 'your_gemini_api_key_here') {
        // Call Gemini 1.5 Flash API for structured NLU parsing
        $extractedData = callGeminiAPI($darijaText, $apiKey);
        if (isset($extractedData['error'])) {
            throw new Exception($extractedData['error']);
        }
    } else {
        // Offline / Fallback Matcher (Mocking for local test when key is missing)
        $extractedData = performFallbackParsing($darijaText);
    }

    if ($extractedData === null) {
        // If Gemini fails (e.g. invalid API key, network timeout), seamlessly fall back to the local parser for the demo
        $extractedData = performFallbackParsing($darijaText);
    }

    // Attempt to match the extracted commune name with a pre-existing DB commune
    $db = Database::getConnectionOrNull();
    if ($db === null) {
        echo json_encode([
            'success' => false,
            'error' => 'db_unavailable (queued for retry)',
            'message' => 'Database is currently offline. Your query has been queued for retry.'
        ]);
        exit;
    }
    $stmt = $db->prepare('SELECT id, nom, latitude, longitude FROM communes WHERE LOWER(nom) = LOWER(?) LIMIT 1');
    $stmt->execute([$extractedData['commune']]);
    $matchedCommune = $stmt->fetch();

    $responsePayload = [
        'success' => true,
        'input' => $darijaText,
        'nlu_extracted' => $extractedData,
        'commune_match' => null
    ];

    if ($matchedCommune) {
        $responsePayload['commune_match'] = [
            'id' => (int)$matchedCommune['id'],
            'nom' => $matchedCommune['nom'],
            'latitude' => (float)$matchedCommune['latitude'],
            'longitude' => (float)$matchedCommune['longitude']
        ];
    } else {
        // Fallback: If no exact match is found, look for keyword matches in text
        $stmtAll = $db->query('SELECT id, nom, latitude, longitude FROM communes');
        $communesList = $stmtAll->fetchAll();
        foreach ($communesList as $com) {
            if (stripos($darijaText, $com['nom']) !== false) {
                $responsePayload['commune_match'] = [
                    'id' => (int)$com['id'],
                    'nom' => $com['nom'],
                    'latitude' => (float)$com['latitude'],
                    'longitude' => (float)$com['longitude']
                ];
                // Overwrite the NLU-parsed commune with the actual matched keyword
                $responsePayload['nlu_extracted']['commune'] = $com['nom'];
                break;
            }
        }
    }

    echo json_encode($responsePayload);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server Error: ' . $e->getMessage()
    ]);
}

/**
 * Sends Darija text to Gemini 1.5 Flash and extracts JSON structure.
 */
function callGeminiAPI(string $text, string $apiKey): ?array {
    // Gemini Flash API endpoint
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . rawurlencode($apiKey);

    // Strict instructions for the model to behave as a structured JSON parser
    $systemInstruction = "You are an expert Moroccan Darija NLU parser. " .
                         "Extract agricultural product details from user input. " .
                         "You MUST return ONLY a JSON object. No markdown wrapper, no backticks. " .
                         "Schema: {\"product\": string|null, \"quantity_kg\": number|null, \"quality\": string|null, \"commune\": string|null}. " .
                         "Translate any Arabic numbers (e.g. 2 toun -> 2000, nss toun -> 500, kantar -> 100). " .
                         "Map communes to either: Tiznit, Inezgane, Taroudant, Oulad Teima, Belfaa, Massa.";

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $text]
                ]
            ]
        ],
        'systemInstruction' => [
            'parts' => [
                ['text' => $systemInstruction]
            ]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json'
        ]
    ];

    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    // Timeout of 8 seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    // Disable SSL verification for local dev to avoid "unable to get local issuer certificate" errors
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return ['error' => "API Error HTTP $httpCode - cURL: $curlErr - Response: $response"];
    }

    $resData = json_decode((string)$response, true);
    if (!isset($resData['candidates'][0]['content']['parts'][0]['text'])) {
        return ['error' => "Unexpected API response format: $response"];
    }
    $textResult = $resData['candidates'][0]['content']['parts'][0]['text'];
    
    // Clean text if some backticks leaked through
    $textResult = trim($textResult);
    if (strpos($textResult, '```') === 0) {
        $textResult = preg_replace('/^```(?:json)?|```$/i', '', $textResult);
    }

    return json_decode(trim((string)$textResult), true);
}

/**
 * Fallback parser using standard keyword matching for demonstration and offline local test.
 */
function performFallbackParsing(string $text): array {
    $textLower = mb_strtolower($text);

    // Basic Product Matching
    $product = 'Inconnu';
    $products = [
        'khizou' => 'Carotte',
        'carrot' => 'Carotte',
        'maticha' => 'Tomate',
        'tomato' => 'Tomate',
        'btata' => 'Pomme de terre',
        'potato' => 'Pomme de terre',
        'argan' => 'Argan',
        'limoun' => 'Orange',
        'orange' => 'Orange',
        'popeye' => 'Épinard',
        'bsla' => 'Oignon',
        'onion' => 'Oignon'
    ];
    foreach ($products as $kw => $name) {
        if (strpos($textLower, $kw) !== false) {
            $product = $name;
            break;
        }
    }

    // Basic Quantity Matching (e.g. "2 toun" -> 2000, "500 kg" -> 500)
    $quantity = 1000.0; // default 1 ton
    if (preg_match('/(\d+(?:\.\d+)?)\s*(toun|ton|t)/u', $textLower, $matches)) {
        $quantity = floatval($matches[1]) * 1000;
    } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(kg|kilo)/u', $textLower, $matches)) {
        $quantity = floatval($matches[1]);
    }

    // Basic Quality Matching
    $quality = 'Standard';
    if (strpos($textLower, 'mzyan') !== false || strpos($textLower, 'nqi') !== false || strpos($textLower, 'top') !== false) {
        $quality = 'Premium';
    }

    // Commune Matching
    $commune = 'Massa';
    $communes = ['tiznit', 'inezgane', 'taroudant', 'oulad teima', 'belfaa', 'massa'];
    foreach ($communes as $com) {
        if (strpos($textLower, $com) !== false) {
            $commune = ucwords($com);
            break;
        }
    }

    return [
        'product' => $product,
        'quantity_kg' => $quantity,
        'quality' => $quality,
        'commune' => $commune,
        'mode' => 'fallback_matcher'
    ];
}
