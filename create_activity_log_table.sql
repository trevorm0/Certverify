-- Run this once in phpMyAdmin
CREATE TABLE IF NOT EXISTS activity_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    role       VARCHAR(20)  NOT NULL,
    user_id    INT          NOT NULL DEFAULT 0,
    action     VARCHAR(100) NOT NULL,
    detail     TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_role    (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Contact form inquiries table ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_inquiries (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(180) NOT NULL,
    role       VARCHAR(40),
    subject    VARCHAR(120) NOT NULL,
    message    TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
