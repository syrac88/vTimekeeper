<?php
header('Content-Type: application/json; charset=utf-8');

// 🔹 Database connection settings
$host = "localhost";
$user = "USER303858_db";
$pass = "74fCte!HqTptVLm";
$dbname = "db_303858_8";

// 🔹 Connect to database
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["error" => "DB connection failed", "details" => $conn->connect_error]);
  exit;
}

// 🔹 Get ID from URL (?id=...)
$id = $_GET['id'] ?? '';
if (!$id) {
  http_response_code(400);
  echo json_encode(["error" => "Missing id parameter"]);
  exit;
}

// 🔹 Prepare SQL query
$stmt = $conn->prepare("SELECT title, start_time, slots FROM agendas WHERE id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

// 🔹 Return data
if ($row = $result->fetch_assoc()) {
  echo json_encode([
    "title" => $row["title"],
    "startTime" => $row["start_time"],
    "slots" => json_decode($row["slots"], true)
  ], JSON_PRETTY_PRINT);
} else {
  http_response_code(404);
  echo json_encode(["error" => "Agenda not found"]);
}

$stmt->close();
$conn->close();
?>
