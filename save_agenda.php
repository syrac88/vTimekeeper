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
  echo json_encode(["error" => "Database connection failed"]);
  exit;
}

$id = $_GET['id'] ?? '';
$key = $_GET['key'] ?? '';

if (!$id || !$key) {
  http_response_code(400);
  echo json_encode(["error" => "Missing id or editor key"]);
  exit;
}

// 🔹 Body auslesen
$body = file_get_contents("php://input");
$data = json_decode($body, true);

if (!$data) {
  http_response_code(400);
  echo json_encode(["error" => "Missing or invalid JSON body"]);
  exit;
}

// 🔹 Agenda + Secret prüfen
$stmt = $conn->prepare("SELECT id FROM agendas WHERE id=? AND secret=?");
$stmt->bind_param("ss", $id, $key);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  http_response_code(403);
  echo json_encode(["error" => "Invalid editor key — Access denied."]);
  exit;
}

$stmt->close();

$title = $data['title'] ?? 'Untitled';
$start = $data['startTime'] ?? date('Y-m-d H:i:s');
$slots = json_encode($data['slots'], JSON_UNESCAPED_UNICODE);

// 🔹 Update
$update = $conn->prepare("UPDATE agendas SET title=?, start_time=?, slots=? WHERE id=?");
$update->bind_param("ssss", $title, $start, $slots, $id);

if ($update->execute()) {
  echo json_encode(["success" => true, "message" => "Agenda saved successfully"]);
} else {
  http_response_code(500);
  echo json_encode(["error" => "Database update failed"]);
}

$update->close();
$conn->close();
?>
