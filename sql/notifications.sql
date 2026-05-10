USE `rfid_db`;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(64) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  message TEXT NOT NULL,
  event_type VARCHAR(64) NULL,
  provider VARCHAR(32) NOT NULL DEFAULT 'textbee',
  provider_message_id VARCHAR(255) NULL,
  status ENUM('queued', 'sent', 'failed', 'suppressed') NOT NULL DEFAULT 'queued',
  last_error TEXT NULL,
  last_attempt_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_uid_sent_at (uid, sent_at),
  INDEX idx_status (status),
  INDEX idx_provider (provider)
);

CREATE OR REPLACE VIEW notifications_sent_today AS
SELECT uid, COUNT(*) AS count_today
FROM notifications
WHERE status = 'sent' AND DATE(sent_at) = CURDATE()
GROUP BY uid;