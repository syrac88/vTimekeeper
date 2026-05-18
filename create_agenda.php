<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$user = "USER303858_db";
$pass = "74fCte!HqTptVLm";
$dbname = "db_303858_8";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["error" => "DB connection failed"]);
  exit;
}

$body = file_get_contents("php://input");
$data = json_decode($body, true);

if (!$data) {
  http_response_code(400);
  echo json_encode(["error" => "Missing or invalid body"]);
  exit;
}

$title = $data['title'] ?? 'Untitled Agenda';
$start = $data['startTime'] ?? date('Y-m-d H:i:s');
$slots = json_encode($data['slots'] ?? [], JSON_UNESCAPED_UNICODE);

function randomToken($len = 12) {
  return substr(bin2hex(random_bytes($len)), 0, $len);
}

$id = "agenda_" . randomToken(6);
$secret = randomToken(16);

$stmt = $conn->prepare("INSERT INTO agendas (id, secret, title, start_time, slots) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $id, $secret, $title, $start, $slots);

if ($stmt->execute()) {
  echo json_encode([
    "success" => true,
    "id" => $id,
    "secret" => $secret,
    "viewUrl" => "https://syrac.lima-city.de/vtimekeeper/timekeeper.html?id=$id",
    "adminUrl" => "https://syrac.lima-city.de/vtimekeeper/admin.html?id=$id&key=$secret"
  ], JSON_PRETTY_PRINT);
} else {
  http_response_code(500);
  echo json_encode(["error" => "Insert failed"]);
}

$stmt->close();
$conn->close();
?>
