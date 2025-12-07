<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$doctor_id = intval($_GET['doctor_id'] ?? 0);
$date_input = trim($_GET['date'] ?? '');

if (!$doctor_id || !$date_input) {
    http_response_code(400);
    echo json_encode(['error' => 'Doctor and date are required.']);
    exit;
}

// Validate date format (YYYY-MM-DD)
$date = DateTime::createFromFormat('Y-m-d', $date_input);
if (!$date || $date->format('Y-m-d') !== $date_input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date provided.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(appointment_date, '%H:%i') AS slot
        FROM appointments
        WHERE doctor_id = ? AND DATE(appointment_date) = ? AND status = 'approved'");
    $stmt->execute([$doctor_id, $date_input]);
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load slots.']);
    exit;
}

$available = [];
for ($hour = 8; $hour <= 17; $hour++) {
    foreach ([0, 30] as $minute) {
        $slot = sprintf('%02d:%02d', $hour, $minute);
        if (!in_array($slot, $bookedSlots, true)) {
            $available[] = $slot;
        }
    }
}

echo json_encode(['slots' => $available]);
