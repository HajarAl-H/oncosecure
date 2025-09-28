<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method']); exit; }
$data = $_POST['data'] ?? null; // or application/json body
if (!$data) {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
}
$ch = curl_init('http://127.0.0.1:5000/predict'); // adjust flask URL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);
if ($err) echo json_encode(['error'=>'AI unreachable']);
else echo $res;
?>