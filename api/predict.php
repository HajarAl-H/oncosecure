<?php
// 📌 Local AI Proxy API — PHP <-> Flask
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Validate payload
if (!$data || empty($data['report'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing report']);
    exit;
}

// Prepare Flask request
$ch = curl_init('http://127.0.0.1:5000/predict');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Handle Flask errors
if ($error) {
    http_response_code(500);
    echo json_encode(['error' => 'AI service unreachable', 'details' => $error]);
    exit;
}

// Decode Flask response
$json = json_decode($response, true);

// Validate expected AI response structure
if (!is_array($json) || !isset($json['result'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid AI response', 'raw' => $response]);
    exit;
}

// ✅ Return AI result to frontend / doctor page
echo json_encode([
    'result' => $json['result'],
    'confidence' => $json['confidence'] ?? null,
    'recommendation' => $json['recommendation'] ?? null
]);
