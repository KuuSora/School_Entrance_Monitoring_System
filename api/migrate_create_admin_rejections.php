<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$sql = "CREATE TABLE IF NOT EXISTS admin_uid_rejections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  posted_admin_uid VARCHAR(64) NOT NULL,
  scanned_uid VARCHAR(64) NULL,
  direction VARCHAR(8) NULL,
  reason VARCHAR(64) NULL,
  ip VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($sql) === TRUE) {
    echo json_encode(['ok' => true, 'message' => 'Table admin_uid_rejections ensured']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mysqli->error]);
}
