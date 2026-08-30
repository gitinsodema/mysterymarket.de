-- MysteryMarket R1.1 Elite invitation / activation tokens

CREATE TABLE IF NOT EXISTS backoffice_activation_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_backoffice_activation_token_hash (token_hash),
    KEY idx_backoffice_activation_user (user_id),
    KEY idx_backoffice_activation_expiry (expires_at, used_at),
    CONSTRAINT fk_backoffice_activation_user
        FOREIGN KEY (user_id) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_backoffice_activation_creator
        FOREIGN KEY (created_by) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
