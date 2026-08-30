-- MysteryMarket R1.1 Elite membership self-service requests

CREATE TABLE IF NOT EXISTS elite_membership_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id BIGINT UNSIGNED NOT NULL,
    request_type ENUM('pause','end') NOT NULL,
    request_status ENUM('open','approved','rejected','cancelled') NOT NULL DEFAULT 'open',
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolved_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_elite_membership_requests_member (member_id, request_status),
    KEY idx_elite_membership_requests_status (request_status, created_at),
    CONSTRAINT fk_elite_membership_requests_member
        FOREIGN KEY (member_id) REFERENCES elite_members(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_elite_membership_requests_resolver
        FOREIGN KEY (resolved_by) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
