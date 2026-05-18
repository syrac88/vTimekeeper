<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

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

$id = $_GET['id'] ?? '';
if (!$id) {
  http_response_code(400);
  echo json_encode(["error" => "Missing id"]);
  exit;
}

/* Session-ID stabil halten */
if (isset($_COOKIE['viewer_id'])) {
  $session_id = $_COOKIE['viewer_id'];
} else {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
  $session_id = md5($ip . $ua);                 // Fallback
  setcookie('viewer_id', $session_id, time()+86400, "/"); // 24h
}

/* Tabelle mit richtigen Typen + UNIQUE-Key sicherstellen */
$conn->query("
  CREATE TABLE IF NOT EXISTS viewers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agenda_id  VARCHAR(64)  NOT NULL,
    session_id VARCHAR(64)  NOT NULL,
    last_seen  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_viewer (agenda_id, session_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* Upsert */
$stmt = $conn->prepare("
  INSERT INTO viewers (agenda_id, session_id, last_seen)
  VALUES (?, ?, NOW())
  ON DUPLICATE KEY UPDATE last_seen = NOW()
");
$stmt->bind_param("ss", $id, $session_id);
$stmt->execute();
$stmt->close();

/* Alte Einträge (>60s) weg */
$conn->query("DELETE FROM viewers WHERE last_seen < NOW() - INTERVAL 60 SECOND");

/* Zählen */
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM viewers WHERE agenda_id=?");
$stmt->bind_param("s", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$count = (int)$row['cnt'];
$stmt->close();

$conn->close();

echo json_encode(["count" => $count]);
